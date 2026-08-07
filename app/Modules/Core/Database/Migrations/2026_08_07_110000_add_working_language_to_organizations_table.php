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
            // The language the organization's own people write in: field
            // labels, option lists, and the names of spaces, boards, groups and
            // roles. Authored once rather than once per reader, so nobody is
            // asked to type every label twice.
            //
            // Not the same thing as the language someone reads the interface
            // in, which each person chooses for themselves and which flips the
            // page direction with it. A reader on English chrome inside an
            // Arabic organization sees Arabic labels, and that is intended.
            //
            // Defaulted rather than nullable: every organization writes in some
            // language, and a null would push the question onto every caller
            // that reads it.
            $table->string('locale', 5)->default('en')->after('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
