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
        Schema::table('nodes', function (Blueprint $table) {
            // When the work was finished. A timestamp rather than a flag,
            // because "late" is a question somebody will ask of every completed
            // item, and a boolean cannot answer it.
            //
            // A built-in like the due date and the assignee: it is asked across
            // every board at once, since My Work shows what is still open
            // wherever it came from.
            $table->timestamp('completed_at')->nullable()->after('status');

            $table->index(['organization_id', 'assignee_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'assignee_id', 'completed_at']);
            $table->dropColumn('completed_at');
        });
    }
};
