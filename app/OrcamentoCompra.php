<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrcamentoCompra extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','data', 'nacional_valor', 'importado_valor', 'created_by', 'updated_by', 'deleted_by', 'condicoes_pagamento_web_id', 'competencia_orcamento_compras_id', 'pagamento'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['data', 'deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function detalhesFornecedor(){
    	return $this->hasOne('App\FornecedorNasajon', 'codigo', 'fornecedor_codigo');
    }

    public function detalhesCondicoesPagamentoWeb(){
    	return $this->hasOne('App\CondicoesPagamentoWeb', 'id', 'condicoes_pagamento_web_id');
    }

    public function detalhesOrigem(){
    	return $this->hasOne('App\OrcamentoCompra', 'id', 'competencia_orcamento_compras_id');
    }
}
