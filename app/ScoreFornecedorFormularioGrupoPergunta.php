<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScoreFornecedorFormularioGrupoPergunta extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $fillable = [
        'id','grupo_pergunta','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    public function formulario(){
        return $this->hasMany('App\ScoreFornecedorFormulario', 'score_fornecedor_formulario_grupo_perguntas_id', 'id');
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
