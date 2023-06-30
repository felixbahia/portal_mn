<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SugestaoCompraFoto extends Model
{
    use SoftDeletes;
    
    //

    public $fillable = [
        'id',
        'sugestao_compra_id',
        'nome_arquivo',
        'caminho',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function sugestaoCompra(){
        return $this->hasOne('App\ SugestaoCompra', 'sugestao_compra_id', 'id');
    }
}
