<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FichaTecnicaArquivo extends Model
{
    protected $fillable = [
        'ficha_tecnica_produtos_id',
        'arquivo',
        'descricao',
        'tipo',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function ficha_tecnica_produto(){
        return $this->hasOne('App\FichaTecnicaProduto', 'id', 'ficha_tecnica_produtos_id');
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
