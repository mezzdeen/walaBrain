import { AppearanceSwitcher } from '@/components/appearance-switcher';
import { LanguageSwitcher } from '@/components/language-switcher';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="relative">
            {/*
                The guest screens have no header to hang these off, so they sit
                in the corner of the page itself. Logical `end`/`top` insets
                keep them on the trailing side in both reading directions.
            */}
            <nav className="absolute end-4 top-4 z-10 flex items-center gap-1">
                <LanguageSwitcher />
                <AppearanceSwitcher />
            </nav>
            <AuthLayoutTemplate title={title} description={description}>
                {children}
            </AuthLayoutTemplate>
        </div>
    );
}
