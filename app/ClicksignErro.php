<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClicksignErro extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
		'id',
		'clicksign_documento_signatarios_id',
		'clicksign_documentos_id',
        'clicksign_signatarios_id',
        'mensagem_erro',
		'json_retorno_erro',
		'json_enviado'
	];

	function clicksignDocumento(){
        return $this->hasOne('App\ClicksignDocumento', 'id', 'clicksign_documentos_id');
    }

	function clicksignSignatario(){
        return $this->hasOne('App\ClicksignSignatario', 'id', 'clicksign_signatarios_id');
    }

	function clicksignSignatarioDocumento(){
        return $this->hasOne('App\ClicksignSignatarioDocumento', 'id', 'clicksign_documento_signatarios_id');
    }
}
