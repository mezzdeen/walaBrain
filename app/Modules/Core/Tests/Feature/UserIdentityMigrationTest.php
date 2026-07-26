<?php

use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The migration under test, reduced to the two methods these tests drive on it.
 * The file returns an anonymous class, so there is no name to hint against.
 */
interface RollableMigration
{
    public function up(): void;

    public function down(): void;
}

/**
 * Exercises the rollback of the identity migration directly.
 *
 * The migration's `down()` runs, its effect is asserted, and then `up()` is run
 * again to put the schema back the way the rest of the suite expects it — so
 * this test observes the rollback without leaving the users table rewritten for
 * whatever runs next.
 *
 * @return RollableMigration
 */
function identityMigration(): object
{
    return include app_path(
        'Modules/Core/Database/Migrations/2026_07_20_080006_restructure_user_identity_columns.php',
    );
}

test('rolling back does not resurrect a closed account', function () {
    $live = User::factory()->create();
    $closed = User::factory()->create();
    $closed->delete();

    expect($closed->fresh()->trashed())->toBeTrue();

    $migration = identityMigration();
    $migration->down();

    try {
        // The soft-deleted account is gone rather than reappearing as a live one
        // once the column that marked it deleted is dropped; the live account
        // stays.
        expect(DB::table('users')->where('id', $closed->id)->exists())->toBeFalse()
            ->and(DB::table('users')->where('id', $live->id)->exists())->toBeTrue();
    } finally {
        $migration->up();
    }
});

test('rolling back a single-word name does not gain a trailing dash', function () {
    // What `up()` stored for a one-word legacy name: a stand-in surname of "-".
    $single = User::factory()->create(['first_name' => 'Alice', 'last_name' => '-']);
    $full = User::factory()->create(['first_name' => 'Bob', 'last_name' => 'Stone']);

    $migration = identityMigration();
    $migration->down();

    try {
        // The stand-in is dropped, so the recomposed name is exactly the
        // original single word — not "Alice -". A real surname is kept.
        expect(DB::table('users')->where('id', $single->id)->value('name'))->toBe('Alice')
            ->and(DB::table('users')->where('id', $full->id)->value('name'))->toBe('Bob Stone');
    } finally {
        $migration->up();
    }
});
