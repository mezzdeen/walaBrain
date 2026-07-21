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
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('users')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('id')
            ->chunk(200, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'name' => trim($user->first_name.' '.$user->last_name),
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
