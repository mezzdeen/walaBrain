import { Form, Head, setLayoutProps, usePage } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import ProfileController from '@/actions/App/Modules/Core/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { Auth } from '@/types';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;
    const { t } = useTranslations();

    // Changing the address asks for the account password; a name change does
    // not. Tracked here so the field only appears once the address actually
    // differs, matching what the server requires. Compared case-insensitively,
    // since the server stores and matches the address in lower case.
    const [email, setEmail] = useState(auth.user.email);
    const emailChanged =
        email.trim().toLowerCase() !== auth.user.email.toLowerCase();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.settings.profile_title'),
                href: edit(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.settings.profile_title')} />

            <h1 className="sr-only">{t('core.settings.profile_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('core.common.profile')}
                    description={t('core.settings.profile_description')}
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="first_name">
                                        {t('core.settings.first_name')}
                                    </Label>

                                    <Input
                                        id="first_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.first_name}
                                        name="first_name"
                                        required
                                        autoComplete="given-name"
                                        placeholder={t(
                                            'core.settings.first_name_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.first_name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="last_name">
                                        {t('core.settings.last_name')}
                                    </Label>

                                    <Input
                                        id="last_name"
                                        className="mt-1 block w-full"
                                        defaultValue={auth.user.last_name}
                                        name="last_name"
                                        required
                                        autoComplete="family-name"
                                        placeholder={t(
                                            'core.settings.last_name_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.last_name}
                                    />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('core.settings.email_address')}
                                </Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder={t(
                                        'core.settings.email_address',
                                    )}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {emailChanged && (
                                <div className="grid gap-2">
                                    <Label htmlFor="current_password">
                                        {t('core.settings.current_password')}
                                    </Label>

                                    <Input
                                        id="current_password"
                                        type="password"
                                        className="mt-1 block w-full"
                                        name="current_password"
                                        required
                                        autoComplete="current-password"
                                    />

                                    <p className="text-sm text-muted-foreground">
                                        {t(
                                            'core.settings.confirm_email_change',
                                        )}
                                    </p>

                                    <InputError
                                        className="mt-2"
                                        message={errors.current_password}
                                    />
                                </div>
                            )}

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            {t(
                                                'core.settings.email_unverified',
                                            )}{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                {t(
                                                    'core.settings.resend_verification_email',
                                                )}
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                {t(
                                                    'core.settings.verification_link_sent',
                                                )}
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    {t('core.common.save')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}
