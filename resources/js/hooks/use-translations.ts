import { usePage } from '@inertiajs/react';

export type Direction = 'ltr' | 'rtl';

export type Replacements = Record<string, string | number>;

type Dictionary = Record<string, unknown>;

export type UseTranslationsReturn = {
    readonly locale: string;
    readonly direction: Direction;
    readonly supportedLocales: readonly string[];
    readonly t: (key: string, replacements?: Replacements) => string;
    readonly setLocale: (locale: string) => void;
};

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const lookup = (dictionary: Dictionary, key: string): string | null => {
    const value = key
        .split('.')
        .reduce<unknown>(
            (carry, segment) =>
                carry && typeof carry === 'object' && segment in carry
                    ? (carry as Record<string, unknown>)[segment]
                    : undefined,
            dictionary,
        );

    return typeof value === 'string' ? value : null;
};

const translate = (
    dictionary: Dictionary,
    key: string,
    replacements: Replacements = {},
): string => {
    const line = lookup(dictionary, key);

    if (line === null) {
        return key;
    }

    return Object.entries(replacements).reduce(
        (carry, [token, value]) => carry.replaceAll(`:${token}`, String(value)),
        line,
    );
};

/**
 * Reads the active locale and its dictionary from the Inertia page props.
 *
 * Everything comes from props rather than client-only state, so the
 * server-rendered markup and the hydrated markup are always identical. The
 * dictionary itself is a once-prop: it rides along with the initial load and
 * is not resent on subsequent visits.
 */
export function useTranslations(): UseTranslationsReturn {
    const { locale, direction, translations, supportedLocales } =
        usePage().props;

    const t = (key: string, replacements?: Replacements): string =>
        translate(translations, key, replacements);

    const setLocale = (next: string): void => {
        if (next === locale) {
            return;
        }

        // The server resolves the locale from this cookie. Reloading in full
        // brings back the template's `dir`/`lang`, the Radix direction context
        // and the new dictionary together, so nothing can drift out of step.
        setCookie('locale', next);
        window.location.reload();
    };

    return { locale, direction, supportedLocales, t, setLocale } as const;
}
