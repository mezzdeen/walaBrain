import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { PermissionMatrix } from '@/components/permission-matrix';
import type { PermissionGroups } from '@/components/permission-matrix';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index, update } from '@/routes/super/roles';

type Props = {
    role: {
        hash_id: string;
        name: string;
        permissions: string[];
        protected: boolean;
    };
    permissionGroups: PermissionGroups;
};

export default function EditRole({ role, permissionGroups }: Props) {
    const { t } = useTranslations();
    const [permissions, setPermissions] = useState<string[]>(role.permissions);

    setLayoutProps({
        breadcrumbs: [
            { title: t('core.roles.title'), href: index() },
            { title: t('core.roles.edit_breadcrumb'), href: edit(role) },
        ],
    });

    return (
        <>
            <Head
                title={t('core.roles.edit_page_title', { name: role.name })}
            />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.roles.edit_title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.roles.edit_description')}
                    </p>
                </div>

                <Card className="max-w-3xl">
                    <CardContent>
                        <Form
                            {...update.form(role)}
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid max-w-md gap-2">
                                        <Label htmlFor="name">
                                            {t('core.roles.name')}
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                            defaultValue={role.name}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex flex-col gap-4">
                                        <div>
                                            <p className="text-sm font-medium">
                                                {t('core.roles.permissions')}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'core.roles.permissions_description',
                                                )}
                                            </p>
                                        </div>
                                        <PermissionMatrix
                                            groups={permissionGroups}
                                            selected={permissions}
                                            onChange={setPermissions}
                                        />
                                        <InputError
                                            message={errors.permissions}
                                        />
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t('core.roles.save_changes')}
                                        </Button>
                                        <Button variant="ghost" asChild>
                                            <Link href={index()}>
                                                {t('core.common.cancel')}
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
