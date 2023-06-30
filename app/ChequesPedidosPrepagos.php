<?php

namespace App;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChequesPedidosPrepagos extends Pivot
{
    use SoftDeletes;
    
    protected $table = 'cheques_pedidos_prepagos'; 
    protected $primaryKey = ['cheque_id', 'pedido_prepago_id'];
    
    public $incrementing = false;
    
    protected $fillable = [
        'cheque_id',
        'pedido_prepago_id',
        'valor_pago',
        'comissao',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function cheque(){
        return $this->hasOne('App\Cheque', 'id', 'cheque_id');
    }

    public function pedido(){
        return $this->hasOne('App\PedidosPrePago', 'id', 'pedido_prepago_id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function deletadoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

}
