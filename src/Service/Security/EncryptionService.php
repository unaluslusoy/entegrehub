<?php

namespace App\Service\Security;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Hassas verileri (API key'ler, şifreler vb.) şifrelemek için servis
 */
class EncryptionService
{
    private string $encryptionKey;
    private string $cipher = 'AES-256-CBC';

    public function __construct(ParameterBagInterface $params)
    {
        $key = $params->get('app.encryption_key');
        
        // base64: prefix'i varsa çıkar
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        
        $this->encryptionKey = $key;
    }

    /**
     * Veriyi şifreler
     */
    public function encrypt(string $data): string
    {
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);
        
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // IV'yi şifreli veriyle birlikte sakla
        $result = base64_encode($iv . $encrypted);
        
        return $result;
    }

    /**
     * Şifreli veriyi çözer
     */
    public function decrypt(string $encryptedData): string
    {
        $data = base64_decode($encryptedData);
        
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }

    /**
     * Array'i şifreler (JSON olarak)
     */
    public function encryptArray(array $data): string
    {
        return $this->encrypt(json_encode($data));
    }

    /**
     * Şifreli array'i çözer
     */
    public function decryptArray(string $encryptedData): array
    {
        $decrypted = $this->decrypt($encryptedData);
        return json_decode($decrypted, true) ?? [];
    }

    /**
     * Veri şifreli mi kontrol eder
     */
    public function isEncrypted(string $data): bool
    {
        try {
            $decoded = base64_decode($data, true);
            return $decoded !== false && base64_encode($decoded) === $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Hash oluşturur (tek yönlü, şifre için)
     */
    public function hash(string $data): string
    {
        return password_hash($data, PASSWORD_ARGON2ID);
    }

    /**
     * Hash doğrulama
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return password_verify($data, $hash);
    }

    /**
     * Random key üretir
     */
    public function generateKey(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * API key üretir
     */
    public function generateApiKey(): string
    {
        return 'ek_' . $this->generateKey(32);
    }
}
