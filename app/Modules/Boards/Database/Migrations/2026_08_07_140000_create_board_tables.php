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
        // A board is one process's worth of work: it owns the fields its nodes
        // carry and the groups they are partitioned into. It lives in a space,
        // which is what decides who can reach it.
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();

            // Authored, so it is in the organization's working language.
            $table->string('name');
            $table->unsignedInteger('position')->default(0);

            // The board a business line starts with, for work that belongs to no
            // particular process — a task somebody wrote for themselves. It
            // cannot be removed, for the same reason the default space cannot.
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'space_id', 'position']);
        });

        // What a board's nodes are described by. Every node on a board carries
        // this same set, however it was created.
        Schema::create('board_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('type');

            // Only the select-like types use this: the list of choices offered.
            $table->jsonb('options')->nullable();

            // A short line shown beneath the field, e.g. "including VAT".
            $table->string('help')->nullable();

            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'board_id', 'position']);
        });

        // A visual partition of a board's nodes, usually one per stage. Grouping
        // changes only how nodes are displayed; every node in every group
        // carries the same fields.
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'board_id', 'position']);
        });

        // The universal work item: a request, a task, a booking, anything
        // tracked.
        //
        // Split deliberately between real columns and JSON. Anything queried
        // *across* boards gets a column of its own, because My Work sorts every
        // node assigned to one person by due date across every board in a
        // business line, and that cannot afford to be a JSON extraction. What a
        // Process Designer invents goes in `values`, because there is no way to
        // give a column to a field that does not exist yet.
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();

            // Every node has these, whoever made it and whatever board it is on
            // — a task written by hand carries the same ones a flow generates.
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();

            // The board's own fields, keyed by field id. Keyed by id rather than
            // by name so renaming a field does not orphan every value under it.
            $table->jsonb('values')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'board_id', 'group_id']);

            // My Work: what is assigned to one person, soonest first.
            $table->index(['organization_id', 'assignee_id', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('board_fields');
        Schema::dropIfExists('boards');
    }
};
