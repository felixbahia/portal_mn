<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoneTransacaoParcelamento extends Model
{
    use SoftDeletes;
    
    protected $connection = 'pgsql';

    protected $fillable = [
        'id', 
        'stone_transacoes_pedido_id', 
        'actual_installment',
        'gross_amount', 
        'net_amount', 
        'prevision_liquidation_date',
        'stone_transacoes_pagamento_restantes_id',
        'created_by', 
        'updated_by', 
        'deleted_by',
        'created_at'
    ];
    
    public function transacaoRestante(){
        return $this->hasOne('App\StoneTransacoesPagamentosRestante', 'id', 'stone_transacoes_pagamento_restantes_id');
    }
    public function transacao(){
        return $this->hasOne('App\StoneTransacoesPedido', 'id', 'stone_transacoes_pedido_id');
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
