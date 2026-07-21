import { Form, Head, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules?: string;
};

export default function Register({ passwordRules }: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        title: t('core.auth.register_title'),
        description: t('core.auth.register_description'),
    });

    return (
        <>
            <Head title={t('core.auth.register_title')} />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="first_name">
                                    {t('core.auth.first_name')}
                                </Label>
                                <Input
                                    id="first_name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="given-name"
                                    name="first_name"
                                />
                                <InputError message={errors.first_name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="last_name">
                                    {t('core.auth.last_name')}
                                </Label>
                                <Input
                                    id="last_name"
                                    type="text"
                                    required
                                    tabIndex={2}
                                    autoComplete="family-name"
                                    name="last_name"
                                />
                                <InputError message={errors.last_name} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                {t('core.auth.email_address')}
                            </Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                tabIndex={3}
                                autoComplete="email"
                                name="email"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('core.auth.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                required
                                tabIndex={4}
                                autoComplete="new-password"
                                name="password"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('core.auth.confirm_password')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                required
                                tabIndex={5}
                                autoComplete="new-password"
                                name="password_confirmation"
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={6}
                            data-test="register-button"
                        >
                            {processing && <Spinner />}
                            {t('core.auth.create_account')}
                        </Button>

                        <div className="text-center text-sm text-muted-foreground">
                            {t('core.auth.have_account')}{' '}
                            <TextLink href={login()} tabIndex={7}>
                                {t('core.auth.log_in')}
                            </TextLink>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}
