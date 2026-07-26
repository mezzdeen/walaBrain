<?php

namespace App\Modules\Core\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Refuses an address at a domain that hands out throwaway inboxes.
 *
 * The confirmation link proves someone can read the address today; it says
 * nothing about tomorrow. A disposable inbox satisfies every other check and
 * then stops existing, leaving an account whose password can never be reset and
 * whose security notices go nowhere.
 */
class NotDisposableEmail implements ValidationRule
{
    /**
     * The domains, memoized so a validation run reads the file once.
     *
     * @var list<string>|null
     */
    private static ?array $domains = null;

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $domain = Str::lower(Str::afterLast($value, '@'));

        foreach (self::domains() as $disposable) {
            // A suffix match rather than an equality one: these services hand
            // out per-user subdomains as freely as they hand out addresses.
            if ($domain === $disposable || str_ends_with($domain, '.'.$disposable)) {
                $fail('core::validation.disposable_email')->translate();

                return;
            }
        }
    }

    /**
     * The blocked domains.
     *
     * @return list<string>
     */
    private static function domains(): array
    {
        return self::$domains ??= require __DIR__.'/../Support/disposable-domains.php';
    }
}
