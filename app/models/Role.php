<?php

class Role extends Eloquent {
	
	protected $table = 'roles';
	public $timestamps = false;
	protected $softDelete = true;
	protected $guarded = array('id');

	public function assets() {
		return $this->hasMany('Asset');
	}

}