<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreFornecedoresNotasLancamento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "id",
        "pergunta",
        "resposta",
        "grupo_pergunta",
        "notas_importadas_compra_id",
        "score_fornecedor_formulario_id",
        "score_fornecedor_tipo_respostas_nota_id",
        "created_by",
        "updated_by",
        "deleted_by",
    ];

    public function formulario(){
        return $this->hasOne('App\ScoreFornecedorFormulario', 'id', 'score_fornecedor_formulario_id');
    }

    public function tipoResposta(){
        return $this->hasOne('App\ScoreFornecedorFormularioTipoRespostas', 'id', 'score_fornecedor_tipo_respostas_nota_id');
    }

    public function notasCompras(){
        return $this->hasOne('App\NotasImportadasCompra', 'id', 'notas_importadas_compra_id');
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

    public function documentos(){
        return $this->hasMany('App\ScoreFornecedoresNotasLancamentosDocumento', 'score_fornecedores_notas_lancamentos_id', 'id');
    } 

}
