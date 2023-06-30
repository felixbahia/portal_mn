<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use Auth;
use Carbon\Carbon;
use Exception;

use App\Vendedor;
use App\Produto;
use App\Cliente;
use App\Transportador;
use App\Vencimentos;
use App\TipoOperacao;
use App\FaturamentoItemNasajon;
use App\NotaVendaItemNasajon;
use App\ProdutoNasajon;
use App\FaturamentoNotaNasajon;
use App\ClienteNasajon;
use App\NotasNasajon;
use App\PedidoVenda;
use App\ConfirmacaoNotaSaida;
use App\PedidosPrePago;
use App\TitulosEmAbertoNasajon;

use Illuminate\Support\Facades\Storage;

class HistoricoDeVendasController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\HistoricoDeVendas") === false){
            return abort(403);
		}

		if(Auth::user()->hasRole('Cliente')){

			$cliente = ClienteNasajon::where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), Auth::user()->username)
				->first();

			$cliente_nome = $cliente->nome . ' - ' . $cliente->cpf_cnpj;
		}
		else{
			$cliente_nome = '';
		}
		
		$tipo_operacao = $this->getTipoOperacao();

        $request->session()->flash('model', 'App\HistoricoDeVendas');
    	return view("programs.historico_vendas.index")->with(['tipo_operacao' => $tipo_operacao, 'cliente_nome' => $cliente_nome]);
    }

    public function acessaNotas($estabelecimento, $data = null){

    	if (is_null($data)){
    		$timestamp = time();
    	}
    	else{
    		$timestamp = strtotime($data);
    	}
    	$table = 'DUM' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";
    	if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table)){
    		return DB::connection('srv_prologos')->table($table);
    	}
    	else{
    		return null;
		}
    }

    public function acessaItensNotas($estabelecimento, $data){

    	if (is_null($data)){
    		$timestamp = time();
    	}
    	else{
    		$timestamp = strtotime($data);
    	}

    	$table = 'DUI' . str_pad($estabelecimento, 2, "0", STR_PAD_LEFT) . "_" .  date("y", $timestamp) . date("m", $timestamp) . "2";

    	if (DB::connection('srv_prologos')->getSchemaBuilder()->hasTable($table)){
    		return DB::connection('srv_prologos')->table($table);
    	}
    	else{
    		return null;
    	}
    }

    public function listaNotas($estabelecimento, $cliente, $data = null, $tipo_operacao = '', $numero_nota = ''){

    	if(empty($data)){
    		$data = date("Y-m-d");
    	}

		$notas = [];

		if(empty($tipo_operacao)){
			$tipo_operacao = 'todas';
		}

		$tipo_operacao = $this->getOperacaoPrologos($tipo_operacao);

		if (!is_null($this->acessaNotas($estabelecimento, $data))){
    	   	$temp_array = $this->acessaNotas($estabelecimento, $data)
    	   		->select('*')
    	   		->selectRaw("'". $estabelecimento . "' as estabelecimento")
    	   		->whereIn('CODCAD', $cliente)
				->where('STATDOC', '!=', "C");
				   
			if(empty($tipo_operacao)){
				$temp_array = $temp_array
				->whereIn('TIPOPER', $tipo_operacao);
			}  
			if(!empty($numero_nota)){
				$temp_array = $temp_array
				->where('NF_NUMNF', $numero_nota);
			}

			if(Auth::user()->tipo_usuario_id === 12){
				if(!empty(Auth::user()->codigo_representante)){
					$temp_array->where('CODVND', Auth::user()->codigo_representante);
				}
				else{
					$temp_array->where('CODVND', null);
				}
			}
			
			$temp_array = $temp_array
    	   		->get()
    	   		->toArray();

    	   $notas = array_merge($notas, $temp_array);
			
		}

    	$data = date('Y-m-d', strtotime($data . "+1 month"));

    	return $notas;
    }

    public function filter(Request $request){
        ini_set('memory_limit', '1024M');
    	$fields = $request->only(['cliente', 'cliente_nome', 'data_inicio', 'data_fim', 'tipo_operacao', 'numero_nota']);
		
		$result = [];
		if(empty($fields['tipo_operacao'])){
			$fields['tipo_operacao'] = 'todas';
		}
		$estabelecimentos = returnEmpresasNasajonView();
		$clientes = [];
		$clienteObj = null;
		$clienteNasajonObj = null;

		$notasNasasjonObj = NotasNasajon::query()->with(['ocorrenciaEntrega', 'revisao_vendedor_comissao', 'confirmacaoNotaSaida']);

		if(Auth::user()->tipo_usuario_id == 21){
			$clienteNasajonObj = ClienteNasajon::select('codigo', 'cpf_cnpj')->
				where(DB::raw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '')"), preg_replace("/[\._\/-]/", '', Auth::user()->username))->
				get();
		}
		else if(!empty($fields['cliente_nome'])){
			$clienteNasajonObj = ClienteNasajon::select('codigo', 'cpf_cnpj')->
				where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', '%'.strtolower($fields['cliente_nome']).'%')->
				get();

		}else{
			$clientes = [$fields['cliente']];
		}

		$numero_nota = null;
		if(!empty($fields['numero_nota'])){
			$notasNasasjonObj->where('numero', 'ilike', '%'.trim($fields['numero_nota']));
			$numero_nota = trim($fields['numero_nota']);
		}

		$tipo_operacao_nasajon = $this->getOperacaoNasajon($fields['tipo_operacao']);

		if(!is_null($clienteNasajonObj)){
			$notasNasasjonObj->whereIn('cliente_documento', $clienteNasajonObj->pluck('cpf_cnpj'));
		}
		if(
			!empty($fields['data_inicio']) ||
			!empty($fields['data_fim'])
		){
			if(!empty($fields['data_inicio'])){
				$data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
			}else{
				$data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
				$data_inicio->day = 1;
			}

			if(!empty($fields['data_fim'])){
				$data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
			}else{
				$data_fim = Carbon::now();
			}
			$notasNasasjonObj->whereBetween('datasaida', [$data_inicio->format('Y-m-d'), $data_fim->format('Y-m-d')]);

		}else{
			$data_inicio = Carbon::now();
			$data_inicio->day = 1;
			$data_fim = Carbon::now();
			$notasNasasjonObj->whereBetween('datasaida', [$data_inicio->format('Y-m-d'), $data_fim->format('Y-m-d')]);
		}
		if($fields['tipo_operacao'] != 'todas'){
			$notasNasasjonObj->whereIn('operacao_codigo', $tipo_operacao_nasajon);
		}

		if(in_array(Auth::user()->tipo_usuario_id, [12, 16]) && Auth::user()->codigo_representante != '998'){
			if(Auth::user()->codigo_representante){
				$notasNasasjonObj->whereHas('faturamento', function($query){
					$query->where('Vendedor - Código', Auth::user()->codigo_representante);
				});
			}
			else{
				$notasNasasjonObj->whereHas('faturamento', function($query){
					$query->where('Vendedor - Código', null);
				});
			}
		}
		$notasNasasjonObj = $notasNasasjonObj->get();

		$response = [];

		$notasNasasjonObj->each(function($nota_nasajon) use (&$response, $estabelecimentos){
			$query = $nota_nasajon->confirmacaoNotaSaida;
			if(!empty($query)){
				$data_saida = parserData($query->data_saida);

			}else{
				$data_saida = '';
			}
			if(isset($nota_nasajon->revisao_vendedor_comissao) && !is_null($nota_nasajon->revisao_vendedor_comissao)){
				$vendedor_nome = $nota_nasajon->revisao_vendedor_comissao->vendedor_nome;
			}
			else {
				$vendedor_nome = '';
			}

			if(isset($nota_nasajon->ocorrenciaEntrega->nota_id)){
				$nota_id_ocorrencia = true;
			}else{
				$nota_id_ocorrencia = false;
			}
			
			$response[] = [
				'id_nota' => $nota_nasajon->id,
				'nota_id_ocorrencia' =>  $nota_id_ocorrencia,
				'numnfe' => $nota_nasajon->numero,
				'dtemis' => parserData($nota_nasajon->emissao),
                'dtsaida' => $data_saida,
                'valtotdoc' => parserValor($nota_nasajon->valor),
                'nome_vendedor' => '<div><div data-toggle="tooltip" data-html="true" title="' . $vendedor_nome . '">' . $vendedor_nome . '</div></div>',
                'estabelecimento' => ($nota_nasajon->estabelecimento_descricao),
				'estabelecimento_nome' => '<div><div data-toggle="tooltip" data-html="true" title="' . ($estabelecimentos[(int) $nota_nasajon->estabelecimento_codigo]) . '">' . ($estabelecimentos[(int) $nota_nasajon->estabelecimento_codigo]) . '</div></div>',
				'origem' => 'nasajon',
				'tipo_operacao' => '<div><div data-toggle="tooltip" data-html="true" title="' . $nota_nasajon->naturezaoperacao . '">' .$nota_nasajon->naturezaoperacao . '</div></div>',
				'dt_doc' => parserData($nota_nasajon->emissao),
				'cliente' => $nota_nasajon->cliente_nome." - ".$nota_nasajon->cliente_documento,
			];
		});
    	return response()->json($response);
	}    
	
	public function dialog(Request $request){
		$fields = $request->only('estabelecimento', 'documento', 'data', 'hash', 'origem', 'num_nota', 'link_pedido', 'id_nota');
		
		$dados = [];
		$dados = $this->dadosDialogNasajon($fields);
		$itens_array = empty($dados)? '' : $dados['itens_array'];
		$header_nota_array = empty($dados)? '' : $dados['header_nota_array'];

    	return view("programs.historico_vendas.dialog")->with(['itens_array' => $itens_array, "header_nota_array" => $header_nota_array]);
	}

    public function dadosDialogPrologos($dados){
        if (!empty($dados['hash'])){

            $array = Crypt::decrypt($dados['hash']);

            $dados = [
                'estabelecimento' => $array[0],
                'documento' => $array[1],
                'data' => $array[2]
            ];            
		}

    	$header_nota = $this->acessaNotas($dados['estabelecimento'], str_replace("/", "-", $dados['data']))
    		->select('*')
			->selectRaw("'". $dados['estabelecimento'] . "' as estabelecimento")
			->where('NUMDOC', $dados['documento'])->first();
		$itens_nota = $this->acessaItensNotas($dados['estabelecimento'], str_replace("/", "-", $dados['data']))
	    	->where('NUMDOC', $dados['documento'])->get();
		
	    $clienteObj = Cliente::find($header_nota->CODCAD);
	    $vencimentoObj = Vencimentos::find($header_nota->CODVCT);
		$transportadoraObj = Transportador::find($header_nota->CODTRAN);
		
		$vendedorObj = Vendedor::select('CODVND', 'NOME')->find($header_nota->CODVND)->toArray();
		
		$tipo_operacao = TipoOperacao::find($header_nota->TIPOPER);

        $header_nota_array = [
			"nota_serie" => utf8_encode($header_nota->NF_NUMNF) . " / " . utf8_encode($header_nota->NF_SERIE), 
			"estabelecimento" => $header_nota->estabelecimento,
			"origem" => 'pedido',
            "natureza" => utf8_encode($tipo_operacao->IDENTIFICACAO),
            "nome" => utf8_encode($clienteObj->NOME),
            "cpf_cnpj" => utf8_encode($clienteObj->CGC_CPF),
            'data_emissao' => date("d/m/Y", strtotime(utf8_encode($header_nota->DTEMIS))),
            'data_saida' => date("d/m/Y", strtotime(utf8_encode($header_nota->DTSAIDA))),

            'base_calculo_icms' => empty($header_nota->BASECALC_ICMT1) ? '' : parserValor(utf8_encode($header_nota->BASECALC_ICMT1)),
            'valor_icms' => empty($header_nota->VALOR_ICMT1)? '' : parserValor(utf8_encode($header_nota->VALOR_ICMT1)),

            'base_calculo_substituicao' => empty($header_nota->BASECALC_ICMST) ? '': parserValor(utf8_encode($header_nota->BASECALC_ICMST)),
            'valor_substituicao' => empty($header_nota->VALOR_ICMST) ? '': parserValor(utf8_encode($header_nota->VALOR_ICMST)),

            'valor_total_frete' => empty($header_nota->TOTFRETE) ? '' : parserValor(utf8_encode($header_nota->TOTFRETE)),
            'valor_seguro' => empty($header_nota->TOTSEGURO) ? '' : parserValor(utf8_encode($header_nota->TOTSEGURO)),
            'valor_desconto' => empty($header_nota->TOTDESCONTO) ? '' : parserValor(utf8_encode($header_nota->TOTDESCONTO)),
            'valor_outras_despesas' => empty($header_nota->TOTOUTRAS) ? '' : parserValor(utf8_encode($header_nota->TOTOUTRAS)),

            'valor_total_produtos' => empty($header_nota->TOTMERCADORIA) ? '' : parserValor(utf8_encode($header_nota->TOTMERCADORIA)),
            "valor_total" => empty($header_nota->VALTOTDOC) ? '' : parserValor(utf8_encode($header_nota->VALTOTDOC)),

            'transportadora_nome' => !is_null($transportadoraObj) ? utf8_encode($transportadoraObj->NOME) : ' ',
            'transportadora_cnpj' => !is_null($transportadoraObj) ? (empty(preg_replace("/[\._\/-]/", '', $transportadoraObj->CGC)) ? '' : utf8_encode($transportadoraObj->CGC) ): ' ',

            'qtd_volumes' => utf8_encode($header_nota->QTDVOL),
            'volume_especie' => utf8_encode($header_nota->ESPVOL),
			'peso_liquido' => utf8_encode($header_nota->PESO_LIQUIDO),
			
			'vendedor' => implode($vendedorObj, ' - '),
			'comissao' => parserValor($header_nota->COMISSAO_VND)
		];


		if(isset($dados['link_pedido']) && $dados['link_pedido'] === 'true'){
			$pedidoVenda = PedidoVenda::select('NUMPED')->where('NUMULTNF', $header_nota->NF_NUMNF)->where('ESTABEL', $header_nota->estabelecimento)->first();

			if(!empty($pedidoVenda)){
				$header_nota_array['pedido'] = $pedidoVenda->NUMPED;
			}

		}

	    $itens_array = [];

	    foreach ($itens_nota as $value){
	    	$produto = Produto::find($value->CODPRD);

	    	$itens_array_temp = [
	    		"codigo" => utf8_encode($produto->CODPRD),
                "descricao" => utf8_encode($produto->DESCR),
                "ncm" => utf8_encode($produto->CLASFISC),
                "cst" => utf8_encode($value->CST),
                "cfo" => utf8_encode($value->CFO),
                "un" => utf8_encode($produto->UNIDADE_VND),
                "quantidade" => parserValor(utf8_encode($value->QTDE)),
                "preco_unitario" => parserValor($value->PRECOTOT / $value->QTDE),
                "valor_total" => parserValor(utf8_encode($value->PRECOTOT)),
                "base_icms" => parserValor(utf8_encode($value->BASECALC_ICM)),
                "valor_icms" => parserValor(utf8_encode($value->VALOR_ICM)),
                "valor_ipi" => parserValor(utf8_encode($value->VALOR_IPI)),
                "aliquota_icms" => utf8_encode($value->ALIQICM),
                "aliquota_ipi" => utf8_encode($value->ALIQIPI),
	    	];

	    	$itens_array[] = $itens_array_temp;

	    }
    	$retorno = [
			'itens_array' => $itens_array, 
			"header_nota_array" => $header_nota_array
		];

		return $retorno;
	}
	
	public function dadosDialogNasajon($dados){
		$itens_array = [];

		$query_itens = FaturamentoItemNasajon::select();
		if(isset($dados['num_nota']) && !empty($dados['num_nota'])){
			$query_itens->where('Número Documento', $dados['num_nota']);
			$query_itens->where('Estabelecimento', $dados['estabelecimento']);
		}else{
			$query_itens->where("Identificador Documento", $dados['id_nota']);
		}
		$itens_nota = $query_itens->get()->toArray();


		$desconto = 0;
		$valor_total_produtos = 0;

		foreach ($itens_nota as $value){
			$queryNota = NotaVendaItemNasajon::select();
			$queryNota->where('cod_produto', $value['Item - Código']);
			$queryNota->where('id_nota', $value['Identificador Documento']);
			$result_nota = $queryNota->first();
			if(!is_null($result_nota)){
				$result_nota = $result_nota->toArray();
				$base_icms = ($result_nota['base_icms']);
				$valor_icms = ($result_nota['valor_icms']);
				$valor_ipi = ($result_nota['valor_ipi']);
				$aliquota_icms = ($result_nota['aliquota_icms']);
				$aliquota_ipi = !empty($result_nota['aliquota_ipi']) ? $result_nota['aliquota_ipi'] : '';
			}else{
				$base_icms = '';
				$valor_icms = '';
				$valor_ipi = '';
				$aliquota_icms = '';
				$aliquota_ipi = '';
			}

			$produto = ProdutoNasajon::select()->where('codigo', $value['Item - Código'])->first()->toArray();

			$valor_total_produtos = $valor_total_produtos + $value['Item - Valor Total'];
			
	    	$itens_array_temp = [
	    		"codigo" => ($produto['codigo']),
                "descricao" => ($produto['especificacao']),
                "ncm" => ($produto['ncm']),
                "cst" => '000',
                "cfo" => ($value['Item - CFOP']),
                "un" => ($produto['unidade']),
                "quantidade" => parserValor(($value['Item - Quantidade'])),
                "preco_unitario" => parserValor($value['Item - Valor Total'] / $value['Item - Quantidade']),
                "valor_total" => parserValor(($value['Item - Valor Total'])),
                "base_icms" => parserValor($base_icms),
                "valor_icms" => parserValor($valor_icms),
                "valor_ipi" => parserValor($valor_ipi),
                "aliquota_icms" => $aliquota_icms,
                "aliquota_ipi" => $aliquota_ipi
	    	];

	    	$itens_array[] = $itens_array_temp;

		}

		$header_nota_array = [];

		$query = FaturamentoNotaNasajon::select();
		$query->with(['pedido' => function($query){
            $query->select();
		}])->with('nota_origem', 'pedido', 'pedido.pedido');
		if(isset($dados['num_nota']) && !empty($dados['num_nota'])){
			$query->where('Número Documento', $dados['num_nota']);
			$query->where('Estabelecimento', $dados['estabelecimento']);
		}else{
			$query->where("Identificador Documento", $dados['id_nota']);
		}
		$result = $query->first()->toArray();

		$clienteObj = ClienteNasajon::where('codigo', $result['Cliente'])->first()->toArray();
		$transportadora_cnpj = $result['Transportadora - CNPJ'];
		$transportadora_nome = $result['Transportadora - Nome'];

		$tipo_operacao = $result['Descrição da Operação'];

		if(isset($result['pedido']['pedido']['id']) && PedidosPrePago::where('pedido_nasajon_id', $result['pedido']['pedido']['id'])->exists()){
			$prepago = true;
		}
		else{
			$prepago = false;
		}

		$header_nota_array = [
            "nota_serie" => $result['Número Documento'], 
            "natureza" => ($tipo_operacao),
            "nome" => ($clienteObj['nome']),
            "cpf_cnpj" => ($clienteObj['cpf_cnpj']),
            'data_emissao' => date("d/m/Y", strtotime(($result['Data de Emissão']))),
            'data_saida' => date("d/m/Y", strtotime(($result['pedido']['data_saida']))),

            'base_calculo_icms' => '',
            'valor_icms' => '',

            'base_calculo_substituicao' => '',
            'valor_substituicao' => '',

            'valor_total_frete' => empty($result['pedido']['frete']) ? '' : parserValor(($result['pedido']['frete'])),
            'valor_seguro' => empty($result['pedido']['seguro']) ? '' : parserValor(($result['pedido']['seguro'])),
            'valor_desconto' => empty($result['Desconto']) ? '' : parserValor(($result['Desconto'])),
            'valor_outras_despesas' => empty($result['pedido']['outras']) ? '' : parserValor(($result['pedido']['outras'])),

            'valor_total_produtos' => parserValor($valor_total_produtos),
            "valor_total" => empty($result['Valor Documento']) ? '' : parserValor(($result['Valor Documento'])),

            'transportadora_nome' => $transportadora_nome?? '',
            'transportadora_cnpj' => $transportadora_cnpj? (empty(preg_replace("/[\._\/-]/", '', $transportadora_cnpj)) ? '' : ($transportadora_cnpj) ): ' ',

            'qtd_volumes' => ($result['Quantidade Volumes']),
            'volume_especie' => '',
			'peso_liquido' => parserValor($result['Peso Líquido']),
			
			'vendedor' => $result['Vendedor - Código'] . ' - ' . $result['Vendedor - Nome'],
			'comissao' => parserValor($result['Vendedor - Percentual Comissão']),

			'prepago' => $prepago
		];

		if($result['Descrição da Operação'] == 'Devolução de Venda de Mercadorias' && !empty($result['nota_origem'])){
			$header_nota_array['nota_original']['numero_documento'] = $result['nota_origem']['Número Documento'];
			$header_nota_array['nota_original']['estabelecimento'] = $result['nota_origem']['Estabelecimento'];
			$header_nota_array['nota_original']['data_emissao'] = parserData($result['nota_origem']['Data de Emissão']);
		}

		$retorno = [
			'itens_array' => $itens_array, 
			"header_nota_array" => $header_nota_array
		];

		return $retorno;
	}

	private function getTipoOperacao(){
		$tipo_operacao = [
			'todas' => 'Todas as Operações',
			'devolucao' => 'Devolução',
			'remessa' => 'Remessa',
			'transferencia' => 'Transferência',
			'venda' => 'Venda'
		];
		return $tipo_operacao;
	}

	private function getOperacaoNasajon($tipo_operacao){
		$operacao = [];
		$venda = [
			'PEDIDOISENTO',
			'PEDVENDATORO',
			'VENDA',
			'VENDAAORDEM',
			'VENDAISENTO',
			'VENDALOJAS',
			'VENDAORDEMTORO',
			'VENDAORGPUBLICO',
			'VENDATORO'
		];
		$remessa = [
			'REMESSAORDEM',
			'REMESSAORDEMTORO',
			'REMESSAORDEMTOROTERC',
			'REMARMAZMOVTO',
			'REMESSAORDEMSEMMOV',
		];
		$devolucao = [
			'DEVOLUCAODECOMPRA'
		];
		$transferencia = [
			'TRANSFERENCIAESTABELECIMENTOS',
			'TRANSFESTABSEMICMS',
		];
		switch($tipo_operacao){
			case 'venda':
				$operacao = $venda;
				break;
			case 'remessa':
				$operacao = $remessa;
				break;
			case 'devolucao':
				$operacao = $devolucao;
				break;
			case 'transferencia':
				$operacao = $transferencia;
				break;
			default:
				$operacao = array_merge($venda, array_merge($remessa, array_merge($devolucao, $transferencia)));
			break;
		}
		return $operacao;
	}

	private function getOperacaoPrologos($tipo_operacao){
		$operacao = [];
		switch($tipo_operacao){
			case 'todas':
				$operacao = [
					'SVR',
					'SVS',
					'SVD',
					'SV3',
					'SV0',
					'SV1',
					'SV!',
					'SVG',
					'SVH',
					'SVI',
					'SV%',
					'SVM',
					'SVN',
					'NSM',
					'NSN',
					'SV-',
					'SV;',
					'SVL',
					'SVX',
					'SV_',
					'NSW',
					'SV^',
					'SV+',
					'SV,',
					'SV(',
					'SV)',
					'SV}',
					'SVª',
					'SV>',
					'SVP',
					'SVV',
					'SVB',
					'SVE',
					'SV4',
					'SVJ',
					'SVZ',
					'SVT',
					'SVF',
					'SV2',
					'SV8',
					'SV9',
					'SV/',
					'SV#',
					'SVK',
					'SV5',
					'SV6',
					'SVC',
					'SVY',
					'SV7',
					'SOP',
					'SV@',
					'SOD',
					'SO7',
					'NSL',
					'SDO',
					'SOM',
					'SOQ',
					'SO!',
					'SOC',
					'SO8',
					'SOB',
					'SO&',
					'SOU',
					'SOS',
					'SO9',
					'SO=',
					'SVW',
					'SV&',
					'SO/',
					'SOE',
					'SOY',
					'SO)',
					'SV?',
					'SVU',
					'SO(',
					'SOT',
					'SOZ',
					'SOV',
					'SO*',
					'SO?',
					'SV<',
					'SO4',
					'SOH',
					'SO1',
					'SOW',
					'SOI',
					'SOF',
					'SOA',
					'NSR',
					'SO3',
					'SDL',
					'EDI',
					'EDB',
					'SD0',
					'SD1',
					'SD2',
					'SD6',
					'EDD',
					'EDG',
					'ED9',
					'EDA',
					'ED6',
					'SD7',
					'SD!',
					'SD3',
					'SD*',
					'SD4',
					'EDJ',
					'SD8',
					'SD#',
					'NSZ',
					'ED\\',
					'EDL',
					'ED1',
					'ED3',
					'SD5',
					'SDI',
					'ED/',
					'SDS',
					'SD9',
					'ED@',
					'ED#',
					'ED4',
					'ED5',
					'ED2',
					'ECO',
					'SO\\',
					'SO#',
					'EC+',
					'SO:',
					'SOL',
					'ECQ',
					'EC/',
					'SVQ',
					'SVO',
					'SV\\',
					'SO$',
					'ECT',
					'EOY',
					'SO%',
					'SVA'
				];
				break;
			case 'venda':
				$operacao = [
					'SVR',
					'SVS',
					'SVD',
					'SV3',
					'SV0',
					'SV1',
					'SV!',
					'SVG',
					'SVH',
					'SVI',
					'SV%',
					'SVM',
					'SVN',
					'NSM',
					'NSN',
					'SV-',
					'SV;',
					'SVL',
					'SVX',
					'SV_',
					'NSW',
					'SV^',
					'SV+',
					'SV,',
					'SV(',
					'SV)',
					'SV}',
					'SVª',
					'SV>',
					'SVP',
					'SVV',
					'SVB',
					'SVE',
					'SV4',
					'SVJ',
					'SVZ',
					'SVT',
					'SVF',
					'SV2',
					'SV8',
					'SV9',
					'SV/',
					'SV#',
					'SVK',
					'SV5',
					'SV6',
					'SVC',
					'SVY',
					'SV7'
				];
				break;
			case 'remessa':
				$operacao = [
					'SOP',
					'SV@',
					'SOD',
					'SO7',
					'NSL',
					'SDO',
					'SOM',
					'SOQ',
					'SO!',
					'SOC',
					'SO8',
					'SOB',
					'SO&',
					'SOU',
					'SOS',
					'SO9',
					'SO=',
					'SVW',
					'SV&',
					'SO/',
					'SOE',
					'SOY',
					'SO)',
					'SV?',
					'SVU',
					'SO(',
					'SOT',
					'SOZ',
					'SOV',
					'SO*',
					'SO?',
					'SV<',
					'SO4',
					'SOH',
					'SO1',
					'SOW',
					'SOI',
					'SOF',
					'SOA',
					'NSR',
					'SO3'
				];
				break;
			case 'devolucao':
				$operacao = [
					'SDL',
					'EDI',
					'EDB',
					'SD0',
					'SD1',
					'SD2',
					'SD6',
					'EDD',
					'EDG',
					'ED9',
					'EDA',
					'ED6',
					'SD7',
					'SD!',
					'SD3',
					'SD*',
					'SD4',
					'EDJ',
					'SD8',
					'SD#',
					'NSZ',
					'ED\\',
					'EDL',
					'ED1',
					'ED3',
					'SD5',
					'SDI',
					'ED/',
					'SDS',
					'SD9',
					'ED@',
					'ED#',
					'ED4',
					'ED5',
					'ED2'
				];
				break;
			case 'transferencia':
				$operacao = [
					'ECO',
					'SO\\',
					'SO#',
					'EC+',
					'SO:',
					'SOL',
					'ECQ',
					'EC/',
					'SVQ',
					'SVO',
					'SV\\',
					'SO$',
					'ECT',
					'EOY',
					'SO%',
					'SVA'
				];
				break;
			default:
				$operacao = ['SVR','SVS','SVD','SV3','SV0','SV1','SV!','SVG','SVH','SVI','SV%','SVM','SVN','NSM','NSN','SV-','SV;','SVL','SVX','SV_','NSW','SV^','SV+','SV,','SV(','SV)','SV}','SVª','SV>','SVP','SVV','SVB','SVE','SV4','SVJ','SVZ','SVT','SVF','SV2','SV8','SV9','SV/','SV#','SVK','SV5','SV6','SVC','SVY','SV7','SOP','SV@','SOD','SO7','NSL','SDO','SOM','SOQ','SO!','SOC','SO8','SOB','SO&','SOU','SOS','SO9','SO=','SVW','SV&','SO/','SOE','SOY','SO)','SV?','SVU','SO(','SOT','SOZ','SOV','SO*','SO?','SV<','SO4','SOH','SO1','SOW','SOI','SOF','SOA','NSR','SO3','SDL','EDI','EDB','SD0','SD1','SD2','SD6','EDD','EDG','ED9','EDA','ED6','SD7','SD!','SD3','SD*','SD4','EDJ','SD8','SD#','NSZ','ED\\','EDL','ED1','ED3','SD5','SDI','ED/','SDS','SD9','ED@','ED#','ED4','ED5','ED2','ECO','SO\\','SO#','EC+','SO:','SOL','ECQ','EC/','SVQ','SVO','SV\\','SO$','ECT','EOY','SO%','SVA'];
				break;
		}
		return $operacao;
	}

	public function exibirDocumentos(Request $request){

		$fields = $request->only('id_nota');
		
		$notaObj = NotasNasajon::with(['titulosAbertos'=> function($query){
			$query->orderBy('vencimento');
		}])->find($fields['id_nota']);

		$nota_info = collect();
		$foto_canhoto = '';

		$query = ConfirmacaoNotaSaida::select('foto_canhoto')->where('estabelecimento',$notaObj->estabelecimento_codigo)->where('nota', $notaObj->numero)->first();

		if(!empty($query->foto_canhoto) && Storage::exists($query->foto_canhoto)){
			$foto_canhoto = Storage::url($query->foto_canhoto);
		}

		$nota_info->id = $fields['id_nota'];
		$nota_info->foto_canhoto = $foto_canhoto;
		$nota_info->nota = $notaObj->numero . ' - ' . $notaObj->serie;
		$nota_info->cliente = $notaObj->cliente_nome . ' - ' . $notaObj->cliente_documento;
		$nota_info->estabelecimento_descricao = $notaObj->estabelecimento_codigo . ' - ' . $notaObj->estabelecimento_descricao;
		$nota_info->chave = $notaObj->chavene;
		$nota_info->boleto = collect();
		
		if($notaObj->titulosAbertos->isNotEmpty()){
			
			$nota_info->banco = $notaObj->titulosAbertos[0]->banco_nome;

			$data_boleto_nasajon = Carbon::createFromFormat('d-m-Y H:m:s','01-10-2022 00:00:00');
			$hoje = Carbon::now();
			
			foreach($notaObj->titulosAbertos as $titulo_dados){
				$boleto = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_boleto_titulo('" . $titulo_dados->titulo_id . "');");
				
				if(isset($boleto[0]->exportar_boleto_titulo) && !empty($boleto[0]->exportar_boleto_titulo) && $hoje->gte($data_boleto_nasajon)){
					$nota_info->banco_codigo = 'boleto_nasajon';
					
					$vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $titulo_dados->vencimento);
					$nota_info->boleto->push([
						'titulo' => $titulo_dados->numero,
						'nosso_numero' => '',
						'id' => encrypt($titulo_dados->titulo_id),
						'vencimento' => $vencimentoCarbon->format('d/m/Y'),
						'banco_codigo' => 'boleto_nasajon',
					]);

					if ($notaObj->estabelecimento_detalhes->raizcnpj == '05075884'){
						$nota_info->cnpj_cedente = '05075884000248';
		
					}
					else if($notaObj->estabelecimento_detalhes->raizcnpj == '06311274'){
						$nota_info->cnpj_cedente = '06311274000188';
					}

					$nota_info->cnpj_sacado =  preg_replace("/[\._\/-]/", '', $notaObj->cliente_documento);
					continue;
				}else if($titulo_dados->banco_codigo == 'BB'){
					
					if ($notaObj->estabelecimento_detalhes->raizcnpj == '05075884'){
						$nota_info->cnpj_cedente = '05075884000248';
		
					}
					else if($notaObj->estabelecimento_detalhes->raizcnpj == '06311274'){
						$nota_info->cnpj_cedente = '06311274000188';
					}

					$nota_info->cnpj_sacado =  preg_replace("/[\._\/-]/", '', $notaObj->cliente_documento);
					$nota_info->link = 'https://www63.bb.com.br/portalbb/boleto/boletos/hc21e,802,3322,10343.bbx';

					$vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $titulo_dados->vencimento);
					
					$nota_info->boleto->push([
						'titulo' => $titulo_dados->numero,
						'nosso_numero' => $titulo_dados->nossonumero,
						'vencimento' => $vencimentoCarbon->format('d/m/Y'),
						'banco_codigo' => $titulo_dados->banco_codigo,
					]);
				}
				else if($titulo_dados->banco_codigo == 'ITAU'){

					if ($notaObj->estabelecimento_detalhes->raizcnpj == '05075884'){
						$nota_info->cnpj_cedente = '05075884000248';

					}
					else if($notaObj->estabelecimento_detalhes->raizcnpj == '06311274'){
						$nota_info->cnpj_cedente = '06311274000188';
					}

					$nota_info->cnpj_sacado = preg_replace("/[\._\/-]/", '', $notaObj->cliente_documento);

					$vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $titulo_dados->vencimento);

					if(intval($titulo_dados->nossonumero) > 0){
						$nosso_numero = $titulo_dados->conta_agencia . $titulo_dados->conta_numero . $titulo_dados->conta_digito . '112' . str_replace('-', '',$titulo_dados->nossonumero);
					}
					else{
						$nosso_numero = 'Não processado, por favor tente mais tarde.';
					}

					$feriadoControllerObj = new FeriadoController;
					$feriado = $feriadoControllerObj->getFeriadosPeriodo($vencimentoCarbon->format('Y-m-d'),$vencimentoCarbon->format('Y-m-d'));
					if(count($feriado) == 1){
						$vencimentoCarbon->addDays(1);
					}

					if($vencimentoCarbon->dayOfWeekIso == 6){
						$vencimentoCarbon->addDays(2);

						$feriado = $feriadoControllerObj->getFeriadosPeriodo($vencimentoCarbon->format('Y-m-d'),$vencimentoCarbon->format('Y-m-d'));
						if(count($feriado) == 1){
							$vencimentoCarbon->addDays(1);
						}

					}
					elseif($vencimentoCarbon->dayOfWeekIso == 7){
						$vencimentoCarbon->addDays(1);

						$feriado = $feriadoControllerObj->getFeriadosPeriodo($vencimentoCarbon->format('Y-m-d'),$vencimentoCarbon->format('Y-m-d'));
						if(count($feriado) == 1){
							$vencimentoCarbon->addDays(1);
						}
					}

					$nota_info->boleto->push([
						'titulo' => $titulo_dados->numero,
						'nosso_numero' => $nosso_numero,
						'vencimento' => $vencimentoCarbon->format('d/m/Y'),
						'link' => $vencimentoCarbon->gte(Carbon::now()->format('Y-m-d')) ? 'https://www.itau.com.br/servicos/boletos/segunda-via' : 'https://www.itau.com.br/servicos/boletos/atualizar',
						'banco_codigo' => $titulo_dados->banco_codigo,
					]);
				}
				else if($titulo_dados->banco_codigo == 'BRADESCO'){
					if ($notaObj->estabelecimento_detalhes->raizcnpj == '05075884'){
						$nota_info->cnpj_cedente = '05075884000248';

					}
					else if($notaObj->estabelecimento_detalhes->raizcnpj == '06311274'){
						$nota_info->cnpj_cedente = '06311274000188';
					}

					$nota_info->cnpj_sacado = preg_replace("/[\._\/-]/", '', $notaObj->cliente_documento);
					$nota_info->link = 'https://www.ib12.bradesco.com.br/ibpfsegundaviaboleto/segundaViaBoletoPesquisarCPFCNPJ.do';

					$vencimentoCarbon = Carbon::createFromFormat('Y-m-d', $titulo_dados->vencimento);

					$nota_info->boleto->push([
						'titulo' => $titulo_dados->numero,
						'nosso_numero' => substr($titulo->nossonumero, 0, -1),
						'vencimento' => $vencimentoCarbon->format('d/m/Y'),
						'banco_codigo' => $titulo_dados->banco_codigo,
					]);
				}else{
					$nota_info->banco_codigo = $titulo_dados->banco_codigo;
				}

				if ($notaObj->estabelecimento_detalhes->raizcnpj == '05075884'){
					$nota_info->cnpj_cedente = '05075884000248';
	
				}
				else if($notaObj->estabelecimento_detalhes->raizcnpj == '06311274'){
					$nota_info->cnpj_cedente = '06311274000188';
				}

				$nota_info->cnpj_sacado = preg_replace("/[\._\/-]/", '', $notaObj->cliente_documento);
			}
			
		}

		$nota_info->boleto = $nota_info->boleto->unique()->values();

		return view("programs.historico_vendas.modal.documentos")->with(['nota_info' => $nota_info]);
	}

	public function testarDanfe(Request $request){
		
		$fields = $request->only('id');

		$id = $fields['id'];

		$notaObj = NotasNasajon::find($id);

		$danfe = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_danfe_docfis('" . $id . "');");

		if(!empty($danfe[0]->exportar_danfe_docfis)){
			return response()->json([
				'status' => 'success',
				'message' => 'Danfe disponível para download',
				'error' => [],
				'response' => []
			], 200);
		}
		else{
			return response()->json([
				'status' => 'error',
				'message' => 'Danfe indisponível!',
				'error' => [],
				'response' => []
			], 422);
		}
	}

	public function downloadPdf(Request $request){

		$fields = $request->only('id');

		$id = $fields['id'];

		$notaObj = NotasNasajon::find($id);

		$danfe = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_danfe_docfis('" . $id . "');");

		$danfe = \stream_get_contents($danfe[0]->exportar_danfe_docfis);

		return response()->make($danfe, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="pdf_' . $notaObj->numero . '_' . $notaObj->serie . '.pdf"'
		]);
	}

	public function downloadBoleto(Request $request){
		$id = $request->only(['id']);

		try{
			$id = decrypt($id['id']);
		}catch(Exception $e){
			return response()->json([
				'status' => 'error',
				'message' => 'Erro ao descriptografar.',
				'error' => [$e->getMessage()],
				'response' => []
			], 422);
		}

		$titulo = TitulosEmAbertoNasajon::where('titulo_id',$id)->first();

		if(empty($titulo)){
			return response()->json([
				'status' => 'error',
				'message' => 'Boleto Não Encontrado',
				'error' => [],
				'response' => []
			], 422);
		}

		$boleto = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_boleto_titulo('" . $id . "');");

		$pdf = \stream_get_contents($boleto[0]->exportar_boleto_titulo);

		return response()->make($pdf, 200, [
			'Content-Type' => 'application/pdf',
			'Content-Disposition' => 'attachment; filename="boleto_pdf_titulo_' . $titulo->numero . '.pdf"'
		]);
	}

	public function testarXml(Request $request){

		$fields = $request->only('id');

		$id = $fields['id'];

		$notaObj = NotasNasajon::find($id);

		$xml = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_xml_docfis('" . $id . "');");

		if(!empty($xml[0]->exportar_xml_docfis)){
			return response()->json([
				'status' => 'success',
				'message' => 'XML disponível para download',
				'error' => [],
				'response' => []
			], 200);
		}
		else{
			return response()->json([
				'status' => 'error',
				'message' => 'XML indisponível!',
				'error' => [],
				'response' => []
			], 422);
		}
	}

	public function downloadXml(Request $request){

		$fields = $request->only('id');

		$id = $fields['id'];

		$notaObj = NotasNasajon::find($id);

		$xml = DB::connection('nasajon')->select("SELECT * FROM integracoes.exportar_xml_docfis('" . $id . "');");

		$xml = \stream_get_contents($xml[0]->exportar_xml_docfis);

		return response()->make($xml, 200, [
			'Pragma' => 'public',
			'Expires' => '0',
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Content-Type' => 'text/xml',
			'Content-Disposition' => 'attachment; filename="xml_' . $notaObj->numero . '_' . $notaObj->serie . '.xml"',
			'Content-Transfer-Encoding' => 'binary',
		]);
	}
}
