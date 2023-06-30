<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendasSantistaLog extends Model
{
    //
    public $fillable = [
        'ip',
        'nota',
        'cnpj',
        'resultado'
    ];
}
