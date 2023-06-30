<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagarmeLogEnvio extends Model
{
    public $timestamps = false;
	protected $connection = 'pgsql';
	protected $fillable = [
		'cielo_pedido_id',
		'codigo',
		'envio',
		'datahora_envio',
	];

	protected $dates = [
		'datahora_envio',
	];

	public function pedido(){
		return $this->hasOne('App\CieloPedido', 'id', 'cielo_pedido_id');
	}

	public function getDescricaoAttribute(){
		switch($this->codigo){
			case 'envio_email':
			case 'envio_email_recusa':
				return 'Envio de e-mail para o cliente';
			break;
			case 'envio_pagarme':
				return 'Enviado para o Pagar-me';
			break;
			case 'retorno_pagarme':
				return 'Retorno do Pagar-me';
			break;
		}

	}
}
