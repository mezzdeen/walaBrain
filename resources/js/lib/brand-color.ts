import type { Page, SharedPageProps } from '@inertiajs/core';
import { router } from '@inertiajs/react';

const STYLE_ID = 'brand-color';

/**
 * Apply the active organization's brand stylesheet.
 *
 * Rewrites the one <style> element the Blade root view already wrote for the
 * first paint, rather than setting properties on document.documentElement:
 * inline styles outrank both `:root` and `.dark`, so returning to the
 * application's own colours would mean removing them token by token.
 */
function apply(css: string): void {
    let style = document.getElementById(STYLE_ID);

    if (!style) {
        style = document.createElement('style');
        style.id = STYLE_ID;
        document.head.append(style);
    }

    if (style.textContent !== css) {
        style.textContent = css;
    }
}

/**
 * Keep the brand colour in step with the organization being acted on.
 *
 * A router subscription rather than a React component, because there is no
 * place inside the tree that sees every page: `withApp` is handed the page once
 * at mount and never runs again, so anything reading it there would still be
 * showing the first organization's colour after a switch — and a component
 * rendered beside the app cannot call `usePage()` at all.
 *
 * Two events, because neither covers every way a page can change:
 *
 * - `success` fires for visits driven by a request. `navigate` alone misses
 *   these when the visit replaces the current history entry, which is exactly
 *   what switching organization does — it redirects to the dashboard from the
 *   dashboard, so the URL never changes.
 * - `navigate` fires when a page is restored from history, which `success`
 *   never sees because no request was made.
 *
 * Applying twice is free: the stylesheet is only written when it differs.
 *
 * The initial paint needs nothing from here; the root view has already written
 * the correct stylesheet by the time this runs.
 */
export function initializeBrandColor(): void {
    const applyFromPage = (page: Page<SharedPageProps>): void => {
        const css = page.props.brandColorCss;

        // Guarded rather than defaulted: a page that somehow arrives without
        // the prop should keep the colour it has, not be stripped back to the
        // application's own.
        if (typeof css === 'string') {
            apply(css);
        }
    };

    router.on('success', (event) => applyFromPage(event.detail.page));
    router.on('navigate', (event) => applyFromPage(event.detail.page));
}
