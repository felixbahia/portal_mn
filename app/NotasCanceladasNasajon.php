<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasCanceladasNasajon extends Model
{

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_nfes_saida_cancelada';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'id';
    protected $keyType = 'string';

    protected $fillable = [
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
        'total_desconto',
        'total_produto_sem_desconto',
        'situacao',
        'chavene',
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


    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'cpf_cnpj', 'cliente_documento');
    }
}
