<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotaMotivoStatus extends Model
{
    use SoftDeletes;

    protected $table = 'devolucao_nota_motivo_status';

    protected $fillable = [
        'ordem',
        'devolucao_nota_motivo_id',
        'devolucao_nota_status_id',
        'created_by',
        'deleted_by'
    ];

    public function devolucao_nota_motivo(){
        return $this->hasOne('App\DevolucaoNotaMotivos', 'id', 'devolucao_nota_motivo_id');
    }

    public function devolucao_nota_status(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'devolucao_nota_status_id');
    }
}
