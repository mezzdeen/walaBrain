import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/routes/super/organizations';

type OrganizationRow = {
    id: number;
    name: string;
    users_count: number;
};

type Props = {
    organizations: OrganizationRow[];
};

export default function OrganizationsIndex({ organizations }: Props) {
    const { t } = useTranslations();
    const { can } = useCan();

    setLayoutProps({
        breadcrumbs: [{ title: t('core.organizations.title'), href: index() }],
    });

    return (
        <>
            <Head title={t('core.organizations.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {t('core.organizations.title')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('core.organizations.description')}
                        </p>
                    </div>
                    {can('organizations.create') && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('core.organizations.new')}
                            </Link>
                        </Button>
                    )}
                </div>

                <Card className="overflow-hidden p-0">
                    {organizations.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 p-12 text-center">
                            <p className="text-sm font-medium">
                                {t('core.organizations.empty_title')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('core.organizations.empty_description')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-start text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.organizations.name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.organizations.users')}
                                        </th>
                                        <th className="px-4 py-3 text-end font-medium">
                                            {t('core.organizations.actions')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {organizations.map((organization) => (
                                        <tr
                                            key={organization.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={show(organization.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {organization.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant="secondary">
                                                    {organization.users_count}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-2">
                                                    {can(
                                                        'organizations.update',
                                                    ) && (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={edit(
                                                                    organization.id,
                                                                )}
                                                            >
                                                                <Pencil className="size-4" />
                                                                {t(
                                                                    'core.common.edit',
                                                                )}
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {can(
                                                        'organizations.delete',
                                                    ) && (
                                                        <DeleteOrganization
                                                            organization={
                                                                organization
                                                            }
                                                        />
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card>
            </div>
        </>
    );
}

function DeleteOrganization({
    organization,
}: {
    organization: OrganizationRow;
}) {
    const { t } = useTranslations();

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="text-destructive hover:text-destructive"
                >
                    <Trash2 className="size-4" />
                    {t('core.common.delete')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('core.organizations.delete_title')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('core.organizations.delete_description', {
                            name: organization.name,
                        })}
                    </DialogDescription>
                </DialogHeader>
                <Form {...destroy.form(organization.id)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary" type="button">
                                    {t('core.common.cancel')}
                                </Button>
                            </DialogClose>
                            <Button
                                variant="destructive"
                                type="submit"
                                disabled={processing}
                            >
                                {processing && <Spinner />}
                                {t('core.common.delete')}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
