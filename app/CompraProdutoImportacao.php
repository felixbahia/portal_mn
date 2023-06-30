<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CompraProdutoImportacao extends Model
{
    protected $fillable = [
        'estabelecimento',
        'numero_documento',
        'codigo_produto',
        'data_faturamento',
        'preco_total',
        'quantidade',
        'preco_unitario',
        'origem'
    ];

    protected $dates = ['data_faturamento'];
}
