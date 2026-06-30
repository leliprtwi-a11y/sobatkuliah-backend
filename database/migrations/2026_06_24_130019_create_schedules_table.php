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
        Schema::create('schedules', function (Blueprint $table) {
            $table->string('id')->primary();        // UUID dari Flutter
            $table->string('firebase_uid');
            $table->string('course_id');
            $table->tinyInteger('day_of_week');     // 1=Senin…7=Minggu
            $table->string('start_time');           // "08:00"
            $table->string('end_time');             // "09:40"
            $table->string('room');
            $table->timestamps();

            $table->foreign('firebase_uid')
              ->references('firebase_uid')
              ->on('users')
              ->onDelete('cascade');

            $table->foreign('course_id')
              ->references('id')
              ->on('courses')
              ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
