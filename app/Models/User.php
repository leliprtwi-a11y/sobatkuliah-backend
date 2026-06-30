<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'firebase_uid',
        'fcm_token',
        'name',
        'email',
    ];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'firebase_uid', 'firebase_uid');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'firebase_uid', 'firebase_uid');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'firebase_uid', 'firebase_uid');
    }
}