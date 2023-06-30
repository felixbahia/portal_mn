<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PedidosVendaNasajon extends Model
{

    use \Awobaz\Compoships\Compoships;

    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_pedidos_venda_v3';
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;
    public $primaryKey = 'id';

    protected $fillable = ['id',
     'numero',
     'numeroexterno',
     'valor',
     'situacao',
     'situacaogerencial',
     'emissao',
     'frete',
     'seguro',
     'id_usuario_cadastro',
     'anotacoes_manuais',
     'observacao_dadosgerais',
     'estabelecimento',
     'estabelecimento_codigo',
     'estabelecimento_descricao',
     'tabeladepreco',
     'tabeladepreco_codigo',
     'tabeladepreco_descricao',
     'cliente',
     'cliente_nomefantasia',
     'id_emitente',
     'id_pessoa',
     'id_destinatario',
     'tipoemitente',
     'tiporeceptor',
     'rascunho',
     'operacao',
     'operacao_codigo',
     'operacao_descricao',
     'sinal',
     'status',
     'ra',
     'num_ra',
     'nf_id',
     'situacao_descricao',
     'vendedor_codigo',
     'vendedor_nome',
     'dataprevisao_entradasaida',
     'transportadora_codigo',
     'transportadora_nome',
     'modocompra',
     'notafiscal_id',
     'notafiscal_numero',
     'vendedor_comissao',
     'origem',
     'grupodeoperacao',
     'nf_sdituacao',
     'observacao',
     'grupodeoperacao_pedido',
     'cliente_cnpj',
     'cliente_codigo',
     'cliente_razaosocial',
     'id_transportadora_redespacho'
    ];

    public function getEstabelecimentoIntAttribute(){
        return intval($this->estabelecimento_codigo); 
    }

    public function itens_pedido(){
        return $this->hasMany('App\ItensPedidosVendaNasajon', 'id_docfis', 'id');
    }
    
    public function nota(){
        return $this->hasOne('App\NotasNasajon', 'id', 'notafiscal_id');
    }

    public function notaEmAberto(){
        return $this->hasOne('App\NotasEmAbertoNasajon', 'id', 'notafiscal_id');
    }

    public function cliente_detalhes(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'cliente');        
    }

    public function userNasajon(){
        return $this->hasOne('App\User', 'codigo_nasajon', 'id_usuario_cadastro');
    }

    public function userPortal(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }
    
    public function valor_total(){
        return $this->hasOne('App\ItensPedidosVendaNasajon', 'id_docfis', 'id')
            ->select('id_docfis', DB::raw("SUM(valortotal) as total"))
            ->groupBy('id_docfis');
    }

    
    public function formasPagamentosMultiplos(){
        return $this->hasMany('App\PedidoFormaPagamentoNasajon', 'id_docfis', 'id');
    }

    public function forma_pagamento(){
        return $this->hasOne('App\PedidoFormaPagamentoNasajon', 'id_docfis', 'id');
    }

    public function formaPagamento(){
        return $this->hasOne('App\PedidoFormaPagamentoNasajon', 'id_docfis', 'id')->where('formapagamento_descricao','=', 'Usar Crédito');
    }

    public function getCodigoClienteAttribute(){
        return $this->cliente_detalhes->codigo;
    }
    public function pedido_portal(){
        return $this->hasOne('App\PedidoPortal', ['pedido_gerado', 'estabelecimento', 'codigo_operacao', 'cod_cliente'], ['numero', 'estabelecimento_int', 'operacao_codigo', 'codigo_cliente']);
    }

    public function vendedor_detalhes(){
        return $this->hasOne('App\VendedorNasajon', 'codigo', 'vendedor_codigo');
    }

    public function condicoes_pagamento(){
        return $this->hasMany('App\CondicoesPagamentoNasajon', 'id_docfis', 'id');
    }

    public function faturamento(){
        return $this->hasOne('App\FaturamentoNotaNasajon', 'Id_Nota', 'nf_id');
    }

    public function pedido_pre_pago(){
        return $this->hasOne('App\PedidosPrePago', 'pedido_nasajon_id', 'id');
    }

    public function notaCancelada(){
        return $this->hasOne('App\NotasCanceladasNasajon', 'id', 'notafiscal_id');
    }
    
    public function cielo(){
        return $this->hasOne('App\CieloPedido', 'pedido_nasajon_id', 'id');
    }

    public function valorTotalFaturado(){
        return $this->hasOne('App\ItensPedidosVendaNasajon', 'id_docfis', 'id')
            ->select('id_docfis', DB::raw("SUM(valorunitariocomercial*
                case
                    when quantidade_faturada is null then
                        quantidadecomercial 
                    else 
                        quantidade_faturada 
                end) 
                as total_faturado"))
            ->groupBy('id_docfis');
    }

    public function valorTotalSeparacao(){
        return $this->hasOne('App\ItensPedidosVendaNasajon', 'id_docfis', 'id')
            ->select('id_docfis', DB::raw("SUM(valorunitariocomercial *
                quantidade_apurada) 
                as separacao"))
            ->groupBy('id_docfis');
    }
    
    public function transportador(){
        return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora_codigo');
    }

    public function nfce(){
        return $this->hasOne('App\ContasReceberBaixadoNasajon', 'documento_id', 'notafiscal_id');
    }

    public function confirmacaoRetiraNota(){
        return $this->hasOne('App\ConfirmacaoNotaSaida', ['nota', 'estabelecimento'], ['notafiscal_numero', 'estabelecimento_codigo'])->whereNull('nfce');
    }

    public function confirmacaoRetiraCupom(){
        return $this->hasOne('App\ConfirmacaoNotaSaida', 'id_cupom', 'notafiscal_id')->where('nfce',true);
    }

    public function campanhaComissao(){
        return $this->setConnection('pgsql')->hasOne('App\CampanhasComissaoCalculo', 'pedido_nasajon_id', 'id');
    }

    public function transportadoraRedespacho(){
        return $this->hasOne('App\TransportadorNasajon', 'cnpj', 'transportadora_redespacho_documento');
    }

    public function percentualComissaoVendedores(){
        return $this->hasOne('App\VendedorComissaoNota', 'id_docfis', 'notafiscal_id');
    }
}
