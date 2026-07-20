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
import { create, destroy, edit, index } from '@/routes/super/roles';

type RoleRow = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    protected: boolean;
};

type Props = {
    roles: RoleRow[];
};

export default function RolesIndex({ roles }: Props) {
    const { t } = useTranslations();
    const { can } = useCan();

    setLayoutProps({
        breadcrumbs: [{ title: t('core.roles.title'), href: index() }],
    });

    const count = (key: string, value: number): string =>
        t(`core.roles.${key}_count_${value === 1 ? 'one' : 'other'}`, {
            count: value,
        });

    return (
        <>
            <Head title={t('core.roles.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {t('core.roles.title')}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {t('core.roles.description')}
                        </p>
                    </div>
                    {can('roles.create') && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus className="size-4" />
                                {t('core.roles.new')}
                            </Link>
                        </Button>
                    )}
                </div>

                <Card className="overflow-hidden p-0">
                    {roles.length === 0 ? (
                        <div className="flex flex-col items-center gap-2 p-12 text-center">
                            <p className="text-sm font-medium">
                                {t('core.roles.empty_title')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {t('core.roles.empty_description')}
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b text-start text-muted-foreground">
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.roles.name')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.roles.permissions')}
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            {t('core.roles.assigned')}
                                        </th>
                                        <th className="px-4 py-3 text-end font-medium">
                                            {t('core.roles.actions')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {roles.map((role) => (
                                        <tr
                                            key={role.id}
                                            className="border-b last:border-0"
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-medium">
                                                        {role.name}
                                                    </span>
                                                    {role.protected && (
                                                        <Badge variant="outline">
                                                            {t(
                                                                'core.roles.protected',
                                                            )}
                                                        </Badge>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {count(
                                                    'permission',
                                                    role.permissions_count,
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {count(
                                                    'assigned',
                                                    role.users_count,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center justify-end gap-2">
                                                    {can('roles.update') &&
                                                        !role.protected && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit(
                                                                        role.id,
                                                                    )}
                                                                >
                                                                    <Pencil className="size-4" />
                                                                    {t(
                                                                        'core.common.edit',
                                                                    )}
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    {can('roles.delete') &&
                                                        !role.protected && (
                                                            <DeleteRole
                                                                role={role}
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

function DeleteRole({ role }: { role: RoleRow }) {
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
                    <DialogTitle>{t('core.roles.delete_title')}</DialogTitle>
                    <DialogDescription>
                        {t('core.roles.delete_description', {
                            name: role.name,
                        })}
                    </DialogDescription>
                </DialogHeader>
                <Form {...destroy.form(role.id)}>
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
