<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TitulosEmAbertoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_titulosemaberto';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
		'codigo',
		'cod_cliente',
		'nome_cliente',
		'numero',
		'parcela',
		'vencimento',
		'valor',
		'conta_agencia',
		'conta_agencia_digito',
		'conta_numero',
		'conta_digito',
		'id_estabelecimento',
		'titulo_emissao',
		'nota_numero',
		'nota_id',
		'nota_emissao',
		'banco_codigo',
		'banco_nome',
		'cnpj',
		'id_cliente',
		'titulo_de_terceiro',
		'documento_terceiro',
		'nome_terceiro',
		'saldotitulo',
		'nossonumero',
		'identificadorbancario',
		'percentualjurosdiario',
		'datainiciomultaejuros',
		'multa',
		'desconto',
		'titulo_id',
		'vencimento_original',
		'tem_prorrogacao',
		'observacao',
		'enviado_para_banco',
		'enviado_para_cartorio',
		'enviado_para_cartorio_data',
		'origem',
		'origem_texto',
		'juros',
		'numero_titulo_renegociado',
		'vencimento_titulo_renegociado',
		'associado_998'
    ];

    public function notaDetalhes(){
        return $this->hasOne('App\NotasNasajon', 'id', 'nota_id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }

    public function revisao_vendedor_comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'nota_id');
    }

    function condicaoDePagamento(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'nota_id');
    }

    public function estabelecimento_detalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'estabelecimento', 'id_estabelecimento');
    }

    public function devolucoes(){
        return $this->hasMany('App\DevolucaoNota', 'nota_id', 'nota_id');
	}
	
    public function vendedorTitulo(){
        return $this->hasMany('App\VendedorTituloNasajon', 'tituloreceber', 'titulo_id');
	}
	
	public function cenprot(){
        return $this->hasOne('App\CenprotTitulo', 'titulo_id', 'titulo_id')->withTrashed();
	}
	
	public function renegociacao(){
        return $this->hasOne('App\RenegociacaoTituloTitulo', 'titulo_numero', 'numero');
    }
	
	public function tituloNovoRenegociado(){
        return $this->hasOne('App\RenegociacaoTituloParcela', 'titulo_id_nasajon', 'titulo_id');
    }

	public function clienteBlackListTitulo(){
        return $this->hasOne('App\ClienteBlackListTitulo', 'titulo_numero', 'numero');
    }

	public function detalhesTitulo(){
        return $this->hasOne('App\FinancasTitulosNasajon', 'id', 'titulo_id');
    }

	public function detalhesFaturamentoOnline(){
        return $this->hasOne('App\FaturamentoOnline', 'numero_documento', 'nota_numero');
    }
}
