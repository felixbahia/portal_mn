<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IntesNotasNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_notas_itens';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id_nota',
        'id_item_nota',
        'cod_produto',
        'desc_produto',
        'ncm',
        'cfop',
        'unidade',
        'quantidade',
        'valor_unitario',
        'valor_total',
        'aliquota_icms',
        'base_icms',
        'valor_icms',
        'aliquota_icms_st',
        'base_icms_st',
        'valor_icms_st',
        'aliquota_ipi',
        'base_ipi',
        'valor_ipi'
    ];

}
