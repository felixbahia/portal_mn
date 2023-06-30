<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FichaTecnicaSequenciaOperacional extends Model
{
	use SoftDeletes;

    protected $fillable = [
        'ficha_tecnica_produtos_id',
        'ordem',
        'operacao',
        'tipo_ponto',
    ];

    public function ficha_tecnica_produto(){
        $this->hasOne('App\FichaTecnicaProduto', 'id', 'ficha_tecnica_produtos_id');
    }
}
