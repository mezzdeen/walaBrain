// Components
import { Form, Head, setLayoutProps } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslations();

    setLayoutProps({
        title: t('core.auth.verify_email_title'),
        description: t('core.auth.verify_email_description'),
    });

    return (
        <>
            <Head title={t('core.auth.verify_email_title')} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {t('core.auth.verification_link_sent')}
                </div>
            )}

            <Form {...send.form()} className="space-y-6 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && <Spinner />}
                            {t('core.auth.resend_verification_email')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('core.auth.log_out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
