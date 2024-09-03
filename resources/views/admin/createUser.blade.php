@extends('customLayout.layout')

@section('title', 'Create Developer')

<!-- navbar -->

@section('navbar')
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
        <a href="{{ route('adminDashboard') }}" class="nav-link text-light"><i class="bi bi-arrow-left-circle"></i></a>
        <a class="navbar-brand" href="{{ route('admin/create/project') }}">Admin | <small>Create new User</small> </a>
        <a href="{{ route('adminlogout') }}" class="nav-link ml-auto text-light">Logout</a>

    </nav>
@endsection

<!-- main content -->

@section('main-content')
    <div class="row my-2">
        @if (session('success'))
            <small class="mx-auto text-success">{{ session('success') }}</small>
        @endif
    </div>
    <div class="row">
        <h4 class="mx-auto text-secondary"><i>Create New <span class="text-danger">User</i></span></h4>
    </div>
    <div class="row mt-2">
        <div class="col-md-8 offset-md-2">
            <form action="{{ route('admin.create.user') }}" method="post">
                @csrf
                <label for="name" class="form-label">Enter Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control form-control-sm"
                    placeholder="enter developer name">
                <small class="ml-3 text-info">minimum 7 characters</small>
                @if ($errors->any('name'))
                    @foreach ($errors->get('name') as $error)
                        <small class="text-danger ml-3">{{ $error }}</small>
                    @endforeach
                @endif
                <br>
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" class="form-control form-control-sm"
                    placeholder="user@example.com">
                @if ($errors->any('email'))
                    @foreach ($errors->get('email') as $error)
                        <small class="text-danger ml-3">{{ $error }}</small>
                    @endforeach
                @endif
                <br>
                <label for="password" class="form-label">Enter Password</label>
                <input type="password" name="passkey" id="passkey" class="form-control form-control-sm"
                    placeholder="enter password">
                <small class="ml-3 text-info">minimum 8 characters</small>
                @if ($errors->any('passkey'))
                    @foreach ($errors->get('passkey') as $error)
                        <small class="text-danger ml-3">{{ $error }}</small>
                    @endforeach
                @endif
                <br>

                <label for="password" class="form-label">Enter Company</label>
                <input type="text" class="form-control" name="company" value="{{ old('company') }}" id="company" placeholder="Company">
                    @if ($errors->any())
                        @foreach ($errors->get('company') as $company_err)
                            <small class="text-danger ml-3">{{ $company_err }}</small>
                        @endforeach
                    @endif
                    <br>

                    <label for="password" class="form-label">Enter Eddress One</label>
                    <input type="text" class="form-control" name="address_line_one" value="{{ old('address_line_one') }}" id="address-line-one"
                        placeholder="Address Line 1">
                    @if ($errors->any())
                        @foreach ($errors->get('address_line_one') as $address_line_one_err)
                            <small class="text-danger ml-3">{{ $address_line_one_err }}</small>
                        @endforeach
                    @endif
                    <br>

                    <label for="password" class="form-label">Enter Eddress Two</label>
                    <input type="text" class="form-control" name="address_line_two" value="{{ old('address_line_two') }}" id="address-line-two"
                        placeholder="Address Line 2">
                    @if ($errors->any())
                        @foreach ($errors->get('address_line_two') as $address_line_two_err)
                            <small class="text-danger ml-3">{{ $address_line_two_err }}</small>
                        @endforeach
                    @endif
                    <br>
                    <label for="password" class="form-label">Enter Country</label>
                    <input type="text" class="form-control" name="country" value="{{ old('country') }}" id="country" placeholder="Country">
                    @if ($errors->any())
                        @foreach ($errors->get('country') as $country_err)
                            <small class="text-danger ml-3">{{ $country_err }}</small>
                        @endforeach
                    @endif
                    <br>

                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="developer" value="developer" checked>
                    <label class="form-check-label" for="developer">
                        Developer
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="role" id="client" value="client">
                    <label class="form-check-label" for="client">
                        Client
                    </label>
                </div>
                <br>
                @if ($errors->any('role'))
                    @foreach ($errors->get('role') as $error)
                        <small class="text-danger ml-3">{{ $error }}</small>
                    @endforeach
                @endif
                <button type="submit" name="createDeveloper" id="createDeveloper"
                    class="btn btn-outline-info btn-block">Create New User</button>
            </form>
        </div>
    </div>
@endsection
