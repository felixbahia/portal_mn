<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class HistoricoFinanceiroClienteTitulo extends Model
{
    protected $fillable = [
        'historico_financeiro_clientes_id', 'cliente', 'titulo_id', 'titulo_estabelecimento', 'titulo_numero', 'titulo_valor_original', 'titulo_valor_saldo', 'titulo_vencimento', 'titulo_emissao', 'created_by'
    ];

    public function historicoFinanceiro(){
        return $this->hasOne('App\HistoricoFinanceiroCliente', 'id', 'historico_financeiro_clientes_id');
    }
    
    public function dadosCliene(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cliente');
    }

    public function tituloNasajon(){
        return $this->hasOne('App\TitulosEmAbertoNasajon', 'titulo_id', 'titulo_id');
    }

    public function tituloNasajon998(){
        return $this->hasOne('App\TitulosVendedor998Nasajon', 'titulo_id', 'titulo_id');
    }

    public function titulosFaturados(){
        return $this->hasOne('App\ContasReceberBaixadoNasajon', 'numero', 'titulo_numero');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

}
