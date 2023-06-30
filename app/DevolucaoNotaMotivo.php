<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaMotivo extends Model
{
    use SoftDeletes;
    
    public $fillable = [
        'id',
        'descricao',
        'created_by',
        'updated_by',
        'deleted_by',
        'afeta_premiacao',
        'assinatura_pedido'
    ];

    public function status(){
        return $this->hasMany('App\DevolucaoNotaMotivoStatus', 'devolucao_nota_motivo_id', 'id');
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
