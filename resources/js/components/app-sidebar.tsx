import {
    Building2,
    LayoutGrid,
    Settings2,
    ShieldCheck,
    UserPlus,
    Users,
} from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { OrgSwitcher } from '@/components/org-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes';
import { index as invitationsIndex } from '@/routes/invitations';
import { index as membersIndex } from '@/routes/members';
import { edit as editOrganization } from '@/routes/organization';
import { index as rolesIndex } from '@/routes/roles';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useTranslations();
    const { can } = useCan();

    // Each screen carries its own permission, so a user sees only the parts of
    // the organization they administer. The routes enforce the same checks.
    const administrationItems: NavItem[] = [
        ...(can('organization.update')
            ? [
                  {
                      title: t('core.settings.organization_title'),
                      href: editOrganization(),
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
        ...(can('members.manage')
            ? [
                  {
                      title: t('core.nav.user_management'),
                      href: membersIndex(),
                      icon: Users,
                  },
              ]
            : []),
        ...(can('members.invite')
            ? [
                  {
                      title: t('core.nav.invite_user'),
                      href: invitationsIndex(),
                      icon: UserPlus,
                  },
              ]
            : []),
    ];

    const mainNavItems: NavItem[] = [
        {
            title: t('core.nav.dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        // Holding none of the three leaves nothing to group, so the heading
        // goes too rather than expanding onto an empty list.
        ...(administrationItems.length > 0
            ? [
                  {
                      title: t('core.nav.general_administration'),
                      // The collapsed rail navigates the parent itself, so it
                      // has to land somewhere the user is actually allowed.
                      href: administrationItems[0].href,
                      icon: Settings2,
                      children: administrationItems,
                  },
              ]
            : []),
    ];

    const footerNavItems: NavItem[] = [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <OrgSwitcher />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
