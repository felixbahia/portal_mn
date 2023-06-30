<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasCfopNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_nfes_saida_cfop';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'id';
    protected $keyType = 'string';

    protected $guarded = [
        'id',
        'numero',
        'serie',
        'emissao',
        'datasaida',
        'naturezaoperacao',
        'operacao_codigo',
        'operacao_descricao',
        'cliente_nome',
        'cliente_documento',
        'baseicms',
        'basesubst',
        'valoricms',
        'seguro',
        'outras',
        'frete',
        'valor',
        'pesoliquido',
        'valoricmsst',
        'total_produto',
        'id_transportadora',
        'transportadora_nome',
        'transportadora_documento',
        'volumes',
        'estabelecimento_id',
        'estabelecimento_codigo',
        'estabelecimento_descricao',
        'estabelecimento_cnpj',
        'total_produto_sem_desconto',
        'total_desconto',
        'total_produto_sem_desconto',
        'situacao',
        'chavene',
        'cfop',
    ];

    public function itens_nota(){
        return $this->hasMany('App\NotaItensNasajon', 'id_docfis', 'id');
    }

    public function pedido(){
        return $this->hasOne('App\PedidosVendaNasajon', 'notafiscal_id', 'id');
    }

    public function revisao_vendedor_comissao(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'id');
    }

    public function estabelecimento_detalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento_codigo');
    }

    public function titulosAbertos(){
        return $this->hasMany('App\TitulosEmAbertoNasajon', 'nota_id', 'id');
    }

    public function transportadora(){
        return $this->hasOne('App\TransportadorNasajon', 'id', 'id_transportadora');
    }
    
    public function condicaoDePagamento(){
        return $this->hasOne('App\CondicoesPagamentoNasajon', 'id_docfis', 'id');
    }

    public function detalhesDeCondicoesPagamentos(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'id');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_documento');
    }

    public function primeiroItensNota(){
        return $this->hasOne('App\NotaItensNasajon', 'id_docfis', 'id');
    }

    public function ocorrenciaEntrega(){
        return $this->hasOne('App\OcorrenciasDeEntrega', 'nota_id', 'id');
    }

    public function devolucao(){
        return $this->hasOne('App\DevolucaoNota', 'nota_id', 'id');
    }

    public function transportadoraRedespacho(){
        return $this->hasOne('App\TransportadorNasajon', 'id', 'id_transportadora_redespacho');
    }

    public function faturamento(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Identificador Documento', 'id');
    }

    public function confirmacaoNotaSaida(){
        return $this->hasOne('App\ConfirmacaoNotaSaida', ['nota', 'estabelecimento'], ['numero', 'estabelecimento_codigo']);
    }
    
    public function faturamentoOnline(){
        return $this->hasOne('App\FaturamentoOnline', 'nota_uuid', 'id');
    }

    public function lancamentoDebCredVendedor(){
        return $this->hasMany('App\LancamentoDebCredVendedor', 'nota_uuid', 'id');
    }

    public function primeiro_itens_nota(){
        return $this->hasOne('App\NotaItensNasajon', 'id_docfis', 'id');
    }

    public function clienteNasajon(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_documento');
    }
}
