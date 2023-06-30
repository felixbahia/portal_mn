<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClicksignSignatario extends Model
{
    protected $connection = 'pgsql';
    protected $fillable = [
		'id',
		'signatario_clicksign_id',
		'nome',
        'email',
        'cpf',
        'data_nascimento',
		'json_retorno'
	];
}
