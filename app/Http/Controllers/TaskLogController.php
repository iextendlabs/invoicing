<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Symfony\Component\HttpFoundation\Session\Session;


class TaskLogController extends Controller
{
    public function index()
    {
        // return to log creation form with some (details) like project, user, task
        $project = Project::all();
        $task = Task::all();
        $devs = User::where('user_role', 'developer')->get();

        return view('admin.createTaskLog', ['dev' => $devs, 'task' => $task, 'project' => $project]);
    }

    public function getTasksByProject($project_id)
    {
        $tasks = Task::where('project_id', $project_id)->get();
        return response()->json($tasks);
    }


    public function createLog(Request $request)
    {
        // dd($request->all());
        // add new task log
        // calculate time difference of paid and unpaid logs
        // update paid/unpaid/totalPaid columns in Task Table
        // dd($request->all());
        $request->validate([
            'dev_id' => 'required|integer',
            'task_id' => 'required|integer',
            'starttime' => 'required',
            'endtime' => 'required',
            'date_creation' => 'required',
            'logStatus' => 'required',
        ]);

        if ($request->starttime > $request->endtime) {
            $request->session()->flash('success', 'Invalid End Time entered');
            return redirect()->route('create.task.log');
        }
        $taskLog = new TaskLog();
        $taskLog->user_id           = $request->dev_id;
        $taskLog->task_id           = $request->task_id;
        $taskLog->start_time        = $request->starttime;
        $taskLog->end_time          = $request->endtime;
        $taskLog->log_creation_date = $request->date_creation;
        $taskLog->log_status        = $request->logStatus;
        $taskLog->save();
        if ($taskLog->save()) {
            $logDifference = $this->calculateDifference($request->starttime, $request->endtime);

            $logDifferenceInSeconds = $this->timeToSeconds($logDifference);
            $task = Task::find($request->task_id);

            $unPaidLogsInSeconds = $this->timeToSeconds($task->unPaidLogs);
            $paidLogsInSeconds = $this->timeToSeconds($task->paidLogs);


            if ($request->logStatus == "pending") {
                $totalUnpaidSeconds = $unPaidLogsInSeconds + $logDifferenceInSeconds;
                $task->unPaidLogs = $this->secondsToTime($totalUnpaidSeconds);
                $task->totalHours = $this->secondsToTime($this->timeToSeconds($task->unPaidLogs) + $paidLogsInSeconds);
            } elseif ($request->logStatus == "complete") {
                $totalPaidSeconds = $paidLogsInSeconds + $logDifferenceInSeconds;
                $task->paidLogs = $this->secondsToTime($totalPaidSeconds);
                $task->totalHours = $this->secondsToTime($this->timeToSeconds($task->paidLogs) + $unPaidLogsInSeconds);
            }
            $task->save();
            $request->session()->flash('success', 'Log Added Successfully!!');
            return redirect()->route('explore-task/logs', ['task_id' => $request->task_id]);
        }
    }


    public function exploreLogs(Request $request, $task_id)
    {
        // get task Log details from $task_id
        // get taskLog related task detail
        // get the related developer detail
        // display number of logs on specific task
        // details about logs (how many hours spend on each logs i.e difference of start-time and end-time)
        // developer name
        // 
        $completedLogs = 0;
        $pendingLogs   = 0;

        $task    = Task::find($task_id);
        $taskLog = $task->tasklog;
        $hours   = $this->calcHours($taskLog);
        $logDifference = [];
        foreach ($taskLog as $logs) {
            // check log is PENDING or COMPLETED
            if ($logs->log_status == 'pending') {
                $pendingLogs++;
            }
            if ($logs->log_status == 'complete') {
                $completedLogs++;
            }

            $startTime  = Carbon::parse($logs->start_time);
            $finishTime = Carbon::parse($logs->end_time);

            array_push($logDifference, $this->calculateDifference($startTime, $finishTime));
        }

        $taskTotalCost =  $this->timeToDecimalHours($hours) * $task->project->per_hour_rate;
        return view('admin.logDetails', ['task' => $task, 'taskLog' => $taskLog, 'hours' => $hours, 'taskTotalCost' => $taskTotalCost, 'logDifference' => $logDifference, 'per_hour_rate' => $task->project->per_hour_rate, 'pendingLogs' => $pendingLogs, 'completedLogs' => $completedLogs]);
    }

    public function changeLogStatus($id)
    {
        $taskID = TaskLog::find($id)->task->id;
        $update = TaskLog::where('id', $id)->update([
            'log_status' => 'complete'
        ]);
        if ($update) {
            return redirect()->route('explore-task/logs', ['task_id' => $taskID]);
        }
    }

    // calculate hours
    public function calcHours($taskLog)
    {
        $arr   = [];
        $index = 0;
        foreach ($taskLog as $logs) {
            $start  = new Carbon($logs->start_time);
            $end    = new Carbon($logs->end_time);
            array_push($arr, $this->calculateDifference($start, $end));
        }
        return $this->totalTimeSpend($arr);
    }

    public function calculateDifference($start, $end)
    {

        // calculate the difference between two times
        // $diff = str_replace(':', '.', $start->diff($end)->format('%H:%I'));
        $start  = new Carbon($start);
        $end    = new Carbon($end);
        $diff   = $start->diff($end)->format('%H:%I');
        return $diff;
        // $diff = $start->diffInHours($end) . ':' . $start->diff($end)->format('%H:%I:%S');
        // $d1 = new DateTime($start);
        // $d2 = new DateTime($end);
        // $interval = $d1->diff($d2);
        // return $interval->h . ':' . $interval->i;
    }


    public function totalTimeSpend($time)
    {
        $sum = strtotime('00:00:00');

        $totaltime = 0;

        foreach ($time as $element) {

            // Converting the time into seconds
            $timeinsec = strtotime($element) - $sum;

            // Sum the time with previous value
            $totaltime = $totaltime + $timeinsec;
        }

        $h = intval($totaltime / 3600);

        $totaltime = $totaltime - ($h * 3600);
        // Minutes is obtained by dividing
        // remaining total time with 60
        $m = intval($totaltime / 60);
        // Remaining value is seconds
        $s = $totaltime - ($m * 60);
        // Printing the result
        return ("$h:$m");
    }


    public function editLog(Request $request, $id)
    {
        // return view containing edit form for a given log
        $edit      = TaskLog::find($id);
        $developer = User::where('user_role', 'developer')->get();
        if ($edit) {
            return view('admin.editLog', ['log' => $edit, 'developer' => $developer]);
        }
    }

    public function postEditLog(Request $request)
    {
        $request->validate([
            'logID'         => 'required|numeric',
            'starttime'     => 'required',
            'endtime'       => 'required',
            'developerName' => 'required',
            'logStatus'     => 'required',
            'date'          => 'required',
        ]);

        if ($request->endtime < $request->starttime) {
            $request->session()->flash('success', 'Invalid End Time entered');
            return redirect(url()->previous());
        }
        // log update
        $task_Log  = TaskLog::find($request->logID);
        $previousLogDifference = $this->calculateDifference($task_Log->start_time, $task_Log->end_time);
        $previousLogDifferenceSec = $this->timeToSeconds($previousLogDifference);
        $logUpdate = $task_Log->update([
            'user_id'    => $request->developerName,
            'start_time' => $request->starttime,
            'end_time'   => $request->endtime,
            'log_status' => $request->logStatus,
            'log_creation_date' => $request->date,
        ]);
        
        $taskID     = TaskLog::where('id', $request->logID)->pluck('task_id');
        $projectID  = Task::find($taskID[0])->project->id;


        if ($logUpdate) {

            $logDifference = $this->calculateDifference($request->starttime, $request->endtime);
            $logDifferenceInSeconds = $this->timeToSeconds($logDifference);
            $task = Task::find($taskID[0]);
            $unPaidLogsInSeconds = $this->timeToSeconds($task->unPaidLogs);
            $paidLogsInSeconds = $this->timeToSeconds($task->paidLogs);

            if ($request->logStatus == "pending") {
                if ($unPaidLogsInSeconds == 0){
                    $totalUnpaidSeconds = $unPaidLogsInSeconds  + $logDifferenceInSeconds;
                    $task->unPaidLogs = $this->secondsToTime($totalUnpaidSeconds);    
                } else {
                    $totalUnpaidSeconds = $unPaidLogsInSeconds - $previousLogDifferenceSec + $logDifferenceInSeconds;
                    $task->unPaidLogs = $this->secondsToTime($totalUnpaidSeconds);
                }

                $task->totalHours = $this->secondsToTime($totalUnpaidSeconds + $paidLogsInSeconds);
            } elseif ($request->logStatus == "complete") {
                if ($unPaidLogsInSeconds == 0){
                    $totalUnpaidSeconds = $unPaidLogsInSeconds  + $logDifferenceInSeconds;
                    $task->unPaidLogs = $this->secondsToTime($totalUnpaidSeconds);    
                } else {
                    $totalUnpaidSeconds = $unPaidLogsInSeconds - $previousLogDifferenceSec + $logDifferenceInSeconds;
                    $task->unPaidLogs = $this->secondsToTime($totalUnpaidSeconds);
                }

                $task->totalHours = $this->secondsToTime($totalUnpaidSeconds + $unPaidLogsInSeconds);
            }

            $task->save();

            // Flash a success message and redirect
            $request->session()->flash('success', 'Log Updated Successfully');
            return redirect()->action([ProjectController::class, 'viewProject'], ['id' => $projectID]);
        }
    }

    public function deleteLog(Request $request, $id)
    {
        $taskID = TaskLog::find($id)->task->id;
        $del    = TaskLog::where('id', $id)->delete();
        if ($del) {
            $request->session()->flash('success', 'Log Deleted Successfully!!');
            return redirect()->route('explore-task/logs', ['task_id' => $taskID]);
        }
    }

    public function createSpecificTaskLog($taskID)
    {
        // create new LOG for a specific task
        $developer = User::where('user_role', 'developer')->get();
        return view('admin.newLog', ['dev' => $developer, 'taskID' => $taskID]);
    }

    protected function timeToSeconds($time)
    {
        sscanf($time, "%d:%d:%d", $hours, $minutes, $seconds);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    // Function to convert total seconds back to "HH:MM:SS" format
    protected function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    public function timeToDecimalHours($time)
    {
        $parts = explode(':', $time);

        // Assign hours and minutes, defaulting to 0 if not present
        $hours = isset($parts[0]) ? (int)$parts[0] : 0;
        $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
        $second = isset($parts[2]) ? (int)$parts[2] : 0;

        // Convert minutes to decimal hours
        $decimalHours = $hours + ($minutes / 60);

        return $decimalHours;
    }
}
