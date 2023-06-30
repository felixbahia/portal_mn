<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricoProjeto extends Model
{
    use SoftDeletes;

	protected $fillable = 
	[
		'id','lancamento_projetos_id','natureza','motivo','users_id','created_by','updated_by','deleted_by'
	];

	public function detalhes_natureza(){
        return $this->hasOne('App\LogsNaturezaProjeto', 'chave', 'natureza')->withTrashed();
	}
	
	public function detalhes_usuario(){
        return $this->hasOne('App\User', 'id', 'users_id')->withTrashed();
    }
}
