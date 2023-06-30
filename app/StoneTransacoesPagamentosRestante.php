<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StoneTransacoesPagamentosRestante extends Model
{
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'pedido_id', 
        'stone_cadastro_maquininha_id', 
        'pre_transaction_id', 
        'stone_transaction_id', 
        'stone_pagamento_parciais_id', 
        'stone_transacoes_pedido_id', 
        'status_pre_transacao', 
        'pre_transaction_token', 
        'card_brand', 
        'payment_type',
        'status_transacao',
        'data_transacao',
        'transaction_amount',
        'transaction_net_amount',
        'installments_number',
        'card_holder_name',
        'card_number',
        'transaction_authorization_code',
        'prevision_liquidation_date',
        'pos_serial_number',
        'siclos_transaction_id',
        'data_pre_transacao',
        'token',
        'pagamento_parcial',
        'pago',
        'pagamento_restante',
        'credito',
        'api_nasajon',
        'query_api_nasajon',
        'retorno_api_nasajon_pagamento',
        'retorno_api_nasajon_cartao',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'created_at'
    ];

    public function pedido(){
        return $this->hasOne('App\PedidoPortal', 'id', 'pedido_id');
    }

    public function maquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'id', 'stone_cadastro_maquininha_id');
    }
    
    public function parcelamentos(){
        return $this->hasOne('App\StoneTransacaoParcelamento', 'stone_transacoes_pagamento_restantes_id', 'id');
    }

    public function pagamentosParciais(){
        return $this->hasMany('App\StonePagamentosParciai', 'stone_transacoes_pagamento_restantes_id', 'id');
    }

    public function transacaoStone(){
        return $this->hasOne('App\StoneTransacoesPedido', 'id', 'stone_transacoes_pedido_id');
    }

    public function retornoTransacaoAvulsa(){
        return $this->hasMany('App\StoneRetornoTransacoesAvulsa', 'order_id', 'pre_transaction_id');
    }

    public function orders(){
        return $this->hasMany('App\StonePedidoOrder', 'order_id', 'pre_transaction_id');
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
