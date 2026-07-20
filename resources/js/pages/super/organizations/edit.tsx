import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { index, update } from '@/routes/super/organizations';

type Props = {
    organization: {
        id: number;
        name: string;
    };
};

export default function EditOrganization({ organization }: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            { title: t('core.organizations.title'), href: index() },
            { title: t('core.organizations.edit_breadcrumb'), href: index() },
        ],
    });

    return (
        <>
            <Head
                title={t('core.organizations.edit_page_title', {
                    name: organization.name,
                })}
            />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.organizations.edit_title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.organizations.edit_description')}
                    </p>
                </div>

                <Card className="max-w-xl">
                    <CardContent>
                        <Form
                            {...update.form(organization.id)}
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
                                            autoFocus
                                            defaultValue={organization.name}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="flex items-center gap-3">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            {t(
                                                'core.organizations.save_changes',
                                            )}
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
