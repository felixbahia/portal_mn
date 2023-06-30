<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FichaTecnicaInfoAdicional extends Model
{
    
    public $fillable = [
        'ficha_tecnica_produtos_id',
        'lavagem',
        'encolhimento',
        'imagem_produto'
    ];

    public function ficha_tecnica_produto(){
        $this->hasOne('App\FichaTecnicaProduto', 'id', 'ficha_tecnica_produtos_id');
    }
}
