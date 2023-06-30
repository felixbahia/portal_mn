<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoliticaPerfil extends Model{
	protected $connection = 'pgsql';
	protected $fillable = [
		'politicas_id',
		'perfil_id',
		'created_by',
		'updated_by',
	];
	
	public function politica(){
		return $this->hasOne('App\Politica', 'id', 'politicas_id');
	}
	public function perfil(){
		return $this->hasOne('App\Role', 'id', 'perfil_id');
	}
	public function createdby(){
		return $this->hasOne('App\User', 'id', 'created_by');
	}
	public function updatedby(){
		return $this->hasOne('App\User', 'id', 'updated_by');
	}

}
