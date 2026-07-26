<?php

namespace App\Modules\Core\Support;

/**
 * Turns an organization's chosen colour into the CSS that rebrands the
 * application for everyone acting inside it.
 *
 * The whole of the colour logic lives here, and the finished stylesheet is
 * handed to both consumers — the Blade root view for the first paint, and the
 * client for every Inertia visit after it. Building the same rules twice would
 * mean the colour could differ between the two, which reads as a flicker.
 */
final class BrandColor
{
    /**
     * Text placed on the brand colour when the brand colour is dark.
     */
    private const ON_DARK = '#ffffff';

    /**
     * And when it is light. Matches the theme's own near-black rather than pure
     * black, so a branded button sits in the same family as everything else.
     */
    private const ON_LIGHT = '#0a0a0a';

    /**
     * Above this relative luminance a colour is treated as light. Sits between
     * the two extremes rather than at 0.5 because the eye tolerates white on a
     * mid tone better than black on one.
     */
    private const LIGHT_THRESHOLD = 0.45;

    /**
     * The stylesheet that rebrands the application, or an empty string when the
     * organization has chosen nothing.
     *
     * Selectors are `html:root` and `html.dark` rather than the `:root` and
     * `.dark` the theme itself uses. Both match the same element, so the winner
     * would otherwise be decided by source order — and Tailwind's stylesheet is
     * injected after this one in development. The element qualifier raises
     * specificity just enough to settle it either way.
     *
     * Both selectors carry the same values on purpose: an organization's colour
     * is its identity, so it does not invert between light and dark the way the
     * default theme's greyscale primary does.
     */
    public static function css(?string $color): string
    {
        if ($color === null || $color === '') {
            return '';
        }

        $foreground = self::foregroundFor($color);

        return <<<CSS
        html:root, html.dark {
            --primary: {$color};
            --primary-foreground: {$foreground};
            --ring: {$color};
            --sidebar-primary: {$color};
            --sidebar-primary-foreground: {$foreground};
        }
        CSS;
    }

    /**
     * The text colour that stays readable on the given background.
     *
     * Without this a light brand colour — yellow, say — would keep the theme's
     * near-white foreground and leave every button unreadable.
     */
    public static function foregroundFor(string $color): string
    {
        return self::luminance($color) > self::LIGHT_THRESHOLD
            ? self::ON_LIGHT
            : self::ON_DARK;
    }

    /**
     * WCAG relative luminance, 0 for black through 1 for white.
     *
     * The channels are gamma-corrected before weighting because sRGB values are
     * not linear in perceived light, and weighted unevenly because the eye is
     * far more sensitive to green than to blue.
     */
    private static function luminance(string $color): float
    {
        $hex = ltrim($color, '#');

        // Expand the three-digit form so #f00 and #ff0000 agree.
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            // Anything unparseable is treated as dark, which pairs it with
            // white text — the safer of the two guesses.
            return 0.0;
        }

        $channels = array_map(
            static fn (string $pair): float => self::linearize(hexdec($pair) / 255),
            str_split($hex, 2),
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * Undo the sRGB transfer function for one channel.
     */
    private static function linearize(float $channel): float
    {
        return $channel <= 0.03928
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
