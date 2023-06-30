<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagarmeCodigoErro extends Model
{
    public $timestamps = false;
    protected $fillable = [
		'codigo',
		'descricao'
	];
}
