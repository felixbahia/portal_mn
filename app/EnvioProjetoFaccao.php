<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnvioProjetoFaccao extends Model
{
    use SoftDeletes;
    protected $table = 'envio_projeto_faccao';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'id', 'lancamento_projetos_id', 'created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function projeto_detalhes(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'lancamento_projetos_id');
    }

    public function criado_por(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizado_por(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletado_por(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
