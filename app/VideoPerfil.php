<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VideoPerfil extends Model
{
    protected $connection = 'pgsql';
	protected $fillable = [
		'id',
		'videos_id',
		'perfil_id',
		'created_by',
		'updated_by',
	];
	
	public function politica(){
		return $this->hasOne('App\Video', 'id', 'videos_id');
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
