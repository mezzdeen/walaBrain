import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
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
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslations } from '@/hooks/use-translations';

const APPEARANCE_ICONS: Record<Appearance, LucideIcon> = {
    light: Sun,
    dark: Moon,
    system: Monitor,
};

/**
 * Compact appearance switcher for the top navigation bar.
 *
 * The trigger shows the selected mode rather than the resolved one, so
 * "system" stays visible as a distinct choice instead of masquerading as
 * whichever theme the OS happens to be using.
 */
export function AppearanceSwitcher() {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTranslations();

    const TriggerIcon = APPEARANCE_ICONS[appearance];
    const label = t('core.common.appearance');

    const options: Appearance[] = ['light', 'dark', 'system'];

    return (
        <DropdownMenu>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="group h-9 w-9 cursor-pointer"
                            data-test="appearance-switcher"
                        >
                            <span className="sr-only">{label}</span>
                            <TriggerIcon className="!size-5 opacity-80 group-hover:opacity-100" />
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
            <DropdownMenuContent align="end" className="min-w-36">
                <DropdownMenuRadioGroup
                    value={appearance}
                    onValueChange={(value) =>
                        updateAppearance(value as Appearance)
                    }
                >
                    {options.map((value) => {
                        const OptionIcon = APPEARANCE_ICONS[value];

                        return (
                            <DropdownMenuRadioItem
                                key={value}
                                value={value}
                                data-test={`appearance-option-${value}`}
                            >
                                <OptionIcon className="me-2 size-4" />
                                {t(`core.common.appearances.${value}`)}
                            </DropdownMenuRadioItem>
                        );
                    })}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
