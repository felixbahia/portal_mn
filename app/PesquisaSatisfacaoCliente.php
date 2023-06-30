<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PesquisaSatisfacaoCliente extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nome',
        'documento',
        'link_formulario',
        'email',
        'envio_7_dias',
        'created_by',
        'updated_by',
        'deleted_by',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function formulariosRespondidos(){
        return $this->hasMany('App\PesquisaSatisfacaoFormularioResposta', 'pesquisa_satisfacao_clientes_id', 'id');
    }

    public function clienteNotas(){
        return $this->hasMany('App\PesquisaSatisfacaoClienteNota', 'pesquisa_satisfacao_clientes_id', 'id');
    }

    public function clienteNasajon(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'documento');
    }

    public function ultimoEnvio(){
        return $this->hasOne('App\PesquisaSatisfacaoCliente', 'documento', 'documento')->orderBy('created_at','desc');
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
