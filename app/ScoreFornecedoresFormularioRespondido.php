<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreFornecedoresFormularioRespondido extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'id','pergunta','resposta','grupo_pergunta','score_fornecedores_id','score_fornecedor_formulario_tipo_respostas_id','score_fornecedor_formulario_id','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function formulario(){
        return $this->hasOne('App\ScoreFornecedorFormulario', 'id', 'score_fornecedor_formulario_id');
    }
    public function tipoResposta(){
        return $this->hasOne('App\ScoreFornecedorFormularioTipoRespostas', 'id', 'score_fornecedor_formulario_tipo_respostas_id');
    }

    public function scoreFornecedores(){
        return $this->hasOne('App\ScoreFornecedore', 'id', 'score_fornecedores_id');
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
