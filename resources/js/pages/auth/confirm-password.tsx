import { Form, Head, setLayoutProps } from '@inertiajs/react';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const { t } = useTranslations();

    setLayoutProps({
        title: t('core.auth.confirm_password'),
        description: t('core.auth.confirm_password_description'),
    });

    return (
        <>
            <Head title={t('core.auth.confirm_password')} />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={t('core.auth.confirm_with_passkey')}
                loadingLabel={t('core.auth.confirming')}
                separator={t('core.auth.or_confirm_with_password')}
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('core.auth.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t('core.auth.password')}
                                autoComplete="current-password"
                                autoFocus
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {t('core.auth.confirm_password')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}
