<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreFornecedorFormulario extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'id','pergunta','score_fornecedor_formulario_tipo_respostas_id','score_fornecedor_formulario_grupo_perguntas_id','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public function fornulario(){
        return $this->hasMany('App\ScoreFornecedoresFormularioRespondido', 'score_fornecedor_formulario_tipo_respostas_id', 'id');
    } 

    public function grupoPergunta(){
        return $this->hasOne('App\ScoreFornecedorFormularioGrupoPergunta', 'id', 'score_fornecedor_formulario_grupo_perguntas_id');
    }

    public function tipoResposta(){
        return $this->hasOne('App\ScoreFornecedorFormularioTipoRespostas', 'id', 'score_fornecedor_formulario_tipo_respostas_id');
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
