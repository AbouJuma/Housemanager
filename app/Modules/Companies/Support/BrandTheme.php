<?php

namespace App\Modules\Companies\Support;

use App\Modules\Companies\Models\Company;
use Illuminate\Support\Facades\Auth;

/**
 * A company's brand colors/logo, resolved for whoever is signed in and
 * turned into the CSS custom properties resources/css/app.scss reads
 * (--brand, --brand-rgb, --accent, ...). Bootstrap's own primary color
 * is compiled in, so a per-company color can only reach it at runtime
 * through these variables - see the "Brand tokens" layer in app.scss.
 */
final class BrandTheme
{
    public const DEFAULT_PRIMARY = '#1F5A94';

    public const DEFAULT_ACCENT = '#5EEAD4';

    /**
     * Curated starting points offered on the branding screen - each
     * primary keeps white button text at WCAG AA contrast.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PRESETS = [
        'Ocean Mint' => ['#1F5A94', '#5EEAD4'],
        'Lagoon' => ['#0F766E', '#F59E0B'],
        'Royal' => ['#1D4ED8', '#38BDF8'],
        'Indigo' => ['#4F46E5', '#EC4899'],
        'Forest' => ['#15803D', '#EAB308'],
        'Terracotta' => ['#C2410C', '#0EA5E9'],
        'Plum' => ['#7E22CE', '#F97316'],
        'Graphite' => ['#334155', '#14B8A6'],
        'Crimson' => ['#B91C1C', '#F59E0B'],
    ];

    public function __construct(
        public readonly string $primary,
        public readonly string $accent,
        public readonly ?string $logoUrl = null,
        public readonly ?string $companyName = null,
        // For greetings/dates - see company_now() in app/Core/helpers.php.
        public readonly ?string $timezone = null,
    ) {}

    public static function forCompany(?Company $company): self
    {
        return new self(
            self::validHex($company?->primary_color) ?? self::DEFAULT_PRIMARY,
            self::validHex($company?->secondary_color) ?? self::DEFAULT_ACCENT,
            $company?->logoUrl(),
            $company?->name,
            $company?->timezone,
        );
    }

    /**
     * Staff, tenants, landlords and buyers each sign in through their
     * own guard, but all of them belong to exactly one company.
     */
    public static function current(): self
    {
        // once(): the layout, its head partial and the logo component
        // each ask for this on the same request - one query, not three.
        return once(function (): self {
            foreach (['web', 'tenant', 'landlord', 'buyer'] as $guard) {
                $user = Auth::guard($guard)->user();

                if ($user && $user->company_id) {
                    return self::forCompany(Company::query()->find($user->company_id));
                }
            }

            return self::forCompany(null);
        });
    }

    /**
     * Shape sent to the mobile app with every login response, so it can
     * theme itself in the company's colors and show its logo.
     *
     * @return array{company_name: ?string, logo_url: ?string, primary_color: string, accent_color: string}
     */
    public function toArray(): array
    {
        return [
            'company_name' => $this->companyName,
            'logo_url' => $this->logoUrl,
            'primary_color' => $this->primary,
            'accent_color' => $this->accent,
        ];
    }

    public function cssVariables(): string
    {
        $light = self::shade($this->primary, 0.45);

        $vars = [
            '--brand' => $this->primary,
            '--brand-rgb' => implode(', ', self::rgb($this->primary)),
            '--brand-hover' => self::shade($this->primary, -0.14),
            '--brand-light' => $light,
            '--brand-light-rgb' => implode(', ', self::rgb($light)),
            '--brand-contrast' => self::contrastText($this->primary),
            '--accent' => $this->accent,
            '--accent-rgb' => implode(', ', self::rgb($this->accent)),
            '--accent-contrast' => self::contrastText($this->accent),
        ];

        return collect($vars)->map(fn (string $value, string $name) => "{$name}: {$value};")->implode(' ');
    }

    public static function validHex(?string $color): ?string
    {
        return $color && preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? strtoupper($color) : null;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    public static function rgb(string $hex): array
    {
        return [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
    }

    /**
     * Mixes toward white (positive amount) or black (negative).
     */
    public static function shade(string $hex, float $amount): string
    {
        $target = $amount > 0 ? 255 : 0;
        $amount = abs($amount);

        return '#'.collect(self::rgb($hex))
            ->map(fn (int $channel) => str_pad(dechex((int) round($channel + ($target - $channel) * $amount)), 2, '0', STR_PAD_LEFT))
            ->implode('');
    }

    /**
     * White or near-black text, whichever reads better on this color
     * (WCAG relative luminance).
     */
    public static function contrastText(string $hex): string
    {
        [$r, $g, $b] = array_map(function (int $channel): float {
            $c = $channel / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, self::rgb($hex));

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        // Contrast against white vs against #101828 - pick the larger.
        return (1.05 / ($luminance + 0.05)) >= (($luminance + 0.05) / 0.0591) ? '#FFFFFF' : '#101828';
    }
}
