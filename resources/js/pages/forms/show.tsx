import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { FieldInput } from '@/components/field-input';
import type { FieldDefinition } from '@/components/field-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/forms';

type Props = {
    form: { hash_id: string; name: string; board: string };
    fields: FieldDefinition[];
};

export default function FormShow({ form, fields }: Props) {
    const { t } = useTranslations();

    setLayoutProps({ breadcrumbs: [{ title: form.name, href: '#' }] });

    return (
        <>
            <Head title={form.name} />

            <div className="mx-auto flex w-full max-w-xl flex-1 flex-col gap-6 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{form.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {t('forms.forms.submitting_to', { board: form.board })}
                    </p>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <Form
                            {...store.form(form.hash_id)}
                            className="flex flex-col gap-5"
                        >
                            {({ errors, processing }) => (
                                <>
                                    {fields.map((field) => (
                                        <FieldInput
                                            key={field.hash_id}
                                            field={field}
                                            error={
                                                errors[
                                                    `values.${field.hash_id}`
                                                ]
                                            }
                                        />
                                    ))}

                                    <InputError message={errors.form} />

                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="form-submit"
                                    >
                                        <Send className="size-4" />
                                        {t('forms.forms.submit')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
