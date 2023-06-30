<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use App\PedidoVenda;
use App\PedidoPortal;
use App\Cliente;
use App\User;
use App\TipoUsuario;
use App\LancamentoDebCredVendedor;
use App\FaturamentoNotaNasajon;
use App\ClienteNasajon;
use App\PedidosVendaNasajon;
use App\VendedorComissaoNota;
use App\PedidosPrePago;
use App\UnidadeNegocioMetaXUser;
use App\UnidadeNegocio;

use Auth;
use PDF;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\ComissaoRequest;
use App\Http\Requests\ComissaoDialogRequest;

use App\Http\Controllers\UserController;

class ComissaoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware(['auth']);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
		// Auth::loginUsingId(91);
		
        if(Auth::user()->hasPermissionTo("programas App\Comissao") === false){
            return abort(403);
        }
		$request->session()->flash('model', 'App\Comissao');
		
		$representantes = [];

        if(!in_array(Auth::user()->tipo_usuario_id,[12, 16, 22, 19, 14])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
            $representantes = [];
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }
        else if(Auth::user()->tipo_usuario_id == 19 || Auth::user()->tipo_usuario_id == 14){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->where('codigo_representante', '!=', '')
                ->where('responsavel', Auth::id())
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[Auth::user()->codigo_representante] = Auth::user()->codigo_representante . " - " . strtoupper(Auth::user()->name);

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[$representante->codigo_representante] = $representante->codigo_representante . " - " . strtoupper($representante->name);
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value->codigo_representante] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }


		$tiposObj = TipoUsuario::select('id', 'nome');
		$tiposObj->where('nome', 'Representante');
		$tiposObj->orWhere('nome', 'Vendedor Interno');
		$tiposObj->orWhere('nome', 'Agente de Venda');
		$result = $tiposObj->orderBy('nome')->get(); 
        $tipos = [
            '' => 'Todos tipos'
        ];
		
        foreach($result as $value){
            $tipos[$value->id] = $value["nome"];
		}

		$estabelecimentos = returnEmpresasNasajonView();
		unset($estabelecimentos[20]);
		
        return view('programs.comissao.index')->with(['representantes' => $representantes, 'tipos' => $tipos, 'estabelecimentos' => $estabelecimentos]);
	}
	
	public function filter(ComissaoRequest $request){
		ini_set('memory_limit', '1024M');
        set_time_limit(300);
		$fields = $request->only(['estabelecimento', 'data_inicio', 'data_fim', 'representantes', 'api', 'tipo']);

		$data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
		$data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);

		$prologos = [];
		$lancamento = $this->totalLancamento($fields);
		$nasajon = $this->filterNasajon($fields);
		$mesclar = $this->mesclarFilter($prologos, $lancamento, $nasajon);
		
		foreach($mesclar['titulos'] as $index => $representante){
			$meta = UnidadeNegocioMetaXUser::select();
			$meta->with(['detalhesUnidadeNegocioMeta' => function($query)use($data_fim){
				$query->where('data','<=', $data_fim);
			}, 'detalhesUnidadeNegocioMeta.detalhesUnidadeNegocio']);
			$meta->where('users_id', $representante['representante_not_parse']);
			$meta->whereHas('detalhesUnidadeNegocioMeta', function($query)use($data_fim){
				$query->where('data','<=', $data_fim);
			});
			$meta->orderBy('updated_at', 'desc');
			$meta = $meta->first();
			
			$mesclar['titulos'][$index]['equipe'] = empty($meta)? '' : $meta->detalhesUnidadeNegocioMeta->detalhesUnidadeNegocio->unidade;

			if(empty($meta)){
				$equipe = UnidadeNegocio::select();
				$equipe->where('users_id', $representante['representante_not_parse']);
				$equipe = $equipe->first();

				$mesclar['titulos'][$index]['equipe'] = empty($equipe)? '' : $equipe->unidade;
			}
		}

		$mesclar['exibir_comissao'] = false;

		$return = [
			'status' => 'success', /// success, error
			'message' => '', /// mensagem
			'error' => [],
			'response' => 
				$mesclar
		];
		return response()->json($return);
	}

    public function filterPrologos($fields){
        
    	$HistoricoDeVendasControllerObj = new HistoricoDeVendasController();
        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
		$data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';

        $data_inicio_temp = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->setTime(0,0,0);
		$data_fim_temp = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->setTime(0,0,0);
		$data_virada = Carbon::parse('2019-07-07')->setTime(0,0,0);


		if(
			$data_virada->gt($data_inicio_temp) == false &&
			$data_virada->gt($data_fim_temp) == false
		){
			return [
				'titulos' => [],
				'total' => []
			];
		}


        if(!in_array(Auth::user()->tipo_usuario_id,[12,16,22]) && (!isset($fields['api']) || $fields['api'] != true)) {
	        $codigo_representantes = [];
	        if(!empty($fields['representantes'])){
	        	$codigo_representantes[] = $fields['representantes'];
	        }else{
				$representantes_busca = User::select('codigo_representante');
				$representantes_busca->where('codigo_representante', '!=', '');
				if(!empty($fields['tipo'])){
					$representantes_busca->where('tipo_usuario_id', '=', $fields['tipo']);
				}
				$result = $representantes_busca->get()->toArray();
        		foreach ($result as $key => $value) {
					$codigo_representantes[] = $value['codigo_representante'];
		        }
				unset($representantes_busca);
	        }
	    }else{
			$codigo_representantes = [Auth::user()->codigo_representante];
	    }
		$notas = [];
		$notas_devolucao = [];
		$data = date('Y-m-d', strtotime("-1 month", strtotime($data_inicio)));
		if(strlen($fields['estabelecimento']) === 0){
			$estabelecimentos = returnEmpresasPrologusView();
			do {
				$data = date('Y-m-d', strtotime($data . "+1 month"));
				foreach($estabelecimentos as $key => $value){
					$estabelecimento = str_pad($key, 2, "0", STR_PAD_LEFT);
					if (!is_null($HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data))){
						$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
							->select('NUMDOC', 'FLAGCV', 'DTEMIS', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC', 'CODVND')
							->selectRaw("'". $estabelecimento . "' as estabelecimento")
							->whereIn('CODVND', $codigo_representantes)
							->whereIn('FLAGCV', ['+'])
							->where('STATDOC', '!=', "C")
							->whereBetween('DTEMIS', [$data_inicio, $data_fim])
							->get()
							->toArray();
						$notas = array_merge($notas, $temp_array);
						$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
							->select('NUMDOC', 'FLAGCV', 'DTMOV', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC', 'CODVND')
							->selectRaw("'". $estabelecimento . "' as estabelecimento")
							->whereIn('CODVND', $codigo_representantes)
							->whereIn('FLAGCV', ['-'])
							->where('STATDOC', '!=', "C")
							->whereBetween('DTMOV', [$data_inicio, $data_fim])
							->get()
							->toArray();
						$notas_devolucao = array_merge($notas_devolucao, $temp_array);
					}
				}
			}while(date('Ym', strtotime($data)) !==  date('Ym', strtotime($data_fim)));
		}else{
			$estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
			do {
				$data = date('Y-m-d', strtotime($data . "+1 month"));
				if (!is_null($HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data))){
					$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
						->select('NUMDOC', 'FLAGCV', 'DTEMIS', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC', 'CODVND')
						->selectRaw("'". $estabelecimento . "' as estabelecimento")
						->whereIn('CODVND', $codigo_representantes)
						->whereIn('FLAGCV', ['+'])
						->where('STATDOC', '!=', "C")
						->whereBetween('DTEMIS', [$data_inicio, $data_fim])
						->get()
						->toArray();
					$notas = array_merge($notas, $temp_array);
					$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
						->select('NUMDOC', 'FLAGCV', 'DTMOV', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC', 'CODVND')
						->selectRaw("'". $estabelecimento . "' as estabelecimento")
						->whereIn('CODVND', $codigo_representantes)
						->whereIn('FLAGCV', ['-'])
						->where('STATDOC', '!=', "C")
						->whereBetween('DTMOV', [$data_inicio, $data_fim])
						->get()
						->toArray();
					$notas_devolucao = array_merge($notas_devolucao, $temp_array);
				}
			}while(date('Ym', strtotime($data)) !==  date('Ym', strtotime($data_fim)));
		}
		$notas_representantes = [];
		foreach ($notas as $key => $nota) {
			$nota = (array) $nota;

			$comissao = floatval($nota['VALTOTDOC']) * (floatval($nota['COMISSAO_VND']) / 100);
			if(!isset($notas_representantes[$nota['CODVND']])){
				$notas_representantes[$nota['CODVND']] = [
					'base_comissao' => 0,
					'valor_comissao' => 0,
					'valor_devolucao' => 0,
					'valor_total' => 0
				];
			}
			$notas_representantes[$nota['CODVND']]['base_comissao'] += floatval($nota['VALTOTDOC']);
			$notas_representantes[$nota['CODVND']]['valor_comissao'] += $comissao;
			$notas_representantes[$nota['CODVND']]['valor_total'] += floatval($nota['VALTOTDOC']);
		}
		foreach ($notas_devolucao as $key => $nota) {
			$nota = (array) $nota;

			$comissao = floatval($nota['VALTOTDOC']) * (floatval($nota['COMISSAO_VND']) / 100);
			if(!isset($notas_representantes[$nota['CODVND']])){
				$notas_representantes[$nota['CODVND']] = [
					'base_comissao' => 0,
					'valor_comissao' => 0,
					'valor_devolucao' => 0,
					'valor_total' => 0
				];
			}
			$notas_representantes[$nota['CODVND']]['valor_comissao'] -= $comissao;
			$notas_representantes[$nota['CODVND']]['valor_devolucao'] += floatval($nota['VALTOTDOC']);
			$notas_representantes[$nota['CODVND']]['valor_total'] = floatval($notas_representantes[$nota['CODVND']]['valor_total']) - floatval($nota['VALTOTDOC']);
		}
		$result_titulos = [];
		
		$result_total = ['comissao' => 0.0, 'base_comissao' => 0.0, 'devolucao' => 0.0, 'total' => 0.0];
		foreach ($notas_representantes as $key => $nota) {
			$user = User::where('codigo_representante', $key)->first()->toArray();

			$result_total['base_comissao'] 	+= $nota['base_comissao'];
			$result_total['comissao'] 		+= $nota['valor_comissao'];
			$result_total['devolucao'] 		+= $nota['valor_devolucao'];
			$result_total['total'] 			+= $nota['valor_total'];
			
			$result_titulos[] = [
				'representante' => $user['name'],
				'representante_not_parse' => $user['id'],
				'cod_representante' => $user['codigo_representante'],
				'base_comissao' => ($nota['base_comissao']),
				'valor_comissao' => ($nota['valor_comissao']),
				'valor_devolucao' => (!empty($nota['valor_devolucao']))? '-'.($nota['valor_devolucao']) : ' ',
				'valor_total' => (floatval($nota['base_comissao']) - floatval($nota['valor_devolucao'])),
			];

		}

		$result_total = [
			'comissao' => parserValor($result_total['comissao']),
			'devolucao' => (!empty($result_total['devolucao']))? '-'.parserValor($result_total['devolucao']) : ' ',
			'total' => parserValor($result_total['total']),
			'base_comissao' => parserValor($result_total['base_comissao'])
		];

		$retorno = [
			'titulos' => $result_titulos,
			'total' => $result_total
		];

		return $retorno;
	}
	
	public function filterNasajon($fields){
		ini_set('memory_limit', '2024M');
        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];
		
        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d');
		$data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d');
        if(!in_array(Auth::user()->tipo_usuario_id,[12,16,22]) && (!isset($fields['api']) || $fields['api'] != true)) {
	        $codigo_representantes = [];
	        if(!empty($fields['representantes'])){
	        	$codigo_representantes[] = $fields['representantes'];
				$representantes_busca = User::where('codigo_representante', $fields['representantes']);
				$result = $representantes_busca->get()->toArray();
				foreach ($result as $key => $value) {
					$usuario[$value['codigo_representante']] = $value;
				}
	        }else{
				$representantes_busca = User::where('codigo_representante', '!=', '');
				if(!empty($fields['tipo'])){
					$representantes_busca->where('tipo_usuario_id', '=', $fields['tipo']);
				}
				$result = $representantes_busca->get()->toArray();
				foreach ($result as $key => $value) {
					$codigo_representantes[] = $value['codigo_representante'];
					$usuario[$value['codigo_representante']] = $value;
				}
				unset($representantes_busca);
	        }
	    }else{
			$codigo_representantes = [Auth::user()->codigo_representante];
			$representantes_busca = User::where('codigo_representante', $codigo_representantes);
			$result = $representantes_busca->get()->toArray();
			foreach ($result as $key => $value) {
				$usuario[$value['codigo_representante']] = $value;
			}
		}
		$notas = [];
		$notas_devolucao = [];
		
		$busca = array_search("001", $codigo_representantes);
		if($busca == 0){
			$busca++;
		}
		$clientes_exluir = ClienteNasajon::select('id')
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
			->get();
		$clientes_exluir = $clientes_exluir->pluck('id')->toArray();

		$temp_array = FaturamentoNotaNasajon::query();
		$temp_array->whereRaw('case
				when "TIPO" like \'DEVOLUÇÃO\' then
					"Data Lançamento" between \''.$data_inicio.'\' and \''.$data_fim.'\'
				else
					"Data de Emissão" between \''.$data_inicio.'\' and \''.$data_fim.'\'
				end');
		$temp_array->whereNotIn('Identificador Cliente', $clientes_exluir);
		if(strlen($fields['estabelecimento']) !== 0){
			$estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
			$temp_array->where('Estabelecimento', $estabelecimento);
		}

		$query_faturamento = str_replace(['?'], ['\'%s\''], $temp_array->toSql()); 
		$query_faturamento = vsprintf($query_faturamento, $temp_array->getBindings());

		$query_busca = 'with faturamento as('.$query_faturamento.')'.' select faturamento."Id_Nota", faturamento."Código da Operação", faturamento."Pedido - ID", faturamento."Valor Documento", vw_df_vendedores.vendedor_codigo as codigo_vendedor, vw_df_vendedores.vendedor_nome as nome_vendedor, vw_df_vendedores.percentual_comissao from faturamento left join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = faturamento."Id_Nota") where ';
		if(empty($busca)){
			$codigo_representantes_text = "'".implode("', '",$codigo_representantes)."'";
			$query_busca .= 'vw_df_vendedores.vendedor_codigo = any(array['.$codigo_representantes_text."])";
		}else{
			$codigo_representantes_text = "'".implode("', '",$codigo_representantes)."'";
			$query_busca .= '(vw_df_vendedores.vendedor_codigo = any(array['.$codigo_representantes_text.']) OR '.
				'vw_df_vendedores.vendedor_codigo IS NULL)';
		}
		$notas_temp = DB::connection('nasajon')->select($query_busca);
		unset($query_faturamento);
		$id_pedido = [];
		$array_pedidos_id = [];
		foreach($notas_temp as $nota){
			$nota = (array) $nota;
			if(!empty($nota['Pedido - ID'])){
				$id_pedido[] = $nota['Pedido - ID'];
			}

			if(!isset($array_pedidos_id[$nota['Pedido - ID']])){
				$array_pedidos_id[$nota['Pedido - ID']] = [];
			}
			$key = $nota['Id_Nota'];
			if(!empty($nota['codigo_vendedor'])){
				$key .= '-' . $nota['codigo_vendedor'];
			}
			$array_pedidos_id[$nota['Pedido - ID']][] = $key;
			$notas[$key] = $nota;
			if(!empty($nota['codigo_vendedor']) && $usuario[$nota['codigo_vendedor']]['tipo_usuario_id'] == 16){
				$percentual_comissao = $usuario[$nota['codigo_vendedor']]['comissao_a'];
			}else{
				$percentual_comissao = $nota['percentual_comissao'];
			}
			$percentual_comissao = $nota['percentual_comissao'];
			if(empty($nota['percentual_comissao'])){
				$percentual_comissao = 0;
			}
			$notas[$key]['revisao_comissao'] = ['percentual_comissao' => $percentual_comissao];
			$notas[$key]['pedido'] = ['pedido_pre_pago' => []];
			
		}
		unset($notas_temp);
		$PedidosPrePagoObj = PedidosPrePago::with('pedido.usuario_detalhes')->whereIn('pedido_nasajon_id', $id_pedido)->get();
		foreach($PedidosPrePagoObj as $PedidosPrePago){
			if(isset($array_pedidos_id[$PedidosPrePago->pedido_nasajon_id])){
				foreach($array_pedidos_id[$PedidosPrePago->pedido_nasajon_id] as $pedido_id){
					$notas[$pedido_id]['pedido']['pedido_pre_pago'] = $PedidosPrePago->toArray();
				}
			}
		}
		unset($array_pedidos_id);
		unset($PedidosPrePagoObj);
		$notas_representantes = [];
		$representantes = [];
		foreach ($notas as $key => $nota) {
			$nota = (array) $nota;
			if(empty($nota['codigo_vendedor'])){
				$nota['codigo_vendedor'] = '001';
			}
			$valor_documento = floatval($nota['Valor Documento']);
			if(!empty($nota['pedido']['pedido_pre_pago'])){
				$valor_documento = floatval($nota['Valor Documento']) * 2;
			}
			$comissao = floatval($valor_documento) * (floatval($nota['revisao_comissao']['percentual_comissao']) / 100);

			if(!isset($notas_representantes[$nota['codigo_vendedor']])){
				$notas_representantes[$nota['codigo_vendedor']] = [
					'base_comissao' => 0,
					'valor_comissao' => 0,
					'valor_devolucao' => 0,
					'valor_total' => 0
				];
			}
			if(substr($nota["Código da Operação"], 0, 5) == 'VENDA'){
				$notas_representantes[$nota['codigo_vendedor']]['base_comissao'] += floatval($valor_documento);
				$notas_representantes[$nota['codigo_vendedor']]['valor_comissao'] += $comissao;
				$notas_representantes[$nota['codigo_vendedor']]['valor_total'] += floatval($valor_documento);
			}
			if(substr($nota["Código da Operação"], 0, 3) == 'DEV'){
				$notas_representantes[$nota['codigo_vendedor']]['valor_comissao'] -= $comissao;
				$notas_representantes[$nota['codigo_vendedor']]['valor_devolucao'] += floatval($valor_documento);
				$notas_representantes[$nota['codigo_vendedor']]['valor_total'] -= floatval($valor_documento);
			}
			$representantes[] = $nota['codigo_vendedor'];
		}
		unset($notas);
		$representantes = array_unique($representantes);
		$representantes = User::whereIn('codigo_representante', $representantes)->get();
		$result_titulos = [];
		$result_total = ['comissao' => 0.0, 'base_comissao' => 0.0, 'devolucao' => 0.0, 'total' => 0.0];
		foreach ($notas_representantes as $key => $nota) {
			$user = $representantes->firstWhere('codigo_representante', $key)->toArray();

			$result_total['base_comissao'] 	+= $nota['base_comissao'];
			$result_total['comissao'] 		+= $nota['valor_comissao'];
			$result_total['devolucao'] 		+= $nota['valor_devolucao'];
			$result_total['total'] 			+= $nota['valor_total'];

			$result_titulos[] = [
				'representante' => $user['name'],
				'representante_not_parse' => $user['id'],
				'cod_representante' => $user['codigo_representante'],
				'base_comissao' => ($nota['base_comissao']),
				'valor_devolucao' => (!empty($nota['valor_devolucao']))? '-'.($nota['valor_devolucao']) : '',
				'valor_total' => (floatval($nota['base_comissao']) - floatval($nota['valor_devolucao'])),
			];

		}

		$result_total = [
			'devolucao' => (!empty($result_total['devolucao']))? '-'.parserValor($result_total['devolucao']) : '',
			'total' => parserValor($result_total['total']),
			'base_comissao' => parserValor($result_total['base_comissao'])
		];

		$retorno = [
			'titulos' => $result_titulos,
			'total' => $result_total
		];
		return $retorno;
	}
	
	public function mesclarFilter($prologos, $lancamento, $nasajon){
		$retorno = '';
		$tamanho = $nasajon? count($nasajon['titulos']): 0;
		$lancamento_total = 0.0;
		$result_titulos = [];
		if(!empty($prologos)){
			foreach($prologos['titulos'] as $value){
				$continue = true;
				$i = 0;
				while($continue && $i <= $tamanho){
					if(!empty($nasajon['titulos'][$i])){
						if($nasajon['titulos'][$i]['representante_not_parse'] == $value['representante_not_parse']){
							$valor_devolucao_prologos = trim($value['valor_devolucao'])?$value['valor_devolucao']:0.0;
							$valor_devolucao_nasajon = trim($nasajon['titulos'][$i]['valor_devolucao'])?$nasajon['titulos'][$i]['valor_devolucao']: 0.0;
							$valor_devolucao_total = $valor_devolucao_nasajon + $valor_devolucao_prologos?$valor_devolucao_nasajon + $valor_devolucao_prologos:'';
							$lancamento_valor = 0.0;
							
							if(!empty($lancamento['lancamento']['0'.$value['representante_not_parse']])){
								$lancamento_valor = $lancamento['lancamento']['0'.$value['representante_not_parse']];
								$lancamento_total = $lancamento_total + $lancamento_valor;
							}
							$valor_comissao = $value['valor_comissao'] + $nasajon['titulos'][$i]['valor_comissao'] + ($lancamento_valor);
							$result_titulos[] = [
								'representante' => $value['representante'],
								'representante_not_parse' => $value['representante_not_parse'],
								'cod_representante' => $value['cod_representante'],
								'base_comissao' => $value['base_comissao'] + $nasajon['titulos'][$i]['base_comissao'],
								'valor_devolucao' => $valor_devolucao_total,
								'valor_total' => $value['valor_total'] + $nasajon['titulos'][$i]['valor_total']
							];
							unset($nasajon['titulos'][$i]);
							$continue = false;
						} else if($i == $tamanho){
							$lancamento_valor = 0.0;
							if(!empty($lancamento['lancamento']['0'.$value['representante_not_parse']])){
								$lancamento_valor = $lancamento['lancamento']['0'.$value['representante_not_parse']];
								$lancamento_total = $lancamento_total + $lancamento_valor;
							}
							$result_titulos[] = [
								'representante' => $value['representante'],
								'representante_not_parse' => $value['representante_not_parse'],
								'cod_representante' => $value['cod_representante'],
								'base_comissao' => $value['base_comissao'],
								'valor_devolucao' => $value['valor_devolucao'],
								'valor_total' => $value['valor_total'],
							];
						}
					} else if($i == $tamanho){
						$lancamento_valor = 0.0;
						if(!empty($lancamento['lancamento']['0'.$value['representante_not_parse']])){
							$lancamento_valor = $lancamento['lancamento']['0'.$value['representante_not_parse']];
							$lancamento_total = $lancamento_total + $lancamento_valor;
						}
						$result_titulos[] = [
							'representante' => $value['representante'],
							'representante_not_parse' => $value['representante_not_parse'],
							'cod_representante' => $value['cod_representante'],
							'base_comissao' => $value['base_comissao'],
							'valor_devolucao' => $value['valor_devolucao'],
							'valor_total' => $value['valor_total'],
						];
					}
					$i++;
				}
			}
		}
		if(!empty($nasajon)){
			if(count($nasajon['titulos']) != 0){
				foreach($nasajon['titulos'] as $value){
					$lancamento_valor = 0.0;
					if(!empty($lancamento['lancamento'][$value['representante_not_parse']])){
						$lancamento_valor = $lancamento['lancamento'][$value['representante_not_parse']];
						$lancamento_total = $lancamento_total + $lancamento_valor;
					}
					$result_titulos[] = [
						'representante' => $value['representante'],
						'representante_not_parse' => $value['representante_not_parse'],
						'cod_representante' => $value['cod_representante'],
						'base_comissao' => $value['base_comissao'],
						'valor_devolucao' => $value['valor_devolucao'],
						'valor_total' => $value['valor_total'],
					];
				}
			}
		}
		if(empty($nasajon['titulos']) && !empty($lancamento['lancamento'])){
			foreach($lancamento['lancamento'] as $representante => $valor){
				$lancamento_valor = $valor;
				$lancamento_total = $lancamento_total + $lancamento_valor;
				
				$queryVendedor = User::select('name', 'codigo_representante');
				$queryVendedor->where('id', $representante);
				$resultVendedor = $queryVendedor->first();
				$result_titulos[] = [
					'representante' => $resultVendedor->name,
					'representante_not_parse' => $representante,
					'cod_representante' => $resultVendedor->codigo_representante,
					'base_comissao' => 0,
					'valor_devolucao' => 0,
					'valor_total' => 0,
				];
			}
		}
		
		$devolucao = $this->formatacaoNum($prologos['total']['devolucao']??0.0) + $this->formatacaoNum($nasajon['total']['devolucao']??0.0);
		$total = $this->formatacaoNum($prologos['total']['total']??0.0) + $this->formatacaoNum($nasajon['total']['total']??0.0);
		$base_comissao = $this->formatacaoNum($prologos['total']['base_comissao']??0.0) + $this->formatacaoNum($nasajon['total']['base_comissao']??0.0);

		$result_total = [
			'devolucao' => parserValor($devolucao),
			'total' => parserValor($total),
			'base_comissao' => parserValor($base_comissao)
		];

		
		$retorno = [
			'titulos' => $result_titulos,
			'total' => $result_total
		];

		return $retorno;
	}

	public function dialog(ComissaoDialogRequest $request){
		$fields = $request->only(['representante', 'data_inicio', 'data_fim', 'estabelecimento', 'api', 'tipo']);

		//$prologos = $this->dadosDialogPrologos($fields);
		$prologos = [];
		$nasajon = $this->dadosDialogNasajon($fields);
		$return = $this->mesclarDadosDialog($prologos, $nasajon);

		$data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
		$data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
		
		if($data_inicio->lte('2020-10-01') && $data_fim->lte('2020-10-01')){
			$exibir_comissao = true;
		}
		else{
			$exibir_comissao = false;
		}

        if(isset($fields['api'])){
        	return $return;
        }
        else{
			return view('programs.comissao.dialog')->with(['notas' => $return, 'codigo_representante' => $fields['representante'], 'exibir_comissao' => $exibir_comissao]);
        }
	}

    public function dadosDialogPrologos($fields){
    	$HistoricoDeVendasControllerObj = new HistoricoDeVendasController();

        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $codigo_representante = '';
        $representante = User::find($fields['representante'])->toArray();
        $codigo_representante = $representante['codigo_representante'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
		$data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';
		
		if($data_fim >= '2019-07-07'){
			return [
				'titulos' => [],
				'total' => [
					'comissao' => 0,
					'devolucao' => '',
					'valor_nota' => 0,
					'total' => 0
				]
			];
		}

    	$notas = [];
    	$notas_devolucao = [];
    	$data = date('Y-m-d', strtotime("-1 month", strtotime($data_inicio)));

    	if(strlen($fields['estabelecimento']) === 0){
			$estabelecimentos = returnEmpresasPrologusView();
	    	do {
	    		$data = date('Y-m-d', strtotime($data . "+1 month"));
	    		foreach($estabelecimentos as $key => $value){
		    		$estabelecimento = str_pad($key, 2, "0", STR_PAD_LEFT);
					if (!is_null($HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data))){
						$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
							->select('NUMDOC', 'FLAGCV', 'DTEMIS', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC')
							->selectRaw("'". $estabelecimento . "' as estabelecimento")
							->where('CODVND', $codigo_representante)
							->whereIn('FLAGCV', ['+'])
							->where('STATDOC', '!=', "C")
							->whereBetween('DTEMIS', [$data_inicio, $data_fim])
							->get()
							->toArray();
						$notas = array_merge($notas, $temp_array);
						$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
							->select('NUMDOC', 'FLAGCV', 'DTMOV', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC')
							->selectRaw("'". $estabelecimento . "' as estabelecimento")
							->where('CODVND', $codigo_representante)
							->whereIn('FLAGCV', ['-'])
							->where('STATDOC', '!=', "C")
							->whereBetween('DTMOV', [$data_inicio, $data_fim])
							->get()
							->toArray();
						$notas_devolucao = array_merge($notas_devolucao, $temp_array);
					}
		    	}
	    	}while(date('Ym', strtotime($data)) !==  date('Ym', strtotime($data_fim)));
    	}else{
	    	$estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
	    	do {
	    		$data = date('Y-m-d', strtotime($data . "+1 month"));
				if (!is_null($HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data))){
					$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
						->select('NUMDOC', 'FLAGCV', 'DTEMIS', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC')
						->selectRaw("'". $estabelecimento . "' as estabelecimento")
						->where('CODVND', $codigo_representante)
						->whereIn('FLAGCV', ['+'])
						->where('STATDOC', '!=', "C")
						->whereBetween('DTEMIS', [$data_inicio, $data_fim])
						->get()
						->toArray();
					$notas = array_merge($notas, $temp_array);
					$temp_array = $HistoricoDeVendasControllerObj->acessaNotas($estabelecimento, $data)
						->select('NUMDOC', 'FLAGCV', 'DTMOV', 'NF_NUMNF', 'CODCAD', 'COMISSAO_VND', 'VALTOTDOC')
						->selectRaw("'". $estabelecimento . "' as estabelecimento")
						->where('CODVND', $codigo_representante)
						->whereIn('FLAGCV', ['-'])
						->where('STATDOC', '!=', "C")
						->whereBetween('DTMOV', [$data_inicio, $data_fim])
						->get()
						->toArray();
					$notas_devolucao = array_merge($notas_devolucao, $temp_array);
				}
	    	}while(date('Ym', strtotime($data)) !==  date('Ym', strtotime($data_fim)));
	    }

	    $result_titulos = [];
	    $result_total = ['comissao' => 0.0, 'valor_nota' => 0.0, 'devolucao' => 0.0, 'total' => 0.0];

        $empresa = returnEmpresasPrologusView();
	    foreach ($notas as $key => $nota) {
	    	$nota = (array) $nota;
	    	$comissao = floatval($nota['VALTOTDOC']) * (floatval($nota['COMISSAO_VND']) / 100);
	    	$result_total['valor_nota'] += floatval($nota['VALTOTDOC']);

	    	$devolucao = 0;
	    	$total = 0;

	    	if($nota['FLAGCV'] === '+'){
	    		$result_total['comissao'] += $comissao;
	    		$result_total['total'] += floatval($nota['VALTOTDOC']);
	    	}
	    	$operado = '';
	    	if($nota['FLAGCV'] === '-'){
	    		$operado = $nota['FLAGCV'].' ';	    		
			}
	    	$total = 0;
			$cliente = Cliente::find($nota['CODCAD'])->toArray();
			
			$pedido_portal = '';

			$PedidoVenda = PedidoVenda::where('NUMULTNF', $nota['NF_NUMNF'])->where('ESTABEL', $nota['estabelecimento'])->first();

			if(!is_null($PedidoVenda)){
				$pedidoPortalObj = PedidoPortal::where('pedido_gerado', $PedidoVenda->NUMPED)->where('status_pedido', 3)->first();
				
				if(!is_null($pedidoPortalObj)){
					$pedido_portal = $pedidoPortalObj->id;
				}
			}

	    	$result_titulos[] = [
				'id' => null,
	    		'estabelecimento' => $empresa[intval($nota['estabelecimento'])],
	    		'estabelecimento_not_parse' => $nota['estabelecimento'],
	    		'data_emissao' => parserData($nota['DTEMIS']),
	    		'numero_documento' => $nota['NUMDOC'],
	    		'numero_nota' => $nota['NF_NUMNF'],
	    		'cliente' => utf8_decode($cliente['NOME']),
	    		'valor_nota' => parserValor($nota['VALTOTDOC']),
	    		'comissao' => parserValor($nota['COMISSAO_VND']),
	    		'valor_comissao' => parserValor($comissao),
				'valor_devolucao' => '',
				'total' => parserValor($nota['VALTOTDOC']),
				'pedido_portal' => $pedido_portal,
				'origem' => 'PROLOGOS',
				'vendedor_id' => '',
				'user_id' => ''
	    	];
		}
		
	    foreach ($notas_devolucao as $key => $nota) {
	    	$nota = (array) $nota;
	    	$comissao = floatval($nota['VALTOTDOC']) * (floatval($nota['COMISSAO_VND']) / 100);

	    	$devolucao = 0;
	    	$total = 0;

	    	if($nota['FLAGCV'] === '+'){
	    		$result_total['comissao'] += $comissao;
	    		$result_total['total'] += floatval($nota['VALTOTDOC']);
	    	}elseif($nota['FLAGCV'] === '-'){
	    		$result_total['comissao'] -= $comissao;
	    		$result_total['devolucao'] += floatval($nota['VALTOTDOC']);
	    		$devolucao = floatval($nota['VALTOTDOC']);
	    		$result_total['total'] -= floatval($nota['VALTOTDOC']);
	    	}
	    	$total = 0;
	    	$operado = '';
	    	if($nota['FLAGCV'] === '-'){
	    		$operado = $nota['FLAGCV'].' ';	    		
			}
				$cliente = Cliente::find($nota['CODCAD'])->toArray();
				$result_titulos[] = [
					'estabelecimento' => $empresa[intval($nota['estabelecimento'])],
					'estabelecimento_not_parse' => $nota['estabelecimento'],
					'data_emissao' => parserData($nota['DTMOV']),
					'numero_documento' => $nota['NUMDOC'],
					'numero_nota' => $nota['NF_NUMNF'],
					'cliente' => utf8_decode($cliente['NOME']),
					'valor_nota' => '',
					'comissao' => parserValor($nota['COMISSAO_VND']),
					'valor_comissao' => $operado.parserValor($comissao),
					'total' => "- ".parserValor($nota['VALTOTDOC']),
					'valor_devolucao' => (!empty($devolucao)) ? '-'.parserValor($devolucao) : '',
					'origem' => 'PROLOGOS'
				];
	    }
	    $result_total = [
	    	'comissao' => parserValor($result_total['comissao']),
	    	'devolucao' => (!empty($result_total['devolucao'])) ? '-'.parserValor($result_total['devolucao']) : '',
	    	'valor_nota' => parserValor($result_total['valor_nota']),
	    	'total' => parserValor((floatval($result_total['valor_nota']) - floatval($result_total['devolucao'])))
	    ];

        $return = [
			'titulos' => $result_titulos,
			'total' => $result_total
		];
		
		$exportar = [
			'notas' => $return,
			'codigo' => $fields['representante']
		];
		$exportar = encrypt($exportar);

        return $return;
	}

	public function dadosDialogNasajon($fields){
		$data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d');
		$data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d');

		$codigo_representante = '';
        $representante = User::find($fields['representante'])->toArray();
		$codigo_representante = $representante['codigo_representante'];
	

		$notas = [];
		$notas_devolucao = [];

		$clientes_exluir = ClienteNasajon::select('id')
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
			->get();
		$clientes_exluir = $clientes_exluir->pluck('id')->toArray();

		
		$busca = array_search("001", [$codigo_representante]);
		if($busca == 0){
			$busca++;
		}
		$temp_array = FaturamentoNotaNasajon::with('revisao_comissao', 'pedido', 'pedido.pedido', 'pedido.pedido.pedido_pre_pago')
			->whereNotIn("Identificador Cliente", $clientes_exluir)
			->whereRaw('case
				when "TIPO" like \'DEVOLUÇÃO\' then
					"Data Lançamento" between \''.$data_inicio.'\' and \''.$data_fim.'\'
				else
					"Data de Emissão" between \''.$data_inicio.'\' and \''.$data_fim.'\'
				end');
		if(strlen($fields['estabelecimento']) !== 0){
			$estabelecimento = str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT);
			$temp_array->where('Estabelecimento', $estabelecimento);
		}

		$query_faturamento = str_replace(['?'], ['\'%s\''], $temp_array->toSql()); 
		$query_faturamento = vsprintf($query_faturamento, $temp_array->getBindings());
		
		$query_busca = 'with faturamento as('.$query_faturamento.')'.' select *, vw_df_vendedores.vendedor as id_vendedor, vw_df_vendedores.vendedor_codigo as codigo_vendedor, vw_df_vendedores.vendedor_nome as nome_vendedor from faturamento left join integracoes.vw_df_vendedores on (vw_df_vendedores.id_docfis = faturamento."Id_Nota") left join "integracoes"."vw_pedidos_venda_v3" on (vw_pedidos_venda_v3.notafiscal_id = faturamento."Id_Nota") where ';
		if(empty($busca)){
			$query_busca .= 'vw_df_vendedores.vendedor_codigo = \''.$codigo_representante.'\'';
		}else{
			$query_busca .= '(vw_df_vendedores.vendedor_codigo = \''.$codigo_representante.'\' OR '.
				'vw_df_vendedores.vendedor_codigo IS NULL)';
		}
		$notas_temp = DB::connection('nasajon')->select($query_busca);
		unset($query_faturamento);

		$id_pedido = [];
		foreach($notas_temp as $nota){
			$nota = (array) $nota;
			if(!empty($nota['id'])){
				$id_pedido[] = $nota['id'];
			}
			$key = $nota['id'];
			if(empty($key)){
				$key = $nota['Id_Nota'];
			}
			$notas[$key] = $nota;
			if($representante['tipo_usuario_id'] == 16){
				$percentual_comissao = $representante['comissao_a'];
			}else{
				$percentual_comissao = $nota['percentual_comissao'];
			}
			if(empty($nota['percentual_comissao'])){
				$percentual_comissao = 0;
			}
			$notas[$key]['revisao_comissao'] = ['percentual_comissao' => $percentual_comissao];
			$notas[$key]['pedido'] = ['pedido_pre_pago' => []];
			$notas[$key]['pedido_id'] = $nota['id'];
		}
		unset($notas_temp);
		
		$PedidosPrePagoObj = PedidosPrePago::whereIn('pedido_nasajon_id', $id_pedido)->get();
		foreach($PedidosPrePagoObj as $PedidosPrePago){
			$notas[$PedidosPrePago->pedido_nasajon_id]['pedido']['pedido_pre_pago'] = $PedidosPrePago->toArray();
		}
		unset($PedidosPrePagoObj);
	    foreach ($notas as $key => $nota) {
			$nota = (array) $nota;
			if(substr($nota["Código da Operação"], 0, 5) == 'VENDA' || substr($nota["Código da Operação"], 0, 3) == 'DEV'){
				if(substr($nota["Código da Operação"], 0, 3) == 'DEV'){
					$notas_devolucao[] = $nota;
					unset($notas[$key]);
				}
			}else{
				unset($notas[$key]);
			}

		}

		$result_titulos = [];
		$result_total = ['comissao' => 0.0, 'valor_nota' => 0.0, 'devolucao' => 0.0, 'total' => 0.0];
		
		$empresa = returnEmpresasNasajonView();
	    foreach ($notas as $key => $nota) {
			$nota = (array) $nota;
			
			$id_pedido = '';
			$id_pedido = $nota['pedido_id'];
			if(!empty($nota['pedido']['pedido_pre_pago'])){
				$nota['Valor Documento'] = $nota['Valor Documento'] * 2;
			}
			$comissao = floatval($nota['Valor Documento']) * (floatval($nota['revisao_comissao']['percentual_comissao']) / 100);
			$result_total['valor_nota'] += floatval($nota['Valor Documento']);

			if(empty($notas['vendedor_codigo'])){
				$notas['vendedor_codigo'] = '001';
				$notas['vendedor_nome'] = 'sistema';
			}

	    	$devolucao = 0;
			$total = 0;
			
			$result_total['comissao'] += $comissao;
			$result_total['total'] += floatval($nota['Valor Documento']);
				
			$total = 0;

			$pedido_portal = '';
		
			$pedido = $nota['Pedido - Número'];

			$cupom = ($nota['Tipo do Documento'] == 'Cupom Fiscal(SAT)');

			if(empty($nota['Nome do Cliente'])){
				$nota['Nome do Cliente'] = '';
			}

	    	$result_titulos[] = [
				'id' => $id_pedido,
	    		'estabelecimento' => $empresa[intval($nota['Estabelecimento'])],
	    		'estabelecimento_not_parse' => $nota['Estabelecimento'],
	    		'data_emissao' => parserData($nota['Data de Emissão']),
	    		'numero_documento' => $nota['Pedido - Número'],
	    		'numero_nota' => $nota['Número Documento'],
	    		'cliente' => $nota['Nome do Cliente'],
	    		'valor_nota' => parserValor($nota['Valor Documento']),
	    		'comissao' => parserValor($nota['revisao_comissao']['percentual_comissao']),
	    		'valor_comissao' => parserValor($comissao),
				'valor_devolucao' => '',
				'total' => parserValor($nota['Valor Documento']),
				'pedido_portal' => $pedido,
				'origem' => 'NASAJON',
				'vendedor_id' => $nota['id_vendedor'],
				'user_id' => $representante['id'],
				'id_nota' => $nota['Id_Nota'],
				'tipo' => 'venda',
				'cupom' => $cupom
			];
			
		}

		foreach ($notas_devolucao as $key => $nota) {
			$nota = (array) $nota;
			
	    	$comissao = floatval($nota['Valor Documento']) * (floatval($nota['Vendedor - Percentual Comissão']) / 100);

	    	$devolucao = 0;
	    	$total = 0;

			$result_total['comissao'] -= $comissao;
			$result_total['devolucao'] += floatval($nota['Valor Documento']);
			$devolucao = floatval($nota['Valor Documento']);
			$result_total['total'] -= floatval($nota['Valor Documento']);
				
	    	$total = 0;
			$operado = '';
			$result_titulos[] = [
				'estabelecimento' => $empresa[intval($nota['Estabelecimento'])],
				'estabelecimento_not_parse' => $nota['Estabelecimento'],
				'data_emissao' => parserData($nota['Data Lançamento']),
				'numero_documento' => $nota['Pedido - Número'],
				'numero_nota' => $nota['Número Documento'],
	    		'cliente' => $nota['Nome do Cliente'],
				'valor_nota' => '',
				'comissao' => parserValor($nota['Vendedor - Percentual Comissão']),
				'valor_comissao' => $operado.parserValor($comissao),
				'total' => "- ".parserValor($nota['Valor Documento']),
				'valor_devolucao' => (!empty($devolucao)) ? '-'.parserValor($devolucao) : '',
				'origem' => 'NASAJON',
				'vendedor_id' => $nota['id_vendedor'],
				'user_id' => $representante['id'],
				'id_nota' => $nota['Id_Nota'],
				'tipo' => 'devolucao'
			];
	    }

		$result_total = [
	    	'comissao' => parserValor($result_total['comissao']),
	    	'devolucao' => (!empty($result_total['devolucao'])) ? '-'.parserValor($result_total['devolucao']) : '',
	    	'valor_nota' => parserValor($result_total['valor_nota']),
	    	'total' => parserValor((floatval($result_total['valor_nota']) - floatval($result_total['devolucao'])))
	    ];
		
        $return = [
			'titulos' => $result_titulos,
			'total' => $result_total
		];

		return $return;
	}

	public function mesclarDadosDialog($prologos, $nasajon){
		if(!empty($prologos) && !empty($nasajon)){
			$mesclarTitulos = array_merge($prologos['titulos'], $nasajon['titulos']);
			$mesclarTotal = [
				"comissao" => parserValor($this->formatacaoNum($prologos['total']['comissao']) + $this->formatacaoNum($nasajon['total']['comissao'])),
				"devolucao" => parserValor($this->formatacaoNum($prologos['total']['devolucao']) + $this->formatacaoNum($nasajon['total']['devolucao'])),
				"valor_nota" => parserValor($this->formatacaoNum($prologos['total']['valor_nota']) + $this->formatacaoNum($nasajon['total']['valor_nota'])),
				"total" => parserValor($this->formatacaoNum($prologos['total']['total']) + $this->formatacaoNum($nasajon['total']['total']))
			];
		}else if(!empty($prologos)){
			$mesclarTitulos = $prologos['titulos'];
			$mesclarTotal = [
				"comissao" => parserValor($this->formatacaoNum($prologos['total']['comissao'])),
				"devolucao" => parserValor($this->formatacaoNum($prologos['total']['devolucao'])),
				"valor_nota" => parserValor($this->formatacaoNum($prologos['total']['valor_nota'])),
				"total" => parserValor($this->formatacaoNum($prologos['total']['total']))
			];
		}else if(!empty($nasajon)){
			$mesclarTitulos = $nasajon['titulos'];

			$mesclarTotal = [
				"comissao" => empty($nasajon['total']['comissao'])? '' : parserValor($this->formatacaoNum($nasajon['total']['comissao'])),
				"devolucao" => empty($nasajon['total']['devolucao'])? '' : parserValor($this->formatacaoNum($nasajon['total']['devolucao'])),
				"valor_nota" => empty($nasajon['total']['valor_nota'])? '' : parserValor($this->formatacaoNum($nasajon['total']['valor_nota'])),
				"total" => empty($nasajon['total']['total'])? '' : parserValor($this->formatacaoNum($nasajon['total']['total']))
			];
		}
		
		$return = [
			'titulos' => $mesclarTitulos,
			'total' => $mesclarTotal
		];

		return $return;
	}

	private function ajusteCampoTabela($campo){
        $retorno = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='$campo'>$campo</div></div>";
        return $retorno;
	}

	private function totalLancamento($fields){
		$total_representante = 0.0;
		$total = 0.0;
		$credito = 0.0;
		$debito = 0.0;
		$data_inicio = $fields['data_inicio'];
		$data_fim = $fields['data_fim'];
		$lancamento_val = [];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
		$data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';
        if(Auth::user()->tipo_usuario_id != 12 && (!isset($fields['api']) || $fields['api'] != true)) {
	        $codigo_representantes = [];
	        if(!empty($fields['representantes'])){
				$representantes_busca = User::select('id');
				$representantes_busca->where('codigo_representante', '=', $fields['representantes']);
				$result = $representantes_busca->first();
				$codigo_representantes[] = $result->id;
	        }else{
				$representantes_busca = User::select();
				if(!empty($fields['tipo'])){
					$representantes_busca->where('tipo_usuario_id', '=', $fields['tipo']);
				}
				$result = $representantes_busca->get()->toArray();
        		foreach ($result as $key => $value) {
					$codigo_representantes[] = $value['id'];
				}
				unset($representantes_busca);
	        }
	    }else{
			$representantes_busca = User::select('id');
			$representantes_busca->where('codigo_representante', '=', Auth::user()->codigo_representante);
			$result = $representantes_busca->first();
			$codigo_representantes[] = $result->id;
		}
		$queryLancamento = LancamentoDebCredVendedor::select('codigo_vendedor', 'tipo', 'valor');
		$queryLancamento->whereIn('codigo_vendedor', $codigo_representantes);
		$queryLancamento->whereBetween('data_lancamento', [$data_inicio, $data_fim]);
		$Lancamentos = $queryLancamento->get();
		$lancamento_val = [];
		$total = 0;
		foreach($Lancamentos as $lancamento){
			if(empty($lancamento_val[$lancamento->codigo_vendedor])){
				$lancamento_val[$lancamento->codigo_vendedor] = 0;
			}
			if(trim($lancamento->tipo) == 'C'){
				$lancamento_val[$lancamento->codigo_vendedor] += $lancamento->valor;
			} else{
				$lancamento_val[$lancamento->codigo_vendedor] -= $lancamento->valor;
			}
			$total += $lancamento->valor;
		}
		$retorno = [
			'lancamento' => $lancamento_val,
			'total' => $total
		];
		return $retorno;
	}


	public function exportarCsv($notas, $lancamentos){
		$arquivo = "";
		$arquivo = $arquivo.$lancamentos['nome'].";".$lancamentos['representante'].";;Periodo;".$lancamentos['data_inicio']." ate ".$lancamentos['data_fim'].";\n";
		$arquivo = $arquivo."\n";
		$arquivo = $arquivo.'Estabelecimento;Data Emissão;Titulo;Nota;Cliente;Valor Vendido;Devolução;Total;Comissão %;Valor Comissão;'."\n";
		foreach($notas['titulos'] as $titulos){
			$arquivo = $arquivo.$titulos['estabelecimento'].";";
			$arquivo = $arquivo.$titulos['data_emissao'].";";
			$arquivo = $arquivo.$titulos['numero_documento'].";";
			$arquivo = $arquivo.$titulos['numero_nota'].";";
			$arquivo = $arquivo.$titulos['cliente'].";";
			$arquivo = $arquivo.$titulos['valor_nota'].";";
			$arquivo = $arquivo.$titulos['valor_devolucao'].";";
			$arquivo = $arquivo.$titulos['total'].";";
			$arquivo = $arquivo.$titulos['comissao'].";";
			$arquivo = $arquivo.$titulos['valor_comissao'].";";
			$arquivo = $arquivo."\n";
		}
		$arquivo = $arquivo.";;;;Total:;";
		$arquivo = $arquivo.$notas['total']['valor_nota'].";";
		$arquivo = $arquivo.$notas['total']['devolucao'].";";
		$arquivo = $arquivo.$notas['total']['total'].";";
		$arquivo = $arquivo.";";
		$arquivo = $arquivo.$notas['total']['comissao'].";";
		$arquivo = $arquivo."\n";
		
		if(!empty($lancamentos)){
			$arquivo = $arquivo."Lançamentos\n";
			$arquivo = $arquivo."Data;Documento;Motivo;Crédito;Débito;\n";
			foreach($lancamentos['dados'] as $lancamento){
				$arquivo = $arquivo.$lancamento['data'].";";
				$arquivo = $arquivo.$lancamento['documento'].";";
				$arquivo = $arquivo.$lancamento['motivo'].";";
				$arquivo = $arquivo.$lancamento['credito'].";";
				$arquivo = $arquivo.$lancamento['debito'].";";
				$arquivo = $arquivo."\n";
			}
			$arquivo = $arquivo.";;Total Apurado:;";
			$arquivo = $arquivo.$lancamentos['total']['credito'].";";
			$arquivo = $arquivo.$lancamentos['total']['debito'].";";
			$arquivo = $arquivo."\n";
			$arquivo = $arquivo.";;;;;;;;Total Comissão:;";
			$arquivo = $arquivo.$lancamentos['total']['comissao'].";";
			$arquivo = $arquivo;
		}
		
		return $arquivo;
	}

	public function gerarArquivoPdf(Request $request){
		$field = $request->only('exportar');
		try{
            $exportar = decrypt($field['exportar']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => $e,
                'response' => []
            ]);
		}
		$pdfFilePath = 'rep_'.$exportar['lancamentos']['representante'].'.pdf';
		$pdf = PDF::loadView(
			'pdf.comissao', 
			[
				'representante' => $exportar['lancamentos']['nome'],
				'cod_representante' => $exportar['lancamentos']['representante'],
				'notas' => $exportar['notas'],
				'data_inicio' => $exportar['lancamentos']['data_inicio'],
				'data_fim' => $exportar['lancamentos']['data_fim'],
				'lancamentos' => $exportar['lancamentos']
			], 
			[], 
			['title' => 'Comissão '.$exportar['lancamentos']['nome'], 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
		$pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);
	}

	private function formatacaoNum($value){
		return floatval(str_replace(",", ".", str_replace(".", "", $value)));
	}

}