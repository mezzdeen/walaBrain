<?php

use App\Modules\Core\Models\User;

test('the full name joins both halves', function () {
    $user = User::factory()->make([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect($user->full_name)->toBe('Ada Lovelace');
});

test('the full name is trimmed when a half is missing', function () {
    $user = User::factory()->make([
        'first_name' => 'Ada',
        'last_name' => '',
    ]);

    expect($user->full_name)->toBe('Ada');
});

test('the full name is serialized with the model', function () {
    $user = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect($user->toArray())
        ->toHaveKey('full_name', 'Ada Lovelace');
});
