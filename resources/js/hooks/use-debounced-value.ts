import { useEffect, useState } from 'react';

/**
 * Track a value, but only report it once it has held still for a moment.
 *
 * Keeps a keystroke-driven value from firing a request per character.
 */
export function useDebouncedValue<T>(value: T, delay = 250): T {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timeout = setTimeout(() => setDebounced(value), delay);

        return () => clearTimeout(timeout);
    }, [value, delay]);

    return debounced;
}
