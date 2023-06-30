<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidosPrePago extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'pedidos_prepagos';

	protected $fillable = ['pedido_id', 'pedido_nasajon_id', 'pedido_nasajon_numero', 'valor', 'valor_pago', 'created_by', 'updated_by', 'deleted_by', 'atualizado'];

	public function pedidoNasajon(){
           return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_nasajon_id')
           ->where(function($query){
               $query->where('grupodeoperacao', 'VENDA')
               ->orWhere(['grupodeoperacao' => NULL]);
           });
	}

	public function pedido(){
   		return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }
    
    public function lancamentos(){
        return $this->hasMany('App\ChequesPedidosPrepagos', 'pedido_prepago_id', 'id');
    }
	
    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
    
}
