import { Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/hooks/use-translations';

/**
 * Compact language switcher for the top navigation bar.
 *
 * Each option is labelled in its own language so a reader can always recognise
 * their language, even while the interface is still in the other one.
 */
export function LanguageSwitcher() {
    const { locale, supportedLocales, setLocale, t } = useTranslations();

    const label = t('core.common.language');

    return (
        <DropdownMenu>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="group h-9 w-9 cursor-pointer"
                            data-test="language-switcher"
                        >
                            <span className="sr-only">{label}</span>
                            <Globe className="!size-5 opacity-80 group-hover:opacity-100" />
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
            <DropdownMenuContent align="end" className="min-w-36">
                <DropdownMenuRadioGroup
                    value={locale}
                    onValueChange={setLocale}
                >
                    {supportedLocales.map((value) => (
                        <DropdownMenuRadioItem
                            key={value}
                            value={value}
                            data-test={`language-option-${value}`}
                        >
                            {t(`core.common.languages.${value}`)}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
