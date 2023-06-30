<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\NotasEmAbertoNasajon;

class NotasEmAbertoNasajonController extends Controller
{
    public function exibirNota(Request $request){
        
        $fields = $request->only('id', 'link_pedido');

        $nota = NotasEmAbertoNasajon::with(['itens_nota', 'pedido', 'pedido.pedido_pre_pago', 'pedido.forma_pagamento'])->findOrfail($fields['id']);
        if (isset($nota->revisao_vendedor_comissao) && !empty($nota->revisao_vendedor_comissao)){
            $vendedor_codigo = $nota->revisao_vendedor_comissao->vendedor_codigo;
            $vendedor_nome = $nota->revisao_vendedor_comissao->vendedor_nome;
        }
        else{
            $vendedor_codigo = '';
            $vendedor_nome = '';
        }

        if(isset($nota->pedido->pedido_pre_pago) && !empty($nota->pedido->pedido_pre_pago)){
            $pre_pago = true;
        }
        else{
            $pre_pago = false;
        }
        $data_saida = '';
        $peso_confirmado = '';

        $forma_pagamento = '';
        $forma_pagamento_media = '';

        if(!empty($nota->pedido) && !empty($nota->pedido->forma_pagamento) && !empty($nota->pedido->forma_pagamento->condicao)){
            $forma_pagamento = $nota->pedido->forma_pagamento->condicao->descricao;
            $forma_pagamento_media = $nota->pedido->forma_pagamento->condicao->media;
        }
        $frete_pedido = '';
        if(!empty($nota->pedido->pedido_portal)){
            $frete_pedido = strtoupper($nota->pedido->pedido_portal->frete_preco);
        }

        $header_nota_array = [
            "nota_id" => $nota->id,
            "nota_serie" => $nota->numero . ' - ' . $nota->serie,
            "estabelecimento" => $nota->estabelecimento_codigo,
            "origem" => 'nasajon',
            "natureza" => $nota->naturezaoperacao . ' - '. $nota->operacao_descricao ,
            "nome" => $nota->cliente_nome,
            "cpf_cnpj" => $nota->cliente_documento,
            'data_emissao' => parserData($nota->emissao),
            'data_saida' => $data_saida,

            'base_calculo_icms' => parserValor($nota->baseicms),
            'valor_icms' => parserValor($nota->valoricms),

            'base_calculo_substituicao' => $nota->basesubst,
            'valor_substituicao' => parserValor($nota->valoricmsst),

            'valor_total_frete' => parserValor($nota->frete),
            'valor_seguro' => parserValor($nota->seguro),
            'valor_desconto' => parserValor($nota->total_desconto),
            'valor_outras_despesas' => parserValor($nota->outras),

            'valor_total_produtos' => parserValor($nota->total_produto_sem_desconto),
            "valor_total" => parserValor($nota->total_produto),

            'transportadora_nome' => $nota->transportadora_nome,
            'transportadora_cnpj' => $nota->transportadora_documento,

            'qtd_volumes' => $nota->volumes,
            'volume_especie' => '',
            'peso_liquido' => parserQtd3CasaDecimais($nota->pesoliquido) . ' KG',

            'vendedor_codigo' => $vendedor_codigo,
            'vendedor_nome' => $vendedor_nome,

            'pre_pago' => $pre_pago,

            'peso_confirmado' => $peso_confirmado,

            'forma_pagamento' => $forma_pagamento,
            'forma_pagamento_media' => $forma_pagamento_media,

            'frete_pedido' => $frete_pedido
        ];

        if(isset($fields['link_pedido']) && $fields['link_pedido'] === 'true'){
            $header_nota_array['pedido_id'] = $nota->pedido->id??null;
            $header_nota_array['pedido'] = $nota->pedido->numero??null;
        }

	    $itens_array = [];

	    foreach ($nota->itens_nota as $value){
	    	$itens_array[] = [
                "codigo" => $value->codigo,
                "descricao" => $value->especificacao,
                "ncm" => $value->ncm,
                "cst" => $value->valorsituacaotributariaicms,
                "cfo" => $value->cfop,
                "un" => $value->unidade,
                "quantidade" => parserValor($value->quantidadecomercial),
                "preco_unitario" => parserValor($value->valorunitariocomercial),
                "valor_total" => parserValor($value->valortotal),
                "base_icms" => parserValor($value->valorbaseicms),
                "valor_icms" => parserValor($value->valoricms),
                "valor_ipi" => parserValor($value->valoripi),
                "aliquota_icms" => ($value->valoraliquotaicms),
                "aliquota_ipi" => ($value->valoraliquotaipi),
            ];
	    }

        return view('programs.notas_nasajon.dialog')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array]);
    }
}
