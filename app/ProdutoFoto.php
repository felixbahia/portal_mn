<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProdutoFoto extends Model
{
    use SoftDeletes;
    
    //

    public $fillable = [
        'codigo_produto',
        'filename',
        'thumb_filename',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function produto_especificacao(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function produto_nasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
    }
}
