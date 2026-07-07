<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('notify_time');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->date('last_reminder_sent_date')->nullable()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('last_reminder_sent_date');
        });
    }
};