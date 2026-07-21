import type { Direction } from '@/hooks/use-translations';
import type { Auth, Registration } from '@/types/auth';
import type { OrganizationSummary } from '@/types/organization';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            direction: Direction;
            supportedLocales: readonly string[];
            translations: Record<string, unknown>;
            permissions: string[];
            organization: OrganizationSummary | null;
            organizations: OrganizationSummary[];
            /** Null on the admin platform, where accounts are never self-created. */
            registration: Registration | null;
            brandColorCss: string;
            [key: string]: unknown;
        };
    }
}
