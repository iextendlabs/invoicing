<?php

namespace App\Models;

use App\Models\User;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    public $timestamps = true;


    protected $fillable = [
        'project_name',
        'project_desc',
        'project_status',
        'project_rate',
        'per_hour_rate',
        'user_id',
        'created_at',
        'updated_at',
    ];

    // one project is belongs to atleast one user (developer)
    // single project has many user (developers)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // create relation with task
    // project has many task
    public function task()
    {
        return $this->hasMany(Task::class, 'project_id', 'id');
    }


    public function totalHours($tasks)
    {
        $totalHoursArray = []; 
        foreach ($tasks as $task) {
            $unPaidLogs = $task->unPaidLogs;
            $taskSeconds = 0;

            $taskSeconds = $this->timeToSeconds($unPaidLogs);
            $formattedTime = sprintf(
                '%02d:%02d',
                floor($taskSeconds / 3600),
                floor(($taskSeconds / 60) % 60)
            );

            $totalHoursArray[] = $formattedTime;
        }

        return $totalHoursArray;
    }


    public function timeToSeconds($time)
    {
        $parts = explode(':', $time);
        $seconds = 0;

        if (count($parts) === 3) {
            // Format HH:MM:SS
            $seconds += $parts[0] * 3600;
            $seconds += $parts[1] * 60;
            $seconds += $parts[2];
        } elseif (count($parts) === 2) {
            // Format HH:MM
            $seconds += $parts[0] * 3600;
            $seconds += $parts[1] * 60;
        } elseif (count($parts) === 1) {
            // Format SS
            $seconds += $parts[0];
        }


        return $seconds;
    }
   

}
