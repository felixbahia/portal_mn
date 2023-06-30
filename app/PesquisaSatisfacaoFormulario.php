<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesquisaSatisfacaoFormulario extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'pergunta',
        'ordem_pergunta',
        'pesquisa_satisfacao_formulario_tipo_respostas_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function tipoRespostas(){
        return $this->hasOne('App\PesquisaSatisfacaoFormularioTipoResposta', 'id', 'pesquisa_satisfacao_formulario_tipo_respostas_id');
    }

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
