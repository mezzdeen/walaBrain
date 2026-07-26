import { usePage } from '@inertiajs/react';
import type { OrganizationSummary } from '@/types/organization';

export type UseCanReturn = {
    readonly permissions: readonly string[];
    readonly organization: OrganizationSummary | null;
    readonly organizations: readonly OrganizationSummary[];
    readonly can: (permission: string) => boolean;
    readonly canAny: (...permissions: string[]) => boolean;
    readonly canAll: (...permissions: string[]) => boolean;
};

/**
 * Reads the signed-in identity's permissions from the Inertia page props.
 *
 * Only useful for hiding interface elements. The routes enforce the same
 * permissions server-side, so a hidden button is a courtesy rather than a
 * control — nothing here is trusted.
 *
 * On the company platform the permissions are those of the active organization,
 * so they change when the user switches between them.
 */
export function useCan(): UseCanReturn {
    const { permissions, organization, organizations } = usePage().props;

    const can = (permission: string): boolean =>
        permissions.includes(permission);

    const canAny = (...wanted: string[]): boolean => wanted.some(can);

    const canAll = (...wanted: string[]): boolean => wanted.every(can);

    return {
        permissions,
        organization,
        organizations,
        can,
        canAny,
        canAll,
    } as const;
}
