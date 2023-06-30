<?php

namespace App\Http\Controllers;

use App\NotasNasajon;
use App\ConfirmacaoNotaSaida;
use App\NotasCfopNasajon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NotasNasajonController extends Controller
{
    public function exibirNota(Request $request){
        
        $fields = $request->only('id_nota', 'link_pedido');

        $nota = NotasCfopNasajon::with(['itens_nota', 'pedido', 'pedido.pedido_pre_pago', 'pedido.forma_pagamento', 'pedido.pedido_portal'])->findOrfail($fields['id_nota']);
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
        $foto_canhoto = '';

        $query = ConfirmacaoNotaSaida::select('data_saida', 'peso','foto_canhoto')->where('estabelecimento',$nota->estabelecimento_codigo)->where('nota', $nota->numero)->first();
        if(!empty($query)){
            $data_saida = parserData($query->data_saida);
            if(!empty($query->foto_canhoto) && Storage::exists($query->foto_canhoto)){
                $foto_canhoto = Storage::url($query->foto_canhoto);
            }
            if($query->peso > 0){
                $peso_confirmado = parserQtd3CasaDecimais($query->peso) . ' KG';
            }
            else{
                $peso_confirmado = '';
            }
            
        }else{
            $data_saida = '';
            $peso_confirmado = '';
        }
        
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
            "nota_serie" => $nota->numero . ' - ' . $nota->serie,
            "nota_id" => $nota->id,
            "foto_canhoto" => $foto_canhoto,
            "estabelecimento" => $nota->estabelecimento_codigo,
            "origem" => 'nasajon',
            "natureza" => $nota->naturezaoperacao,
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
        
        if(!empty($nota->pedido)){
            $header_nota_array['pedido_id'] = $nota->pedido->id??null;
            $header_nota_array['pedido'] = $nota->pedido->numero??null;
        }

	    $itens_array = [];

	    foreach ($nota->itens_nota as $value){
	    	$itens_array_temp = [
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

	    	$itens_array[] = $itens_array_temp;
	    }

        return view('programs.notas_nasajon.dialog')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array]);
    }

    public function exibirNotaBusca(Request $request){
        $fields = $request->only('numero', 'estabelecimento');

        $nota = NotasNasajon::where('numero', $fields['numero'])->where('estabelecimento_codigo', $fields['estabelecimento'])->first();
        if(!empty($nota)){
            return $this->exibirNota(new Request(['id_nota'=>$nota->id]));
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada',
                'error' => [],
                'response' => []
            ], 422);
        }
    }

    
}
