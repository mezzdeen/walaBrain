import { createInertiaApp } from '@inertiajs/react';
import { DirectionProvider } from '@radix-ui/react-direction';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import SuperLayout from '@/layouts/super-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('super/auth/'):
                return AuthLayout;
            case name.startsWith('super/'):
                return SuperLayout;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    // Radix reads the direction from context rather than the document, and the
    // page is handed to us here so it stays identical on the server and during
    // hydration. A language switch reloads the page, so this never goes stale.
    withApp(app, { page }) {
        return (
            <DirectionProvider dir={page.props.direction}>
                <TooltipProvider delayDuration={0}>
                    {app}
                    <Toaster />
                </TooltipProvider>
            </DirectionProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
