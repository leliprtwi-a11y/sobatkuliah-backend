<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Waktu notifikasi H-1 deadline, misal "07:00"
            // Null = user tidak set waktu notifikasi
            $table->string('notify_time')->nullable()->after('is_done');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('notify_time');
        });
    }
};