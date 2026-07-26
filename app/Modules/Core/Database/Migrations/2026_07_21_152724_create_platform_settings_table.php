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
        // Keyed by the setting's name rather than an auto-increment id: a
        // setting is looked up by what it is, never by a row number, and the
        // primary key is then what stops the same setting being stored twice.
        //
        // The value is JSON so a setting is not limited to the one shape a
        // column would fix it at. Today that is a boolean and a map of enabled
        // login providers; neither would survive a `string` column intact.
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
