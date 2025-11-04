<?php

namespace App\Service\Cargo;

/**
 * QR Code Generator Service
 * Generates QR codes for tracking numbers and other data
 */
class QRCodeService
{
    /**
     * Generate QR code as base64 data URI
     *
     * @param string $data Data to encode in QR code
     * @param int $size Size of QR code in pixels (default: 200)
     * @return string Base64 data URI
     */
    public function generateBase64(string $data, int $size = 200): string
    {
        // Check if endroid/qr-code is installed
        if (!class_exists('Endroid\QrCode\QrCode')) {
            // Fallback: Use Google Chart API
            return $this->generateViaGoogleCharts($data, $size);
        }

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($data)
                ->setSize($size)
                ->setMargin(10);

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);

            return $result->getDataUri();
        } catch (\Exception $e) {
            // Fallback to Google Charts if endroid fails
            return $this->generateViaGoogleCharts($data, $size);
        }
    }

    /**
     * Generate QR code as PNG file
     *
     * @param string $data Data to encode
     * @param string $filepath Path to save PNG file
     * @param int $size Size in pixels
     * @return bool Success status
     */
    public function generateFile(string $data, string $filepath, int $size = 200): bool
    {
        if (!class_exists('Endroid\QrCode\QrCode')) {
            // Download from Google Charts and save
            $imageData = file_get_contents($this->getGoogleChartsUrl($data, $size));
            return file_put_contents($filepath, $imageData) !== false;
        }

        try {
            $qrCode = \Endroid\QrCode\QrCode::create($data)
                ->setSize($size)
                ->setMargin(10);

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qrCode);

            $result->saveToFile($filepath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fallback: Generate QR code using Google Charts API
     *
     * @param string $data Data to encode
     * @param int $size Size in pixels
     * @return string Data URI
     */
    private function generateViaGoogleCharts(string $data, int $size): string
    {
        $url = $this->getGoogleChartsUrl($data, $size);

        // Try to fetch the image
        try {
            $imageData = @file_get_contents($url);
            if ($imageData === false) {
                // Return placeholder if fetch fails
                return $this->getPlaceholderDataUri($data);
            }
            return 'data:image/png;base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            return $this->getPlaceholderDataUri($data);
        }
    }

    /**
     * Get Google Charts API URL for QR code
     *
     * @param string $data Data to encode
     * @param int $size Size in pixels
     * @return string URL
     */
    private function getGoogleChartsUrl(string $data, int $size): string
    {
        return sprintf(
            'https://chart.googleapis.com/chart?chs=%dx%d&cht=qr&chl=%s&choe=UTF-8',
            $size,
            $size,
            urlencode($data)
        );
    }

    /**
     * Generate a simple placeholder image with text
     *
     * @param string $text Text to display
     * @return string Data URI
     */
    private function getPlaceholderDataUri(string $text): string
    {
        // Create a simple SVG placeholder
        $svg = sprintf(
            '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="200" fill="#f0f0f0"/>
                <text x="100" y="100" text-anchor="middle" font-size="12" font-family="Arial">%s</text>
            </svg>',
            htmlspecialchars(substr($text, 0, 20))
        );

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Generate tracking URL QR code
     *
     * @param string $trackingNumber Tracking number
     * @param string $baseUrl Base URL for tracking page
     * @return string Base64 data URI
     */
    public function generateTrackingQR(string $trackingNumber, string $baseUrl): string
    {
        $trackingUrl = rtrim($baseUrl, '/') . '/track/' . $trackingNumber;
        return $this->generateBase64($trackingUrl, 150);
    }
}
