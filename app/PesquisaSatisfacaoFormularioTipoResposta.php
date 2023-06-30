<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PesquisaSatisfacaoFormularioTipoResposta extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'id',
        'resposta',
    ];

    public function formulario(){
        return $this->hasMany('App\PesquisaSatisfacaoFormulario', 'pesquisa_satisfacao_formulario_tipo_respostas_id', 'id');
    }

}
