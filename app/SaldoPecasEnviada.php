<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SaldoPecasEnviada extends Model
{
    protected $fillable = [
        'empresa',
        'produto',
        'quantidades'
    ];
}
