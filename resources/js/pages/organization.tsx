import { Form, Head, setLayoutProps } from '@inertiajs/react';
import OrganizationSettingsController from '@/actions/App/Modules/Core/Http/Controllers/OrganizationSettingsController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { edit } from '@/routes/organization';
import type { OrganizationSummary } from '@/types/organization';

export default function Organization({
    organization,
}: {
    organization: OrganizationSummary;
}) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.settings.organization_title'),
                href: edit(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.settings.organization_title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.settings.organization_title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.settings.organization_description')}
                    </p>
                </div>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...OrganizationSettingsController.update.form()}
                            options={{ preserveScroll: true }}
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">
                                            {t('core.organizations.name')}
                                        </Label>

                                        <Input
                                            id="name"
                                            name="name"
                                            required
                                            autoComplete="organization"
                                            defaultValue={organization.name}
                                            placeholder={t(
                                                'core.organizations.name_placeholder',
                                            )}
                                        />

                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="update-organization-button"
                                        >
                                            {processing && <Spinner />}
                                            {t('core.common.save')}
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
