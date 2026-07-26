<?php

use App\Modules\Core\Enums\OrganizationPermission;
use App\Modules\Core\Enums\SuperPermission;
use App\Modules\Core\Support\Locale;

/**
 * The permission matrix labels a checkbox by splitting the permission name into
 * a group and an ability and translating each half. Nothing about that fails
 * loudly — a missing key renders as the key itself — so every combination the
 * catalogues can produce is checked here, in every locale.
 */
dataset('permissions', fn (): array => [
    ...array_map(fn (SuperPermission $case): array => [$case->value], SuperPermission::cases()),
    ...array_map(fn (OrganizationPermission $case): array => [$case->value], OrganizationPermission::cases()),
]);

test('every permission has a group and an ability label', function (string $permission) {
    [$group, $ability] = explode('.', $permission, 2);

    foreach (Locale::SUPPORTED as $locale) {
        app()->setLocale($locale);

        $groupKey = "core::roles.groups.{$group}";
        $abilityKey = "core::roles.abilities.{$ability}";

        expect(__($groupKey))->not->toBe($groupKey, "missing [{$groupKey}] in [{$locale}]")
            ->and(__($abilityKey))->not->toBe($abilityKey, "missing [{$abilityKey}] in [{$locale}]");
    }
})->with('permissions');

test('every role name the seeder creates is translatable', function () {
    foreach (Locale::SUPPORTED as $locale) {
        app()->setLocale($locale);

        foreach (['title', 'description', 'permissions', 'protected', 'new'] as $key) {
            expect(__("core::roles.{$key}"))->not->toBe("core::roles.{$key}");
        }
    }
});
