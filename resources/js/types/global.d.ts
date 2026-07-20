import type { Direction } from '@/hooks/use-translations';
import type { Auth } from '@/types/auth';

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
            [key: string]: unknown;
        };
    }
}
