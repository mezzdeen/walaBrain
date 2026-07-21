import { Link } from '@inertiajs/react';
import { Building2, LayoutGrid, Settings2, ShieldCheck } from 'lucide-react';
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
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes/super';
import { index as organizationsIndex } from '@/routes/super/organizations';
import { index as rolesIndex } from '@/routes/super/roles';
import { edit as settingsEdit } from '@/routes/super/settings';
import type { NavItem } from '@/types';

export function SuperSidebar() {
    const { t } = useTranslations();
    const { can } = useCan();

    const mainNavItems: NavItem[] = [
        {
            title: t('core.nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(can('organizations.view')
            ? [
                  {
                      title: t('core.organizations.title'),
                      href: organizationsIndex(),
                      icon: Building2,
                  },
              ]
            : []),
        ...(can('roles.view')
            ? [
                  {
                      title: t('core.roles.title'),
                      href: rolesIndex(),
                      icon: ShieldCheck,
                  },
              ]
            : []),
        ...(can('settings.manage')
            ? [
                  {
                      title: t('core.nav.platform_settings'),
                      href: settingsEdit(),
                      icon: Settings2,
                  },
              ]
            : []),
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
