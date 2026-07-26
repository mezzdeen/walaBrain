import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { Check, DoorClosed, DoorOpen } from 'lucide-react';
import { useState } from 'react';
import PlatformSettingsController from '@/actions/App/Modules/Core/Http/Controllers/PlatformSettingsController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import { edit } from '@/routes/super/settings';

type Props = {
    settings: {
        registrationOpen: boolean;
        socialProviders: Record<string, boolean>;
    };
};

export default function PlatformSettings({ settings }: Props) {
    const { t } = useTranslations();

    // The pending choice, which is not the same thing as the live one. The prop
    // is what the platform is actually doing right now; this is what the form
    // would change it to. Keeping them apart is the point — the screen has to
    // answer "what is switched on?" before it answers "what am I picking?".
    const [choice, setChoice] = useState(settings.registrationOpen);

    // The providers live in state rather than on the inputs, so an edit made
    // while the section is open survives the section being hidden and shown
    // again — a controlled value is held here, where nothing unmounts, instead
    // of on a checkbox that resets to its default every time the card is
    // toggled.
    const [providers, setProviders] = useState(settings.socialProviders);

    const unsaved = choice !== settings.registrationOpen;

    setLayoutProps({
        breadcrumbs: [{ title: t('core.platform.title'), href: edit() }],
    });

    const options = [
        {
            open: true,
            icon: DoorOpen,
            label: t('core.platform.registration_open'),
            help: t('core.platform.registration_open_help'),
        },
        {
            open: false,
            icon: DoorClosed,
            label: t('core.platform.registration_closed'),
            help: t('core.platform.registration_closed_help'),
        },
    ];

    return (
        <>
            <Head title={t('core.platform.title')} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">
                        {t('core.platform.title')}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {t('core.platform.description')}
                    </p>
                </div>

                <Card className="max-w-2xl">
                    <CardContent>
                        <Form
                            {...PlatformSettingsController.update.form()}
                            options={{ preserveScroll: true }}
                            className="flex flex-col gap-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="flex flex-col gap-4">
                                        <div className="flex flex-wrap items-start justify-between gap-2">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {t(
                                                        'core.platform.registration_title',
                                                    )}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'core.platform.registration_description',
                                                    )}
                                                </p>
                                            </div>

                                            {/* The saved state, not the pending
                                                one: this is the answer to "what
                                                is the platform doing?" and it
                                                must not move when a card is
                                                clicked. */}
                                            <Badge
                                                variant={
                                                    settings.registrationOpen
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                data-test="registration-live-state"
                                            >
                                                {settings.registrationOpen
                                                    ? t(
                                                          'core.platform.currently_open',
                                                      )
                                                    : t(
                                                          'core.platform.currently_closed',
                                                      )}
                                            </Badge>
                                        </div>

                                        <div className="grid gap-3 sm:grid-cols-2">
                                            {options.map((option) => {
                                                const selected =
                                                    choice === option.open;
                                                const Icon = option.icon;

                                                return (
                                                    <label
                                                        key={String(
                                                            option.open,
                                                        )}
                                                        data-test={`registration-${option.open ? 'open' : 'closed'}`}
                                                        className={cn(
                                                            'relative flex cursor-pointer flex-col gap-2 rounded-lg border p-4 transition-colors',
                                                            selected
                                                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                                                : 'border-input hover:bg-accent/50',
                                                        )}
                                                    >
                                                        {/* A native radio, so
                                                            the choice posts
                                                            itself and arrow keys
                                                            move between the two
                                                            without any of it
                                                            being reimplemented. */}
                                                        <input
                                                            type="radio"
                                                            name="registration_open"
                                                            value={
                                                                option.open
                                                                    ? '1'
                                                                    : '0'
                                                            }
                                                            checked={selected}
                                                            onChange={() =>
                                                                setChoice(
                                                                    option.open,
                                                                )
                                                            }
                                                            className="sr-only"
                                                        />

                                                        <div className="flex items-center gap-2">
                                                            <Icon
                                                                className={cn(
                                                                    'size-4',
                                                                    selected
                                                                        ? 'text-primary'
                                                                        : 'text-muted-foreground',
                                                                )}
                                                            />
                                                            <span className="text-sm font-medium">
                                                                {option.label}
                                                            </span>

                                                            {selected && (
                                                                <Check className="ms-auto size-4 text-primary" />
                                                            )}
                                                        </div>

                                                        <span className="text-xs text-muted-foreground">
                                                            {option.help}
                                                        </span>
                                                    </label>
                                                );
                                            })}
                                        </div>

                                        <InputError
                                            message={errors.registration_open}
                                        />
                                    </div>

                                    {choice && (
                                        <div className="flex flex-col gap-3 border-t pt-6">
                                            <div>
                                                <p className="text-sm font-medium">
                                                    {t(
                                                        'core.platform.providers_title',
                                                    )}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'core.platform.providers_description',
                                                    )}
                                                </p>
                                            </div>

                                            <div className="flex flex-col gap-2">
                                                {Object.entries(providers).map(
                                                    ([provider, enabled]) => (
                                                        <div
                                                            key={provider}
                                                            className="flex items-center gap-2"
                                                        >
                                                            <Checkbox
                                                                id={provider}
                                                                data-test={`provider-${provider}`}
                                                                name={`social_providers[${provider}]`}
                                                                value="1"
                                                                checked={
                                                                    enabled
                                                                }
                                                                onCheckedChange={(
                                                                    state,
                                                                ) =>
                                                                    setProviders(
                                                                        (
                                                                            current,
                                                                        ) => ({
                                                                            ...current,
                                                                            [provider]:
                                                                                state ===
                                                                                true,
                                                                        }),
                                                                    )
                                                                }
                                                            />
                                                            <Label
                                                                htmlFor={
                                                                    provider
                                                                }
                                                                className="text-sm font-normal"
                                                            >
                                                                {t(
                                                                    `core.platform.providers.${provider}`,
                                                                )}
                                                            </Label>
                                                            <Badge
                                                                variant="outline"
                                                                className="ms-1"
                                                            >
                                                                {t(
                                                                    'core.platform.providers_not_wired',
                                                                )}
                                                            </Badge>
                                                        </div>
                                                    ),
                                                )}
                                            </div>

                                            <p className="text-xs text-muted-foreground">
                                                {t(
                                                    'core.platform.providers_pending',
                                                )}
                                            </p>
                                        </div>
                                    )}

                                    <div className="flex items-center gap-3 border-t pt-6">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            data-test="save-platform-settings"
                                        >
                                            {processing && <Spinner />}
                                            {t('core.common.save')}
                                        </Button>

                                        {unsaved && (
                                            <span className="text-sm text-muted-foreground">
                                                {t('core.platform.unsaved')}
                                            </span>
                                        )}
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
