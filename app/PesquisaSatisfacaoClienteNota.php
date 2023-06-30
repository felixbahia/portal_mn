<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesquisaSatisfacaoClienteNota extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'numero',
        'nota_nasajon_id',
        'pesquisa_satisfacao_clientes_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    public function clienteNotas(){
        return $this->hasOne('App\PesquisaSatisfacaoCliente', 'id', 'pesquisa_satisfacao_clientes_id');
    }

    public function notasNasajon(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_nasajon_id');
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
