import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil, ShieldCheck } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index } from '@/routes/super/organizations';
import { index as organizationRoles } from '@/routes/super/organizations/roles';

type OrganizationMember = {
    hash_id: string;
    full_name: string;
    email: string;
};

type Props = {
    organization: {
        hash_id: string;
        name: string;
    };
    users: OrganizationMember[];
};

export default function ShowOrganization({ organization, users }: Props) {
    const { t } = useTranslations();
    const { can } = useCan();

    setLayoutProps({
        breadcrumbs: [
            { title: t('core.organizations.title'), href: index() },
            {
                title: t('core.organizations.details_breadcrumb'),
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title={organization.name} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {organization.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t(
                                users.length === 1
                                    ? 'core.organizations.member_count_one'
                                    : 'core.organizations.member_count_other',
                                { count: users.length },
                            )}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" asChild>
                            <Link href={index()}>
                                {t('core.organizations.back')}
                            </Link>
                        </Button>
                        {can('organizations.roles.manage') && (
                            <Button variant="outline" asChild>
                                <Link href={organizationRoles(organization)}>
                                    <ShieldCheck className="size-4" />
                                    {t('core.roles.manage_roles')}
                                </Link>
                            </Button>
                        )}
                        {can('organizations.update') && (
                            <Button asChild>
                                <Link href={edit(organization)}>
                                    <Pencil className="size-4" />
                                    {t('core.common.edit')}
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('core.organizations.members')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {users.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('core.organizations.no_members')}
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-start text-muted-foreground">
                                            <th className="px-4 py-3 font-medium">
                                                {t('core.organizations.name')}
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                {t('core.organizations.email')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.map((user) => (
                                            <tr
                                                key={user.hash_id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium">
                                                    {user.full_name}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {user.email}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
