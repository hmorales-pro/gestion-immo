<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Private link given to the prospect (manage / reschedule / cancel / complete info).
            // Distinct from `token`, which lets the owner approve.
            $table->string('manage_token', 64)->nullable()->unique()->after('token');
            // iCalendar SEQUENCE: incremented on every change so calendars update the event.
            $table->unsignedInteger('sequence')->default(0)->after('manage_token');
            $table->text('cancellation_reason')->nullable()->after('decided_at');
            $table->timestamp('information_requested_at')->nullable()->after('cancellation_reason');
        });

        Schema::table('users', function (Blueprint $table) {
            // Secret of the agent's subscribable calendar feed (Google / Apple / Outlook).
            $table->string('calendar_token', 64)->nullable()->unique()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['manage_token']);
            $table->dropColumn(['manage_token', 'sequence', 'cancellation_reason', 'information_requested_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['calendar_token']);
            $table->dropColumn('calendar_token');
        });
    }
};
