<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoneRetornoTransacoesAvulsa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id', 
        'pedido_id', 
        'stone_transacoes_pedido_id', 
        'id_web_hook', 
        'account_id', 
        'account_name', 
        'type', 
        'data_id', 
        'data_code',
        'data_amount',
        'data_paid_amount',
        'data_status',
        'data_created_at',
        'order_id',
        'order_code',
        'order_amount',
        'order_closed',
        'order_currency',
        'order_status',
        'customer_id',
        'customer_name',
        'customer_delinquent',
        'customer_created_at',
        'metadata_scheme_name',
        'metadata_account_funding_source',
        'metadata_autorization_code',
        'metadata_account_holder_name',
        'metadata_initiator_transaction_key',
        'metadata_installment_quantity',
        'metadata_installment_type',
        'metadata_terminal_serial_number',
        'metadata_transaction_time',
        'json',
        'pre_pago',
        'retorno_api_nasajon', 
        'api_nasajon',
        'integracao_pedido',
        'finalizado'
    ];

    public function pedidoPortal(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function pedidoStone(){
        return $this->hasOne('App\StoneTransacoesPedido', 'id', 'stone_transacoes_pedido_id');
    }

    public function order(){
        return $this->hasMany('App\StonePedidoOrder', 'order_id', 'order_id');
    }
}
