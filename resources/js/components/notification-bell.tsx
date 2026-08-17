import { router, useHttp } from '@inertiajs/react';
import { Bell, CheckCheck, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';
import notificationRoutes from '@/routes/notifications';

type NotificationData = {
    key?: string;
    params?: Record<string, string | number>;
    url?: string;
};

type NotificationItem = {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
};

type NotificationIndexResponse = {
    notifications: NotificationItem[];
    unread: number;
};

const DIVISIONS: { amount: number; unit: Intl.RelativeTimeFormatUnit }[] = [
    { amount: 60, unit: 'seconds' },
    { amount: 60, unit: 'minutes' },
    { amount: 24, unit: 'hours' },
    { amount: 7, unit: 'days' },
    { amount: 4.34524, unit: 'weeks' },
    { amount: 12, unit: 'months' },
    { amount: Number.POSITIVE_INFINITY, unit: 'years' },
];

const relativeTime = (value: string, locale: string): string => {
    const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
    let duration = (new Date(value).getTime() - Date.now()) / 1000;

    for (const division of DIVISIONS) {
        if (Math.abs(duration) < division.amount) {
            return formatter.format(Math.round(duration), division.unit);
        }

        duration /= division.amount;
    }

    return '';
};

/**
 * The in-app notification centre, as a bell in the top navigation bar.
 *
 * Each notification stores a translation key and parameters rather than
 * prose, so the line below is rendered in whatever language its reader has
 * chosen — not the language the platform spoke when the notification was
 * sent. Opening one marks it read and follows its deep link.
 */
export function NotificationBell() {
    const { t, locale } = useTranslations();
    const [items, setItems] = useState<NotificationItem[]>([]);
    const [unread, setUnread] = useState(0);

    const { get } = useHttp<Record<string, never>, NotificationIndexResponse>(
        {},
    );

    const refresh = () => {
        get(notificationRoutes.index.url(), {
            onSuccess: (payload) => {
                setItems(payload.notifications);
                setUnread(payload.unread);
            },
        });
    };

    useEffect(() => {
        refresh();

        // The badge is what tells someone work arrived while they were
        // looking elsewhere, so it refreshes on its own — but not while the
        // tab is hidden, where nobody would see it anyway.
        const interval = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                refresh();
            }
        }, 60_000);

        return () => window.clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const visitOptions = {
        preserveScroll: true,
        preserveState: true,
    } as const;

    const open = (item: NotificationItem) => {
        const target =
            typeof item.data.url === 'string' ? item.data.url : null;

        if (item.read_at === null) {
            router.patch(notificationRoutes.update.url(item.id), undefined, {
                ...visitOptions,
                onSuccess: () =>
                    target ? router.visit(target) : refresh(),
            });

            return;
        }

        if (target) {
            router.visit(target);
        }
    };

    const dismiss = (
        event: React.MouseEvent<HTMLButtonElement>,
        item: NotificationItem,
    ) => {
        event.stopPropagation();

        router.delete(notificationRoutes.destroy.url(item.id), {
            ...visitOptions,
            onSuccess: refresh,
        });
    };

    const markAllRead = () => {
        router.post(notificationRoutes.readAll.url(), undefined, {
            ...visitOptions,
            onSuccess: refresh,
        });
    };

    const label = t('core.notifications.title');

    return (
        <DropdownMenu onOpenChange={(isOpen) => isOpen && refresh()}>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="group relative h-9 w-9 cursor-pointer"
                            data-test="notification-bell"
                        >
                            <span className="sr-only">{label}</span>
                            <Bell className="!size-5 opacity-80 group-hover:opacity-100" />
                            {unread > 0 && (
                                <span
                                    data-test="notification-badge"
                                    className="absolute -end-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-white"
                                >
                                    <span className="sr-only">
                                        {t('core.notifications.unread_count', {
                                            count: unread,
                                        })}
                                    </span>
                                    <span aria-hidden>
                                        {unread > 9 ? '9+' : unread}
                                    </span>
                                </span>
                            )}
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent>{label}</TooltipContent>
            </Tooltip>
            <DropdownMenuContent align="end" className="w-80">
                <div className="flex items-center justify-between gap-2">
                    <DropdownMenuLabel>{label}</DropdownMenuLabel>
                    {unread > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 cursor-pointer px-2 text-xs"
                            onClick={markAllRead}
                            data-test="notifications-mark-all-read"
                        >
                            <CheckCheck className="!size-3.5" />
                            {t('core.notifications.mark_all_read')}
                        </Button>
                    )}
                </div>
                <DropdownMenuSeparator />
                {items.length === 0 ? (
                    <p
                        className="text-muted-foreground px-2 py-6 text-center text-sm"
                        data-test="notifications-empty"
                    >
                        {t('core.notifications.empty')}
                    </p>
                ) : (
                    <div className="max-h-96 overflow-y-auto">
                        {items.map((item) => (
                            <DropdownMenuItem
                                key={item.id}
                                onSelect={() => open(item)}
                                className="cursor-pointer items-start gap-2 py-2"
                                data-test="notification-item"
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                        item.read_at === null
                                            ? 'bg-primary'
                                            : 'bg-transparent',
                                    )}
                                />
                                <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                    <span
                                        className={cn(
                                            'text-sm break-words',
                                            item.read_at === null &&
                                                'font-medium',
                                        )}
                                    >
                                        {item.data.key
                                            ? t(item.data.key, item.data.params)
                                            : item.type}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {relativeTime(item.created_at, locale)}
                                    </span>
                                </span>
                                <button
                                    type="button"
                                    className="text-muted-foreground/60 hover:text-foreground mt-0.5 shrink-0 cursor-pointer transition-colors"
                                    onClick={(event) => dismiss(event, item)}
                                    onPointerDown={(event) =>
                                        event.stopPropagation()
                                    }
                                    data-test="notification-dismiss"
                                >
                                    <span className="sr-only">
                                        {t('core.notifications.dismiss')}
                                    </span>
                                    <X aria-hidden className="size-3.5" />
                                </button>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
