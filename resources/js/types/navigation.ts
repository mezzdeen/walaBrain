import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    /**
     * Where the item goes. An item that only groups others still needs one —
     * it is what the collapsed sidebar navigates to — so point it at the first
     * child rather than making this optional, which every consumer would then
     * have to narrow.
     */
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
    /** Sub-items, rendered as a collapsible group. One level only. */
    children?: NavItem[];
};
