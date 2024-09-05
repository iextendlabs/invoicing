<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaskLog;
use App\Models\Task;
use App\Models\Project;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskLogController;
use App\Http\Controllers\Admin\PreviewController;
use App\Models\Invoice;
use Carbon\Carbon;
use Symfony\Component\Routing\Route;
use DateTime;
use Doctrine\DBAL\Schema\Index;
use Illuminate\Support\Facades\DB;


class InvoiceController extends Controller
{
    protected $projectController;
    public function __construct()
    {
        $this->projectController = new ProjectController();
    }

    public function generateReceipt(Request $request)
    {
        $log    = TaskLog::find($request->logId);
        $start  = Carbon::parse($log->start_time);
        $end    = Carbon::parse($log->end_time);

        $diff   = $start->diff($end)->format('%h:%i');

        // Log Cost
        // $this->per_hour_rate = $request->input('per_hour_rate', config('app.per_hour_rate'));
        // $logCost = str_replace(":", ".", $diff) * $this->per_hour_rate;  // THROUGH ENV VARIABLE
        $logCost = str_replace(":", ".", $diff) * $log->task->project->per_hour_rate;
        // related TASK Info
        $task    = $log->task;
        // related PROJECT Info
        $project = $task->project;

        return view('admin.invoice.taskReceipt', ['log' => $log, 'task' => $task, 'project' => $project, 'diff' => $diff, 'logCost' => $logCost]);
    }

    public function createInvoiceView(Request $request, $projectId)
    {
        return view('admin.invoice.invoiceView', ['projectId' => $projectId]);
    }

    public function dateWiseDetail(Request $request)
    {

        // get two dates
        // validate dates
        // pick project -> Task between dates
        // calculate their total hours PLUS find total cost
        // create new invoice entry
        // add invoice_id reference in front of each task
        $taskArr = [];

        $request->validate([
            'firstDate' => 'required',
            'lastDate'  => 'required',
            'projectId' => 'required|numeric'
        ]);

        if ($request->lastDate < $request->firstDate) {
            $request->session()->flash('success', 'Incorret date choosen');
            return redirect(url()->previous());
        }
        $invoiceTitle = $request->invoiceTitle;
        $project = Project::find($request->projectId);
        $tasks   = $project->task()->whereBetween('date', [$request->firstDate, $request->lastDate])->get();

        $totalHours = $this->calculateTotalTime($tasks);
        
        $totalHours =  (strtotime($totalHours) - strtotime('TODAY'));
        $task_total_hours =  $totalHours;


        if (count($tasks) == 0) {
            $request->session()->flash('success', 'No record found from ' . $request->firstDate .  'to ' . $request->lastDate . '!!');
            return redirect()->route('create.invoice.view', ['id' => $project->id]);
        } elseif ($totalHours == 0) {
            $request->session()->flash('success',  $request->firstDate . 'to' . $request->lastDate . ' Are paid hours');
            return redirect()->route('create.invoice.view', ['id' => $project->id]);
        } else {

            foreach ($tasks as $key => $task) {
                $unpaidSeconds = strtotime($task->unPaidLogs) - strtotime('TODAY');
                if ($unpaidSeconds >= $totalHours) {

                    $task->paidLogs = date('H:i:s', strtotime($task->paidLogs) + $totalHours);
                    $task->unPaidLogs = date('H:i:s', strtotime($task->unPaidLogs) - $totalHours);
                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                    break;
                } else {

                    $task->paidLogs  = date('H:i:s', strtotime($task->paidLogs) + $unpaidSeconds);
                    $totalHours -= $unpaidSeconds;
                    $task->unPaidLogs = '00:00:00';


                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                }
            }
            $invoiceRate = ($task_total_hours / 3600);
            $invoiceRate =  $invoiceRate * $project->per_hour_rate;

            $data = ['projectName' => $project->project_name, 'totalHours' => $task_total_hours, 'invoiceRate' => $invoiceRate, 'startDate' => $request->firstDate, 'lastDate' => $request->lastDate, 'invoiceTitle' => $invoiceTitle];
            $newInvoiceId = $this->newInvoiceEntry($data);
            if ($newInvoiceId) {
                $request->session()->flash('success', 'Invoice record created!!');
                // $this->addInvoiceReference($tasks, $newInvoiceId);
                return redirect()->route('view.project', ['id' => $project->id]);
            }
        }
    }

    public function fixedAmountInvoice(Request $request)
    {


        $request->validate([
            'amount'    => 'required|numeric',
            'projectId' => 'required|numeric',
        ]);
        $amount    = $request->amount;
        $projectID = $request->projectId;
        $project   = Project::find($projectID);
        $invoiceTitle = $request->invoiceTitle;
        $tasks     = $project->task;
        $obj       = new ProjectController();
        $taskHours  = $project->totalHours($tasks);
        $projectHours = $obj->totalTimeSpend($taskHours);
        $projectHours = strtotime($projectHours) - strtotime('TODAY');
        $calculatedHours = ($amount / $project->per_hour_rate) * 3600;
        $totalHours = $calculatedHours;
        // dd($calculatedHours);
        // dd($this->secondsToTime($totalHours));
        foreach ($tasks as $key => $task) {
            $unpaidSeconds = strtotime($task->unPaidLogs) - strtotime('TODAY');
            
            if ($projectHours >=  $calculatedHours) {
                

                if ($unpaidSeconds >= $calculatedHours) {
                    $task->paidLogs = date('H:i:s', strtotime($task->paidLogs) + $calculatedHours);
                    $task->unPaidLogs = date('H:i:s', strtotime($task->unPaidLogs) - $calculatedHours);
                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                    break;
                } else {
                    $task->paidLogs  = date('H:i:s', strtotime($task->paidLogs) + $unpaidSeconds);
                    $calculatedHours -= $unpaidSeconds;
                    $task->unPaidLogs = '00:00:00';
                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                }

                // dd($this->secondsToTime($totalHours));
            } else {
                $request->session()->flash('invoice', 'Your invoice amount exceed the due amount of the project. Please provide the amount equal to due amount or less than due amount.');
                return redirect()->route('view.project', ['id' => $projectID]);
            }
        }
        $invoiceId = $this->fixedAmountInvoiceEntry($project->project_name, $totalHours, $amount, $invoiceTitle);
        if ($invoiceId) {
            $request->session()->flash('invoice', 'Invoice Created of Amount $' . $amount);
            return redirect()->route('view.project', ['id' => $projectID]);
        }
    }

    public function fixedHourInvoice(Request $request)
    {
        $request->validate([
            'hours' => 'required|numeric',
            'projectId' => 'required|numeric',
        ]);

        $hours =  $request->hours;
        $projectID = $request->projectId;
        $project   = Project::find($projectID);
        $projectHourRate = $project->per_hour_rate;
        $invoiceTitle = $request->invoiceTitle;
        $tasks     = $project->task;
        $obj       = new ProjectController();
        $taskHours  = $project->totalHours($tasks);
        $projectHours = $obj->totalTimeSpend($taskHours);
        $projectHours = strtotime($projectHours) - strtotime('TODAY');
        $calculatedHours = $hours * 3600;

        foreach ($tasks as $key => $task) {
            $unpaidSeconds = strtotime($task->unPaidLogs) - strtotime('TODAY');

            if ($projectHours >=  $calculatedHours) {

                if ($unpaidSeconds >= $calculatedHours) {
                    $task->paidLogs = date('H:i:s', strtotime($task->paidLogs) + $calculatedHours);
                    $task->unPaidLogs = date('H:i:s', strtotime($task->unPaidLogs) - $calculatedHours);
                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                    break;
                } else {
                    $task->paidLogs  = date('H:i:s', strtotime($task->paidLogs) + $unpaidSeconds);
                    $calculatedHours -= $unpaidSeconds;
                    $task->unPaidLogs = '00:00:00';
                    if ($task->paidLogs == $task->totalHours) {
                        $task->payment_status = 'paid';
                    }
                    if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                        $task->payment_status = 'partialPaid';
                    }
                    if ($task->unPaidLogs == $task->totalHours) {
                        $task->payment_status = 'unpaid';
                    }
                    $task->save();
                }
            } else {
                $request->session()->flash('invoice', 'Your invoice hours exceed the due hours of the project. Please provide the hours equal to due hours or less than due hours.');
                return redirect()->route('view.project', ['id' => $projectID]);
            }
        }

        $invoiceId = $this->fixedHourInvoiceEntry($project->project_name, $hours, $projectHourRate, $invoiceTitle);
        if ($invoiceId) {
            $request->session()->flash('invoice', 'Invoice Created of total Hours ' . str_replace('.', ':', $request->hours));
            return redirect()->route('view.project', ['id' => $projectID]);
        }
    }

    public function fixedHourInvoiceEntry($projectName, $invoiceHours, $projectHourRate, $invoiceTitle)
    {
        $invoiceRate = $invoiceHours * $projectHourRate;

        $invoiceHours = $this->secondsToTime($invoiceHours * 3600);

        $invoice = new Invoice();
        $invoice->project_name  = $projectName;
        $invoice->date_created  = Carbon::now();
        $invoice->total_hours   = str_replace('.', ':', $invoiceHours);
        $invoice->invoice_rate  = $invoiceRate;
        $invoice->invoice_title = $invoiceTitle;
        $invoice->start_date    = Null;
        $invoice->end_date      = Null;
        if ($invoice->save()) {
            return $invoice->id;
        }
    }

    public function unPaidHours($projectTasks)
    {
        // loop through each TASK -> unPaidHours
        // return the sum of unPaidHours
        $unPaidHours = [];
        foreach ($projectTasks as $task) {
            $unpaid = $task->unPaidLogs;
            array_push($unPaidHours, $unpaid);
        }
        return $unPaidHours;
        // return $this->totalTimeSpend($unPaidHours);
    }

    public function updatePaymentStatus($tasks)
    {
        foreach ($tasks as $task) {
            // update payment_status to PAID
            if ($task->paidLogs == $task->totalHours) {
                Task::where('id', $task->id)->update(['payment_status' => 'paid']);
            }
            // update payment_status to Partial PAID
            if (($task->paidLogs !== $task->totalHours) && ($task->unPaidLogs !== '00:00:00')) {
                Task::where('id', $task->id)->update(['payment_status' => 'partialPaid']);
            }
            // update payment_status to UNPAID
            if ($task->unPaidLogs == $task->totalHours) {
                Task::where('id', $task->id)->update(['payment_status' => 'unpaid']);
            }
        }
    }

    public function updatePaidHours($taskArr)
    {
        foreach ($taskArr as $value) {
            $data = Task::find($value->id, ['totalHours', 'unPaidLogs']);
            $unPaidLogs = Carbon::createFromFormat('H:i:s', $data['unPaidLogs']);
            $totalHours = Carbon::createFromFormat('H:i:s', $data['totalHours']);
            $updatedPaidLogs = $unPaidLogs->diff($totalHours)->format('%H:%i');
            $updatedPaidLogs = Carbon::parse($updatedPaidLogs)->format('H:i:s');
            Task::where('id', $value->id)->update(['paidLogs' =>  $updatedPaidLogs]);
        }
    }

    public function getLogTimeDifference($logs)
    {
        $logsHours = [];
        $obj = new ProjectController();
        foreach ($logs as $value) {
            $logDifference = $this->projectController->timeDifference($value->start_time, $value->end_time);
            // match the entered time with $d 
            array_push($logsHours, $logDifference);
        }
        return $obj->totalTimeSpend($logsHours);
    }

    public function fixedAmountInvoiceEntry($projectName, $projectHours, $invoiceRate, $invoiceTitle)
    {
        $hours = floor($projectHours / 3600);
        $minutes = floor(($projectHours % 3600) / 60);
        $seconds = $projectHours % 60;

        // Format the result as HH:MM:SS
        $projectHours = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        // dd($projectHours);
        $invoice = new Invoice();
        $invoice->project_name = $projectName;
        $invoice->date_created = Carbon::now();
        $invoice->total_hours  = str_replace('.', ':', $projectHours);
        $invoice->invoice_rate = $invoiceRate;
        $invoice->invoice_title = $invoiceTitle;
        $invoice->start_date   = Null;
        $invoice->end_date     = Null;

        if ($invoice->save()) {
            return $invoice->id;
        }
    }

    public function calculateTotalTime($taskArr)
    {
        // get start time and end time of each log
        // add difference in an array
        // calculate total time
        // dd($taskArr);
        $time = [];
        $obj = new ProjectController();
        foreach ($taskArr as $task) {
            $unPaidLogs  = $task->unPaidLogs;
            array_push($time, $unPaidLogs);
        }
        $totalTime = $obj->totalTimeSpend($time);

        return $totalTime;
    }

    public function addInvoiceReference($tasks, $invoiceID)
    {
        // add invoiceID foreign key in front of each task
        // array_push($logID, $log->id);
        foreach ($tasks as $task) {
            Task::where('id', $task->id)->update(['invoice_id' => $invoiceID]);
        }
        return true;
    }

    public function newInvoiceEntry($data)
    {
        // add new invoice record in invoice table

        // dd($data['invoiceRate']);
        $totalHours = $this->secondsToTime($data['totalHours']);

        $invoice = new Invoice();
        $invoice->invoice_title  = $data['invoiceTitle'];
        $invoice->project_name = $data['projectName'];
        $invoice->date_created = Carbon::now();
        $invoice->start_date   = $data['startDate'];
        $invoice->end_date     = $data['lastDate'];
        $invoice->invoice_rate = $data['invoiceRate'];
        $invoice->total_hours  = str_replace('.', ':', $totalHours);

        if ($invoice->save()) {
            return $invoice->id;
        }
    }

    public function viewTaskInvoice(Request $request, $taskID)
    {
        // $invoice = [];
        $logs = TaskLog::whereNotNull('invoice_id')->where('task_id', $taskID)->where('invoice_status', '!=', 0)->get();

        // $taskLogs  = Task::find($taskID)->tasklog;
        $projectID = Task::find($taskID)->project->id;

        if (count($logs) == 0) {
            $request->session()->flash('success', 'No Invoice created against task ID ' . $taskID);
            return redirect()->route('view.project', ['id' => $projectID]);
        }
        $invoiceID = array_unique($this->fetchInvoiceId($logs));

        $invoices = $this->getInvoiceInfo($invoiceID);

        return view('admin.invoice.invoice', ['invoices' => $invoices, 'projectID' => $projectID, 'logs' => $logs]);
    }

    public function fetchInvoiceId($arr)
    {
        $invoice = [];
        foreach ($arr as $value) {
            if (!$value->invoice_id == NULL) {
                array_push($invoice, $value->invoice_id);
            }
        }
        return $invoice;
    }

    public function getInvoiceInfo($invoice)
    {
        $data = [];

        foreach ($invoice as $invoice) {
            $info = Invoice::find($invoice);
            array_push($data, $info);
        }
        return $data;
        // return $data;
    }

    function timeSubtractionFirstTime($actual_time, $time_to_reduce)
    {
        $actual_time_array = explode(":", $actual_time);
        $time_to_reduce = explode(":", $time_to_reduce);
        $final_result = [];
        if ($actual_time_array[1] < $time_to_reduce[1]) {
            $actual_time_array[0] = $actual_time_array[0] - 1;
            $final_result[] = $actual_time_array[1] + 60 - $time_to_reduce[1];
        } else {
            $final_result[] = $actual_time_array[1] - $time_to_reduce[1];
        }
        $final_result[] = $actual_time_array[0] - $time_to_reduce[0];

        return implode(":", array_reverse($final_result));
    }

    public function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%02d:%02d', $hours, $minutes,);
    }

    public function timeToSeconds($time)
    {
        $parts = explode(':', $time);

        // Handle different cases based on the number of parts
        $hours = isset($parts[0]) ? $parts[0] : 0;
        $minutes = isset($parts[1]) ? $parts[1] : 0;
        $seconds = isset($parts[2]) ? $parts[2] : 0;

        // Convert the time to total seconds
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function generateInvoice($timeToReduceLeft, $val)
    {
        // $timeToReduceLeft = "13:45";
        // $val = ["12:10", "4:16", "2:05"];
        // $arr = [];
        foreach ($val as &$value) {
            $diff = $this->timeSubtractionFirstTime($value, $timeToReduceLeft);
            if (strpos($diff, chr(45)) !== false) { //if $value < $timeToReduceLeft
                $timeToReduceLeft = $this->timeSubtractionFirstTime($timeToReduceLeft, $value);
                $value = "00:00";
            } else { //if $value >= $timeToReduceLeft
                $value = $this->timeSubtractionFirstTime($value, $timeToReduceLeft);
                $timeToReduceLeft = "00:00";
            }
            if ($timeToReduceLeft == "00:00") {
                break;
            }
        }

        return $val;
        // return array_push($arr, explode(",", $val));
        // return implode(",", $val);
    }

    // delete invoice
    public function deleteInvoice(Request $request, $id)
    {
        $deleteInvoice = Invoice::where('id', $id)->delete();
        if ($deleteInvoice) {
            $request->session()->flash('success', 'Invoice Deleted Successfully');
            return redirect(url()->previous());
        }
    }
}
