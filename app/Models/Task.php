<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable  = [
        'id', 'firebase_uid', 'course_id', 'title',
        'description', 'deadline', 'priority', 'is_done',
    ];
    protected $casts = [
        'deadline' => 'datetime',
        'is_done'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'firebase_uid', 'firebase_uid');
    }
}