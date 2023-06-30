<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ConsultaProdutoComprasView extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'consulta_produtos_compras';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODPRD';

    protected $guarded = [
        'CODPRD',
        'ESTABEL',
        'ANO',
        'MES',
        'QUINZENA',
        'QTDCOMPRA',
        'NUMPEDCMP'
    ];
}
