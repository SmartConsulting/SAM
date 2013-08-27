<form method="post" action="{{ isset($role) ? URL::to('role/'.$role->id) : URL::to('role') }}" class="form-horizontal">
	
	<div class="control-group">
		<label class="control-label" for="name">Name</label>
		<div class="controls">
			<input type="text" name="name" placeholder="role name" value="{{ isset($role) ? $role->name : '' }}" >
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="user">user</label>
		<div class="controls">
			<input type="text" name="user" autocomplete="off" data-provide="typeahead" data-source='[{{ $role_users }}]' placeholder="primary user" value="{{ isset($role) ? $role->user : '' }}">
		</div>
	</div>

	<div class="control-group">
		<label class="control-label" for="location">Location</label>
		<div class="controls">
			<input type="text" name="location" autocomplete="off" data-provide="typeahead" data-source='[{{ $locations }}]' placeholder="primary location" value="{{ isset($role) ? $role->location : '' }}">
		</div>
	</div>

	@unless(isset($role))
	<div class="form-actions">
	  <button type="submit" class="btn btn-success">Create role</button>
	  <button type="button" class="btn">Cancel</button>
	</div>
	@endunless

</form>

@if(isset($role))
<div id="assets" class="carousel slide" data-interval="false">
	<ol class="carousel-indicators">
		@for($i = 0; $i < count($role->assets); $i++)
    <li data-target="#assets" data-slide-to="{{ $i }}"{{ $role->assets[$i]->active ? ' class="active"' : '' }}></li>
    @endfor
  </ol>
  <div class="carousel-inner">
  	@foreach($role->assets as $asset)
    <div class="item{{ $asset->active ? ' active' : '' }}">
    	<div class="well" style="padding-left:70px; padding-right:70px; margin-bottom: 0px;">
    		<h4 style="text-align:center;"><a href="{{ URL::to('asset/'.$asset->id) }}">{{ $asset->maker }} {{ $asset->product }}</a> {{ $asset->active ? ' <span class="label label-warning" style="padding: 4px 8px;">active</span>' : '' }}</h4>
    		<div class="row-fluid">
    			<div class="span6">
    				<h5>Aquired: <span class="label">{{ $asset->purchase_year }}</span></h5>
    				Purchase Cost:<br>
    				${{ $asset->purchase_cost }}
    			</div>
    			<div class="span6" style="text-align:right;">
    				<h5>{{ (Carbon\Carbon::now()->year < ($asset->purchase_year+$asset->lifespan)) ? 'Retiring' : 'Retired' }}: <span class="label">{{ $asset->purchase_year+$asset->lifespan }}</span></h5>
    				Replacement Cost:<br>
    				${{ $asset->purchase_cost }}
    			</div>
    			
    		</div>
    	</div>
    </div>
    @endforeach
  </div>

  <a class="carousel-control left" href="#assets" data-slide="prev">&lsaquo;</a>
  <a class="carousel-control right" href="#assets" data-slide="next">&rsaquo;</a>
</div>
@endif