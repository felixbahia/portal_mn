<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaAprovador extends Model
{
    use SoftDeletes;

    protected $table = 'devolucao_nota_aprovadores';
    
    protected $fillable = [
        'devolucao_nota_id',
        'devolucao_nota_status_id',
        'aprovador',
        'created_by',
        'deleted_by'
    ];

    public function devolucao_nota(){
        return $this->hasOne('App\DevolucaoNota', 'id', 'devolucao_nota_id');
    }

    public function status(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'devolucao_nota_status_id');
    }

    public function aprovador_detalhes(){
        return $this->hasOne('App\User', 'id', 'aprovador');
    }
}
