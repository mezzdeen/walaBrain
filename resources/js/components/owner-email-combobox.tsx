import { useEffect, useId, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { useInitials } from '@/hooks/use-initials';
import { useTranslations } from '@/hooks/use-translations';
import { search } from '@/routes/super/users';

interface SearchUser {
    id: number;
    name: string;
    email: string;
}

interface OwnerEmailComboboxProps {
    /** Field name the surrounding `<Form>` submits this address under. */
    name: string;
    error?: string;
}

/** Matches the server's own floor, below which it returns nothing. */
const MINIMUM_QUERY_LENGTH = 2;

/**
 * Email field that suggests existing accounts as the address is typed.
 *
 * Whether the address is known decides what happens on submit — the user is
 * either made owner outright or sent an invitation — so the field says which of
 * the two the admin is about to trigger.
 */
export function OwnerEmailCombobox({ name, error }: OwnerEmailComboboxProps) {
    const { t } = useTranslations();
    const getInitials = useInitials();
    const listboxId = useId();

    const [email, setEmail] = useState('');
    const [selected, setSelected] = useState<SearchUser | null>(null);
    const [results, setResults] = useState<SearchUser[]>([]);
    // Which query the results in hand answer. Comparing it to what is typed is
    // what "still loading" means here, so there is no flag to keep in step.
    const [resultsQuery, setResultsQuery] = useState<string | null>(null);
    const [open, setOpen] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);

    const containerRef = useRef<HTMLDivElement>(null);
    const debouncedEmail = useDebouncedValue(email);

    useEffect(() => {
        // Once someone is picked the field is settled; searching again would
        // only reopen the list under a confirmed answer. Stale results are not
        // cleared here — what renders is derived from the live query below, so
        // they are already invisible.
        if (selected || debouncedEmail.trim().length < MINIMUM_QUERY_LENGTH) {
            return;
        }

        const query = debouncedEmail.trim();
        const controller = new AbortController();

        fetch(search.url({ query: { q: query } }), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((response) => (response.ok ? response.json() : { users: [] }))
            .then((data: { users: SearchUser[] }) => {
                setResults(data.users);
                setResultsQuery(query);
                setActiveIndex(-1);
                setOpen(true);
            })
            .catch(() => {
                // An aborted request has been superseded by a newer one, whose
                // own result will settle the field. A genuine failure has to
                // settle it here, or the spinner would never stop.
                if (!controller.signal.aborted) {
                    setResults([]);
                    setResultsQuery(query);
                }
            });

        // Abort in flight, so a slow early response cannot land on top of the
        // answer to what the admin is actually typing now.
        return () => controller.abort();
    }, [debouncedEmail, selected]);

    useEffect(() => {
        function handlePointerDown(event: PointerEvent) {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('pointerdown', handlePointerDown);

        return () =>
            document.removeEventListener('pointerdown', handlePointerDown);
    }, []);

    function select(user: SearchUser) {
        setSelected(user);
        setEmail(user.email);
        setResults([]);
        setResultsQuery(null);
        setOpen(false);
        setActiveIndex(-1);
    }

    function clear() {
        setSelected(null);
        setEmail('');
        setResults([]);
        // Forgotten alongside the results, so retyping the same address
        // searches again instead of reading an empty list as "no account".
        setResultsQuery(null);
        setActiveIndex(-1);
    }

    // All derived from the live query rather than the debounced one, so
    // deleting a character hides the suggestions immediately instead of a beat
    // later, and results for a query that is no longer typed never show.
    const query = email.trim();
    const searchable = !selected && query.length >= MINIMUM_QUERY_LENGTH;
    const settled = resultsQuery === query;
    const showSpinner = searchable && !settled;
    const showResults = open && searchable && settled && results.length > 0;
    const showNoResults =
        open && searchable && settled && results.length === 0;

    function handleKeyDown(event: React.KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'Escape') {
            setOpen(false);

            return;
        }

        // Guarded on what is actually on screen, so Enter can never pick a
        // suggestion the admin cannot see.
        if (!showResults) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((index) => (index + 1) % results.length);
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex(
                (index) => (index - 1 + results.length) % results.length,
            );
        }

        if (event.key === 'Enter' && activeIndex >= 0) {
            // Only swallow the submit when a suggestion is actually highlighted,
            // so Enter still submits the form the rest of the time.
            event.preventDefault();
            select(results[activeIndex]);
        }
    }

    return (
        <div className="grid gap-2">
            <Label htmlFor="owner_email">
                {t('core.organizations.owner_email')}
            </Label>

            <div ref={containerRef} className="relative">
                <Input
                    id="owner_email"
                    name={name}
                    type="email"
                    required
                    autoComplete="off"
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listboxId}
                    aria-autocomplete="list"
                    placeholder={t('core.organizations.owner_email_placeholder')}
                    value={email}
                    onChange={(event) => {
                        setEmail(event.target.value);
                        setSelected(null);
                        setOpen(true);
                    }}
                    onFocus={() => setOpen(true)}
                    onKeyDown={handleKeyDown}
                    className={showSpinner ? 'pe-9' : undefined}
                />

                {showSpinner && (
                    <span className="pointer-events-none absolute inset-y-0 end-3 flex items-center">
                        <Spinner className="size-4 text-muted-foreground" />
                        <span className="sr-only">
                            {t('core.organizations.owner_searching')}
                        </span>
                    </span>
                )}

                {showResults && (
                    <ul
                        id={listboxId}
                        role="listbox"
                        className="absolute inset-x-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
                    >
                        {results.map((user, index) => (
                            <li key={user.id}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={index === activeIndex}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    onClick={() => select(user)}
                                    className={`flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-start ${
                                        index === activeIndex
                                            ? 'bg-accent text-accent-foreground'
                                            : ''
                                    }`}
                                >
                                    <Avatar className="size-7">
                                        <AvatarFallback className="bg-neutral-200 text-xs text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(user.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="grid flex-1 leading-tight">
                                        <span className="truncate text-sm font-medium">
                                            {user.name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {user.email}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            <InputError message={error} />

            {selected && (
                <div className="flex items-center gap-3 rounded-md border bg-muted/40 p-3">
                    <Avatar className="size-9">
                        <AvatarFallback className="bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                            {getInitials(selected.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 leading-tight">
                        <span className="truncate text-sm font-medium">
                            {selected.name}
                        </span>
                        <span className="truncate text-xs text-muted-foreground">
                            {t('core.organizations.owner_will_be_added')}{' '}
                            {t('core.organizations.owner_independent')}
                        </span>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={clear}
                    >
                        {t('core.organizations.owner_clear')}
                    </Button>
                </div>
            )}

            {showNoResults && (
                <p className="text-xs text-muted-foreground">
                    {t('core.organizations.owner_no_results')}{' '}
                    {t('core.organizations.owner_will_be_invited')}
                </p>
            )}
        </div>
    );
}
