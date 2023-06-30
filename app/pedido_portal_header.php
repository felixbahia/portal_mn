<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class pedido_portal_header extends Model
{

    protected $connection = 'srv_pedido';
    protected $table = 'pedido_portal_header';
    protected $primaryKey = 'id_app';

    public $timestamps = false;
    public $incrementing = false;

	public $fillable = ['estabelecimento', 'id_app', 'id_sequencial', 'data_pedido', 'tipo_operacao', 'codigo_cliente', 'codigo_vendedor', 'comissao_percentual', 'codigo_atendente','nome_comprador', 'numero_pedido_cliente', 'codigo_transportadora', 'codigo_local_entrega', 'forma_pagamento', 'codigo_vencimento', 'desconto_geral_porcentagem', 'valor_total_pedido', 'desconto_porcentagem_total', 'tipo_frete', 'frete_valor', 'seguro_valor', 'outros_valor', 'modalidade_negociacao', 'obs_vendedor', 'data_previsao_entrega', 'hora_previsao_entrega', 'msg_nf_1', 'msg_nf_2', 'msg_suframa', 'msg_dados_adicionais', 'valor_desconto', 'estabelecimento_faturamento', 'situacao_pedido', 'nf_referencia', 'codigo_cliente_referencia', 'ma_due_nf_referencia', 'data_nf_referencia', 'indicador_processamento', 'motivo_rejeicao', 'observacao_redespacho'];

	// 'redespacho', 'cod_transportadora_redespacho', 'frete_redespacho'
}
