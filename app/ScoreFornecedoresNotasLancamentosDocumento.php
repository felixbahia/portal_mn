<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreFornecedoresNotasLancamentosDocumento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "id",
        "descricao",
        "caminho",
        "score_fornecedores_notas_lancamentos_id",
        "created_by",
        "updated_by",
        "deleted_by",
    ];

    public function scoreNotas(){
        return $this->hasOne('App\ScoreFornecedoresNotasLancamento', 'id', 'score_fornecedores_notas_lancamentos_id');
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
