<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\CustomerTransaction;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/customers/{id}/transactions')]
class CustomerTransactionController extends AbstractController
{
    public function __construct(
        private CustomerRepository $customerRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'admin_customers_transactions', methods: ['GET'])]
    public function index(int $id): Response
    {
        $customer = $this->customerRepository->find($id);
        if (!$customer) {
            $this->addFlash('error', 'Müşteri bulunamadı.');
            return $this->redirectToRoute('admin_customers_index');
        }

        $transactions = $this->entityManager->getRepository(CustomerTransaction::class)->findBy(['customer' => $customer], ['createdAt' => 'DESC']);

        return $this->render('admin/customers/transactions.html.twig', [
            'customer' => $customer,
            'transactions' => $transactions,
        ]);
    }

    #[Route('/create', name: 'admin_customers_transactions_create', methods: ['GET', 'POST'])]
    public function create(Request $request, int $id): Response
    {
        $customer = $this->customerRepository->find($id);
        if (!$customer) {
            $this->addFlash('error', 'Müşteri bulunamadı.');
            return $this->redirectToRoute('admin_customers_index');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $tx = new CustomerTransaction();
            $tx->setCustomer($customer);
            $tx->setType($data['type'] ?? 'debit');
            $tx->setAmount((string)(float)($data['amount'] ?? 0));
            $tx->setCurrency($data['currency'] ?? 'TRY');
            $tx->setDescription($data['description'] ?? null);
            $tx->setRelatedInvoiceId(!empty($data['related_invoice_id']) ? (int)$data['related_invoice_id'] : null);

            $this->entityManager->persist($tx);
            $this->entityManager->flush();

            $this->addFlash('success', 'İşlem kaydedildi.');
            return $this->redirectToRoute('admin_customers_transactions', ['id' => $customer->getId()]);
        }

        return $this->render('admin/customers/transaction_create.html.twig', [
            'customer' => $customer,
        ]);
    }
}
