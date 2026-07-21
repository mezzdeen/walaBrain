import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PermissionMatrix } from '@/components/permission-matrix';
import type { PermissionGroups } from '@/components/permission-matrix';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useCan } from '@/hooks/use-can';
import { useTranslations } from '@/hooks/use-translations';
import { destroy, index, store, update } from '@/routes/roles';

type RoleRow = {
    id: number;
    name: string;
    permissions: string[];
    permissions_count: number;
    protected: boolean;
};

type Props = {
    roles: RoleRow[];
    permissionGroups: PermissionGroups;
};

export default function OrganizationRoleSettings({
    roles,
    permissionGroups,
}: Props) {
    const { t } = useTranslations();
    const { can, organization } = useCan();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.roles.title'),
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.roles.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.roles.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {organization
                            ? t('core.roles.organization_title', {
                                  name: organization.name,
                              })
                            : t('core.roles.description')}
                    </p>
                </div>

                {can('roles.create') && (
                    <div>
                        <RoleDialog
                            permissionGroups={permissionGroups}
                            trigger={
                                <Button size="sm">
                                    <Plus className="size-4" />
                                    {t('core.roles.new')}
                                </Button>
                            }
                        />
                    </div>
                )}

                <div className="flex flex-col gap-3">
                    {roles.map((role) => (
                        <Card key={role.id}>
                            <CardContent className="flex items-center justify-between gap-4">
                                <div className="flex flex-col gap-1">
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm font-medium">
                                            {role.name}
                                        </span>
                                        {role.protected && (
                                            <Badge variant="outline">
                                                {t('core.roles.protected')}
                                            </Badge>
                                        )}
                                    </div>
                                    <span className="text-sm text-muted-foreground">
                                        {t(
                                            role.permissions_count === 1
                                                ? 'core.roles.permission_count_one'
                                                : 'core.roles.permission_count_other',
                                            { count: role.permissions_count },
                                        )}
                                    </span>
                                </div>

                                <div className="flex items-center gap-2">
                                    {can('roles.update') && !role.protected && (
                                        <RoleDialog
                                            role={role}
                                            permissionGroups={permissionGroups}
                                            trigger={
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    {t('core.common.edit')}
                                                </Button>
                                            }
                                        />
                                    )}
                                    {can('roles.delete') && !role.protected && (
                                        <DeleteRole role={role} />
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

function RoleDialog({
    role,
    permissionGroups,
    trigger,
}: {
    role?: RoleRow;
    permissionGroups: PermissionGroups;
    trigger: React.ReactNode;
}) {
    const { t } = useTranslations();
    const [permissions, setPermissions] = useState<string[]>(
        role?.permissions ?? [],
    );

    return (
        <Dialog>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent className="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {role
                            ? t('core.roles.edit_title')
                            : t('core.roles.new')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('core.roles.permissions_description')}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...(role ? update.form(role.id) : store.form())}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor={`role-name-${role?.id ?? 'new'}`}
                                >
                                    {t('core.roles.name')}
                                </Label>
                                <Input
                                    id={`role-name-${role?.id ?? 'new'}`}
                                    name="name"
                                    required
                                    defaultValue={role?.name}
                                    placeholder={t(
                                        'core.roles.name_placeholder',
                                    )}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <PermissionMatrix
                                groups={permissionGroups}
                                selected={permissions}
                                onChange={setPermissions}
                            />
                            <InputError message={errors.permissions} />

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" type="button">
                                        {t('core.common.cancel')}
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {role
                                        ? t('core.roles.save_changes')
                                        : t('core.roles.create_action')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
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
