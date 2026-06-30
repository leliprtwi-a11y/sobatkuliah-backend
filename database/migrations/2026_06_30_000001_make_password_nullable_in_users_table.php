<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * User di app ini login via Firebase (Google Sign-In / Email-Password
     * yang divalidasi Firebase, bukan Laravel auth tradisional). Jadi kolom
     * 'password' bawaan Laravel tidak relevan & harus nullable supaya
     * User::firstOrCreate() di FirebaseAuthMiddleware tidak gagal insert.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};