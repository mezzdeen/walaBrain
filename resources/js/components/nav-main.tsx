import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslations } from '@/hooks/use-translations';
import type { NavItem } from '@/types';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { t } = useTranslations();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{t('core.nav.platform')}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) =>
                    item.children?.length ? (
                        <NavGroup key={item.title} item={item} />
                    ) : (
                        <NavLink key={item.title} item={item} />
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}

function NavLink({ item }: { item: NavItem }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarMenuItem>
            <SidebarMenuButton
                asChild
                isActive={isCurrentUrl(item.href)}
                tooltip={{ children: item.title }}
            >
                <Link href={item.href} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

/**
 * A parent item that expands to its children.
 *
 * Two renderings rather than one, because `collapsible="icon"` hard-hides
 * `SidebarMenuSub` through a CSS rule this component cannot opt out of. On the
 * collapsed rail there is no room to expand into anyway, so the children move
 * into a flyout instead of disappearing.
 */
function NavGroup({ item }: { item: NavItem }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const { direction } = useTranslations();

    const children = item.children ?? [];
    const holdsCurrentPage = children.some((child) => isCurrentUrl(child.href));

    // Exact match per child rather than a prefix test on the parent:
    // `isCurrentOrParentUrl` is a bare `startsWith`, so `/roles` would also
    // count `/roles-archive` as being inside this group.
    const [open, setOpen] = useState(holdsCurrentPage);

    if (state === 'collapsed' && !isMobile) {
        return (
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {/* No `tooltip` prop: it wraps the button in a Tooltip,
                            which the trigger would then Slot onto instead of a
                            real element, silently dropping the trigger. */}
                        <SidebarMenuButton
                            isActive={holdsCurrentPage}
                            className="data-[state=open]:bg-sidebar-accent"
                        >
                            {item.icon && <item.icon />}
                            <span>{item.title}</span>
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        className="min-w-48 rounded-lg"
                        // `side` is physical, so the flyout has to follow the
                        // reading direction by hand.
                        side={direction === 'rtl' ? 'left' : 'right'}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            {item.title}
                        </DropdownMenuLabel>
                        {children.map((child) => (
                            <DropdownMenuItem key={child.title} asChild>
                                <Link href={child.href} prefetch>
                                    {child.icon && (
                                        <child.icon className="size-4" />
                                    )}
                                    <span className="truncate">
                                        {child.title}
                                    </span>
                                </Link>
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        );
    }

    return (
        <Collapsible asChild open={open} onOpenChange={setOpen}>
            <SidebarMenuItem>
                <CollapsibleTrigger asChild>
                    <SidebarMenuButton
                        className="group/collapsible"
                        isActive={holdsCurrentPage && !open}
                    >
                        {item.icon && <item.icon />}
                        <span>{item.title}</span>
                        {/* Rotating a downward chevron is direction-agnostic;
                            a sideways one would point the wrong way in RTL. */}
                        <ChevronDown className="ms-auto size-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-180" />
                    </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <SidebarMenuSub>
                        {children.map((child) => (
                            <SidebarMenuSubItem key={child.title}>
                                <SidebarMenuSubButton
                                    asChild
                                    isActive={isCurrentUrl(child.href)}
                                >
                                    <Link href={child.href} prefetch>
                                        <span>{child.title}</span>
                                    </Link>
                                </SidebarMenuSubButton>
                            </SidebarMenuSubItem>
                        ))}
                    </SidebarMenuSub>
                </CollapsibleContent>
            </SidebarMenuItem>
        </Collapsible>
    );
}
