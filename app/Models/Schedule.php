<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    protected $fillable  = [
        'id', 'firebase_uid', 'course_id',
        'day_of_week', 'start_time', 'end_time', 'room',
        'last_reminder_sent_date',
    ];
    protected $casts = [
        'last_reminder_sent_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'firebase_uid', 'firebase_uid');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}