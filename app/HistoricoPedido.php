<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoricoPedido extends Model
{
    use SoftDeletes;

	protected $fillable = 
	[
		'pedido',
		'natureza',
		'antigo',
		'novo',
		'created_by',
		'updated_by',
		'deleted_by'
	];

	public function pedido_detalhes(){
		$this->belongsTo('App\PedidoPortal', 'id', 'pedido');
	}

	public function created_by_detalhes(){
		$this->belongsTo('App\User', 'id', 'created_by');
	}

	public function updated_by_detalhes(){
		$this->belongsTo('App\User', 'id', 'updated_by');
	}

	public function deleted_by_detalhes(){
		$this->belongsTo('App\User', 'id', 'deleted_by');
	}
}
