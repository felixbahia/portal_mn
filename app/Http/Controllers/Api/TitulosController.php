<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Cliente;
use App\ContasReceber;
use App\ContasReceberBaixado;
use App\ChequesRecebido;
use App\FaturamentoOnline;
use App\Produto;
use App\TipoOperacao;
use App\Transportador;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests\Api\TtitulosBuscarRequest;

use App\Http\Controllers\HistoricoDeVendasController;

class TitulosController extends Controller
{

    public $successStatus = 200;
    public $errorStatus = 403;

    public function __construct() {
        $this->middleware(['auth:api']);
    }

    public function buscar(TtitulosBuscarRequest $request){
        header('Access-Control-Allow-Origin: *');
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
    	$fields = $request->toArray();


        $data_inicio = $fields['dataInicial'];
        $data_fim = $fields['dataFinal'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio);
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim);

        $ClienteObj = [];
        $ClienteObj = Cliente::find($fields['cliente']);

        $contas_a_receber = [];
        $cheques = [];
        $empresa = returnEmpresasPrologusView();
        if(strtolower($fields['tipo']) === 'emissao'){
            $contas_a_receber = ContasReceber::with('banco', 'portador')->select('*')
                ->where('CODCAD', $ClienteObj->CODCAD)
                ->whereBetween('DTEMIS', [$data_inicio, $data_fim])
                ->get()
                ->toArray();
            $cheques = ChequesRecebido::select('*')
                ->where('CODCAD', $ClienteObj->CODCAD)
                ->whereBetween('DTENTRADA', [$data_inicio, $data_fim])
                ->where(function($query){
                    $query->orWhereRaw('LEN(ACAO) = 0')
                        ->orWhereIn('ACAO',['1','2']);
                })
                ->get()
                ->toArray();
        }elseif(strtolower($fields['tipo']) === 'vencimento'){
            $contas_a_receber = ContasReceber::with('banco', 'portador')->select('*')
                ->where('CODCAD', $ClienteObj->CODCAD)
                ->whereBetween('DTVCTO', [$data_inicio, $data_fim])
                ->get()
                ->toArray();
            $cheques = ChequesRecebido::select('*')
                ->where('CODCAD', $ClienteObj->CODCAD)
                ->whereBetween('BOMPARA', [$data_inicio, $data_fim])
                ->where(function($query){
                    $query->orWhereRaw('LEN(ACAO) = 0')
                        ->orWhereIn('ACAO',['1','2']);
                })
                ->get()
                ->toArray();
        }

        $dados = [];
        $total = ['vencidos' =>  ['valor' => 0.0,'quantidade' => ''], 'avencer' => ['valor' => 0.0,'quantidade' => '']];
        foreach ($contas_a_receber as $key => $value) {
            $dataFinal = (strtotime($value['DTVCTO']) - strtotime(date('c'))) / 86400;
            if($dataFinal < 0){
                $dias_atraso = intval($dataFinal * -1);
            }else{
                $dias_atraso = '';
            }
            if(intval($dias_atraso)>0){
                $total['vencidos']['valor'] += floatval($value['VALOR']);
                $total['vencidos']['quantidade']++;
            }else{
                $total['avencer']['valor'] += floatval($value['VALOR']);
                $total['avencer']['quantidade']++;
            }
            $banco = '';
            $banco_popover = '';
            if(!is_null($value['portador'])){
                $banco = $value['portador']['PORTADOR'];
                if($banco === 'PINDURA'){
                    $banco = "CARTEIRA";
                }
            }elseif(!is_null($value['banco'])){
                $banco = $value['banco']['AGENCIA'];
            }
            $dados[] = [
                'id' => $value['NUMDOC'],
                'estabelecimento' => $empresa[intval($value["ESTABEL"])],
                'estabelecimento_not_parse' => intval($value["ESTABEL"]),
                'tipo' => 'Titulo',
                'nota' => '',
                'serie' => '',
                'parcela' => $value['NPARC'],
                'data_emissao' => parserData($value['DTEMIS']),
                'data_emissao_not_parse' => $value['DTEMIS'],
                'data_vencimento' => parserData($value['DTVCTO']),
                'valor' => parserValor($value['VALOR']),
                'dias_atraso'=> intval($dias_atraso),
                'banco' => $banco
            ];
        }
        foreach ($cheques as $key => $value) {
            $dataFinal = (strtotime($value['BOMPARA']) - strtotime(date('c'))) / 86400;
            if($dataFinal < 0){
                $dias_atraso = intval($dataFinal * -1);
            }else{
                $dias_atraso = '';
            }
            if(intval($dias_atraso)>0){
                $total['vencidos']['valor'] += floatval($value['VALOR']);
                $total['vencidos']['quantidade']++;
            }else{
                $total['avencer']['valor'] += floatval($value['VALOR']);
                $total['avencer']['quantidade']++;
            }
            $dados[] = [
                'id' => trim($value['NCHEQUE']),
                'estabelecimento' => $empresa[intval($value["ESTABEL"])],
                'estabelecimento_not_parse' => intval($value["ESTABEL"]),
                'tipo' => 'Cheque',
                'nota' => '',
                'serie' => '',
                'parcela' => '',
                'data_emissao' => parserData($value['DTENTRADA']),
                'data_emissao_not_parse' => $value['DTENTRADA'],
                'data_vencimento' => parserData($value['BOMPARA']),
                'valor' => parserValor($value['VALOR']),
                'dias_atraso'=> intval($dias_atraso),
                'banco' => $value['BANCO'],
            ];
        }
        $temp = array_values($dados);
        $total = count($temp);
        $dados = [];
        for ($i=intval($fields["offset"]); $i < (intval($fields["offset"]) + intval($fields["limit"])); $i++) {
        	if(!isset($temp[$i])){
        		continue;
        	}
        	$dados[] = $temp[$i];
        }
        if(count($dados) < 0){
        	$response = ['total' => $total, 'titulos' => [] ];
        	if($fields['count'] === 'false'){
        		unset($response['total']);
        	}

			$success = [
	            'error' =>[
	                "error" => false,
	                "msg" => [
	                    "dev" => "",
	                    "user" => ""
	                ]
	            ],
	            'request' => $fields,
	            "response" => $response
	        ];
			return response()->json($success, $this->successStatus); 
        }
        $datas_busca = [
            0 => [],
            1 => [],
            2 => [],
            3 => [],
            4 => [],
            5 => []
        ];
        foreach ($dados as $key => $value) {
            $datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][] = $value['id'];
        }
        $ObjTemps = [];
        $HistoricoDeVendasControllerObj = new HistoricoDeVendasController();
        foreach ($datas_busca as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $value) {
                $ObjTemps[$estabelecimento][$data] = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data);
            }
            
        }
        foreach ($ObjTemps as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $value) {
                if(is_null($value)){
                    continue;
                }
                $datas_busca[$estabelecimento][$data] = $value
                    ->select('NF_NUMNF as nf', 'NUMFAT as titulo', 'NF_SERIE as serie', 'NUMDOC as documento')
                    ->where('CODCAD', $ClienteObj->CODCAD)
                    ->whereIn('NUMFAT', $datas_busca[$estabelecimento][$data])
                    ->get()
                    ->toArray();
            }
        }

        foreach ($datas_busca as $estabelecimento => $estabelecimentos) {
            foreach ($estabelecimentos as $data => $notas) {
                foreach ($notas as $key => $value) {
                    $value = (array) $value;
                    $datas_busca[$estabelecimento][$data][$value['titulo']] = ["nota"=>$value['nf'],"serie"=>$value['serie']];
                    unset($datas_busca[$estabelecimento][$data][$key]);
                }
            }
        }

        foreach ($dados as $key => $value) {
        	foreach ($value as $k => $v) {
        		$dados[$key][$k] = trim($v);
        	}
            if(
                isset($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))]) &&
                array_key_exists($value['id'], $datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))])
            ){
                $dados[$key]['nota'] = trim($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][$value['id']]["nota"]);
                $dados[$key]['serie'] = trim($datas_busca[$value['estabelecimento_not_parse']][date('Y-m-01', strtotime($value['data_emissao_not_parse']))][$value['id']]["serie"]);
            }
            unset($dados[$key]['estabelecimento_not_parse']);
            unset($dados[$key]['data_emissao_not_parse']);
        }

    	$response = ['total' => $total, 'titulos' => $dados ];
    	if($fields['count'] === 'false'){
    		unset($response['total']);
    	}

		$success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => $fields,
            "response" => $response
        ];
		return response()->json($success, $this->successStatus); 
    }

    public function exibeNota(Request $request, $estabelecimento, $numero_nota, $serie_nota){

        header('Access-Control-Allow-Origin: *');
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '2024M');
        $tabela_nota = FaturamentoOnline::select('tabela_data', 'numero_documento')
            ->where('numero_nota', $numero_nota)
            ->where(DB::raw('CAST(estabelecimento AS int)'), intval($estabelecimento))
            ->where('serie_nota', $serie_nota)
            ->first();

        if (empty($tabela_nota)){
            $error = [
                'error' =>[
                    "error" => true,
                    "msg" => [
                        "dev" => "Nota não encontrada.",
                        "user" => "Nota não encontrada."
                    ]
                ],
                'request' => [],
                "response" => []
            ];
            return response()->json($error, 404);
        }

        $table = $tabela_nota->tabela_data;

        $nota = DB::connection('srv_prologos')->table($table)->where('NUMDOC', $tabela_nota->numero_documento)->first();
        $itens = DB::connection('srv_prologos')->table(str_replace("DUM", "DUI", $table))->where('NUMDOC', $tabela_nota->numero_documento)->get();

        $itens_consulta = [];

        foreach ($itens as $value) {

            $produto = Produto::find($value->CODPRD);

            $itens_consulta[] = [
                "codigo" => utf8_encode($produto->CODPRD),
                "descricao" => utf8_encode($produto->DESCR),
                "ncm" => utf8_encode($produto->CLASFISC),
                "cst" => utf8_encode($value->CST),
                "cfo" => utf8_encode($value->CFO),
                "un" => utf8_encode($produto->UNIDADE_VND),
                "quantidade" => utf8_encode($value->QTDE),
                "preco_unitario" => parserValor(utf8_encode($value->PRCUSTO_COMICMS)),
                "valor_total" => parserValor(utf8_encode($value->PRECOTOT)),
                "base_icms" => parserValor(utf8_encode($value->BASECALC_ICM)),
                "valor_icms" => parserValor(utf8_encode($value->VALOR_ICM)),
                "valor_ipi" => parserValor(utf8_encode($value->VALOR_IPI)),
                "aliquota_icms" => utf8_encode($value->ALIQICM),
                "aliquota_ipi" => utf8_encode($value->ALIQIPI),
            ];
        }

        $tipo_operacao = TipoOperacao::find($nota->TIPOPER);
        $clienteObj = Cliente::find($nota->CODCAD);
        $transportadoraObj = Transportador::find($nota->CODTRAN);

        $response = [
            "nota_serie" => utf8_encode($nota->NF_NUMNF) . " / " . utf8_encode($nota->NF_SERIE), 
            "natureza" => utf8_encode($tipo_operacao->IDENTIFICACAO),
            "nome" => utf8_encode($clienteObj->NOME),
            "cpf_cnpj" => utf8_encode($clienteObj->CGC_CPF),
            'data_emissao' => date("d/m/Y", strtotime(utf8_encode($nota->DTEMIS))),
            'data_saida' => date("d/m/Y", strtotime(utf8_encode($nota->DTSAIDA))),
            'base_calculo_icms' => empty($nota->BASECALC_ICMT1) ? '' : parserValor(utf8_encode($nota->BASECALC_ICMT1)),
            'valor_icms' => empty($nota->VALOR_ICMT1)? '' : parserValor(utf8_encode($nota->VALOR_ICMT1)),
            'base_calculo_substituicao' => empty($nota->BASECALC_ICMST) ? '': parserValor(utf8_encode($nota->BASECALC_ICMST)),
            'valor_substituicao' => empty($nota->VALOR_ICMST) ? '': parserValor(utf8_encode($nota->VALOR_ICMST)),
            'valor_total_frete' => empty($nota->TOTFRETE) ? '' : parserValor(utf8_encode($nota->TOTFRETE)),
            'valor_seguro' => empty($nota->TOTSEGURO) ? '' : parserValor(utf8_encode($nota->TOTSEGURO)),
            'valor_desconto' => empty($nota->TOTDESCONTO) ? '' : parserValor(utf8_encode($nota->TOTDESCONTO)),
            'valor_outras_despesas' => empty($nota->TOTOUTRAS) ? '' : parserValor(utf8_encode($nota->TOTOUTRAS)),
            'valor_total_produtos' => empty($nota->TOTMERCADORIA) ? '' : parserValor(utf8_encode($nota->TOTMERCADORIA)),
            "valor_total" => empty($nota->VALTOTDOC) ? '' : parserValor(utf8_encode($nota->VALTOTDOC)),
            'transportadora_nome' => isset($transportadoraObj->NOME)?utf8_encode($transportadoraObj->NOME):'',
            'transportadora_cnpj' => isset($transportadoraObj->CGC)?(empty(preg_replace("/[\._\/-]/", '', $transportadoraObj->CGC)) ? '' : utf8_encode($transportadoraObj->CGC)):'',
            'qtd_volumes' => utf8_encode($nota->QTDVOL),
            'volume_especie' => utf8_encode($nota->ESPVOL),
            'peso_liquido' => utf8_encode($nota->PESO_LIQUIDO),
        ];

        foreach ($response as $key => $value) {
            $response[$key] = trim($value);
        }


        $response['itens_nota'] = array_values($itens_consulta);

        $success = [
            'error' =>[
                "error" => false,
                "msg" => [
                    "dev" => "",
                    "user" => ""
                ]
            ],
            'request' => [],
            "response" => $response
        ];
        return response()->json($success, $this->successStatus);
    }

}
