<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClicksignSignatarioDocumento extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
		'id',
		'signatario_documento_clicksign_id',
		'request_signature_id',
		'clicksign_documentos_id',
        'clicksign_signatarios_id',
		'data_assinatura',
		'ip',
		'assinou_como',
		'json_retorno'
	];

	function clicksignDocumento(){
        return $this->hasOne('App\ClicksignDocumento', 'id', 'clicksign_documentos_id');
    }

	function clicksignSignatario(){
        return $this->hasOne('App\ClicksignSignatario', 'id', 'clicksign_signatarios_id');
    }
}
