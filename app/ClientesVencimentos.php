<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientesVencimentos extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'clientes_vencimentos';

    protected $fillable = ['condicao_id', 'cliente', 'created_by', 'modified_by'];

    function cliente_info(){
    	return $this->hasOne('\\App\Cliente', 'CODCAD', 'cliente');
    }

    function condicoesPagamentoWeb(){
    	return $this->belongsTo('\\App\CondicoesPagamentoWeb', 'id', 'condicao_id');
    }

    function clienteNasajon(){
    	return $this->hasOne('\App\ClienteNasajon', 'codigo', 'cliente');
    }
}
