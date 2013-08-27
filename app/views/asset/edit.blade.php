@extends('layout.main')

@section('content')
@parent
<div class="row-fluid">
	<div class="span4">
		@include('asset.form')

		@if(isset($asset))
		
		<div class="form-actions">
	    <button id="asset-save" class="btn btn-primary pull-right" data-loading-text="Saving...">Save Changes</button>
	    <button class="btn pull-right" aria-hidden="true" style="margin-right:10px;">Cancel</button>

	    <button id="asset-delete" class="btn btn-danger" data-loading-text="Deleting...">Delete Asset</button>
		</div>
		
		@endif
	</div>
</div>
@stop

@section('scripts')
<script>
	$(document).ready(function() {
		$('.select2').select2();

		$('#asset-save').click(function() {
    	$(this).button('loading');
    	$('form#asset').submit();
    });

    $('#asset-delete').click(function() {
    	$(this).button('loading');
    	location.href = $('form#asset').attr('action')+'/delete';
    });
	});
</script>
@stop