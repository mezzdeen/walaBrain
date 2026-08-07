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
        Schema::table('organization_user', function (Blueprint $table) {
            // Who this person reports to, here.
            //
            // On the membership rather than on the user, because someone who
            // belongs to two organizations usually reports to different people
            // in each — and a manager resolved from the user alone could be
            // somebody outside the organization the work belongs to, which the
            // isolation rule forbids.
            //
            // Nullable, and it stays that way: plenty of people report to
            // nobody in a given organization, and an approval routed to a
            // manager who does not exist is a case the flow has to handle
            // rather than one the schema can rule out.
            //
            // Null on delete rather than cascade: losing a manager must not
            // take their reports' memberships with them.
            $table->foreignId('manager_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_id');
        });
    }
};
