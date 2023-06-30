<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoPortal extends Model
{
    use SoftDeletes;

    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'pgsql';
    protected $table = "public.pedido";
    protected $fillable = [
        'data_pedido',
        'usuario',
        'cod_cliente',
        'nome_comprador',
        'email_comprador',
        'status_pedido',
        'estabelecimento',
        'pedido_gerado',
        'pedido_futuro',
        'condicao_pagamento',
        'no_pedido_compra',
        'cod_usuario_autorizador',
        'data_previsao_entrega',
        'tipo_frete',
        'observacao',
        'transportadora',
        'transportadora_redespacho',
        'comissao',
        'updated_by',
        'created_by',
        'base_icms',
        'valor_icms',
        'base_icmsst',
        'valor_icmsst',
        'valor_frete',
        'valor_seguro',
        'valor_desconto',
        'outros_valores',
        'valor_ipi',
        'valor_total_produtos',
        'valor_total_nota',
        'erro_integracao',
        'motivo_rejeicao',
        'codigo_operacao',
        'conta_e_ordem',
        'cod_cliente_conta_e_ordem',
        'tipo_venda',
        'tipo_frete_redespacho',
        'valor_frete_redespacho',
        'pedido_remessa_gerado',
        'tabela_tipo_preco',
        'frete_preco',
        'nasajon',
        'deleted_by',
        'migracao',
        'dias_cancelar',
        'agente_venda_id',
        'projeto_id',
        'cartao',
        'presencial',
        'bionexo',
        'deleted_at',
        'promotor_venda_id',
		'enfestar',
		'bater_amostra',
		'incluir_cartelas',
		'transportadora_retira',
		'transportadora_retira_horario',
		'transportadora_retira_imediato',
		'metragem_exata',
		'cliente_sem_telefone',
		'cliente_telefone',
		'outlet',
		'rj_x_sp',
        'data_previsao_entrega_original',
        'data_previsao_entrega_ultima',
        'necessidades_compras_id',
        'pix_id',
        'created_at'
    ];

    // Mutadores
    public function getCodClienteIntAttribute(){
        if(is_int(intval($this->cod_cliente)) && intval($this->cod_cliente) < 2147483647){
            return intval($this->cod_cliente);
        }else{
            return 0;
        }
    }
    public function getOrigemAttribute(){
        switch ($this->estabelecimento){
        case '3':
            return 'RO';
            break;
        case '4':
            return 'TO';
            break;
        default:
            return 'SP';
            break;
        }
    }
    
    public function getEstabelecimentoPadAttribute(){
        return str_pad($this->estabelecimento, 2, 0, STR_PAD_LEFT); 
    }
    
    // Relações
    public function usuario_detalhes(){
        return $this->hasOne('App\User', 'id', 'usuario')->withTrashed();
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by')->withTrashed();
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by')->withTrashed();
    }
    
    public function excluidoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by')->withTrashed();
    }

    public function aprovador_detalhes(){
        return $this->hasOne('App\User', 'id', 'cod_usuario_autorizador');
    }

    public function status_pedido_detalhes(){
        return $this->hasOne('App\StatusPedido', 'id', 'status_pedido')
            ->select('status', 'id');
    }

    public function condicao_pagamento_detalhes(){
        return $this->hasOne('App\CondicoesPagamentoWeb', 'id', 'condicao_pagamento');
    }

    public function itens_pedido(){
        return $this->hasMany('App\PedidoItemPortal', 'pedido', 'id');
    }

    public function valor_total(){
        if($this->estabelecimento == 3){
            return $this->hasOne('App\PedidoItemPortal', 'pedido', 'id')
                ->select('pedido', DB::raw("SUM(case when tem_ipi is false then
                            (preco_unitario*100)/100 * quantidade
                    else 
                        (preco_unitario * (1 + (ipi_produto / 100))*100)/100 * quantidade
                    end) as total"))
                ->groupBy('pedido');
        }else{
            return $this->hasOne('App\PedidoItemPortal', 'pedido', 'id')
                ->select('pedido', DB::raw("SUM(ROUND(preco_unitario*100)/100 * quantidade) as total"))
                ->groupBy('pedido');
        }
    }

    public function valor_total_ipi(){
        if($this->estabelecimento == 3){
            return $this->hasOne('App\PedidoItemPortal', 'pedido', 'id')
                ->select('pedido', DB::raw("SUM(case when tem_ipi is false then
                            (preco_unitario*100)/100 * quantidade
                    else 
                        (preco_unitario * (1 + (ipi_produto / 100))*100)/100 * quantidade
                    end) as total"))
                ->groupBy('pedido');
        }else{
            return $this->hasOne('App\PedidoItemPortal', 'pedido', 'id')
                ->select('pedido', DB::raw("SUM(ROUND(preco_unitario*100)/100 * quantidade) as total"))
                ->groupBy('pedido');
        }
    }

    public function valor_total_frete_produto(){
        return $this->hasOne('App\PedidoItemPortal', 'pedido', 'id')
            ->select('pedido', DB::raw("SUM(valor_frete) as total"))
            ->groupBy('pedido');
    }

    public function cliente(){
        if($this->nasajon === false){
            return $this->hasOne('App\Cliente', 'CODCAD', 'cod_cliente');
        }else{
            return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente')->orderBy('bloqueado')->orderBy('cpf_cnpj', 'desc');
        }
    }

    public function clienteSemBloqueio(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente');
    }

    public function cliente_conta_e_ordem(){
        if($this->nasajon === false){
            return $this->hasOne('App\Cliente', 'CODCAD', 'cod_cliente_conta_e_ordem');
        }else{
            return $this->hasOne('App\ClienteNasajon', 'codigo', 'cod_cliente_conta_e_ordem')->where('bloqueado', false);
        }
    }
    
    public function cliente_novo(){
        return $this->hasOne('App\ClienteNovo', 'id', 'cod_cliente_int');
    }

    public function aprovacao(){
        return $this->hasOne('App\AprovacaoDePedido', 'pedido_id', 'id')->withTrashed()->orderBy('deleted_at', 'desc');
    }

    public function detalhesTransportador(){
        if($this->nasajon === false){
            return $this->hasOne('App\Transportador', 'CODTRAN', 'transportadora');
        }else{
            return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora');
        }
    }

    public function detalhesTransportadorRedespacho(){
        if($this->nasajon === false){
            return $this->hasOne('App\Transportador', 'CODTRAN', 'transportadora_redespacho');
        }else{
            return $this->hasOne('App\TransportadorNasajon', 'codigo', 'transportadora_redespacho');
        }
    }

    public function margemPrazoPedido(){
        return $this->hasOne('App\MargemPrazo', 'estabelecimento', 'estabelecimento');
    }

    public function pedidoPrologos(){
        return $this->hasOne('App\PedidoVenda', 'NUMPED', 'pedido_gerado');
    }

    public function estabelecimentoDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'codigo', 'estabelecimento_pad');
    }

    public function agenteVendas(){
        return $this->hasOne('App\User', 'id', 'agente_venda_id');
    }

    public static function boot() {

        parent::boot();

        static::deleting(function($pedido) { // before delete() method call this

            if(isset($pedido->aprovacao)){
                $pedido->aprovacao->delete();
            }

        });

    }

    public function detalhesProjeto(){
        return $this->hasOne('App\LancamentoProjeto', 'id', 'projeto_id');
    }

    public function remessa(){
        return $this->hasOne('App\RemessaProduto', 'pedido_id', 'id');
    }

    public function cielo(){
        return $this->hasMany('App\CieloPedido', 'pedido_id', 'id');
    }
    
    public function usarCredito(){
        return $this->hasMany('App\PedidoUsarCredito', 'pedido_id', 'id');
    }

    public function promotorVenda(){
        return $this->hasOne('App\User', 'id', 'promotor_venda_id');
    }
    
    public function pedidoNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', ['numero', 'estabelecimento_codigo', 'operacao_codigo'], ['pedido_gerado', 'estabelecimento_pad', 'codigo_operacao']);
    }

    public function pedidoNasajonAberto(){
        return $this->hasOne('App\PedidosVendaNasajon', ['numero', 'estabelecimento_codigo'], ['pedido_gerado', 'estabelecimento_pad'])->whereIn('situacao_descricao', ['Aberto','Em Faturamento','Em separação'])->where('grupodeoperacao_pedido','=','VENDA');
    }

    public function pedidoPrePago(){
        return $this->hasOne('App\PedidosPrePago', 'pedido_id', 'id');
    }

    public function pedidoRjSp(){
        return $this->hasOne('App\PedidoRjSp', 'pedido_id', 'id');
    }

    public function pedidoTransferenciaRjSp(){
        return $this->hasOne('App\PedidoRjSp', 'pedido_transferencia_id', 'id');
    }

    public function clicksignDocumento(){
        return $this->hasOne('App\ClicksignDocumento', 'id','clicksign_documentos_id');
    }

    public function detalhesNecessidadeCompras(){
        return $this->hasOne('App\NecessidadeCompras', 'id','necessidades_compras_id');
    }

    public function carrinhoCompras(){
        return $this->hasOne('App\CarrinhoCompra', 'pedido_id', 'id');
    }

    public function pagamentoPix(){
        return $this->hasOne('App\PagamentoPixNasajon', 'pix_id','pix_id');
    }

    public function pagamentosStone(){
        return $this->hasMany('App\StoneTransacoesPedido', 'pedido_id','id');
    }

    public function tempoEspera(){
        return $this->hasOne('App\TempoEsperaSeparacaoPedido', 'pedido_id','id');
    }

    public function campanha(){
        return $this->hasMany('App\CampanhasAssociacaoPedido', 'pedido_id','id');
    }

    public function campanhaComissao(){
        return $this->hasMany('App\CampanhasComissaoCalculo', 'pedido_id','id');
    }
    public function campanhasPedido(){
        return $this->hasMany('App\CampanhasAssociacaoPedido', 'pedido_id','id');
    }

    public function pagamentosStoneTransacoesAvulsas(){
        return $this->hasMany('App\StoneRetornoTransacoesAvulsa', 'pedido_id','id');
    }
}
