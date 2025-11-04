<?php

namespace App\Service\Security;

use App\Entity\User;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;

class TwoFactorAuthService
{
    private string $appName;

    public function __construct(string $appName = 'EntegreHub Kargo')
    {
        $this->appName = $appName;
    }

    /**
     * Generate a new 2FA secret for a user
     */
    public function generateSecret(): string
    {
        return trim(Base32::encodeUpper(random_bytes(32)), '=');
    }

    /**
     * Generate QR code URL for 2FA setup
     */
    public function getQRCodeUrl(User $user, string $secret): string
    {
        $totp = TOTP::create($secret);
        $totp->setLabel($user->getEmail());
        $totp->setIssuer($this->appName);

        return $totp->getQrCodeUri(
            'https://api.qrserver.com/v1/create-qr-code/?data=[DATA]&size=200x200&ecc=M',
            '[DATA]'
        );
    }

    /**
     * Verify a 2FA code
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $totp = TOTP::create($secret);
        
        // Verify with 1 period (30 seconds) window before and after
        return $totp->verify($code, null, 1);
    }

    /**
     * Generate backup codes for 2FA recovery
     */
    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
        }
        return $codes;
    }

    /**
     * Get current TOTP code (for testing/debugging only)
     */
    public function getCurrentCode(string $secret): string
    {
        $totp = TOTP::create($secret);
        return $totp->now();
    }
}
