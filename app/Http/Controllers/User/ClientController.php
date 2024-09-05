<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClientController extends Controller
{
    protected $paginate_limit;

    public function __construct(Request $request)
    {
        $this->paginate_limit = $request->input('paginate_limit', config('app.paginate_limit'));
    }


    public function clientDashboard(Request $request)
    {
        if (!session('userEmail')) {
            return redirect()->route('userLogin');
        }

        $user = User::where('email', session('userEmail'))->get(['id', 'user_role']);
        $projects = [];
        $project_name = null;
        $project_id = [];
        foreach ($user as $key => $value) {
            $projects = Project::where("user_id", $value->id)->get();
        }

        foreach ($projects as $project) {
            $project_name = $project->project_name;
            $project_id[$project->id] = $project->id;
            $project_per_hour = $project->per_hour_rate;
        }

        $paid = Invoice::where('project_name', $project_name)->sum('invoice_rate');
        $paid = number_format((float)$paid, 2, '.', '');
        $projects_total = [];
        foreach ($project_id as $key => $value) {
            $projectInfo = Project::find($value);
            $tasks = $projectInfo->task;
            $unPaidHours = $this->unPaidHours($tasks);
            $paidHours = $this->paidHours($tasks);

            $paid = 0;
            foreach ($tasks as $task) {
                $paid += $this->timeToSeconds($task->totalHours);
            }
            $hours =  $this->secondsToTime($paid);
            
            $logsPayment    =  $this->timeToDecimalHours( $paidHours) * $projectInfo->per_hour_rate;
            // $logsPayment    =  str_replace(':', '.', $paidHours) * $projectInfo->per_hour_rate;

            $dueCharges     = $this->timeToDecimalHours( $unPaidHours) * $projectInfo->per_hour_rate;

            $totalCost      = $this->timeToDecimalHours( $hours) * $projectInfo->per_hour_rate;

            $projects_total[$key]['logsPayment'] = $logsPayment;
            $projects_total[$key]['dueCharges'] = $dueCharges;
            $projects_total[$key]['totalCost'] = $totalCost;
        }

        return view('users.client.clientDashboard', [
            'projects' => $projects,
            'role' => $user[0]->user_role,
            'projects_total' => $projects_total,
        ]);
    }

    public function CalcHours($tasks)
    {
        // return array containing each task --> totalTimeSpend
        $arr = [];
        foreach ($tasks as $tasks) {
            foreach ($tasks->tasklog as $loginfo) {
                $start      = new Carbon($loginfo->start_time);
                $end        = new Carbon($loginfo->end_time);
                $difference = $this->timeDifference($start, $end);
                array_push($arr, $difference);
                // $index++;
            }
        }

        return $this->totalTimeSpend($arr);
    }

    public function timeDifference($start, $end)
    {
        // calculate difference b/w two times
        $start  = new Carbon($start);
        $end    = new Carbon($end);
        return $start->diff($end)->format('%H:%I');
    }

    public function totalTimeSpend($time)
    {
        $total = 0;

        // Loop the data items
        foreach ($time as $element) {
            // Explode by separator :
            $temp = explode(":", $element);

            // Convert the hours into seconds
            // and add to total
            $total += (int) $temp[0] * 3600;

            // Convert the minutes to seconds
            // and add to total
            $total += (int) $temp[1] * 60;

            // Add the seconds to total
            // $total += (int) $temp[2];
        }

        // Format the seconds back into HH:MM:SS
        $formatted = sprintf(
            '%02d:%02d',
            ($total / 3600),
            ($total / 60 % 60),
            $total % 60
        );

        return $formatted;
    }

    public function displayInvoices(Request $request)
    {
        $project  = Project::find($request->get('id'));
        $invoices = Invoice::where('project_name', $project->project_name)->get();

        $project_name = null;
        foreach ($invoices as $invoice) {
            $project_name = $invoice['project_name'];
        }
        return view('users.client.invoice.invoice', ['invoices' => $invoices, 'project_name' => $project_name, 'projectID' => $request->get('id'), 'project' => $project]);
    }

    public function invoiceDetail(Request $request)
    {
        $project  = Project::find($request->get('projectId'));
        $invoice = Invoice::find($request->get('id'));
        return view('users.client.invoice.clientInvoice', ['invoice' => $invoice, 'project' => $project]);
    }

    public function exploreProject($id)
    {
        // get project PAID/UNPAID /TOTAL HOURS and pass to View
        $tasks = Project::find($id)->task;

        $obj        = new ProjectController();
        $totalHours = $obj->totalHours($tasks);
        $totalHours = $obj->totalTimeSpend($totalHours);

        $paidHours    = $obj->paidHours($tasks);
        $unPaidHours  = $obj->unPaidHours($tasks);

        return view('users.client.viewProjectTask', ['tasks' => $tasks, 'paidHours' => $paidHours, 'unPaidHours' => $unPaidHours, 'totalHours' => $totalHours]);
    }


    // check invoice
    public function checkInvoice($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        return view('users.client.invoice', ['invoice' => $invoice]);
    }


    // change password form
    public function ChangePasswordForm()
    {
        return view('users.client.changePassword');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old-pass'     => 'required',
            'new-pass'     => 'required|min:8',
            'confirm-pass' => 'required|min:8',
        ]);

        // match new and confirm new password
        if ($request['new-pass'] === $request['confirm-pass']) {
            $data = User::where('email', session('userEmail'))->get();

            if (Hash::check($request['old-pass'], $data[0]->password)) {
                $update = User::where('email', session('userEmail'))->update([
                    'password' => Hash::make($request['new-pass'])
                ]);

                if ($update) {
                    $request->session()->flash('success', 'Password has been updated!!');
                    return redirect()->route('client.dashboard');
                }
            } else {
                $request->session()->flash('success', 'Incorrect Old Password');
                return redirect()->route('client.change.password');
            }
        } else {
            $request->session()->flash('success', 'New & Confirm new password must be same');
            return redirect()->route('client.change.password');
        }
    }

    public function clientLogout(Request $request)
    {
        // Auth::logout();
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect(route('userLogin'));
    }

    public function paidHours($projectTasks)
    {
        // loop through each TASK -> paidHours
        // return the sum of paidHours
        $paidHours = [];
        foreach ($projectTasks as $task) {
            $paid = $task->paidLogs;
            array_push($paidHours, $paid);
        }
        return $this->totalTimeSpend($paidHours);
    }


    // total unPaid Hours
    public function unPaidHours($projectTasks)
    {
        // loop through each TASK -> unPaidHours
        // return the sum of unPaidHours
        $unPaidHours = [];
        foreach ($projectTasks as $task) {
            $unpaid  = $task->unPaidLogs;
            array_push($unPaidHours, $unpaid);
        }
        return $this->totalTimeSpend($unPaidHours);
    }

    public function timeToSeconds($time)
    {
        list($hours, $minutes, $seconds) = explode(':', $time);
        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function secondsToTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        return sprintf('%02d:%02d', $hours, $minutes,);
    }

    public function timeToDecimalHours($time) {
        $parts = explode(':', $time);
        // Assign hours and minutes, defaulting to 0 if not present
        $hours = isset($parts[0]) ? (int)$parts[0] : 0;
        $minutes = isset($parts[1]) ? (int)$parts[1] : 0;
        $second = isset($parts[2]) ? (int)$parts[2] : 0;
        
        // Convert minutes to decimal hours
        $decimalHours = $hours + ($minutes / 60) + ($minutes / 3600);
        $formattedDecimalHours = number_format($decimalHours, 2);
        
        return $formattedDecimalHours;
    }
}
