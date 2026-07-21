import { Head, setLayoutProps } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import { index } from '@/routes/invitations';

export default function MemberInvitations() {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.invitations.manage.title'),
                href: index(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.invitations.manage.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.invitations.manage.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.invitations.manage.description')}
                    </p>
                </div>
            </div>
        </>
    );
}
