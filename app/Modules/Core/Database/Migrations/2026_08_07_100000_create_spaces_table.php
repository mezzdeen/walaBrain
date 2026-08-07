<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A space is where a team's boards live. It exists before boards do
        // because a board has nowhere to be without one, and because access is
        // granted here rather than per board.
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // Written by a person, so it is in the organization's working
            // language rather than translated per reader.
            $table->string('name');

            // Spaces are laid out in the order the business line chose, not the
            // order they happened to be created in.
            $table->unsignedInteger('position')->default(0);

            // The one space an organization starts with, which every member can
            // reach without being added to it. Work that belongs to no
            // particular process still needs somewhere to live, and a new
            // business line should not be an empty screen.
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'position']);
        });

        // Exactly one default per organization, guaranteed rather than assumed:
        // two defaults would make "the space everyone can reach" ambiguous, and
        // the code that resolves it would have to pick one arbitrarily. Partial,
        // so it constrains only the default and ignores soft-deleted rows.
        DB::statement(
            'CREATE UNIQUE INDEX spaces_one_default_per_organization ON spaces (organization_id) WHERE is_default AND deleted_at IS NULL',
        );

        // Who may reach a space, and what they may do once there. A row here is
        // the whole of the answer for every space except the default one.
        Schema::create('space_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('access');
            $table->timestamps();

            // Someone belongs to a space once, at one level of access.
            $table->unique(['space_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_user');
        Schema::dropIfExists('spaces');
    }
};
