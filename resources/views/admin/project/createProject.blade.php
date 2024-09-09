@extends('customLayout.layout')

@section('title', 'Create Project')

<!-- navbar -->

@section('navbar')
<nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
    <a href="{{ route('adminDashboard') }}" class="nav-link text-light"><i class="bi bi-arrow-left-circle"></i></a>
    <a class="navbar-brand" href="{{ route('admin/create/project') }}">Admin | <small>Create Project</small> </a>
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

</nav>
@endsection



<!-- Page => main content -->
@section('main-content')

<h3 class="text-center my-2 mb-4 text-info" style="font-style: oblique;">Create New Project</h3>
<div class="row mt-2">
    <div class="col-md-7 offset-md-2">
        @if(session('success'))
        <span class="alert alert-info">{{ session('success') }}</span>
        @endif
        <form action="{{ route('admin/project/created') }}" method="post">
            @csrf
            <div class="row">
                <div class="col">
                    <label for="ptitle" class="form-label"><small>Project Name</small></label>
                    <input type="text" name="project_name" class="form-control form-control-sm" value="{{ old('project_name') }}" placeholder="Name   ( minimum 5 characters )">

                </div>
            </div>

            <div class="row">
                <div class="col mt-2">
                    <label for="ptitle" class="form-label"><small>Project Description</small></label>
                    <textarea class="form-control form-control-sm" name="project_desc" value="{{ old('project_desc') }}" placeholder="description   ( minimum 10 characters )"></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col mt-2">
                    <label for="prate" class="form-label"><small>Project Rate</small></label>
                    <input type="number" name="project_rate" class="form-control form-control-sm" value="{{ old('project_rate') }}" id="prate" placeholder="Enter Project Rate">
                </div>
            </div>

            <div class="row">
                <div class="col mt-2">
                    <label for="prate" class="form-label"><small>Per Hour Rate</small></label>
                    <input type="number" name="per_hour_rate" class="form-control form-control-sm" value="{{ old('per_hour_rate') }}" id="prate" placeholder="Enter Hour Rate">
                </div>
            </div>

            <div class="row">
                <div class="col mt-2">
                    <label for="client" class="form-label ml-1" style="font-size: 14px;">Client</label>
                    <select name="user_id" id="client" class="form-control form-control-sm">
                    <option value="">---Select Client---</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <br>
            <!-- showing errors -->
            <div class="row">
                @if($errors->any())
                <ul>
                    @foreach($errors->get('projectName') as $title)
                    <li class="text-danger ml-3"><small>{{ $title }}</small></li>
                    @endforeach

                    @foreach($errors->get('projectDesc') as $desc)
                    <li class="text-danger ml-3"><small>{{ $desc }}</small></li>
                    @endforeach

                    @foreach($errors->get('project_rate') as $rate)
                    <li class="text-danger ml-3"><small>{{ $rate }}</small></li>
                    @endforeach

                    @foreach($errors->get('client') as $client)
                    <li class="text-danger ml-3"><small>{{ $client }}</small></li>
                    @endforeach
                </ul>
                @endif
            </div>

            <button class="btn btn-outline-info btn-block mb-5">Create Project</button>
        </form>
    </div>
</div>

@endsection