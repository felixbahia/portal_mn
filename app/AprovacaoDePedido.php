<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AprovacaoDePedido extends Model
{
	use SoftDeletes;

    protected $fillable = [
        'pedido_id', 'aprovador_id', 'created_at', 'updated_at', 'deleted_at', 'nivel_aprovacao', 'credito', 'condicao_pagamento', 'preco', 'integracao', 'aprovacao_credito_user_id', 'aprovacao_preco_user_id', 'credito_sem_limite', 'credito_data_limite', 'preco_limite', 'preco_desconto', 'nivel_aprovacao_credito', 'data_aprovacao_credito', 'nivel_aprovacao_preco', 'data_aprovacao_preco', 'created_by', 'updated_by', 'deleted_by', 'prorrogacao', 'prorrogacao_user_id', 'pedido_pre', 'data_aprovacao_pre_1auth', 'user_aprovacao_pre_1auth', 'data_aprovacao_pre_2auth', 'user_aprovacao_pre_2auth', 'retaguarda', 'credito_disponivel'
    ];

    function pedido(){
    	return $this->belongsTo('App\PedidoPortal', 'pedido_id', 'id');
    }

    function aprovador(){
    	return $this->hasOne('App\User', 'id', 'aprovador_id');
    }

    function nivelaprovacao(){
    	return $this->hasOne('App\NivelAprovacao', 'id', 'nivel_aprovacao');
    }

    function nivelaprovacaoCredito(){
    	return $this->hasOne('App\NivelAprovacao', 'id', 'nivel_aprovacao_credito');
    }

    function nivelaprovacaoPreco(){
    	return $this->hasOne('App\NivelAprovacao', 'id', 'nivel_aprovacao_preco');
    }

    function aprovador_credito(){
    	return $this->hasOne('App\User', 'id', 'aprovacao_credito_user_id');
    }
    
    function aprovador_preco(){
    	return $this->hasOne('App\User', 'id', 'aprovacao_preco_user_id');
    }

    function aprovadorProrrogacao(){
    	return $this->hasOne('App\User', 'id', 'prorrogacao_user_id');
    }
    
    function aprovadorPre1(){
    	return $this->hasOne('App\User', 'id', 'user_aprovacao_pre_1auth');
    }
    
    function aprovadorPre2(){
    	return $this->hasOne('App\User', 'id', 'user_aprovacao_pre_2auth');
    }
    
}
