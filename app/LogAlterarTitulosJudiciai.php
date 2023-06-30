<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LogAlterarTitulosJudiciai extends Model
{
    protected $fillable = [
        'id',
        'titulo',
        'uuid_titulo',
        'uuid_cliente',
        'conta_anterior',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

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
