<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaStatus extends Model
{

    use SoftDeletes;

    protected $table = 'devolucao_nota_status';
    
    protected $fillable = [
        'descricao',
        'selecionavel',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function usuarios(){
        return $this->hasMany('App\DevolucaoNotaStatusUsuario', 'devolucao_nota_status_id', 'id');
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
