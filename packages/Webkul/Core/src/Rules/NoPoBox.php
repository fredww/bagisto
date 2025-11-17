<?php

namespace Webkul\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoPoBox implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $normalized = strtolower(trim($value));

        $patterns = [
            '/\bp\s*\.?\s*o\s*\.?\s*box\b/i',
            '/\bpost\s*office\s*box\b/i',
            '/\bpo\s*box\b/i',
            '/\bpobox\b/i',
            '/邮政信箱/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                $fail(__('The address cannot be a PO Box.'));
                return;
            }
        }
    }
}