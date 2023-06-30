<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mensagem extends Model
{
    use SoftDeletes;

    protected $fillable = ['titulo', 'data_inicio', 'data_fim', 'nome_arquivo', 'arquivo', 'created_by', 'updated_by', 'deleted_by'];
    
    public function tipoUsuarios(){
        return $this->hasMany('App\MensagemTipoUsuario', 'mensagem_id', 'id');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

}
