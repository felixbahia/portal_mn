<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class StonePagamentosParciai extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'stone_transacoes_pedido_id',
        'stone_cadastro_maquininha_id',
        'forma_pagamento', 
        'parcelamento', 
        'valor', 
        'codigo_autoriazacao', 
        'data_autorizacao', 
        'documento_cartao',
        'contrato_cartao',
        'cnpj_operadora',
        'meio_eletronico',
        'operadora',
        'bandeira',
        'retorno_api_atualizar_cartao_nasajon',
        'tipo_operacao',
        'id_pagamento_nasajon',
        'query_api_nasajon',
        'stone_transacoes_pagamento_restantes_id',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'created_at'
    ];

    public function cadastroMaquininha(){
        return $this->hasOne('App\StoneCadastroMaquininha', 'id', 'stone_cadastro_maquininha_id');
    }

    public function transacaoRestanteStone(){
        return $this->hasOne('App\StoneTransacoesPagamentosRestante', 'id', 'stone_transacoes_pagamento_restantes_id');
    }

    public function transacaoStone(){
        return $this->hasOne('App\StoneTransacoesPedido', 'id', 'stone_transacoes_pedido_id');
    }

    public function bandeiraNasajon(){
        return $this->hasOne('App\CartoesBandeirasNasajon', 'bandeiracartao', 'bandeira');
    }

    public function formaPagamento(){
        return $this->hasOne('App\FormaPagamentoNasajon', 'formapagamento', 'forma_pagamento');
    }

    public function parcelamentoNasajon(){
        return $this->hasOne('App\ParcelamentoNasajon', 'parcelamento', 'parcelamento');
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
