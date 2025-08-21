<?php

namespace App\Services;

use Illuminate\Support\Str;
use HTMLPurifier;
use HTMLPurifier_Config;

class InputSanitizationService
{
    private HTMLPurifier $purifier;
    private HTMLPurifier $strictPurifier;

    public function __construct()
    {
        // Standard HTML purifier configuration
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,ol,ul,li,a[href],h1,h2,h3,h4,h5,h6,blockquote,code,pre');
        $config->set('HTML.AllowedAttributes', 'a.href');
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('URI.DisableExternalResources', true);
        $config->set('URI.DisableResources', true);
        $this->purifier = new HTMLPurifier($config);

        // Strict purifier (no HTML allowed)
        $strictConfig = HTMLPurifier_Config::createDefault();
        $strictConfig->set('HTML.Allowed', '');
        $this->strictPurifier = new HTMLPurifier($strictConfig);
    }

    /**
     * Sanitize HTML content allowing basic formatting.
     */
    public function sanitizeHtml(string $input): string
    {
        return $this->purifier->purify($input);
    }

    /**
     * Sanitize input removing all HTML tags.
     */
    public function sanitizeText(string $input): string
    {
        return $this->strictPurifier->purify($input);
    }

    /**
     * Sanitize email input.
     */
    public function sanitizeEmail(string $email): string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /**
     * Sanitize URL input.
     */
    public function sanitizeUrl(string $url): string
    {
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    /**
     * Sanitize phone number input.
     */
    public function sanitizePhone(string $phone): string
    {
        // Remove all non-digit characters except + and spaces
        $phone = preg_replace('/[^\d\+\s\-\(\)]/', '', $phone);
        return trim($phone);
    }

    /**
     * Sanitize filename for safe storage.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Limit length
        if (strlen($filename) > 255) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 255 - strlen($extension) - 1) . '.' . $extension;
        }
        
        return $filename;
    }

    /**
     * Sanitize search query input.
     */
    public function sanitizeSearchQuery(string $query): string
    {
        // Remove HTML tags
        $query = strip_tags($query);
        
        // Remove special characters that could be used for injection
        $query = preg_replace('/[<>"\']/', '', $query);
        
        // Limit length
        $query = Str::limit($query, 200);
        
        return trim($query);
    }

    /**
     * Sanitize array of inputs recursively.
     */
    public function sanitizeArray(array $data, string $type = 'text'): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeText($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value, $type);
            } else {
                $sanitized[$sanitizedKey] = $this->sanitizeByType($value, $type);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize input based on specified type.
     */
    public function sanitizeByType(string $input, string $type): string
    {
        return match ($type) {
            'html' => $this->sanitizeHtml($input),
            'email' => $this->sanitizeEmail($input),
            'url' => $this->sanitizeUrl($input),
            'phone' => $this->sanitizePhone($input),
            'filename' => $this->sanitizeFilename($input),
            'search' => $this->sanitizeSearchQuery($input),
            default => $this->sanitizeText($input),
        };
    }

    /**
     * Validate and sanitize file upload.
     */
    public function validateFileUpload($file, array $allowedTypes = [], int $maxSize = 10485760): array
    {
        $errors = [];
        
        if (!$file || !$file->isValid()) {
            $errors[] = 'Invalid file upload';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Check file size (default 10MB)
        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = 'File type not allowed';
            }
        }
        
        // Check for executable files
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = ['php', 'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jar'];
        if (in_array($extension, $dangerousExtensions)) {
            $errors[] = 'File type not allowed for security reasons';
        }
        
        // Sanitize filename
        $sanitizedName = $this->sanitizeFilename($file->getClientOriginalName());
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_name' => $sanitizedName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize()
        ];
    }

    /**
     * Check for potential SQL injection patterns.
     */
    public function detectSqlInjection(string $input): bool
    {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*\>)/i',
            '/(\'|\"|;|--|\#)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check for potential XSS patterns.
     */
    public function detectXss(string $input): bool
    {
        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi',
            '/<embed\b[^>]*>/i',
            '/<form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form>/mi'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Log security violation.
     */
    public function logSecurityViolation(string $type, string $input, array $context = []): void
    {
        logger()->warning("Security violation detected: {$type}", [
            'input' => $input,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'context' => $context
        ]);
    }
}