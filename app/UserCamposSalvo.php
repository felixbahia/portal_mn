<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class UserCamposSalvo extends Model
{
	use SoftDeletes;

	/**
	* The attributes that are mass assignable.
	*
	* @var array
	*/
	protected $fillable = [
		'user_id', 'programa_id', 'campo', 'valor'
	];
	/**
	* The attributes that should be mutated to dates.
	*
	* @var array
	*/
	protected $dates = ['deleted_at'];

	public function user(){
		return $this->hasOne('App\User', 'user_id', 'id');
	}

	public function programa(){
		return $this->hasOne('App\Programa', 'programa_id', 'id');
	} 

}
