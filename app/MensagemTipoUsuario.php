<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MensagemTipoUsuario extends Model
{
    use SoftDeletes;

    protected $fillable = ['mensagem_id', 'tipo_usuario_id', 'created_by', 'updated_by', 'deleted_by'];
    
    public function tipoUsuario(){
        return $this->hasOne('App\TipoUsuario', 'id', 'tipo_usuario_id');
    }

    public function mensagem(){
        return $this->hasOne('App\Mensagem', 'id', 'mensagem_id');
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
