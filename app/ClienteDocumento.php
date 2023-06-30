<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClienteDocumento extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at', 'created_at', 'updated_at'];

    protected $fillable = [
        'descricao_documento',
        'documento',
        'cpf_cnpj',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updateBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function clienteNovo(){
        return $this->hasOne('App\ClienteNovo', 'cpf_cnpj', 'cpf_cnpj');
    }

    public function clienteNasajon(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cpf_cnpj');
    }

}
