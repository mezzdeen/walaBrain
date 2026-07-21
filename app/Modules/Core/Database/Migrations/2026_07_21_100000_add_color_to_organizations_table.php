<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Nullable rather than defaulted to black: null means "leave the
            // theme alone", which keeps the primary colour inverting between
            // light and dark as it does today. A literal #000000 forced into
            // both modes would render dark mode's buttons black on near-black.
            $table->string('color', 7)->nullable()->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
