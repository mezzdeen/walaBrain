import { Head, Link, router } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

/**
 * Shown to a signed-in user who belongs to no organization, which is what a
 * revoked membership leaves behind. Uses the guest layout on purpose: the app
 * shell would render an organization switcher with nothing to switch between.
 */
export default function NoOrganization() {
    const { t } = useTranslations();

    return (
        <AuthLayout
            title={t('core.organizations.none_title')}
            description={t('core.organizations.none_description')}
        >
            <Head title={t('core.organizations.none_title')} />

            <div className="flex flex-col gap-3">
                <Button variant="outline" asChild>
                    <Link href={editProfile()} data-test="profile-link">
                        {t('core.common.settings')}
                    </Link>
                </Button>

                <Button variant="ghost" asChild>
                    <Link
                        href={logout()}
                        as="button"
                        onClick={() => router.flushAll()}
                        data-test="logout-button"
                    >
                        <LogOut className="me-2" />
                        {t('core.auth.log_out')}
                    </Link>
                </Button>
            </div>
        </AuthLayout>
    );
}
