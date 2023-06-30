<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParametrosPedido extends Model
{
	use SoftDeletes;

	protected $table = "parametros_pedido";

    protected $fillable = [
    	'estabelecimento',
		'valor_minimo_porcentagem',
		'valor_maximo_porcentagem',
		'expiracao_visulizacao',
		'created_by',
		'modified_by',
		'dias_integracao'
	];
}
