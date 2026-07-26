import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/super/login';

export default function SuperLogin() {
    const { t } = useTranslations();

    setLayoutProps({
        title: t('core.auth.admin_platform_title'),
        description: t('core.auth.admin_platform_description'),
    });

    return (
        <>
            <Head title={t('core.auth.admin_log_in')} />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                {t('core.auth.email_address')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="admin@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('core.auth.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder={t('core.auth.password')}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center space-x-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label htmlFor="remember">
                                {t('core.auth.remember_me')}
                            </Label>
                        </div>

                        <Button
                            type="submit"
                            className="mt-4 w-full"
                            tabIndex={4}
                            disabled={processing}
                            data-test="super-login-button"
                        >
                            {processing && <Spinner />}
                            {t('core.auth.log_in')}
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}
