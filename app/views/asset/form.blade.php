<form id="asset" method="post" action="{{ isset($asset) ? URL::to('asset/'.$asset->id) : URL::to('asset') }}" class="form-horizontal">
	
	<div class="control-group">
		<label class="control-label" for="role">Role</label>
		<div class="controls">
			<select class="input-xlarge" name="role" class="select2" data-placeholder="assign a role">
				<option></option>
				@foreach($roles as $role)
				<option value="{{ $role->id }}"{{ (isset($asset) && ($asset->role_id == $role->id)) ? 'selected' : '' }}>{{ $role->name.(($role->user=='') ? '' : ' - '.$role->user) }}</option>
				@endforeach
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="type">Type</label>
		<div class="controls">
			<select class="input-xlarge" name="type" class="select2" data-placeholder="choose a type">
				<option></option>
				@foreach($types as $type)
				<option value="{{ $type->id }}"{{ (isset($asset) && ($asset->asset_type_id == $type->id)) ? 'selected' : '' }}>{{ $type->name }}</option>
				@endforeach
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="ownership">Ownership</label>
		<div class="controls">
			<select class="input-xlarge" name="ownership" data-placeholder="choose an ownership type">
				@foreach($ownership_types as $key => $name)
				<option value="{{ $key }}"{{ (isset($asset) && ($asset->ownership_id == $key)) ? 'selected' : '' }}>{{ $name }}</option>
				@endforeach
			</select>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="maker">Maker</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="maker" placeholder="manufacturer / developer" value="{{ isset($asset) ? $asset->maker : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="product">Product</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="product" placeholder="product name and version" value="{{ isset($asset) ? $asset->product : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="model">Model</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="model" placeholder="model number" value="{{ isset($asset) ? $asset->model : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="serial">Serial</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="serial" placeholder="serial number" value="{{ isset($asset) ? $asset->serial : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="details">Details</label>
		<div class="controls">
			<textarea rows="3" class="input-xlarge" type="text" name="details" placeholder="extra details">{{ isset($asset) ? $asset->license : '' }}</textarea>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="purchase_year">Fiscal Year</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="purchase_year" placeholder="fiscal year purchased" value="{{ isset($asset) ? $asset->purchase_year : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="lifespan">Lifespan (years)</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="lifespan" placeholder="lifespan in years" value="{{ isset($asset) ? $asset->lifespan : '' }}" />
		</div>

	</div>

	<div class="control-group">
		<label class="control-label" for="purchase_cost">Cost / (Per Year)</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="purchase_cost" placeholder="purchase cost / cost per year" value="{{ isset($asset) ? $asset->purchase_cost : '' }}" />
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="replace_cost">Replace Cost</label>
		<div class="controls">
			<input type="text" class="input-xlarge" name="replace_cost" placeholder="expected replacement cost" value="{{ isset($asset) ? $asset->replace_cost : '' }}" />
		</div>
	</div>

	@unless(isset($asset))
	<div class="form-actions">
	  <button type="submit" class="btn btn-success" data-loading-text="saving...">Create Asset</button>
	  <button type="button" class="btn">Cancel</button>
	</div>
	@endunless

</form>
