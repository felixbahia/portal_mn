<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\NotasEntradasNasajon;
use App\PedidoComprasAssociacaoNotaNasajon;

use Auth;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class NotasEntradasNasajonController extends Controller
{
    public function exibirNota(Request $request){
        $fields = $request->only('id','codido_produto');

        $nota = NotasEntradasNasajon::select();
        if(isset($fields['codido_produto'])){
            $nota->with(['itens_nota' => function($query) use ($fields){
                $query->where('Item - Código', $fields['codido_produto']);
            },'itens' => function($query) use ($fields){
                $query->where('cod_produto', $fields['codido_produto']);
            }, 'condicaoDePagamento']);
        }else{
            $nota->with(['itens_nota', 'itens', 'condicaoDePagamento']);
        }
        $nota = $nota->where('Identificador Documento',$fields['id'])->first();
        if(empty($nota)){
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada!',
                'error' => '', 
                'response' => '',
            ], 422);
        }

        $header_nota_array = [
            "nota_numero" => $nota['Número do Documento'],
            "estabelecimento" => $nota['Estabelecimento'],
            "origem" => 'nasajon',
            "natureza" => $nota['Descrição da Operação'],
            "nome" => $nota['Nome do Fornecedor'],
            "cpf_cnpj" => $nota['CNPJ/CPF do Fornecedor'],
            'data_emissao' => parserData($nota['Data de Emissão']),
            'data_entrada' => parserData($nota['Data de Entrada']),
            
            'valor_icms_st' => parserValor($nota['Valor ICMS-ST']),

            'valor_total_frete' => parserValor($nota['Valor Frete']),
            'valor_seguro' => parserValor($nota['Valor Seguro']),
            'valor_desconto' => parserValor($nota['Desconto']),
            'valor_outras_despesas' => $nota['Valor Outras Despesas'],

            "valor_total" => $nota['Valor do Documento'],
            "valor_total_produtos" => 0,
            "valor_impotos" => 0,

            'transportadora_nome' => $nota['Nome da Transportadora'],
            'transportadora_cnpj' => $nota['CNPJ/CPF da Transportadora'],

            'qtd_volumes' => $nota['Quantidade Volumes'],
            'peso_liquido' => parserQtd3CasaDecimais($nota['Peso Líquido']) . ' KG',
            'forma_pagamento' => '',
            'pagamento_valor' => '',

            'valor_aframm' => 0,
            'valor_ii' => 0,
            'valor_pis' => 0,
            'valor_cofins' => 0,
        ];

        if(!empty($nota->condicaoDePagamento)){
            $header_nota_array['forma_pagamento'] = $nota->condicaoDePagamento->formapagamento_descricao;
    
            if($nota->condicaoDePagamento->parcelas_documento == 1){
                $header_nota_array['forma_pagamento'] .= ' à vista';
            }
            else{
                $header_nota_array['forma_pagamento'] .= ' x' . $nota->condicaoDePagamento->parcelas_documento . ' parcelas';
            }
        }
        else{
            $header_nota_array['forma_pagamento'] = '';
        }


        $itens_array = [];
        $valortotal = 0;
        
	    foreach ($nota->itens_nota as $value){
	    	$itens_array_temp = [
	    		"codigo" => $value['Item - Código'],
                "descricao" => $value['Item - Descricao'],
                "ncm" => '',
                "cst" => '',
                "cfo" => $value['Item - CFOP'],
                "un" => $value['Item - Unidade'],
                "quantidade" => parserValor($value['Item - Quantidade']),
                "preco_unitario" => parserValor($value['Item - Valor Unitário']),
                "valor_total" => parserValor($value['Item - Valor Total']),
                "base_icms" => '',
                "valor_icms" => '',
                "valor_ipi" => '',
                "aliquota_icms" => '',
                "aliquota_ipi" => '',
                "outras_despesas" => parserValor($value['Valor Out. Desp']),
                "aframm" => parserValor($value['Valor  AFRAMM']),
                "pis" => parserValor($value['Valor PIS']),
                "cofins" => parserValor($value['Valor COFINS']),
                "valor_ii" => parserValor($value['Valor II']),
	    	];
            $valortotal += $value['Item - Valor Total'];
	    	$itens_array[$value['Item - Código']] = $itens_array_temp;


			$header_nota_array['valor_aframm'] += $value['Valor  AFRAMM'];
			$header_nota_array['valor_ii'] += $value['Valor II'];
			$header_nota_array['valor_pis'] += $value['Valor PIS'];
			$header_nota_array['valor_cofins'] += $value['Valor COFINS'];

			unset($itens_array_temp);
        }
	    foreach ($nota->itens as $item){
			if(isset($itens_array[$item->cod_produto])){
				$itens_array[$item->cod_produto]['ncm'] = $item->ncm;
				$itens_array[$item->cod_produto]['cst'] = $item->valor_icms_st;
				$itens_array[$item->cod_produto]['base_icms'] = parserValor($item->base_icms);
				$itens_array[$item->cod_produto]['valor_icms'] = parserValor($item->valor_icms);
				$itens_array[$item->cod_produto]['valor_ipi'] = parserValor($item->valor_ipi);
				$itens_array[$item->cod_produto]['aliquota_icms'] = $item->aliquota_icms;
				$itens_array[$item->cod_produto]['aliquota_ipi'] = $item->aliquota_ipi;
			}else{
				$itens_array_temp = [
					"codigo" => $item->cod_produto,
					"descricao" => $item->desc_produto,
					"ncm" => $item->ncm,
					"cst" => $item->valor_icms_st,
					"cfo" => $item->cfop,
					"un" => $item->unidade,
					"quantidade" => parserValor($item->quantidade),
					"preco_unitario" => parserValor($item->valor_unitario),
					"valor_total" => parserValor($item->valor_total),
					"base_icms" => parserValor($item->base_icms),
					"valor_icms" => parserValor($item->valor_icms),
					"valor_ipi" => parserValor($item->valor_ipi),
					"aliquota_icms" => $item->aliquota_icms,
					"aliquota_ipi" => $item->aliquota_ipi,
					"outras_despesas" => '',
					"aframm" => '',
					"pis" => '',
					"cofins" => '',
					"valor_ii" => ''
				];
				$valortotal += $item->valor_total;
				$itens_array[$item->cod_produto] = $itens_array_temp;
			}
		}
		unset($nota);
        $valor_impotos = $header_nota_array['valor_total'] - $valortotal - $header_nota_array['valor_outras_despesas'];
        $header_nota_array['valor_total_produtos'] = parserValor($valortotal);
        $header_nota_array['valor_total'] = parserValor($header_nota_array['valor_total']);
        $header_nota_array['valor_outras_despesas'] = parserValor($header_nota_array['valor_outras_despesas']);
        $header_nota_array['valor_impotos'] = parserValor($valor_impotos);

        $header_nota_array['valor_aframm'] = parserValor($header_nota_array['valor_aframm']);
        $header_nota_array['valor_ii'] = parserValor($header_nota_array['valor_ii']);
        $header_nota_array['valor_pis'] = parserValor($header_nota_array['valor_pis']);
        $header_nota_array['valor_cofins'] = parserValor($header_nota_array['valor_cofins']);
        

        return view('programs.notas_entradas_nasajon.modal.dialog')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array]);
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ConsultaNotaEntrada") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaNotaEntrada');

        return view('programs.notas_entradas_nasajon.index');
    }

    public function filter(Request $request){

        $fields = $request->only('fornecedor', 'data_emissao_inicio', 'data_emissao_fim', 'numero_nota');

        $notasEntradasNajasonQuery = NotasEntradasNasajon::with('nota_entrada', 'nota_entrada.pedido');

        if(isset($fields['fornecedor']) && !empty($fields['fornecedor'])){
            $notasEntradasNajasonQuery->where(DB::Raw('CONCAT("Nome do Fornecedor", \' - \', "CNPJ/CPF do Fornecedor")'), 'ilike', '%'. $fields['fornecedor'] . '%');
        }

        if(isset($fields['data_emissao_inicio']) && !empty($fields['data_emissao_inicio'])){
            $notasEntradasNajasonQuery->where('Data de Emissão', '>=', Carbon::createFromFormat('d/m/Y', $fields['data_emissao_inicio'])->format('Y-m-d'));
        }

        if(isset($fields['data_emissao_fim']) && !empty($fields['data_emissao_fim'])){
            $notasEntradasNajasonQuery->where('Data de Emissão', '<=', Carbon::createFromFormat('d/m/Y', $fields['data_emissao_fim'])->format('Y-m-d'));
        }

        if(isset($fields['numero_nota']) && !empty($fields['numero_nota'])){
            $notasEntradasNajasonQuery->where('Número do Documento', $fields['numero_nota']);
        }        

        $notasEntradaNasajonObj = $notasEntradasNajasonQuery->get();

        $notasEntradaNasajonObj->each(function ($entrada) use (&$response){

            $linha = [];

            $linha['id'] = $entrada['Identificador Documento'];
            $linha['numero'] = $entrada['Número do Documento'];
            $linha['estabelecimento_nome'] = $entrada['Estabelecimento'] . ' - ' . $entrada['Nome do Estabelecimento'];
            $linha['fornecedor'] = $entrada['Nome do Fornecedor'] . ' - ' . $entrada['CNPJ/CPF do Fornecedor'];
            $linha['data_emissao'] = parserData($entrada['Data de Emissão']);
            $linha['data_entrada'] = parserData($entrada['Data de Entrada']);
            $linha['natureza_operacao'] = $entrada['Descrição da Operação'];
            if(isset($entrada->nota_entrada->pedido)){
                $linha['nota_compra'] = $entrada->nota_entrada->pedido->id_nota;
            }
            else{
                $linha['nota_compra'] = '';
            }
            $linha['valor_documento'] = parserValor($entrada['Valor do Documento']);

            $response[] = $linha;
        });

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $response
        ];

        return response()->json($response);
    }

    public function exibirNotaBusca(Request $request){
        $fields = $request->only('numero', 'estabelecimento','chave_nota','id_nota');
      
        if(!empty($fields['id_nota']) ){
   
            $nota['Identificador Documento'] = $fields['id_nota'];
        }else  if(!empty($fields['chave_nota']) ){
           
            $nota = NotasEntradasNasajon::where('Chave NE', $fields['chave_nota'])->whereNotIn('Identificador da Operação', ['0acc5b3f-bca6-44e3-8c3c-f5c9cf9389bf'])->first();
           
        }else{
            $nota = NotasEntradasNasajon::where('Número do Documento', $fields['numero'])->where('Estabelecimento', $fields['estabelecimento'])->first();
        }

        if(!empty($nota)){
            return $this->exibirNota(new Request(['id'=>$nota['Identificador Documento']]));
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
