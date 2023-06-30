<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ComissaoGerentesVendedores extends Model
{
    public $fillable = [
        'mes',
        'ano',
        'codigo_representante',
        'porcentagem',
    ];

    public function vendedorDetalhes(){
        return $this->hasOne('App\User', 'codigo_representante', 'codigo_representante');
    }
}
