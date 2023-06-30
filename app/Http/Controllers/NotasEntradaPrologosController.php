<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\TipoOperacao;
use App\Transportador;
use App\Produto;

use Illuminate\Http\Request;

use App\Http\Controllers\HistoricoDeVendasController;

class NotasEntradaPrologosController extends Controller
{
    function modal(Request $request){

        $fields = $request->only('estabelecimento', 'documento', 'data');
        
        $historicoDeVendasControllerObj = new HistoricoDeVendasController;

        $nota = $historicoDeVendasControllerObj->acessaNotas($fields['estabelecimento'], str_replace("/", "-", $fields['data']))
    		->select('*')
			->selectRaw("'". $fields['estabelecimento'] . "' as estabelecimento")
            ->where('NUMDOC', $fields['documento'])->first();
            
		$itens = $historicoDeVendasControllerObj->acessaItensNotas($fields['estabelecimento'], str_replace("/", "-", $fields['data']))
	    	->where('NUMDOC', $fields['documento'])->get();

        if(empty($nota)){
            return response()->json([
                'status' => 'error',
                'message' => 'Nota não encontrada!',
                'error' => '', 
                'response' => '',
            ]);
        }

        $fornecedorObj = Cliente::where('CODCAD', $nota->CODCAD)->first();
        $tipoOperacaoObj = TipoOperacao::where('TIPOPER', $nota->TIPOPER)->first();
        $transportadorObj = Transportador::where('CODTRAN', $nota->CODTRAN)->first();

        $header_nota_array = [];

        $header_nota_array["nota_numero"] = $nota->NF_NUMNF . '/'. $nota->NF_SERIE;
        $header_nota_array["estabelecimento"] = $nota->estabelecimento;
        $header_nota_array["natureza"] = $tipoOperacaoObj->DESCRICAO;
        $header_nota_array["nome"] = $fornecedorObj->NOME;
        $header_nota_array["cpf_cnpj"] = $fornecedorObj->CGC_CPF;
        $header_nota_array['data_emissao'] = parserData($nota->DTEMIS);
        $header_nota_array['data_entrada'] = parserData($nota->DTMOV);

        $header_nota_array['valor_icms_st'] = parserValor($nota->VALOR_ICMST);

        $header_nota_array['valor_total_frete'] = parserValor($nota->TOTFRETE);
        $header_nota_array['valor_seguro'] = parserValor($nota->TOTSEGURO);
        $header_nota_array['valor_desconto'] = parserValor($nota->TOTDESCONTO);
        $header_nota_array['valor_outras_despesas'] = parserValor($nota->TOTOUTRAS);

        $header_nota_array["valor_total"] = parserValor($nota->VALTOTDOC);

        if(!empty($transportadorObj)){
            $header_nota_array['transportadora_nome'] = $transportadorObj->NOME;
            $header_nota_array['transportadora_cnpj'] = $transportadorObj->CGC;
        }
        else{
            $header_nota_array['transportadora_nome'] = '';
            $header_nota_array['transportadora_cnpj'] = '';
        }

        $header_nota_array['qtd_volumes'] = $nota->QTDVOL;
        $header_nota_array['peso_liquido'] = parserQtd3CasaDecimais($nota->PESO_LIQUIDO) . ' KG';

        $itens_array = [];
        $valortotal = 0;

        $produtosObj = Produto::select('CODPRD', 'DESCR', 'UNIDADE_VND')
            ->whereIn('CODPRD', $itens->pluck('CODPRD'))->get();
        
	    $itens->each(function($item) use(&$itens_array, &$valortotal, $produtosObj){
            $linha = [];
            
            $produtoObj = $produtosObj->firstWhere('CODPRD', $item->CODPRD);

            $linha["codigo"] = $item->CODPRD;
            $linha["descricao"] = $produtoObj->DESCR;
            $linha["ncm"] = $item->NCM;
            $linha["cst"] = $item->CST;
            $linha["cfo"] = $item->CFO;
            $linha["un"] = $produtoObj->UNIDADE_VND;
            $linha["quantidade"] = parserValor($item->QTDE);
            $linha["preco_unitario"] = parserValor($item->PRECOTOT / $item->QTDE);
            $linha["valor_total"] = parserValor($item->PRECOTOT);
            $linha["base_icms"] = parserValor($item->BASECALC_ICMST);
            $linha["valor_icms"] = parserValor($item->VALOR_ICMST);
            $linha["valor_ipi"] = parserValor($item->VALOR_IPI);
            $linha["aliquota_icms"] = parserValor($item->ALIQICM);
            $linha["aliquota_ipi"] = parserValor($item->ALIQIPI);
	    	
            $valortotal += $item->PRECOTOT;
	    	$itens_array[] = $linha;
        });

        $header_nota_array['valor_total'] = parserValor($valortotal);
        

        return view('programs.notas_entradas_prologos.modal.dialog')->with(['header_nota_array' => $header_nota_array, 'itens_array' => $itens_array]);

    }
}
