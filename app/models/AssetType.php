<?php

class AssetType extends Eloquent {
	
	protected $table = 'asset_types';
	public $timestamps = false;
	protected $softDelete = true;
	protected $guarded = array('id');

	public function assets() {
		return $this->hasMany('Asset');
	}

}