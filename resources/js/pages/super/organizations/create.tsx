import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { OwnerEmailCombobox } from '@/components/owner-email-combobox';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { create, index, store } from '@/routes/super/organizations';

export default function CreateOrganization() {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            { title: t('core.organizations.title'), href: index() },
            { title: t('core.organizations.new_breadcrumb'), href: create() },
        ],
    });

    return (
        <>
            <Head title={t('core.organizations.new')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.organizations.new')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.organizations.new_description')}
                    </p>
                </div>

                <Form
                    {...store.form()}
                    resetOnSuccess
                    className="flex flex-col gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardContent>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            {t('core.organizations.name')}
                                        </Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoFocus
                                            placeholder={t(
                                                'core.organizations.name_placeholder',
                                            )}
                                        />
                                        <InputError message={errors.name} />
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {t('core.organizations.owner_title')}
                                    </CardTitle>
                                    <CardDescription>
                                        {t(
                                            'core.organizations.owner_description',
                                        )}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <OwnerEmailCombobox
                                        name="owner_email"
                                        error={errors.owner_email}
                                    />
                                </CardContent>
                            </Card>

                            <div className="flex items-center gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    {t('core.organizations.create_action')}
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
            </div>
        </>
    );
}
