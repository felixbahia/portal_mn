<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrecosLog extends Model
{
    protected $fillable = [
        'codigo_produto',
        'preco_real_antigo',
        'preco_dolar_antigo',
        'preco_real_novo',
        'preco_dolar_novo',
        'compra_real_antigo',
        'compra_real_novo',
        'processo',
        'created_by'
    ];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
}
