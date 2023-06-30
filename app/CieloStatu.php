<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CieloStatu extends Model
{
    protected $fillable = [
        'descricao',
        'token',
        'lio',
        'ecommerce',
    ];
}
