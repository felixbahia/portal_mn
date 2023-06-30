<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotasImportadasEntradasTitulosDuplicata extends Model
{
    use SoftDeletes;

    protected $dates = ['vencimento'];

    protected $fillable = [
        'id',
        'duplicata',
        'vencimento',
        'valor',
        'notas_importadas_entradas_titulo_id',
    ];

}
