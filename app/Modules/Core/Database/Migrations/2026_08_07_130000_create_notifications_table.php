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
        // The in-app half of the two channels every notification is delivered
        // on. Email is the one most people actually watch; this is what makes an
        // item still findable once the email has been scrolled past, and what a
        // notification centre is read from.
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');

            // Which organization the notification is about.
            //
            // Nullable, and so not carrying the usual tenancy trait: some
            // notifications belong to no tenant at all — an admin's mail
            // diagnostic, anything sent to somebody about their own account
            // before they are in an organization. Those are addressed to a
            // person rather than about a business line's work, and forcing an
            // organization onto them would mean inventing one.
            //
            // What it does mean is that reads have to say what they want. See
            // Notification::visibleIn().
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();

            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // The notification centre's own query: this person's, in the
            // organization they are working in, newest first.
            $table->index(['notifiable_type', 'notifiable_id', 'organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
