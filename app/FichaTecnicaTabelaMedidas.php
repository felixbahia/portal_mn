<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichaTecnicaTabelaMedidas extends Model
{
	use SoftDeletes;

    public $fillable = [
        'ficha_tecnica_produtos_id',
        'ordem',
        'medida_descricao',
        'medida_p',
        'medida_m',
        'medida_g',
        'medida_gg',
        'medida_xg',
        'medida_xgg',
        'tolerancia'
    ];

    public function ficha_tecnica_produto(){
        $this->hasOne('App\FichaTecnicaProduto', 'id', 'ficha_tecnica_produtos_id');
    }
}
