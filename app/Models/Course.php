<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable  = ['id', 'firebase_uid', 'name', 'lecturer', 'color'];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'course_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'course_id');
    }
}