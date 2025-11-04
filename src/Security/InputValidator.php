<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Input Validation and Sanitization Helper
 * Prevents XSS, SQL Injection, and other injection attacks
 */
class InputValidator
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sanitize string input
     */
    public function sanitizeString(string $input, int $maxLength = 255): string
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Strip tags
        $input = strip_tags($input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Limit length
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }

    /**
     * Validate email format
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL format
     */
    public function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate Shopify shop domain
     */
    public function validateShopDomain(string $domain): bool
    {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-]*\.myshopify\.com$/', $domain) === 1;
    }

    /**
     * Sanitize HTML content (allow safe tags)
     */
    public function sanitizeHtml(string $html): string
    {
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6>';
        return strip_tags($html, $allowedTags);
    }

    /**
     * Validate and sanitize integer
     */
    public function sanitizeInt($value, int $min = null, int $max = null): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        if ($min !== null && $int < $min) {
            return null;
        }

        if ($max !== null && $int > $max) {
            return null;
        }

        return $int;
    }

    /**
     * Validate phone number (Turkish format)
     */
    public function validatePhoneNumber(string $phone): bool
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Turkish mobile: 05XX XXX XX XX or +905XX XXX XX XX
        return preg_match('/^(\+90|0)?5\d{9}$/', $phone) === 1;
    }

    /**
     * Check for SQL injection patterns
     */
    public function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bUNION\b.*\bSELECT\b)/i',
            '/(\bSELECT\b.*\bFROM\b)/i',
            '/(\bINSERT\b.*\bINTO\b)/i',
            '/(\bUPDATE\b.*\bSET\b)/i',
            '/(\bDELETE\b.*\bFROM\b)/i',
            '/(\bDROP\b.*\bTABLE\b)/i',
            '/(\bEXEC\b|\bEXECUTE\b)/i',
            '/(\'|\")(\s)*(OR|AND)(\s)*(\d+|\'|\")/i',
            '/--|\#|\/\*/i', // SQL comments
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logger->warning('SQL injection attempt detected', [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check for XSS patterns
     */
    public function detectXss(string $input): bool
    {
        $patterns = [
            '/<script[^>]*>.*<\/script>/is',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<iframe/i',
            '/<object/i',
            '/<embed/i',
            '/eval\(/i',
            '/expression\(/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logger->warning('XSS attempt detected', [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Check for path traversal attempts
     */
    public function detectPathTraversal(string $input): bool
    {
        $patterns = [
            '/\.\.[\/\\\\]/',
            '/etc\/passwd/i',
            '/\/\//',
            '/windows\/system32/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logger->warning('Path traversal attempt detected', [
                    'input' => substr($input, 0, 100),
                    'pattern' => $pattern,
                ]);
                return true;
            }
        }

        return false;
    }

    /**
     * Comprehensive validation check
     */
    public function isSafe(string $input): bool
    {
        return !$this->detectSqlInjection($input) 
            && !$this->detectXss($input)
            && !$this->detectPathTraversal($input);
    }

    /**
     * Generate CSRF token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Validate JSON
     */
    public function validateJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Sanitize filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = substr(pathinfo($filename, PATHINFO_FILENAME), 0, 250);
            $filename = $name . '.' . $ext;
        }
        
        return $filename;
    }

    /**
     * Validate and sanitize request data
     */
    public function sanitizeRequestData(Request $request): array
    {
        $data = [];
        
        foreach ($request->request->all() as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->sanitizeString($value);
            } elseif (is_array($value)) {
                $data[$key] = array_map([$this, 'sanitizeString'], $value);
            } else {
                $data[$key] = $value;
            }
        }
        
        return $data;
    }
}
