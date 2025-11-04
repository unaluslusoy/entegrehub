<?php

namespace App\Controller\Admin;

use App\Service\Cloudflare\CloudflareService;
use App\Service\Cloudflare\CloudflareRateLimiter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/cloudflare')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class CloudflareController extends AbstractController
{
    public function __construct(
        private CloudflareService $cloudflareService,
        private CloudflareRateLimiter $rateLimiter
    ) {}

    #[Route('/', name: 'admin_cloudflare_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('admin/cloudflare/dashboard.html.twig', [
            'page_title' => 'Cloudflare Yönetimi',
        ]);
    }

    #[Route('/analytics', name: 'admin_cloudflare_analytics', methods: ['GET'])]
    public function analytics(Request $request): JsonResponse
    {
        $since = $request->query->get('since', '-7d');
        $until = $request->query->get('until', 'now');

        $result = $this->cloudflareService->getAnalytics($since, $until);

        return $this->json($result);
    }

    #[Route('/security-events', name: 'admin_cloudflare_security_events', methods: ['GET'])]
    public function securityEvents(Request $request): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 100);

        $result = $this->cloudflareService->getSecurityEvents($limit);

        return $this->json($result);
    }

    #[Route('/firewall/rules', name: 'admin_cloudflare_firewall_rules', methods: ['GET'])]
    public function listFirewallRules(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 50);

        $result = $this->cloudflareService->listFirewallRules($page, $perPage);

        return $this->json($result);
    }

    #[Route('/firewall/rules', name: 'admin_cloudflare_add_firewall_rule', methods: ['POST'])]
    public function addFirewallRule(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['ip']) || empty($data['mode'])) {
            return $this->json([
                'success' => false,
                'message' => 'IP and mode are required',
            ], 400);
        }

        $result = $this->cloudflareService->addFirewallRule(
            $data['ip'],
            $data['mode'],
            $data['notes'] ?? ''
        );

        return $this->json($result);
    }

    #[Route('/firewall/rules/{ruleId}', name: 'admin_cloudflare_delete_firewall_rule', methods: ['DELETE'])]
    public function deleteFirewallRule(string $ruleId): JsonResponse
    {
        $result = $this->cloudflareService->removeFirewallRule($ruleId);

        return $this->json($result);
    }

    #[Route('/rate-limits', name: 'admin_cloudflare_rate_limits', methods: ['GET'])]
    public function listRateLimits(Request $request): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 50);

        $result = $this->rateLimiter->listRateLimits($page, $perPage);

        return $this->json($result);
    }

    #[Route('/rate-limits', name: 'admin_cloudflare_create_rate_limit', methods: ['POST'])]
    public function createRateLimit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['config'])) {
            return $this->json([
                'success' => false,
                'message' => 'Config is required',
            ], 400);
        }

        $result = $this->rateLimiter->createRateLimit($data['config']);

        return $this->json($result);
    }

    #[Route('/rate-limits/{ruleId}', name: 'admin_cloudflare_delete_rate_limit', methods: ['DELETE'])]
    public function deleteRateLimit(string $ruleId): JsonResponse
    {
        $result = $this->rateLimiter->deleteRateLimit($ruleId);

        return $this->json($result);
    }

    #[Route('/cache/purge', name: 'admin_cloudflare_purge_cache', methods: ['POST'])]
    public function purgeCache(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $urls = $data['urls'] ?? [];

        $result = $this->cloudflareService->purgeCache($urls);

        if ($result['success']) {
            $this->addFlash('success', 'Cache temizlendi!');
        } else {
            $this->addFlash('error', 'Cache temizlenirken hata oluştu: ' . ($result['message'] ?? 'Unknown error'));
        }

        return $this->json($result);
    }

    #[Route('/security-level', name: 'admin_cloudflare_set_security_level', methods: ['POST'])]
    public function setSecurityLevel(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['level'])) {
            return $this->json([
                'success' => false,
                'message' => 'Security level is required',
            ], 400);
        }

        $validLevels = ['off', 'essentially_off', 'low', 'medium', 'high', 'under_attack'];
        if (!in_array($data['level'], $validLevels)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid security level',
            ], 400);
        }

        $result = $this->cloudflareService->setSecurityLevel($data['level']);

        return $this->json($result);
    }

    #[Route('/under-attack/enable', name: 'admin_cloudflare_enable_under_attack', methods: ['POST'])]
    public function enableUnderAttackMode(): JsonResponse
    {
        $result = $this->cloudflareService->enableUnderAttackMode();

        if ($result['success']) {
            $this->addFlash('warning', '"Under Attack" modu etkinleştirildi!');
        }

        return $this->json($result);
    }

    #[Route('/under-attack/disable', name: 'admin_cloudflare_disable_under_attack', methods: ['POST'])]
    public function disableUnderAttackMode(): JsonResponse
    {
        $result = $this->cloudflareService->disableUnderAttackMode();

        if ($result['success']) {
            $this->addFlash('success', '"Under Attack" modu devre dışı bırakıldı.');
        }

        return $this->json($result);
    }

    #[Route('/block-country', name: 'admin_cloudflare_block_country', methods: ['POST'])]
    public function blockCountry(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['country_code'])) {
            return $this->json([
                'success' => false,
                'message' => 'Country code is required',
            ], 400);
        }

        $result = $this->cloudflareService->blockCountry($data['country_code']);

        return $this->json($result);
    }

    #[Route('/quick-actions/block-ip', name: 'admin_cloudflare_quick_block_ip', methods: ['POST'])]
    public function quickBlockIp(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['ip'])) {
            return $this->json([
                'success' => false,
                'message' => 'IP address is required',
            ], 400);
        }

        $result = $this->cloudflareService->addFirewallRule(
            $data['ip'],
            'block',
            $data['reason'] ?? 'Manually blocked from admin panel'
        );

        if ($result['success']) {
            $this->addFlash('success', "IP {$data['ip']} engellendi!");
        } else {
            $this->addFlash('error', 'IP engellenirken hata oluştu.');
        }

        return $this->json($result);
    }
}
