<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProformaEncerrado extends Model
{
    protected $fillable = [
        'codigo_produto',
        'previsao_entrega',
        'numero_proforma',
        'valor_venda_us',
        'valor_unitario_fob',
        'valor_unitario_real',
        'data_proforma'
    ];
}
