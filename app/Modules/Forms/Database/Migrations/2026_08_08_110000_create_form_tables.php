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
        // An intake surface mapped to exactly one board. In this phase a form
        // presents the whole of its board's field schema, in board order —
        // choosing a subset of fields per form arrives with the designer
        // screens, not before.
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();

            // Authored, so in the organization's working language.
            $table->string('name');

            // The process the form starts, as its reference numbers say it:
            // FIN, MKT, ONB. Uppercased on the way in.
            $table->string('prefix', 8);

            // Bumped whenever a published form is edited, and stamped onto
            // every run, so history stays meaningful after the definition
            // changes. The full draft-and-freeze versioning model follows with
            // the designer screens.
            $table->unsignedInteger('version')->default(1);

            // Only a published form accepts submissions.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'board_id']);
        });

        // Where the next number in each process's yearly sequence comes from.
        // A row per form per year, locked while allocating, so two submissions
        // racing each other cannot be issued the same reference — and a number
        // once issued is never reused, because the row only ever counts up.
        Schema::create('form_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['form_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_sequences');
        Schema::dropIfExists('forms');
    }
};
