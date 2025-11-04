<?php

namespace App\Command;

use App\Service\Cloudflare\CloudflareService;
use App\Service\Cloudflare\CloudflareRateLimiter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cloudflare',
    description: 'Manage Cloudflare settings and security'
)]
class CloudflareCommand extends Command
{
    public function __construct(
        private CloudflareService $cloudflareService,
        private CloudflareRateLimiter $rateLimiter
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action to perform: analytics, security-events, firewall-list, block-ip, unblock-ip, purge-cache, under-attack-on, under-attack-off, rate-limits')
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'IP address for block/unblock operations')
            ->addOption('rule-id', null, InputOption::VALUE_OPTIONAL, 'Rule ID for unblock operations')
            ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'Firewall mode: block, challenge, whitelist, js_challenge', 'block')
            ->addOption('notes', null, InputOption::VALUE_OPTIONAL, 'Notes for firewall rule')
            ->addOption('since', null, InputOption::VALUE_OPTIONAL, 'Analytics since (e.g., -7d)', '-7d')
            ->addOption('until', null, InputOption::VALUE_OPTIONAL, 'Analytics until (e.g., now)', 'now')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');

        $io->title("Cloudflare Management - {$action}");

        return match($action) {
            'analytics' => $this->getAnalytics($io, $input),
            'security-events' => $this->getSecurityEvents($io),
            'firewall-list' => $this->listFirewallRules($io),
            'block-ip' => $this->blockIp($io, $input),
            'unblock-ip' => $this->unblockIp($io, $input),
            'purge-cache' => $this->purgeCache($io),
            'under-attack-on' => $this->enableUnderAttack($io),
            'under-attack-off' => $this->disableUnderAttack($io),
            'rate-limits' => $this->listRateLimits($io),
            'setup-login-protection' => $this->setupLoginProtection($io),
            default => $this->showHelp($io)
        };
    }

    private function getAnalytics(SymfonyStyle $io, InputInterface $input): int
    {
        $since = $input->getOption('since');
        $until = $input->getOption('until');

        $result = $this->cloudflareService->getAnalytics($since, $until);

        if (!$result['success']) {
            $io->error('Failed to fetch analytics: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('Analytics fetched successfully');
        $io->writeln(json_encode($result['result'], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }

    private function getSecurityEvents(SymfonyStyle $io): int
    {
        $result = $this->cloudflareService->getSecurityEvents(50);

        if (!$result['success']) {
            $io->error('Failed to fetch security events: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('Security events fetched');
        
        if (empty($result['result'])) {
            $io->info('No security events found');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($result['result'] as $event) {
            $rows[] = [
                $event['occurred_at'] ?? 'N/A',
                $event['source'] ?? 'N/A',
                $event['action'] ?? 'N/A',
                $event['ray_id'] ?? 'N/A',
            ];
        }

        $io->table(['Time', 'Source', 'Action', 'Ray ID'], $rows);

        return Command::SUCCESS;
    }

    private function listFirewallRules(SymfonyStyle $io): int
    {
        $result = $this->cloudflareService->listFirewallRules();

        if (!$result['success']) {
            $io->error('Failed to fetch firewall rules: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        if (empty($result['result'])) {
            $io->info('No firewall rules found');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($result['result'] as $rule) {
            $rows[] = [
                $rule['id'] ?? 'N/A',
                $rule['mode'] ?? 'N/A',
                $rule['configuration']['value'] ?? 'N/A',
                $rule['notes'] ?? '',
            ];
        }

        $io->table(['Rule ID', 'Mode', 'Target', 'Notes'], $rows);

        return Command::SUCCESS;
    }

    private function blockIp(SymfonyStyle $io, InputInterface $input): int
    {
        $ip = $input->getOption('ip');
        if (!$ip) {
            $io->error('IP address is required. Use --ip=xxx.xxx.xxx.xxx');
            return Command::FAILURE;
        }

        $mode = $input->getOption('mode');
        $notes = $input->getOption('notes') ?? "Blocked via CLI";

        $result = $this->cloudflareService->addFirewallRule($ip, $mode, $notes);

        if (!$result['success']) {
            $io->error('Failed to block IP: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success("IP {$ip} has been blocked with mode: {$mode}");
        $io->info('Rule ID: ' . ($result['result']['id'] ?? 'N/A'));

        return Command::SUCCESS;
    }

    private function unblockIp(SymfonyStyle $io, InputInterface $input): int
    {
        $ruleId = $input->getOption('rule-id');
        if (!$ruleId) {
            $io->error('Rule ID is required. Use --rule-id=xxx');
            return Command::FAILURE;
        }

        $result = $this->cloudflareService->removeFirewallRule($ruleId);

        if (!$result['success']) {
            $io->error('Failed to remove rule: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success("Rule {$ruleId} has been removed");

        return Command::SUCCESS;
    }

    private function purgeCache(SymfonyStyle $io): int
    {
        if (!$io->confirm('Are you sure you want to purge all cache?', false)) {
            $io->info('Cache purge cancelled');
            return Command::SUCCESS;
        }

        $result = $this->cloudflareService->purgeCache();

        if (!$result['success']) {
            $io->error('Failed to purge cache: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('Cache purged successfully');

        return Command::SUCCESS;
    }

    private function enableUnderAttack(SymfonyStyle $io): int
    {
        $result = $this->cloudflareService->enableUnderAttackMode();

        if (!$result['success']) {
            $io->error('Failed to enable Under Attack mode: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('"Under Attack" mode enabled');
        $io->warning('All visitors will see an interstitial page before accessing your site');

        return Command::SUCCESS;
    }

    private function disableUnderAttack(SymfonyStyle $io): int
    {
        $result = $this->cloudflareService->disableUnderAttackMode();

        if (!$result['success']) {
            $io->error('Failed to disable Under Attack mode: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('"Under Attack" mode disabled - Security level set to medium');

        return Command::SUCCESS;
    }

    private function listRateLimits(SymfonyStyle $io): int
    {
        $result = $this->rateLimiter->listRateLimits();

        if (!$result['success']) {
            $io->error('Failed to fetch rate limits: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        if (empty($result['result'])) {
            $io->info('No rate limit rules found');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($result['result'] as $rule) {
            $rows[] = [
                $rule['id'] ?? 'N/A',
                $rule['description'] ?? 'N/A',
                $rule['threshold'] ?? 'N/A',
                $rule['period'] ?? 'N/A',
                $rule['action']['mode'] ?? 'N/A',
            ];
        }

        $io->table(['Rule ID', 'Description', 'Threshold', 'Period (s)', 'Action'], $rows);

        return Command::SUCCESS;
    }

    private function setupLoginProtection(SymfonyStyle $io): int
    {
        $io->section('Setting up login protection');

        $result = $this->rateLimiter->createLoginRateLimit(5);

        if (!$result['success']) {
            $io->error('Failed to create login protection: ' . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }

        $io->success('Login protection enabled - Max 5 failed attempts per minute');

        return Command::SUCCESS;
    }

    private function showHelp(SymfonyStyle $io): int
    {
        $io->section('Available Actions');
        $io->listing([
            'analytics - Get Cloudflare analytics',
            'security-events - Get recent security events',
            'firewall-list - List firewall rules',
            'block-ip --ip=xxx.xxx.xxx.xxx --mode=block - Block an IP',
            'unblock-ip --rule-id=xxx - Remove firewall rule',
            'purge-cache - Purge all Cloudflare cache',
            'under-attack-on - Enable "Under Attack" mode',
            'under-attack-off - Disable "Under Attack" mode',
            'rate-limits - List rate limit rules',
            'setup-login-protection - Setup login rate limiting',
        ]);

        return Command::SUCCESS;
    }
}
