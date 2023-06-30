<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloErro extends Model
{
    
	protected $fillable = [
		'cielo_pedido_id',
		'motivo_erro',
		'json_enviado',
		'json_retorno',
	];

	public function pedido(){
		return $this->hasOne('App\CieloPedido', 'id', 'cielo_pedido_id');
	}

}
