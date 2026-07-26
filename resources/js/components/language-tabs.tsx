import type { HTMLAttributes } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

export default function LanguageToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { locale, supportedLocales, setLocale, t } = useTranslations();

    const tabs = supportedLocales.map((value) => ({
        value,
        label: t(`core.common.languages.${value}`),
    }));

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, label }) => (
                <button
                    key={value}
                    onClick={() => setLocale(value)}
                    data-test={`language-tab-${value}`}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        locale === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <span className="text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
