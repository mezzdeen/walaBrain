import { Head, setLayoutProps } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import Heading from '@/components/heading';
import LanguageTabs from '@/components/language-tabs';
import { useTranslations } from '@/hooks/use-translations';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    const { t } = useTranslations();

    setLayoutProps({
        breadcrumbs: [
            {
                title: t('core.settings.appearance_title'),
                href: editAppearance(),
            },
        ],
    });

    return (
        <>
            <Head title={t('core.settings.appearance_title')} />

            <h1 className="sr-only">{t('core.settings.appearance_title')}</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('core.settings.appearance_title')}
                    description={t('core.settings.appearance_description')}
                />
                <AppearanceTabs />

                <Heading
                    variant="small"
                    title={t('core.common.language')}
                    description={t('core.common.language_description')}
                />
                <LanguageTabs />
            </div>
        </>
    );
}
