<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClicksignDocumento extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
		'id',
		'documento_clicksign_id',
		'caminho_arquivo',
        'status',
        'data_finalizacao',
		'json_retorno'
	];

	function clicksignSignatarioDocumento(){
        return $this->hasMany('App\ClicksignSignatarioDocumento', 'clicksign_documentos_id', 'id');
    }
}
