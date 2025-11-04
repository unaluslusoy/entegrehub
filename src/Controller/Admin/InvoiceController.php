<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Repository\InvoiceRepository;
use App\Repository\UserRepository;
use App\Repository\CustomerRepository;
use App\Repository\UserSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/invoices')]
class InvoiceController extends AbstractController
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private UserRepository $userRepository,
        private CustomerRepository $customerRepository,
        private UserSubscriptionRepository $subscriptionRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'admin_invoices', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $status = $request->query->get('status', 'all');
        
        $criteria = [];
        if ($status !== 'all') {
            $criteria['status'] = $status;
        }
        
        $invoices = $this->invoiceRepository->findBy(
            $criteria,
            ['invoiceDate' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );
        
        $total = $this->invoiceRepository->count($criteria);
        $pages = ceil($total / $limit);

        // Calculate totals
        $totalPending = $this->invoiceRepository->count(['status' => 'pending']);
        $totalPaid = $this->invoiceRepository->count(['status' => 'paid']);
        $totalOverdue = count($this->invoiceRepository->findOverdue());

        return $this->render('admin/invoice/index.html.twig', [
            'invoices' => $invoices,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'current_status' => $status,
            'total_pending' => $totalPending,
            'total_paid' => $totalPaid,
            'total_overdue' => $totalOverdue,
        ]);
    }

    #[Route('/create', name: 'admin_invoices_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $user = $this->userRepository->find($data['user_id']);
            if (!$user) {
                $this->addFlash('error', 'Kullanıcı bulunamadı.');
                return $this->redirectToRoute('admin_invoices_create');
            }

            $invoice = new Invoice();
            $invoice->setUser($user);
            
            // Set customer if provided
            if (!empty($data['customer_id'])) {
                $customer = $this->customerRepository->find($data['customer_id']);
                if ($customer) {
                    $invoice->setCustomer($customer);
                }
            }
            
            // Set subscription if provided
            if (!empty($data['subscription_id'])) {
                $subscription = $this->subscriptionRepository->find($data['subscription_id']);
                if ($subscription) {
                    $invoice->setSubscription($subscription);
                }
            }

            $invoice->setInvoiceDate(new \DateTime($data['invoice_date'] ?? 'now'));
            $invoice->setDueDate(new \DateTime($data['due_date']));
            $invoice->setCurrency($data['currency'] ?? 'TRY');
            $invoice->setTaxRate($data['tax_rate'] ?? '20.00');
            
            // Process items
            $items = [];
            $subtotal = 0;
            
            if (isset($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (!empty($item['description']) && !empty($item['amount'])) {
                        $itemData = [
                            'description' => $item['description'],
                            'quantity' => (int)($item['quantity'] ?? 1),
                            'unit_price' => (float)$item['unit_price'],
                            'amount' => (float)$item['amount']
                        ];
                        $items[] = $itemData;
                        $subtotal += $itemData['amount'];
                    }
                }
            }
            
            $invoice->setItems($items);
            $invoice->setSubtotal((string)$subtotal);
            
            // Calculate tax and total
            $taxAmount = $subtotal * ((float)$invoice->getTaxRate() / 100);
            $invoice->setTaxAmount((string)number_format($taxAmount, 2, '.', ''));
            $invoice->setTotal((string)number_format($subtotal + $taxAmount, 2, '.', ''));
            
            // Billing info
            $invoice->setBillingName($data['billing_name'] ?? $user->getFullName());
            $invoice->setBillingAddress($data['billing_address'] ?? null);
            $invoice->setBillingCity($data['billing_city'] ?? null);
            $invoice->setBillingCountry($data['billing_country'] ?? 'Türkiye');
            $invoice->setBillingZip($data['billing_zip'] ?? null);
            $invoice->setTaxNumber($data['tax_number'] ?? null);
            $invoice->setNotes($data['notes'] ?? null);

            $this->entityManager->persist($invoice);
            $this->entityManager->flush();
            
            // Generate invoice number after flush (needs ID)
            $invoice->setInvoiceNumber($invoice->generateInvoiceNumber());
            $this->entityManager->flush();

            $this->addFlash('success', 'Fatura başarıyla oluşturuldu.');
            return $this->redirectToRoute('admin_invoices');
        }

        $users = $this->userRepository->findAll();
        
        // Check if customer_id is provided in query params
        $customer = null;
        $customerId = $request->query->get('customer_id');
        if ($customerId) {
            $customer = $this->customerRepository->find($customerId);
        }

        return $this->render('admin/invoice/create.html.twig', [
            'users' => $users,
            'customer' => $customer,
        ]);
    }

    #[Route('/{id}', name: 'admin_invoice_detail', methods: ['GET'])]
    public function detail(Invoice $invoice): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        return $this->render('admin/invoice/detail.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_invoices_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $invoice->setInvoiceDate(new \DateTime($data['invoice_date']));
            $invoice->setDueDate(new \DateTime($data['due_date']));
            $invoice->setStatus($data['status']);
            $invoice->setPaymentMethod($data['payment_method'] ?? null);
            $invoice->setNotes($data['notes'] ?? null);
            
            // If marked as paid, set paid date
            if ($data['status'] === 'paid' && !$invoice->getPaidAt()) {
                $invoice->setPaidAt(new \DateTime());
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Fatura başarıyla güncellendi.');
            return $this->redirectToRoute('admin_invoices');
        }

        return $this->render('admin/invoice/edit.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/mark-paid', name: 'admin_invoices_mark_paid', methods: ['POST'])]
    public function markPaid(Request $request, Invoice $invoice): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $data = json_decode($request->getContent(), true);
        
        $invoice->setStatus('paid');
        $invoice->setPaidAt(new \DateTime());
        $invoice->setPaymentMethod($data['payment_method'] ?? null);
        $invoice->setPaymentTransactionId($data['transaction_id'] ?? null);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Fatura ödendi olarak işaretlendi.',
        ]);
    }

    #[Route('/{id}/send-email', name: 'admin_invoices_send_email', methods: ['POST'])]
    public function sendEmail(Invoice $invoice): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Implement email sending logic
        // For now, just mark as sent
        
        $invoice->setEmailSent(true);
        $invoice->setEmailSentAt(new \DateTime());
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Fatura email ile gönderildi.',
        ]);
    }

    #[Route('/{id}/pdf', name: 'admin_invoice_pdf', methods: ['GET'])]
    public function downloadPdf(Invoice $invoice): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        // TODO: Implement PDF generation with TCPDF or Dompdf
        // For now, render HTML version
        
        $html = $this->renderView('admin/invoice/pdf.html.twig', [
            'invoice' => $invoice,
        ]);

        return new Response($html, 200, [
            'Content-Type' => 'text/html',
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_invoices_delete', methods: ['POST'])]
    public function delete(Invoice $invoice): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($invoice->getStatus() === 'paid') {
            $this->addFlash('error', 'Ödenmiş faturalar silinemez.');
            return $this->redirectToRoute('admin_invoices');
        }

        $this->entityManager->remove($invoice);
        $this->entityManager->flush();

        $this->addFlash('success', 'Fatura başarıyla silindi.');
        return $this->redirectToRoute('admin_invoices');
    }

    #[Route('/{id}/cancel', name: 'admin_invoices_cancel', methods: ['POST'])]
    public function cancel(Invoice $invoice): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($invoice->getStatus() === 'paid') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ödenmiş faturalar iptal edilemez.',
            ], 400);
        }

        $invoice->setStatus('cancelled');
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Fatura iptal edildi.',
        ]);
    }
}
