<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Vencimentos extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBVCT1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODVCT';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODVCT', 'DESCRICAO', 'TIPVCT', 'NUMPARC', 'DIASVCT_1', 'DIASVCT_2', 'DIASVCT_3', 'DIASVCT_4', 'DIASVCT_5', 'DIASVCT_6', 'DIASVCT_7', 'DIASVCT_8', 'PORCVCT_1', 'PORCVCT_2', 'PORCVCT_3', 'PORCVCT_4', 'PORCVCT_5', 'PORCVCT_6', 'PORCVCT_7', 'PORCVCT_8', 'FATORPRAZO', 'PRAZO', 'REQUER_APROVACAO', 'FLAG_IAD', 'CONSIDERAR_DATA_BASE', 'DIASVCT_9', 'DIASVCT_10', 'DIASVCT_11', 'DIASVCT_12', 'DIASVCT_13', 'DIASVCT_14', 'DIASVCT_15', 'DIASVCT_16', 'DIASVCT_17', 'DIASVCT_18', 'DIASVCT_19', 'DIASVCT_20', 'DIASVCT_21', 'DIASVCT_22', 'DIASVCT_23', 'DIASVCT_24', 'PORCVCT_9', 'PORCVCT_10', 'PORCVCT_11', 'PORCVCT_12', 'PORCVCT_13', 'PORCVCT_14', 'PORCVCT_15', 'PORCVCT_16', 'PORCVCT_17', 'PORCVCT_18', 'PORCVCT_19', 'PORCVCT_20', 'PORCVCT_21', 'PORCVCT_22', 'PORCVCT_23', 'PORCVCT_24', 'LIBERADO_WEB', 'VALMINPED'
    ];

    public function pedido_portal(){
        return $this->belongsTo('App\PedidoPortal', 'condicao_pagamento', 'CODVCT');
    }

    public function condicao_pagamento(){
        return $this->belongsTo('App\CondicoesPagamentoWeb', 'id_web', 'CODVCT');
    }
}
