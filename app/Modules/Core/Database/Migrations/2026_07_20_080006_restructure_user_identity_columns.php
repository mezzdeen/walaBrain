<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The stand-in surname for a legacy row whose name was a single word.
     *
     * Both halves are required from here on, so a one word name has to be
     * given something rather than block the migration.
     */
    private const MISSING_SURNAME = '-';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 8)->nullable()->after('email');
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->softDeletes();
        });

        $this->splitExistingNames();

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The column that records a soft delete is about to be dropped, and a
        // row left behind without it reads as a live account again. These
        // accounts were closed, so they are removed rather than resurrected —
        // the schema being rolled back to has no way to say "deleted". Done
        // while `deleted_at` still exists; the `organization_user` pivot
        // cascades on the delete.
        DB::table('users')->whereNotNull('deleted_at')->delete();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('users')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('id')
            ->chunk(200, function ($users): void {
                foreach ($users as $user) {
                    // A single-word name was split with a stand-in surname, so
                    // dropping it back out is what lets the name round-trip to
                    // exactly what it was rather than gaining a trailing "-".
                    $last = $user->last_name === self::MISSING_SURNAME
                        ? ''
                        : (string) $user->last_name;

                    DB::table('users')->where('id', $user->id)->update([
                        'name' => trim($user->first_name.' '.$last),
                    ]);
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    /**
     * Fill the new columns from the single name column.
     *
     * Done in PHP rather than in SQL because the string functions that split on
     * the first space differ between PostgreSQL and the SQLite the test suite
     * runs on.
     */
    private function splitExistingNames(): void
    {
        DB::table('users')
            ->select('id', 'name')
            ->orderBy('id')
            ->chunk(200, function ($users): void {
                foreach ($users as $user) {
                    [$first, $last] = $this->split((string) $user->name);

                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $first,
                        'last_name' => $last,
                    ]);
                }
            });
    }

    /**
     * Split a full name on its first space.
     *
     * @return array{0: string, 1: string}
     */
    private function split(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        if (! Str::contains($name, ' ')) {
            return [$name, self::MISSING_SURNAME];
        }

        return [Str::before($name, ' '), Str::after($name, ' ')];
    }
};
