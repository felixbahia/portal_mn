<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoUsarCreditoHistoricoLiberacoes extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'pedido_usar_credito_historico_liberacoes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id','pedido_usar_creditos_id','codigo_estabelecimento','numero','parcela','vencimento','valor','conta_agencia','conta_agencia_digito','conta_numero','conta_digito', 'pedido_aberto_valor'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function pedidoUsarCredito(){
        return $this->hasOne('App\PedidoUsarCredito', 'id', 'pedido_usar_creditos_id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
