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
        // What happens after a form is submitted: a fixed sequence of steps.
        // In this phase the chain is fixed at design time and the only trigger
        // is a form submission — conditions and the other triggers arrive with
        // the next phase, as the roadmap sequences them.
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'form_id']);
        });

        // One step in the sequence. What varies per type lives in config:
        // an approval carries who decides; a task carries a title, who does
        // the work, and a calendar-day offset from submission.
        Schema::create('flow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('position');
            $table->string('type');
            $table->jsonb('config');

            $table->timestamps();

            $table->unique(['flow_id', 'position']);
        });

        // One execution of a flow for one node. Waiting is the resting state:
        // a run sits at an approval for as long as the decision takes, and
        // nothing is lost while it does.
        Schema::create('runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();

            $table->string('status');
            $table->foreignId('current_step_id')->nullable()->constrained('flow_steps')->nullOnDelete();

            // Which definitions the run is operating under, so history stays
            // meaningful after either is republished.
            $table->unsignedInteger('flow_version');
            $table->unsignedInteger('form_version');

            $table->timestamps();

            $table->index(['organization_id', 'node_id']);
        });

        // One person's pending or recorded decision. A fresh row per round:
        // a request-changes loop leaves the earlier decisions in place and adds
        // a new pending one, so the whole back-and-forth stays readable.
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flow_step_id')->constrained()->cascadeOnDelete();
            $table->foreignId('node_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();

            $table->string('status');
            $table->text('comment')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();

            // My Work's question: what is pending on this person, here.
            $table->index(['organization_id', 'approver_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
        Schema::dropIfExists('runs');
        Schema::dropIfExists('flow_steps');
        Schema::dropIfExists('flows');
    }
};
