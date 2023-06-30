<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendedorTituloNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_titulosreceber_vendedores';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tituloreceberporvendedor', 
        'tituloreceber', 
        'vendedor', 
        'vendedor_codigo', 
        'vendedor_nome', 
        'percentual_comissao', 
        'valor_comissao'
    ];

    public function titulosPagos(){
        return $this->hasOne('App\TitulosPagosNasajon', 'id_titulo', 'tituloreceber');
    }

    public function contaReceberBaixado(){
        return $this->hasOne('App\ContasReceberBaixadoNasajon', 'id_titulo', 'tituloreceber');
    }
    
    public function cenprotTitulos(){
        return $this->hasOne('App\CenprotTitulo', 'titulo_id', 'tituloreceber')->withTrashed();
    }

    public function titulosAbertos(){
        return $this->hasOne('App\TitulosEmAbertoNasajonPortal', 'titulo_id', 'tituloreceber');
    }

    public function detalhesRenegociaoPortal(){
        return $this->hasOne('App\RenegociacaoTituloParcela', 'titulo_id_nasajon','tituloreceber');
    }

    public function detalhesTituloPagamento(){
        return $this->hasOne('App\TituloPagamentoNasajon', 'id_titulo', 'tituloreceber' );
    }
}
