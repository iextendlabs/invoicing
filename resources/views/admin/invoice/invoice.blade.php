@extends('customLayout.layout')

@section('title', 'Invoice')

<!-- navbar -->

@section('navbar')
<nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
    <a href="{{ route('view.project', ['id' => $projectID]) }}" class="nav-link text-light"><i class="bi bi-arrow-left-circle"></i></a>
    <a class="navbar-brand" href="{{ route('view.project', ['id' => $projectID]) }}">Admin | <small>Invoices</small></a>
    <a href="{{ route('adminlogout') }}" class="nav-link ml-auto text-light">Logout</a>
</nav>
@endsection

@section('main-content')

{{-- display flash messages --}}
@if (session('succcess'))
    <div class="row">
        <p class="alert alert-success">{{ session('success') }}</p>
    </div>
@endif

<div class="row mt-5">
    <div class="col-md-12">
        <div class="row">
            <div class="col-12 d-flex flex-column">
                <h4 class="text-center">{{ $project_name }} | Invoices</h4>
            </div>
        </div>
    </div>    
</div>

<div class="row mt-4">
    <div class="col-md-12" style="height:350px; overflow-y: auto">
        <table class="table table-hover">
            <thead class="bg-dark text-light">
                <tr>
                    <th>Invoice ID</th>
                    <th>Invoice Type</th>
                    <th>Invoice Amount</th>
                    <th>Date Created</th>
                    <th class="bg-warning text-center">View Invoice</th>
                    <th class="bg-danger text-center">Delete Invoice</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoices as $invoice)
                <tr>
                <td style="font-weight: bold;">{{ $invoice->id }}</td>
                    <td>
                        @if ($invoice->start_date == NULL)
                            <span class="badge badge-primary text-light">Fixed Amount/Hour</span>
                        @endif

                        @if ($invoice->start_date != NULL)
                            <span class="badge badge-warning text-danger">Datewise Invoice</span>
                        @endif
                    </td>
                    <td>{{ $invoice->invoice_rate }}</td>
                    <td>{{ $invoice->created_at->format('d-M-Y') }}</td>
                    <td class="text-center"><a href="{{ route('invoice.detail', ['id' => $invoice->id, 'projectId' => $projectID]) }}" class="text-warning"><i class="bi bi-eye-fill"></i></a></td>
                    <td class="text-center"><a href="{{ route('delete.invoice', ['id' => $invoice->id]) }}" class="text-danger"><i class="bi bi-trash3-fill"></i></a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
