@extends('customLayout.layout')

@section('title', 'Invoice Management System')

@section('navbar')
<a href="{{ url()->previous() }}" class="nav-link text-light"><i class="bi bi-arrow-left-circle"></i></a>
<a class="navbar-brand" href="#">Admin | <small>Edit Task</small> </a>
<div class="btn-group mx-auto d-md-inline d-none" role="group">
    <a class="btn btn-outline-info" href="{{ route('admin/create-task') }}">Create Task</a>
    <a class="btn btn-outline-info" href="{{ route('admin/create/project') }}">Create Project</a>
    {{-- <a class="btn btn-outline-info" href="{{ route('admin/projects') }}">Available Projects</a> --}}
    <a class="btn btn-outline-info" href="{{ route('create.task.log') }}">Create Task Log</a>
    <a class="btn btn-outline-light" href="{{ route('create.user') }}">Create User</a>
    <a class="btn btn-outline-primary" href="{{ route('work.detail') }}">Work Detail</a>
    <a class="btn btn-outline-success" href="{{ route('change.admin.password') }}">Change Password</a>
</div>
<a href="{{ route('adminlogout') }}" class="nav-link ml-auto text-light">Logout</a>
@endsection

@section('main-content')
<h4 class="text-center my-3" style="font-style:oblique;">Edit Task Details</h4>
<div class="row mt-3">
    <div class="col-md-4 offset-md-4 card-body">
        <form action="{{ route('admin/update-task', $detail->id) }}" method="post">
            @csrf
            @method('PUT')
            <label class="form-label text-muted"><small>Task Id</small></label>
            <input type="text" name="task_id" class="form-control form-control-sm" value="{{ $detail->id }}" readonly>
            <br>
            <label class="form-label text-muted"><small>Task Title</small></label>
            <input type="text" name="task_title" placeholder="Task Title" class="form-control form-control-sm" value="{{ $detail->task_title }}">
            @if($errors->any())
            @foreach($errors->get('task_title') as $show)
            <small class="text-danger ml-3">{{ $show }}</small>
            @endforeach
            @endif
            <br>

            <label for="taskDesc" class="form-label ml-1" style="font-size: 14px;">Task Description</label>
            <textarea name="taskDesc" id="taskDesc" cols="30" rows="5" class="form-control form-control-sm" placeholder="Task Description" required>{{ $detail->task_desc }}</textarea>

            <label class="form-label text-muted"><small>Developer's Name</small></label>
            <select name="assign_to" id="assignTo" class="form-control form-control-sm">
                @foreach($user as $user)
                <option value="{{ $user->id }}">{{ $user->name }} &nbsp; (developer)   </option>
                @endforeach
            </select>
            @if($errors->any())
            @foreach($errors->get('assign_to') as $show)
            <small class="text-danger ml-3">{{ $show }}</small>
            @endforeach
            @endif
            <br>
            <label class="form-label text-muted"><small>Date Assigned</small></label>
            <input type="date" name="date_assign" placeholder="Date Assign" class="form-control form-control-sm" value="{{ $detail->date }}">
            @if($errors->any())
            @foreach($errors->get('date_assign') as $show)
            <small class="text-danger ml-3">{{ $show }}</small>
            @endforeach
            @endif
        
            <br>
            <button type="submit" name="updateTask" class="btn btn-block btn-outline-primary btn-sm">Update Task Info .</button>
            <!-- update messages -->
            <div>
                @if(session('success'))
                <p class="alert alert-info">{{ session('success') }}</p>
                @endif
            </div>
        </form>
    </div>
</div>

@endsection