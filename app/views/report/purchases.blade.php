@extends('layout.main')

@section('content')
@parent

<table class="table table-striped">
	<thead>
		<tr>
			<th>Type</th>
			<th>Asset</th>
			<th>Role</th>
			<th>User</th>
			<th class="number">Cost</th>
		</tr>
	</thead>
	<tbody>
	@foreach($types as $type)
		<tr>
			<th style="padding-top:25px;">{{ $type->name }}</th>
			<th></th>
			<th></th>
			<th></th>
			<th></th>
		</tr>
		@foreach($type->assets as $asset)
		<tr>
			<td></td>
			<td>{{ $asset->maker.' '.$asset->product }}</td>
			<td>{{ is_null($asset->role) ? '<em>unassigned</em>' : $asset->role->name }}</td>
			<td>{{ is_null($asset->role) ? '<em>unassigned</em>' : $asset->role->user }}</td>
			<td class="number">{{ number_format($asset->purchase_cost, 2) }}</td>
		</tr>
		@endforeach
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th>{{ $type->name }} Total:</th>
			<th class="number">{{ number_format($type->total, 2) }}</th>
		</tr>
	@endforeach
		<tr>
			<th></th>
			<th></th>
			<th></th>
			<th>{{ $year }} Total:</th>
			<th class="number">{{ number_format($total, 2) }}</th>
		</tr>
	</tbody>
</table>
@stop

@section('styles')
<style>
	thead {
		background-color: #fff;
		font-size: 20px;
	}
	table tr .number {
		text-align: right;
	}
	thead.affix {
		position: fixed;
		top: 50px;
		box-shadow: 0 7px 7px -5px #ddd;
	}
	.print-title {
		display:none;
	}
	@media print {
    @page land {size: landscape;}
    body {
      padding-top:0px;
    }
    .navbar, .alert, .page-header, footer {
      display: none;
    }
    .print-title {
    	display: block;
    	text-align: center;
    }

    table tbody tr td {
    	padding: 3px !important;
    	font-size: 13px;
    	font-weight: 200;
    }
    table tbody tr td:nth-child(odd) {
    	background-color: #f9f9f9;
    }
    
  }
</style>
@stop

@section('scripts')
<script>
	$(document).ready(function() {
		$('thead th').each(function() {
      $(this).width($(this).width());
  	});

  	$('thead').affix({ offset: 78 });
	});
</script>
@stop