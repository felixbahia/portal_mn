<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AprovacaoDeProjeto extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'projeto_id', 
        'aprovador_id', 
        'nivel_aprovacao', 
        'credito', 
        'condicao_pagamento', 
        'preco', 
        'integracao', 
        'aprovacao_credito_user_id', 
        'aprovacao_preco_user_id', 
        'credito_sem_limite', 
        'credito_data_limite', 
        'preco_limite', 
        'preco_desconto', 
        'nivel_aprovacao_credito', 
        'data_aprovacao_credito', 
        'nivel_aprovacao_preco', 
        'data_aprovacao_preco'
    ];

    function projeto(){
    	return $this->belongsTo('App\LancamentoProjeto', 'projeto_id', 'id');
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
}
