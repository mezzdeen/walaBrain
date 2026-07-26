import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/hooks/use-initials';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslations } from '@/hooks/use-translations';
import { switchMethod } from '@/routes/organizations';
import type { OrganizationSummary } from '@/types/organization';

function OrganizationInfo({
    organization,
    caption,
}: {
    organization: OrganizationSummary;
    caption: string;
}) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-md">
                <AvatarFallback className="rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                    {getInitials(organization.name)}
                </AvatarFallback>
            </Avatar>
            <div className="ms-1 grid flex-1 text-start text-sm leading-tight group-data-[collapsible=icon]:hidden">
                <span className="truncate font-semibold">
                    {organization.name}
                </span>
                <span className="truncate text-xs text-muted-foreground">
                    {caption}
                </span>
            </div>
        </>
    );
}

export function OrgSwitcher() {
    const { organization, organizations, name } = usePage().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const { t, direction } = useTranslations();

    // Nothing to identify yet: a user still outside every organization, or the
    // admin platform, where the props are deliberately empty.
    if (!organization) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg">
                        <AppLogo />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    // A menu holding the one organization you are already in has nothing to
    // offer, so the name is shown plainly rather than as a dead control.
    if (organizations.length < 2) {
        return (
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" tooltip={organization.name}>
                        <OrganizationInfo
                            organization={organization}
                            caption={name}
                        />
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        );
    }

    const switchTo = (target: OrganizationSummary): void => {
        if (target.hash_id === organization.hash_id) {
            return;
        }

        // Neither scroll nor component state is worth keeping: the response
        // lands on the dashboard with a different organization's data.
        router.put(switchMethod.url(target));
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {/* No `tooltip` here: it makes SidebarMenuButton return a
                            Tooltip wrapper, which the DropdownMenuTrigger above
                            would then Slot onto instead of a real element,
                            silently dropping the trigger. */}
                        <SidebarMenuButton
                            size="lg"
                            className="group data-[state=open]:bg-sidebar-accent"
                            data-test="org-switcher-trigger"
                        >
                            <OrganizationInfo
                                organization={organization}
                                caption={name}
                            />
                            <ChevronsUpDown className="ms-auto size-4 group-data-[collapsible=icon]:hidden" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        // `side` is physical, so the collapsed rail's flyout has
                        // to follow the reading direction by hand.
                        side={
                            isMobile
                                ? 'bottom'
                                : state === 'collapsed'
                                  ? direction === 'rtl'
                                      ? 'left'
                                      : 'right'
                                  : 'bottom'
                        }
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            {t('core.organizations.switch')}
                        </DropdownMenuLabel>
                        {organizations.map((candidate) => (
                            <DropdownMenuItem
                                key={candidate.hash_id}
                                onSelect={() => switchTo(candidate)}
                                className="gap-2"
                                data-test={`org-switcher-option-${candidate.hash_id}`}
                            >
                                <span className="truncate">
                                    {candidate.name}
                                </span>
                                {candidate.hash_id === organization.hash_id && (
                                    <Check className="ms-auto size-4" />
                                )}
                            </DropdownMenuItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
