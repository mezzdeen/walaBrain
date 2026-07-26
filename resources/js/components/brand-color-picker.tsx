import { Check, Pipette } from 'lucide-react';
import { useRef, useState } from 'react';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * Presets that read well as a primary colour in both light and dark mode.
 * Purely presentational — the server accepts any valid hex, so a custom choice
 * is as legitimate as one of these.
 */
const PRESETS = [
    '#dc2626',
    '#ea580c',
    '#ca8a04',
    '#16a34a',
    '#0891b2',
    '#2563eb',
    '#7c3aed',
    '#db2777',
] as const;

/**
 * Picks the organization's brand colour.
 *
 * Deliberately a controlled component in a codebase that otherwise submits
 * uncontrolled inputs: the swatches and the custom picker have to agree on one
 * selection, which needs shared state. The value still reaches the server
 * through a hidden input inside the surrounding <Form>, so no useForm is
 * involved and the house pattern holds where it matters.
 */
export function BrandColorPicker({ value }: { value: string | null }) {
    const { t } = useTranslations();
    const [color, setColor] = useState(value ?? '');
    const customInput = useRef<HTMLInputElement>(null);

    const isCustom = color !== '' && !PRESETS.includes(color as never);

    return (
        <div className="grid gap-2">
            <Label htmlFor="brand-color-default">
                {t('core.organizations.color')}
            </Label>

            <p className="text-sm text-muted-foreground">
                {t('core.organizations.color_description')}
            </p>

            <input type="hidden" name="color" value={color} />

            <div className="mt-1 flex flex-wrap items-center gap-2">
                {/* Empty rather than #000000: the application's own primary is
                    near-black in light mode but inverts to near-white in dark,
                    and storing a literal black would freeze it in both. */}
                <Swatch
                    id="brand-color-default"
                    color="#0a0a0a"
                    label={t('core.organizations.color_default')}
                    selected={color === ''}
                    onSelect={() => setColor('')}
                />

                {PRESETS.map((preset) => (
                    <Swatch
                        key={preset}
                        color={preset}
                        label={preset}
                        selected={color === preset}
                        onSelect={() => setColor(preset)}
                    />
                ))}

                <button
                    type="button"
                    onClick={() => customInput.current?.click()}
                    aria-label={t('core.organizations.color_custom')}
                    title={t('core.organizations.color_custom')}
                    data-test="brand-color-custom"
                    className={cn(
                        'relative flex size-8 items-center justify-center rounded-full border border-border transition-shadow',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                        isCustom &&
                            'ring-2 ring-ring ring-offset-2 ring-offset-background',
                    )}
                    style={isCustom ? { backgroundColor: color } : undefined}
                >
                    <Pipette
                        className={cn(
                            'size-4',
                            isCustom
                                ? 'text-white mix-blend-difference'
                                : 'text-muted-foreground',
                        )}
                    />
                </button>

                {/* Visually hidden rather than display:none — Firefox refuses to
                    open the picker for a fully hidden input. */}
                <input
                    ref={customInput}
                    type="color"
                    value={color === '' ? '#000000' : color}
                    onChange={(event) => setColor(event.target.value)}
                    tabIndex={-1}
                    aria-hidden="true"
                    className="pointer-events-none absolute size-0 opacity-0"
                />
            </div>
        </div>
    );
}

function Swatch({
    id,
    color,
    label,
    selected,
    onSelect,
}: {
    id?: string;
    color: string;
    label: string;
    selected: boolean;
    onSelect: () => void;
}) {
    return (
        <button
            id={id}
            type="button"
            onClick={onSelect}
            aria-label={label}
            title={label}
            aria-pressed={selected}
            data-test={`brand-color-${color}`}
            className={cn(
                'flex size-8 items-center justify-center rounded-full border border-border transition-shadow',
                'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                selected &&
                    'ring-2 ring-ring ring-offset-2 ring-offset-background',
            )}
            style={{ backgroundColor: color }}
        >
            {selected && (
                <Check className="size-4 text-white mix-blend-difference" />
            )}
        </button>
    );
}
