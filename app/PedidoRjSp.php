<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoRjSp extends Model
{
    protected $connection = 'pgsql';
    protected $table = "pedido_rj_sp";
    protected $fillable = [
        'pedido_id',
        'pedido_transferencia_id',
        'pedido_nasajon_transferencia_id',
        'pedido_nasajon_venda_id',
        'liberado',
        'email_transportadora',
        'email_pedido'
    ];

    public function pedidoPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function pedidoTransferenciaPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_transferencia_id');
    }

    public function pedidoTransferenciaNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_nasajon_transferencia_id')->whereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA', null]);
    }

    public function pedidoVendaNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'pedido_venda_id');
    }
}
