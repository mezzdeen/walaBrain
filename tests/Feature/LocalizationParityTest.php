<?php

/**
 * English is the fallback locale, so every key it defines has to exist in every
 * other locale — a missing one surfaces an English sentence mid-Arabic-page, in
 * the wrong direction. The placeholders have to match too: a translation that
 * drops a `:value` renders the token as literal text.
 */

/**
 * Flatten a translation array to dot-keyed strings.
 *
 * @param  array<string, mixed>  $lines
 * @return array<string, string>
 */
function flattenLines(array $lines, string $prefix = ''): array
{
    $flat = [];

    foreach ($lines as $key => $value) {
        $dotted = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

        if (is_array($value)) {
            $flat += flattenLines($value, $dotted);
        } else {
            $flat[$dotted] = (string) $value;
        }
    }

    return $flat;
}

/**
 * The placeholders a message carries, sorted, e.g. `[':attribute', ':value']`.
 *
 * @return list<string>
 */
function placeholders(string $message): array
{
    preg_match_all('/:\w+/', $message, $matches);

    $found = array_unique($matches[0]);
    sort($found);

    return array_values($found);
}

dataset('root locale files', [
    'auth' => ['auth'],
    'pagination' => ['pagination'],
    'passwords' => ['passwords'],
    'validation' => ['validation'],
]);

test('every english key exists in arabic', function (string $file) {
    $english = flattenLines(require lang_path("en/{$file}.php"));
    $arabic = flattenLines(require lang_path("ar/{$file}.php"));

    expect(array_keys(array_diff_key($english, $arabic)))->toBe([]);
})->with('root locale files');

test('translated placeholders match english', function (string $file) {
    $english = flattenLines(require lang_path("en/{$file}.php"));
    $arabic = flattenLines(require lang_path("ar/{$file}.php"));

    foreach ($english as $key => $message) {
        if (! isset($arabic[$key])) {
            continue;
        }

        expect(placeholders($arabic[$key]))
            ->toBe(placeholders($message), "placeholders for [{$file}.{$key}] drifted");
    }
})->with('root locale files');
