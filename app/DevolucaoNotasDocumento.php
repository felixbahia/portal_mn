<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DevolucaoNotasDocumento extends Model
{
    use SoftDeletes;

    public $fillable = [
        'id',
        'devolucao_nota_id',
        'tipo_documento',
        'nome_arquivo',
        'caminho',
        'enviado_transportadora',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    
    public function devolucaoNotas(){
        return $this->hasOne('App\DevolucaoNota', 'id', 'devolucao_nota_id');
    }
}
