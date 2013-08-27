<?php

use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableInterface;

class User extends Eloquent implements UserInterface, RemindableInterface {

	protected $table = 'users';
	public $timestamps = false;
	protected $softDelete = true;
	protected $guarded = array('id');
	protected $hidden = array('password');

	public function getAuthIdentifier() {
		return $this->getKey();
	}

	public function getAuthPassword() {
		return $this->password;
	}

	public function getReminderEmail() {
		return $this->email;
	}

	public function setPasswordAttribute($value) {
		$this->attributes['password'] = Hash::make($value);
	}

	public static function add($email, $password) {
		return self::create(array('email' => $email, 'password' => Hash::make($password)));
	}
}