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
        // The record of what happened to everything in the application. Boards,
        // approvals, tasks and bookings all write here, and every reported
        // figure — cycle time, bottleneck step, SLA breach — is derived from
        // these rows rather than collected separately, so a gap here is a gap
        // in the reporting no later query can fill.
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            // What the entry is about: a node, an organization, a role. Written
            // out rather than through morphs() so the only index on the table is
            // the composite below; this table is written to constantly and read
            // in one shape, and a second index on the morph pair alone would be
            // paid for on every insert to serve no query.
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Who acted. Nullable because not everything is done by a person:
            // a hold that expires on its own, or a run that resumes on a date,
            // has no actor, and recording one would be a lie. Deliberately not
            // a foreign key — it points at either a user or an admin, and the
            // account may later be deleted while the history it made must not
            // disappear with it.
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            $table->string('type');

            // Whatever the entry needs to stay meaningful on its own: the
            // values submitted, the previous and new assignee, the comment on a
            // decision. It has to be readable years later, when the form that
            // produced it has been republished twice, so an entry carries its
            // own detail rather than a reference to something that has moved on.
            $table->jsonb('payload')->nullable();

            // Created only. An entry is never updated, so an updated_at column
            // would be a field that can only ever repeat created_at.
            $table->timestamp('created_at')->useCurrent();

            // Reading a subject's timeline is the query this table exists for,
            // and it is always made inside one organization.
            $table->index(['organization_id', 'subject_type', 'subject_id']);

            // Reporting sweeps a period rather than a subject.
            $table->index(['organization_id', 'created_at']);
        });

        // Append-only, enforced by the database rather than by the model alone.
        //
        // The model refuses updates and deletes, but a model can be stepped
        // around: DB::table('activities')->update(...) never loads one, and
        // neither does a migration or a psql session. An audit trail that can be
        // quietly rewritten is worth less than no audit trail at all, because it
        // still reads as authoritative. This makes the guarantee true of the
        // table itself, however it is reached.
        //
        // Corrections are new entries. Nothing is ever amended in place.
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION core_activities_append_only() RETURNS trigger
            LANGUAGE plpgsql AS $$
            BEGIN
                RAISE EXCEPTION 'activities is append-only; % is not permitted', TG_OP
                    USING ERRCODE = 'restrict_violation';
            END;
            $$;

            CREATE TRIGGER activities_append_only
                BEFORE UPDATE OR DELETE ON activities
                FOR EACH ROW EXECUTE FUNCTION core_activities_append_only();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dropping the table takes its trigger with it; the function is shared
        // by nothing else, so it goes too.
        Schema::dropIfExists('activities');

        DB::unprepared('DROP FUNCTION IF EXISTS core_activities_append_only()');
    }
};
