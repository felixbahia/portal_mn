<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Politica extends Model{
	use SoftDeletes;

	protected $connection = 'pgsql';
	protected $fillable = [
		'descricao',
		'arquivo',
		'created_by',
		'updated_by',
		'deleted_by',
	];
	
	public function getCaminhoAttribute(){
		return 'public/politicas/';
	}

	public function getCaminhoArquivoAttribute(){
		return $this->caminho . $this->arquivo;
	}

	public function getUrlArquivoAttribute(){
		if(isset($this->arquivo) && Storage::exists($this->caminho . $this->arquivo)){
			return Storage::url($this->caminho . $this->arquivo);
		}else{
			return '';
		}
	}

	public function createdby(){
		return $this->hasOne('App\User', 'id', 'created_by');
	}
	public function updatedby(){
		return $this->hasOne('App\User', 'id', 'updated_by');
	}
	public function deletedby(){
		return $this->hasOne('App\User', 'id', 'deleted_by');
	}
	public function perfils(){
		return $this->hasMany('App\PoliticaPerfil', 'politicas_id', 'id');
	}

}
