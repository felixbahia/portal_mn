<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DevolucaoNotaStatusUsuario extends Model
{
    protected $fillable = [
        'devolucao_nota_status_id',
        'user_id'
    ];

    public function devolucao_nota(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'devolucao_nota_status_id');
    }

    public function usuario(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }
}
