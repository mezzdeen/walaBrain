import { Link } from '@inertiajs/react';
import { Building2, LayoutGrid } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { SuperNavUser } from '@/components/super-nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes/super';
import { index as organizationsIndex } from '@/routes/super/organizations';
import type { NavItem } from '@/types';

export function SuperSidebar() {
    const { t } = useTranslations();

    const mainNavItems: NavItem[] = [
        {
            title: t('core.nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('core.organizations.title'),
            href: organizationsIndex(),
            icon: Building2,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <SuperNavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
