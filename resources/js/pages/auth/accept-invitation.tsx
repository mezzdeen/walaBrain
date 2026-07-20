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
import { store } from '@/routes/invitations';

type Reason = 'invalid' | 'expired' | 'accepted';

type Props = {
    organization?: { name: string };
    email?: string;
    token?: string;
    passwordRules?: string;
    reason?: Reason;
};

export default function AcceptInvitation({
    organization,
    email,
    token,
    passwordRules,
    reason,
}: Props) {
    const { t } = useTranslations();

    // Called unconditionally, before the branch: the dead-end states are still
    // this page, just with nothing to fill in.
    setLayoutProps(
        reason
            ? {
                  title: t(`core.invitations.${reason}_title`),
                  description: t(`core.invitations.${reason}_description`),
              }
            : {
                  title: t('core.invitations.title'),
                  description: t('core.invitations.description', {
                      name: organization?.name ?? '',
                  }),
              },
    );

    if (reason) {
        return (
            <>
                <Head title={t(`core.invitations.${reason}_title`)} />
                <div className="text-center text-sm text-muted-foreground">
                    <TextLink href={login()}>
                        {t('core.invitations.back_to_login')}
                    </TextLink>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={t('core.invitations.title')} />
            <Form
                {...store.form(token!)}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="email">
                                {t('core.invitations.email')}
                            </Label>
                            {/* Fixed by the invitation: the token was mailed to
                                this address and nowhere else. */}
                            <Input
                                id="email"
                                type="email"
                                value={email}
                                readOnly
                                disabled
                            />
                            <p className="text-xs text-muted-foreground">
                                {t('core.invitations.email_hint')}
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">
                                {t('core.invitations.name')}
                            </Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="name"
                                name="name"
                                placeholder={t(
                                    'core.invitations.name_placeholder',
                                )}
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('core.invitations.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                required
                                tabIndex={2}
                                autoComplete="new-password"
                                name="password"
                                placeholder={t('core.invitations.password')}
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                {t('core.invitations.password_confirmation')}
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                required
                                tabIndex={3}
                                autoComplete="new-password"
                                name="password_confirmation"
                                placeholder={t(
                                    'core.invitations.password_confirmation',
                                )}
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={4}
                            data-test="accept-invitation-button"
                        >
                            {processing && <Spinner />}
                            {t('core.invitations.submit')}
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}
