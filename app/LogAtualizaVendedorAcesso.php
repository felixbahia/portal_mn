<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogAtualizaVendedorAcesso extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'email',
        'documento'
    ];

    protected $dates = ['data_cadastro'];
}
