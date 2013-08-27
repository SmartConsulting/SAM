<form method="post" action="{{ isset($type) ? URL::to('type/'.$type->id) : URL::to('type') }}" class="form-horizontal">

	<div class="control-group">
		<label class="control-label" for="name">Name</label>
		<div class="controls">
			<input type="text" name="name" placeholder="manufacturer / developer" value="{{ isset($type) ? $type->name : '' }}" />
		</div>
	</div>

	@unless(isset($type))
	<div class="form-actions">
	  <button type="submit" class="btn btn-success">Create Type</button>
	  <button type="button" class="btn">Cancel</button>
	</div>
	@endunless

</form>
