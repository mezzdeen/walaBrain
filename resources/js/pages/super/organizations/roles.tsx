import { Head, router, setLayoutProps } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { index as organizationsIndex } from '@/routes/super/organizations';
import { update as updateMemberRoles } from '@/routes/super/organizations/members/roles';
import { index as rolesIndex } from '@/routes/super/organizations/roles';

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    protected: boolean;
};

type MemberRow = {
    id: number;
    full_name: string;
    email: string;
    roles: string[];
};

type Props = {
    organization: { id: number; name: string };
    roles: RoleRow[];
    members: MemberRow[];
};

export default function OrganizationRoles({
    organization,
    roles,
    members,
}: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.organizations.title'),
                href: organizationsIndex(),
            },
            {
                title: t('core.roles.organization_breadcrumb'),
                href: rolesIndex(organization.id),
            },
        ],
    });

    const toggleRole = (
        member: MemberRow,
        role: string,
        checked: boolean,
    ): void => {
        router.put(updateMemberRoles([organization.id, member.id]).url, {
            roles: checked
                ? [...member.roles, role]
                : member.roles.filter((name) => name !== role),
        });
    };

    return (
        <>
            <Head
                title={t('core.roles.organization_title', {
                    name: organization.name,
                })}
            />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.roles.organization_title', {
                            name: organization.name,
                        })}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.roles.organization_description')}
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {t('core.roles.owned_roles')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {roles.map((role) => (
                            <Badge key={role.id} variant="secondary">
                                {role.name}
                                <span className="ms-2 text-muted-foreground">
                                    {role.permissions_count}
                                </span>
                            </Badge>
                        ))}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            {t('core.roles.members_title')}
                        </CardTitle>
                        <p className="text-sm text-muted-foreground">
                            {t('core.roles.members_description')}
                        </p>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-6">
                        {members.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('core.organizations.no_members')}
                            </p>
                        ) : (
                            members.map((member) => (
                                <div
                                    key={member.id}
                                    className="flex flex-col gap-3 border-b pb-6 last:border-0 last:pb-0"
                                >
                                    <div>
                                        <p className="text-sm font-medium">
                                            {member.full_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {member.email}
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap gap-4">
                                        {roles.map((role) => (
                                            <div
                                                key={role.id}
                                                className="flex items-center gap-2"
                                            >
                                                <Checkbox
                                                    id={`member-${member.id}-role-${role.id}`}
                                                    checked={member.roles.includes(
                                                        role.name,
                                                    )}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) =>
                                                        toggleRole(
                                                            member,
                                                            role.name,
                                                            checked === true,
                                                        )
                                                    }
                                                />
                                                <Label
                                                    htmlFor={`member-${member.id}-role-${role.id}`}
                                                    className="text-sm font-normal text-muted-foreground"
                                                >
                                                    {role.name}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
