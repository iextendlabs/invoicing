@extends('customLayout.layout')

@section('title', 'Invoice')

<style>
body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    background-color: #fff !important;
    font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
    font-size: 16px;
	line-height: 24px;
}
* {
    box-sizing: border-box;
    -moz-box-sizing: border-box;
}
.page {
    width: 210mm;
    min-height: 297mm;
    padding: 20mm;
    margin: 10mm auto;
    border: 1px #D3D3D3 solid;
    border-radius: 5px;
    background: white;
    box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
}


@page {
    size: A4;
    margin: 0;
}
@media print {
    html, body {
        width: 210mm;
        height: 297mm;        
    }
    .page {
        margin: 0;
        border: initial;
        border-radius: initial;
        width: initial;
        min-height: initial;
        box-shadow: initial;
        background: initial;
    }
    .container-fluid {
        padding: 0;
    }
}

.page table {
	width: 100%;
	line-height: inherit;
	text-align: left;
}

.page table td {
	padding: 5px;
	vertical-align: top;
}

.page table tr td:nth-child(2) {
	text-align: right;
}

.page table tr.top table td {
	padding-bottom: 20px;
}

.page table tr.top table td.title {
    text-align: center;
	font-size: 45px;
	line-height: 45px;
	color: #333;
}

.page table tr.information table td {
	padding-bottom: 40px;
}

.page table tr.heading td {
	font-weight: bold;
}

.page table tr.details td {
	padding-top: 30px;
}

.page table tr.details1 td {
	padding-bottom: 20px;
}

.page table tr.item td {
	border-bottom: 1px solid #000;
}

.page table tr.item.last td {
	border-bottom: none;
}

.page table tr.total td:nth-child(2) {
	border-top: 2px solid #000;
	font-weight: bold;
}

/*@media only screen and (max-width: 600px) {*/
/*	.page table tr.top table td {*/
/*		width: 100%;*/
/*		display: block;*/
/*		text-align: center;*/
/*	}*/

/*	.page table tr.information table td {*/
/*		width: 100%;*/
/*		display: block;*/
/*		text-align: center;*/
/*	}*/
/*}*/

/** RTL **/
.page.rtl {
	direction: rtl;
	font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
}

.page.rtl table {
	text-align: right;
}

.page.rtl table tr td:nth-child(2) {
	text-align: left;
}
</style>
<!-- navbar -->

@section('navbar')
<nav class="navbar navbar-expand-lg navbar-dark bg-dark w-100">
    {{-- <a href="{{ route('view.project', ['id' => $projectID]) }}" class="nav-link text-light"><i class="bi bi-arrow-left-circle"></i></a> --}}
    <a class="navbar-brand" href="#">Admin | <small>Invoice Details</small></a>
    <a href="{{ route('adminlogout') }}" class="nav-link ml-auto text-light">Logout</a>
    <button class="btn btn-danger" onclick="window.print()"><i class="bi bi-printer"></i> Print Invoice</button>
</nav>
@endsection

@section('main-content')
<div class="page">
    <table cellpadding="0" cellspacing="0">
    	<tr class="top">
    		<td colspan="2">
    			<table>
    				<tr>
    					<td class="title">
    						<h1>Saqib Ashraf</h1><hr />
    					</td>
    				</tr>
    			</table>
    		</td>
    	</tr>
    
    	<tr class="information">
    		<td colspan="2">
    			<table>
    				<tr>
    					<td>
    						To: <strong>{{ $project->user->name }}</strong><br />
    						{{ $project->user->address_line_one }}<br />
    						{{ $project->user->address_line_two }}<br />
    						<strong>{{ $project->user->country }}</strong>
    					</td>
    
    					<td>
    						<strong>Invoice</strong><br />
    						<strong>ID: </strong>@if($invoice->invoice_title ) {{ $invoice->invoice_title }} @else Null @endif<br />
    						<strong>Issue Date: </strong>{{ $invoice->created_at->format('d-M-Y') }}<br />
    						<strong>Customer ID: {{ $project->user->name }}</strong>
    					</td>
    				</tr>
    			</table>
    		</td>
    	</tr>
    
    	<tr class="heading">
    		<td>Quote/Project Description: {{ $invoice->project_name }}</td>
    	</tr>
    	
    	<tr class="detail">
    	    <td>
    	        <table>
    	            <tr class="details">
                		<td><strong>Description</strong></td>
                
                		<td><strong>Line Total</strong></td>
                	</tr>
                
                	<tr class="details1">
                		<td>Payment {{$invoice->total_hours}} hrs (${{ $project->per_hour_rate }}/hr)</td>
                
                		<td>${{ $invoice->invoice_rate }}</td>
                	</tr>
                    
                    <tr>
                		<td></td>
                		<td></td>
                	</tr>
                	<tr>
                		<td></td>
                		<td></td>
                	</tr>
                	<tr>
                		<td></td>
                		<td></td>
                	</tr>
                	<tr class="item">
                		<td>SubTotal</td>
                
                		<td>${{ $invoice->invoice_rate }}</td>
                	</tr>
                
                	<tr class="item">
                		<td>Discount</td>
                
                		<td>-</td>
                	</tr>
                	
                	<tr class="item">
                		<td>Sales Tax Rate (%)</td>
                
                		<td>0</td>
                	</tr>
                	
                	<tr class="item last">
                		<td>Sales Tax</td>
                
                		<td>-</td>
                	</tr>
                
                	<tr class="total">
                	    <td></td>
                		<td>Total Amount: ${{ $invoice->invoice_rate }}</td>
                	</tr>
    	        </table>
    	    </td>
    	</tr>
    	
    	<tr class="footer">
    	    <td colspan="2">
    			<table>
    				<tr class="text-center">
    					<td><strong>Thank you for your business</strong><p>Should you have any enquiries concerning this Invoice, please contact <strong>Saqib Ashraf</strong> on +923201430963</p><hr /></td>
    				</tr>
    				<tr class="text-center">
    					<td><p>5th Floor, Office #245, Aashiana Shopping Center, Main Blvd Gulberg, Block D1 Block D 1 Gulberg III, Lahore, Punjab 54000</p><p>Tel: +923201430963 E-mail: linktosaqib@gmail.com</p></td>
    				</tr>
    			</table>
    		</td>
        </tr>
    </table>
</div>
@endsection