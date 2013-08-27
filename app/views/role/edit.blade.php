@extends('layout.main')

@section('content')
@parent
<div class="row-fluid">
	<div class="span4">

		@include('role.form')
		
		@if(isset($asset))
		
		<div class="form-actions">
	    <button id="asset-save" class="btn btn-primary pull-right" data-loading-text="Saving...">Save Changes</button>
	    <button class="btn pull-right" aria-hidden="true" style="margin-right:10px;">Cancel</button>

	    <button id="asset-delete" class="btn btn-danger" data-loading-text="Deleting...">Delete Role</button>
		</div>
		
		@endif
	</div>
</div>
@stop

@section('scripts')
<script>
	$(document).ready(function() {
		$('#role-save').click(function() {
    	$(this).button('loading');
    	$('#role-modal div.modal-body form').submit();
    });

    $('#role-delete').click(function() {
    	$(this).button('loading');
    	location.href = $('#role-modal div.modal-body form').attr('action')+'/delete';
    });
	});
</script>
@stop