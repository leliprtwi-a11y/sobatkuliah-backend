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
        Schema::create('tasks', function (Blueprint $table) {
            $table->string('id')->primary();        // ID dari Flutter
            $table->string('firebase_uid');
            $table->string('course_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline');
            $table->tinyInteger('priority')->default(2); // 1=rendah,2=sedang,3=tinggi
            $table->boolean('is_done')->default(false);
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
        Schema::dropIfExists('tasks');
    }
};
