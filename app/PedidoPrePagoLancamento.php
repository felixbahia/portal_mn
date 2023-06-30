<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoPrePagoLancamento extends Model
{
    use SoftDeletes;

    protected $table = 'pedidos_prepagos_lancamentos';

    protected $fillable = [
        'pedido_prepago_id',
        'banco',
        'agencia',
        'conta',
        'numero_cheque',
        'valor',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function pedido(){
        return $this->hasOne('App\PedidosPrePago', 'id', 'pedido_prepago_id');
    }
}