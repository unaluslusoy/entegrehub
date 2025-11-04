<?php

namespace App\Service\Cargo;

use App\Entity\Shipment;
use App\Entity\UserLabelTemplate;
use App\Repository\UserLabelTemplateRepository;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cargo Label Generator Service
 * Generates PDF labels for shipments using DomPDF
 */
class CargoLabelGenerator
{
    private const DEFAULT_PAPER_SIZE = [0, 0, 283.46, 425.20]; // 10x15 cm in points
    private const DEFAULT_ORIENTATION = 'portrait';

    public function __construct(
        private Environment $twig,
        private QRCodeService $qrCodeService,
        private UserLabelTemplateRepository $templateRepository,
        private string $projectDir
    ) {}

    /**
     * Generate single shipment label as PDF
     *
     * @param Shipment $shipment Shipment entity
     * @param string|int|null $template Template name, ID, or null for user's default
     * @return Response PDF response
     */
    public function generateLabel(Shipment $shipment, string|int|null $template = null): Response
    {
        $html = $this->renderLabelHtml($shipment, $template);
        $paperSize = $this->getPaperSize($template);
        $pdf = $this->htmlToPdf($html, $paperSize);

        $filename = sprintf('label_%s.pdf', $shipment->getTrackingNumber());

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            ]
        );
    }

    /**
     * Generate multiple labels as single PDF
     *
     * @param array<Shipment> $shipments Array of shipments
     * @param string|int|null $template Template name, ID, or null for user's default
     * @return Response PDF response
     */
    public function generateBulkLabels(array $shipments, string|int|null $template = null): Response
    {
        $htmlPages = [];

        foreach ($shipments as $shipment) {
            $htmlPages[] = $this->renderLabelHtml($shipment, $template);
        }

        // Combine all pages with page breaks
        $html = implode('<div style="page-break-after: always;"></div>', $htmlPages);

        $paperSize = $this->getPaperSize($template);
        $pdf = $this->htmlToPdf($html, $paperSize);

        $filename = sprintf('labels_bulk_%s.pdf', date('YmdHis'));

        return new Response(
            $pdf,
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s"', $filename),
            ]
        );
    }

    /**
     * Save label as file
     *
     * @param Shipment $shipment Shipment entity
     * @param string $filepath File path to save
     * @param string $template Template name
     * @return bool Success status
     */
    public function saveLabelToFile(Shipment $shipment, string $filepath, string $template = 'default'): bool
    {
        try {
            $html = $this->renderLabelHtml($shipment, $template);
            $pdf = $this->htmlToPdf($html);

            return file_put_contents($filepath, $pdf) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Render label HTML from template
     *
     * @param Shipment $shipment Shipment entity
     * @param string|int|null $template Template name, ID, or null for user's default
     * @return string Rendered HTML
     */
    private function renderLabelHtml(Shipment $shipment, string|int|null $template): string
    {
        $order = $shipment->getOrder();
        $address = $order->getShippingAddress();
        $user = $order->getShop()->getUser();

        // Generate QR code for tracking
        $qrCode = $this->qrCodeService->generateBase64(
            $shipment->getTrackingNumber(),
            150
        );

        // Prepare template data
        $data = [
            'shipment' => $shipment,
            'order' => $order,
            'address' => $address,
            'qrCode' => $qrCode,
            'company' => $user,
            'items' => $order->getItems(),
        ];

        // Check if using custom user template (by ID or default)
        if (is_int($template) || $template === null) {
            $userTemplate = null;

            if (is_int($template)) {
                // Get specific template by ID
                $userTemplate = $this->templateRepository->find($template);
                // Verify ownership
                if ($userTemplate && $userTemplate->getUser() !== $user) {
                    $userTemplate = null;
                }
            } elseif ($template === null) {
                // Get user's default template
                $userTemplate = $this->templateRepository->findDefaultByUser($user);
            }

            if ($userTemplate) {
                return $this->renderCustomTemplate($userTemplate, $data);
            }
        }

        // Render built-in template
        $templatePath = sprintf('cargo/labels/%s.html.twig', $template ?: 'default');

        try {
            return $this->twig->render($templatePath, $data);
        } catch (\Exception $e) {
            // Fallback to default template
            return $this->twig->render('cargo/labels/default.html.twig', $data);
        }
    }

    /**
     * Render custom user template
     *
     * @param UserLabelTemplate $template User's custom template
     * @param array $data Template data
     * @return string Rendered HTML
     */
    private function renderCustomTemplate(UserLabelTemplate $template, array $data): string
    {
        $designConfig = $template->getDesignConfig();

        if (!isset($designConfig['elements'])) {
            throw new \RuntimeException('Invalid template design configuration');
        }

        // Convert mm to pixels (96 DPI: 1mm = 3.7795px)
        $width = $template->getWidth() * 3.7795;
        $height = $template->getHeight() * 3.7795;

        $html = sprintf('<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Label %s</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: %.2fmm %.2fmm; margin: 0; }
        body { font-family: Arial, sans-serif; }
        .label { position: relative; width: %.2fpx; height: %.2fpx; background: white; }
        .element { position: absolute; overflow: hidden; }
    </style>
</head>
<body>
    <div class="label">',
            $data['shipment']->getTrackingNumber(),
            $template->getWidth(),
            $template->getHeight(),
            $width,
            $height
        );

        // Render each element
        foreach ($designConfig['elements'] as $element) {
            $html .= $this->renderElement($element, $data);
        }

        $html .= '
    </div>
</body>
</html>';

        // Increment usage count
        $template->incrementUsageCount();

        return $html;
    }

    /**
     * Render single element
     *
     * @param array $element Element configuration
     * @param array $data Template data
     * @return string HTML for element
     */
    private function renderElement(array $element, array $data): string
    {
        $styles = sprintf(
            'left: %dpx; top: %dpx; width: %dpx; height: %dpx; color: %s; background-color: %s; font-size: %dpx; font-family: %s; font-weight: %s; text-align: %s; transform: rotate(%ddeg);',
            $element['x'] ?? 0,
            $element['y'] ?? 0,
            $element['width'] ?? 100,
            $element['height'] ?? 20,
            $element['color'] ?? '#000000',
            $element['backgroundColor'] ?? 'transparent',
            $element['fontSize'] ?? 12,
            $element['fontFamily'] ?? 'Arial',
            $element['fontWeight'] ?? 'normal',
            $element['textAlign'] ?? 'left',
            $element['rotation'] ?? 0
        );

        if (($element['borderWidth'] ?? 0) > 0) {
            $styles .= sprintf(' border: %dpx solid %s;',
                $element['borderWidth'],
                $element['borderColor'] ?? '#000000'
            );
        }

        $content = '';

        if ($element['type'] === 'text') {
            $text = $this->resolveFieldValue($element, $data);
            $content = htmlspecialchars($text);

        } elseif ($element['type'] === 'qrcode') {
            $content = sprintf('<img src="%s" style="width:100%%;height:100%%;object-fit:contain;">', $data['qrCode']);

        } elseif ($element['type'] === 'barcode') {
            // Generate barcode for tracking number
            $barcodeData = $this->generateBarcodeData($data['shipment']->getTrackingNumber());
            $content = sprintf('<div style="width:100%%;height:100%%;background:repeating-linear-gradient(90deg, #000 0px, #000 2px, #fff 2px, #fff 4px);"></div>');

        } elseif ($element['type'] === 'image') {
            // Placeholder for images
            if (!empty($element['content'])) {
                $content = sprintf('<img src="%s" style="width:100%%;height:100%%;object-fit:contain;">', $element['content']);
            }
        }

        return sprintf('<div class="element" style="%s">%s</div>', $styles, $content);
    }

    /**
     * Resolve field value from element configuration
     *
     * @param array $element Element configuration
     * @param array $data Template data
     * @return string Resolved value
     */
    private function resolveFieldValue(array $element, array $data): string
    {
        if (!empty($element['fieldKey'])) {
            $fieldMap = [
                'tracking' => $data['shipment']->getTrackingNumber(),
                'order_number' => $data['order']->getOrderNumber(),
                'receiver_name' => $data['address']->getFirstName() . ' ' . $data['address']->getLastName(),
                'receiver_company' => $data['address']->getCompany() ?? '',
                'receiver_address' => $data['address']->getAddress1(),
                'receiver_city' => $data['address']->getCity(),
                'receiver_phone' => $data['address']->getPhone(),
                'cargo_company' => $data['shipment']->getCargoCompany()->getName(),
                'service_type' => $data['shipment']->getServiceType() === 'express' ? 'EKSPRES' : 'STANDART',
                'weight' => $data['shipment']->getWeight() ? $data['shipment']->getWeight() . ' kg' : '',
                'cod_amount' => $data['shipment']->isIsCOD() ? number_format($data['shipment']->getCodAmount(), 2) . ' ₺' : '',
                'created_date' => $data['shipment']->getCreatedAt()->format('d.m.Y'),
                'sender_name' => $data['company']->getFirstName() . ' ' . $data['company']->getLastName(),
            ];

            return $fieldMap[$element['fieldKey']] ?? $element['content'] ?? '';
        }

        return $element['content'] ?? '';
    }

    /**
     * Generate barcode data (simplified)
     *
     * @param string $value Value to encode
     * @return string Base64 barcode image
     */
    private function generateBarcodeData(string $value): string
    {
        // Simplified barcode generation
        // In production, use a proper barcode library
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    /**
     * Get paper size for template
     *
     * @param string|int|null $template Template identifier
     * @return array Paper size in points [x1, y1, x2, y2]
     */
    private function getPaperSize(string|int|null $template): array
    {
        if (is_int($template) || $template === null) {
            $userTemplate = null;

            if (is_int($template)) {
                $userTemplate = $this->templateRepository->find($template);
            } elseif ($template === null) {
                // This would need user context - fallback to default
                return self::DEFAULT_PAPER_SIZE;
            }

            if ($userTemplate) {
                $dimensions = $userTemplate->getDimensionsInPoints();
                return [0, 0, $dimensions['width'], $dimensions['height']];
            }
        }

        return self::DEFAULT_PAPER_SIZE;
    }

    /**
     * Convert HTML to PDF using DomPDF
     *
     * @param string $html HTML content
     * @param array $paperSize Paper size in points
     * @return string PDF binary content
     */
    private function htmlToPdf(string $html, array $paperSize = null): string
    {
        // Check if DomPDF is installed
        if (!class_exists('Dompdf\Dompdf')) {
            // Fallback: Generate simple HTML page
            return $this->htmlFallback($html);
        }

        try {
            $dompdf = new \Dompdf\Dompdf([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

            $dompdf->loadHtml($html);
            $dompdf->setPaper($paperSize ?? self::DEFAULT_PAPER_SIZE, self::DEFAULT_ORIENTATION);
            $dompdf->render();

            return $dompdf->output();
        } catch (\Exception $e) {
            // Fallback
            return $this->htmlFallback($html);
        }
    }

    /**
     * Fallback: Return HTML as-is (browser will handle printing)
     *
     * @param string $html HTML content
     * @return string HTML with print styles
     */
    private function htmlFallback(string $html): string
    {
        return sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Kargo Etiketi</title>
                <style>
                    @page { size: 10cm 15cm; margin: 0; }
                    body { margin: 0; padding: 10px; font-family: Arial, sans-serif; }
                    @media print {
                        body { width: 10cm; }
                    }
                </style>
            </head>
            <body>
                %s
                <script>
                    window.onload = function() {
                        if (confirm("DomPDF paketi yüklü değil. Yazdırmak ister misiniz?")) {
                            window.print();
                        }
                    };
                </script>
            </body>
            </html>',
            $html
        );
    }

    /**
     * Get available label templates
     *
     * @return array<string> Template names
     */
    public function getAvailableTemplates(): array
    {
        return [
            'default' => 'Standart (10x15cm)',
            'thermal' => 'Termal Yazıcı',
            'a4' => 'A4 Kağıt (4 etiket)',
            'custom' => 'Özel Tasarım',
        ];
    }

    /**
     * Validate if template exists
     *
     * @param string $template Template name
     * @return bool
     */
    public function templateExists(string $template): bool
    {
        $templatePath = $this->projectDir . '/templates/cargo/labels/' . $template . '.html.twig';
        return file_exists($templatePath);
    }
}
