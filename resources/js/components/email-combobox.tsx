import { useEffect, useId, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { useInitials } from '@/hooks/use-initials';

export interface SearchUser {
    hash_id: string;
    first_name: string;
    last_name: string;
    full_name: string;
    email: string;
    /** True when the account already belongs here — suggested, but not pickable. */
    already_member?: boolean;
}

export interface EmailComboboxCopy {
    /** Field label. */
    label: string;
    placeholder: string;
    /** Screen-reader label for the loading spinner. */
    searching: string;
    /** Shown under a chosen existing account. */
    selectedNote: string;
    /** Shown when a settled search matched nothing. */
    noResults: string;
    /** Appended to {@link noResults} — what happens to an unknown address. */
    willBeInvited: string;
    /** Badge on a suggestion that already belongs here. Only needed when results can carry `already_member`. */
    memberBadge?: string;
    /** Clears the chosen account. */
    clear: string;
}

interface EmailComboboxProps {
    /** Field name the surrounding `<Form>` submits this address under. */
    name: string;
    error?: string;
    /** Id shared by the input and its label. */
    inputId: string;
    /** Builds the search URL for a query — the only thing that varies by caller. */
    searchUrl: (query: string) => string;
    copy: EmailComboboxCopy;
}

/** Matches the server's own floor, below which it returns nothing. */
const MINIMUM_QUERY_LENGTH = 2;

/**
 * Email field that suggests existing accounts as the address is typed.
 *
 * The account directory it searches and the words it uses are both injected, so
 * the same field serves the platform's owner form and an organization's own
 * member form. Whether the address is known changes what happens on submit —
 * an existing account is invited, a stranger's address is invited too — so the
 * field says as much before the form is sent.
 */
export function EmailCombobox({
    name,
    error,
    inputId,
    searchUrl,
    copy,
}: EmailComboboxProps) {
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

    // Held in a ref rather than read from the search effect's deps: callers pass
    // this as an inline arrow, so a new reference every render would otherwise
    // re-fire the search on each result and loop the field against the server.
    const searchUrlRef = useRef(searchUrl);

    useEffect(() => {
        searchUrlRef.current = searchUrl;
    });

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

        fetch(searchUrlRef.current(query), {
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
        // answer to what the user is actually typing now.
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
        // An account already here is shown to be recognised, not to be picked;
        // the invite it would create is one the server refuses anyway.
        if (user.already_member) {
            return;
        }

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
    const showNoResults = open && searchable && settled && results.length === 0;

    function handleKeyDown(event: React.KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'Escape') {
            setOpen(false);

            return;
        }

        // Guarded on what is actually on screen, so Enter can never pick a
        // suggestion the user cannot see.
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
            <Label htmlFor={inputId}>{copy.label}</Label>

            <div ref={containerRef} className="relative">
                <Input
                    id={inputId}
                    name={name}
                    type="email"
                    required
                    autoComplete="off"
                    role="combobox"
                    aria-expanded={open}
                    aria-controls={listboxId}
                    aria-autocomplete="list"
                    placeholder={copy.placeholder}
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
                        <span className="sr-only">{copy.searching}</span>
                    </span>
                )}

                {showResults && (
                    <ul
                        id={listboxId}
                        role="listbox"
                        className="absolute inset-x-0 top-full z-50 mt-1 max-h-64 overflow-y-auto rounded-md border bg-popover p-1 shadow-md"
                    >
                        {results.map((user, index) => (
                            <li key={user.hash_id}>
                                <button
                                    type="button"
                                    role="option"
                                    aria-selected={index === activeIndex}
                                    aria-disabled={user.already_member}
                                    disabled={user.already_member}
                                    onMouseEnter={() => setActiveIndex(index)}
                                    onClick={() => select(user)}
                                    className={`flex w-full items-center gap-3 rounded-sm px-2 py-1.5 text-start ${
                                        user.already_member
                                            ? 'cursor-not-allowed opacity-60'
                                            : index === activeIndex
                                              ? 'bg-accent text-accent-foreground'
                                              : ''
                                    }`}
                                >
                                    <Avatar className="size-7">
                                        <AvatarFallback className="bg-neutral-200 text-xs text-black dark:bg-neutral-700 dark:text-white">
                                            {getInitials(user.full_name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span className="grid flex-1 leading-tight">
                                        <span className="truncate text-sm font-medium">
                                            {user.full_name}
                                        </span>
                                        <span className="truncate text-xs text-muted-foreground">
                                            {user.email}
                                        </span>
                                    </span>
                                    {user.already_member &&
                                        copy.memberBadge && (
                                            <span className="shrink-0 rounded-full border px-2 py-0.5 text-xs text-muted-foreground">
                                                {copy.memberBadge}
                                            </span>
                                        )}
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
                            {getInitials(selected.full_name)}
                        </AvatarFallback>
                    </Avatar>
                    <div className="grid flex-1 leading-tight">
                        <span className="truncate text-sm font-medium">
                            {selected.full_name}
                        </span>
                        <span className="truncate text-xs text-muted-foreground">
                            {copy.selectedNote}
                        </span>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={clear}
                    >
                        {copy.clear}
                    </Button>
                </div>
            )}

            {showNoResults && (
                <p className="text-xs text-muted-foreground">
                    {copy.noResults} {copy.willBeInvited}
                </p>
            )}
        </div>
    );
}
