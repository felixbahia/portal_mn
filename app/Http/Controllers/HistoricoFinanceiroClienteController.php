<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\HistoricoFinanceiroCliente;
use App\HistoricoFinanceiroClienteTitulo;
use App\RetornoCobranca;
use App\ClienteNasajon;
use App\GrupoEmpresarial;
use App\TitulosEmAbertoNasajon;
use App\User;
use App\ContasReceberBaixadoNasajon;

use App\Http\Requests\HistoricoFinanceiroClienteAdicionarRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use Carbon\Carbon;
use Auth;

use App\Http\Controllers\EmailController;

class HistoricoFinanceiroClienteController extends Controller
{
    public function retornoHistorico(Request $request){
        $fields = $request->only(['cliente']);
        if(empty($fields['cliente'])){
            return response([], 422);
        }
        $cliente = $fields['cliente'];

        $ClienteNasajonObj = ClienteNasajon::where('codigo', $cliente)->where('bloqueado', false)->first();
        $raiz_cnpj = $ClienteNasajonObj->cpf_cnpj;
		if(strlen(trim($raiz_cnpj)) == 18){
			$raiz_cnpj = substr($raiz_cnpj, 0, 10);
        }
        unset($ClienteNasajonObj);
        $cnpj = [];
        $clientes = $this->grupoClienteGrupo($cliente);
        foreach($clientes as $cliente){
            if(strlen(trim($cliente)) == 18){
                $cliente = substr($cliente, 0, 10);
            }
            $cnpj[] = $cliente;
        }
        $HistoricoFinanceiroClienteObj = HistoricoFinanceiroCliente::with(['retornoCobranca', 'criadoPor', 'titulos'])->
            whereIn('cliente_raiz_cnpj', $cnpj)->
            get();
        $retorno = [];
        foreach($HistoricoFinanceiroClienteObj as $historico){
            $titulos =  '<b>Quantidade Titulos em aberto: </b>'.$historico->titulos_em_aberto_quantidade.
                        '<br><b>Valor Titulos em aberto: </b>'.parserValor($historico->titulos_em_aberto_valor).
                        '<br><b>Maior data em aberto: </b>'.parserData($historico->titulos_em_aberto_maior_atraso);
            if($historico->titulos->isNotEmpty()){
                $titulos .= '<a href="#" class="bt-view bt-historico-titulos" data-id="'.encrypt($historico->id).'" data-toggle="tooltip" data-trigger="hover" title="Titulos"></a>';
            }
            $data_retorno = Carbon::parse($historico->created_at);

            $retorno[] = [
                'data_hora' => parserDataEHora($historico->created_at),
                'usuario' => $historico->CriadoPor->name,
                'contato' => $historico->contato,
                'retorno' => $historico->RetornoCobranca->motivo . (!empty(trim($historico->observacao))? ' - ' . $historico->observacao : ''),
                'titulos' => $titulos,
                'string_data_hora' => $data_retorno->format('YmdHis'), 
                'codigo' => $fields['cliente'],
            ];
        }
        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $retorno
        ]);
    }

    
    public function modalAdcionar(Request $request){
        $fields = $request->only(['cliente', 'cliente_nome']);
        if(empty($fields['cliente'])){
            return response([], 422);
        }
        $cliente = encrypt($fields['cliente']);
        $retorno_possiveis = [];
        $RetornoCobrancaObj = RetornoCobranca::orderBy('motivo')->get();
        $retorno_observacao = [];
        foreach($RetornoCobrancaObj as $retorno){
            $id = encrypt($retorno->id);
            $retorno_possiveis[$id] = $retorno->motivo;
            if($retorno->observacao === true){
                $retorno_observacao[] = $id;
            }
        }
        $titulos = $this->dadosDeTitulosLista($fields['cliente']);
        return view('programs.historico_financeiro.modal.adicionar')->with(['retorno_possiveis' => $retorno_possiveis, 'titulos' => $titulos, 'cliente' => $cliente, 'retorno_observacao' => $retorno_observacao]);
    }

    public function adcionar(HistoricoFinanceiroClienteAdicionarRequest $request){
        $fields = $request->only(['cliente', 'contato', 'retorno', 'retorno_select', 'retorno_todos', 'observacao', 'observacao_totos']);
        if(empty($fields['cliente'])){
            return response([], 422);
        }
        $cliente = decrypt($fields['cliente']);
        $retornos = $fields['retorno'];
        $observacoes = $fields['observacao'];
        $titulos = [];
        $observacao = [];
        foreach($observacoes as $titulo => $retorno){
            unset($observacoes[$titulo]);
            if(!empty($retorno)){
                $observacao[decrypt($titulo)] = ($retorno);
            }
        }
        unset($observacoes);
        foreach($retornos as $titulo => $retorno){
            unset($retornos[$titulo]);
            if(!empty($retorno)){
                $retornos[decrypt($titulo)] = decrypt($retorno);
                if(!isset($titulos[decrypt($retorno)])){
                    $titulos[decrypt($retorno)] = [
                        'titulos' => [],
                        'observacao' => ''
                    ];
                }
                $titulos[decrypt($retorno)]['titulos'][] = decrypt($titulo);
                if(isset($observacao[decrypt($titulo)])){
                    $titulos[decrypt($retorno)]['observacao'] = $observacao[decrypt($titulo)];
                }else{
                    $titulos[decrypt($retorno)]['observacao'] = '';
                }
            }
        }
        unset($retornos);
        $ClienteNasajonObj = ClienteNasajon::where('codigo', $cliente)->where('bloqueado', false)->first();
        $raiz_cnpj = $ClienteNasajonObj->cpf_cnpj;
		if(strlen(trim($raiz_cnpj)) == 18){
			$raiz_cnpj = substr($raiz_cnpj, 0, 10);
        }
        unset($ClienteNasajonObj);
        foreach($titulos as $retorno => $dados){
            $titulos = $dados['titulos'];
            $observacao = $dados['observacao'];
            $email_cliente = $this->pegarEmailCliente($cliente);

            $dadosTitulos = $this->dadosTitulos($titulos);

            $HistoricoFinanceiroClienteObj = new HistoricoFinanceiroCliente;
            $HistoricoFinanceiroClienteObj->cliente_raiz_cnpj = $raiz_cnpj;
            $HistoricoFinanceiroClienteObj->retorno_possivel_id = $retorno;
            $HistoricoFinanceiroClienteObj->contato = $fields['contato'];
            $HistoricoFinanceiroClienteObj->titulos_em_aberto_quantidade = $dadosTitulos['quantidade'];
            $HistoricoFinanceiroClienteObj->titulos_em_aberto_valor = $dadosTitulos['valor'];
            $HistoricoFinanceiroClienteObj->titulos_em_aberto_maior_atraso = $dadosTitulos['data_menor'];
            $HistoricoFinanceiroClienteObj->observacao = $observacao;
            $HistoricoFinanceiroClienteObj->email_enviado = implode(";", $email_cliente);
            $HistoricoFinanceiroClienteObj->created_by = Auth::id();
            $HistoricoFinanceiroClienteObj->save();

            foreach($titulos as $titulo){
                $tituloObj = TitulosEmAbertoNasajon::where('titulo_id', $titulo)->first();
                $HistoricoFinanceiroClienteTituloObj = new HistoricoFinanceiroClienteTitulo();
                $HistoricoFinanceiroClienteTituloObj->historico_financeiro_clientes_id = $HistoricoFinanceiroClienteObj->id;
                $HistoricoFinanceiroClienteTituloObj->cliente = $tituloObj->cod_cliente;
                $HistoricoFinanceiroClienteTituloObj->titulo_id = $titulo;
                $HistoricoFinanceiroClienteTituloObj->titulo_estabelecimento = $tituloObj->codigo;
                $HistoricoFinanceiroClienteTituloObj->titulo_numero = $tituloObj->numero;
                $HistoricoFinanceiroClienteTituloObj->titulo_valor_original = $tituloObj->valor;
                $HistoricoFinanceiroClienteTituloObj->titulo_valor_saldo = $tituloObj->saldotitulo;
                $HistoricoFinanceiroClienteTituloObj->titulo_vencimento = $tituloObj->vencimento;
                $HistoricoFinanceiroClienteTituloObj->titulo_emissao = $tituloObj->titulo_emissao;
                $HistoricoFinanceiroClienteTituloObj->created_by = Auth::id();
                $HistoricoFinanceiroClienteTituloObj->save();
            }

            if(!empty($observacao)){
                $this->enviaEmailDados($email_cliente, $titulos, $observacao);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => []
        ]);
    }

    private function pegarEmailCliente($cliente){
        $emails = [];
        $codigos = $this->codigoClienteGrupo($cliente);
        $ClienteObj = ClienteNasajon::whereIn('codigo', $codigos)->get();
        $emails = $ClienteObj->pluck('email')->toArray();
        $emails = array_unique($emails);
        return $emails;
    }

    private function enviaEmailDados($emails, $titulos, $observacao){
        $tabela = $this->tabelaEmail($titulos);

		$EmailObj = new EmailController();
        $variaveis = [
            'observacao' => $observacao,
            'tabela_titulo' => $tabela
        ];
        $returnEmail = $EmailObj->sendEmailToken('00', "historico_cobranca", [], $variaveis);

    }

    private function tabelaEmail($titulos){
        $html = 
            '<table aling="center" width="600" cellpadding="0" cellspacing="0" border="1" >'.
                '<thead>'.
                    '<tr>'.
                        '<th>Número</th>'.
                        '<th>Emissão</th>'.
                        '<th>Vencimento</th>'.
                        '<th>Valor</th>'.
                    '</tr>'.
                '</thead>'.
                '<tbody>'
        ;
        foreach($titulos as $titulo){
            $tituloObj = TitulosEmAbertoNasajon::where('titulo_id', $titulo)->first();
            $html .= 
                '<tr>'.
                    '<td>'.$tituloObj->numero.'</td>'.
                    '<td>'.parserData($tituloObj->titulo_emissao).'</td>'.
                    '<td>'.parserData($tituloObj->vencimento).'</td>'.
                    '<td>'.parserValor($tituloObj->saldotitulo).'</td>'.
                '</tr>';
        }
        $html .= '</tbody>'.
            '</table>'; 

        return $html;
    }

    private function codigoClienteGrupo($cliente_codigo){

        $Cliente = ClienteNasajon::where('codigo', $cliente_codigo)->where('bloqueado', false)->first();

        $cpf_cnpj = $Cliente->cpf_cnpj;
        
		$codigos = [ $Cliente->codigo ];
        
		$raiz_cnpj = $cpf_cnpj;
		
		if(strlen(trim($cpf_cnpj)) == 18){
			$cpf_cnpj = substr($cpf_cnpj, 0, 10);
		}

		$raiz_cnpj = $cpf_cnpj;

		$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
			->where('raiz_cnpj', $raiz_cnpj)
			->orWhereHas('participantes', function ($query) use ($raiz_cnpj){
				$query->where('raiz_cnpj', $raiz_cnpj);
			})->first();
 
		$clientesNasajonQuery = ClienteNasajon::select('*');
		$clientesNasajonQuery->where("codigo", "!=" , $Cliente->codigo);
		
		if(!is_null($grupoEmpresarialObj)){
			$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
				if(isset($grupoEmpresarialObj->participantes)){
					foreach($grupoEmpresarialObj->participantes as $participante){
						$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
					}
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
		}
		else{
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $raiz_cnpj . '%');
		}

		$clientesNasajon = $clientesNasajonQuery->get()->toArray();
		unset($clientesNasajonQuery);

		foreach ($clientesNasajon as $key => $value) {
			$codigos[] = $value["codigo"];
		}
        unset($clientesNasajon);

        return $codigos;
    }
    

    private function grupoClienteGrupo($cliente_codigo){

        $Cliente = ClienteNasajon::where('codigo', $cliente_codigo)->where('bloqueado', false)->first();

        $cpf_cnpj = $Cliente->cpf_cnpj;
        
		$codigos = [ $Cliente->cpf_cnpj ];

		$raiz_cnpj = $cpf_cnpj;
		
		if(strlen(trim($cpf_cnpj)) == 18){
			$cpf_cnpj = substr($cpf_cnpj, 0, 10);
		}

		$raiz_cnpj = $cpf_cnpj;

		$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
			->where('raiz_cnpj', $raiz_cnpj)
			->orWhereHas('participantes', function ($query) use ($raiz_cnpj){
				$query->where('raiz_cnpj', $raiz_cnpj);
			})->first();

		$clientesNasajonQuery = ClienteNasajon::select('*')->where('bloqueado', false);
		$clientesNasajonQuery->where("codigo", "!=" , $Cliente->codigo);
		
		if(!is_null($grupoEmpresarialObj)){
			$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
				if(isset($grupoEmpresarialObj->participantes)){
					foreach($grupoEmpresarialObj->participantes as $participante){
						$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
					}
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
		}
		else{
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $raiz_cnpj . '%');
		}

		$clientesNasajon = $clientesNasajonQuery->get();
		unset($clientesNasajonQuery);

		foreach ($clientesNasajon as $key => $value) {
			$codigos[] = $value->cpf_cnpj;
		}
        unset($clientesNasajon);

        return $codigos;
    }
    
    private function dadosDeTitulosLista($cliente_codigo){
        $codigos = $this->codigoClienteGrupo($cliente_codigo);
        $titulosNasajon = TitulosEmAbertoNasajon::with(['cliente'])
            ->whereIn('cod_cliente', $codigos)
            ->where('vencimento', '<', Carbon::now())
            ->get();
        $titulos = [];
        $empresas = returnEmpresasNasajonView();
        foreach($titulosNasajon as $titulo){
            $cliente = $titulo->cliente->nome . ' - ' . $titulo->cliente->cpf_cnpj;
            $id = encrypt($titulo->titulo_id);
            $id_campo = str_replace('=','', $id);
            $titulos[] = [
                'estabelecimento' => $empresas[intval($titulo->codigo)],
                'cliente' => "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\"\" data-original-title=\"{$cliente}\">{$cliente}</div></div>",
                'titulo' => $titulo->numero,
                'data_emissao' => parserData($titulo->titulo_emissao),
                'data_vencimento' => parserData($titulo->vencimento),
                'valor_original' => parserValor($titulo->valor),
                'valor_saldo' => parserValor($titulo->saldotitulo),
                'id' => $id,
                'id_campo' => $id_campo
            ];
        }
        return $titulos;
    }

    private function dadosDeTitulos($cliente_codigo){
        $codigos = $this->codigoClienteGrupo($cliente_codigo);
        $titulosNasajon = TitulosEmAbertoNasajon::whereIn('cod_cliente', $codigos);

        $quantidade = $titulosNasajon->count();
        $valor = $titulosNasajon->sum('saldotitulo');
        $data_menor = $titulosNasajon->min('vencimento');
        return ['quantidade' => $quantidade, 'valor' => $valor, 'data_menor' => $data_menor];
    }

    private function dadosTitulos($titulos){
        $titulosNasajon = TitulosEmAbertoNasajon::whereIn('titulo_id', $titulos);

        $quantidade = $titulosNasajon->count();
        $valor = $titulosNasajon->sum('saldotitulo');
        $data_menor = $titulosNasajon->min('vencimento');
        return ['quantidade' => $quantidade, 'valor' => $valor, 'data_menor' => $data_menor];
    }

    public function modalTitulos(Request $request){
        $fields = $request->only(['id']);
        if(empty($fields['id'])){
            return response([], 422);
        }
        $id = decrypt($fields['id']);
        $titulos = [];
        $empresas = returnEmpresasNasajonView();
        $HistoricoFinanceiroClienteObj = HistoricoFinanceiroCliente::with(['titulos', 'titulos.dadosCliene', 'titulos.tituloNasajon',  'titulos.titulosFaturados'])->find($id);
        foreach($HistoricoFinanceiroClienteObj->titulos as $titulo){
            $cliente = $titulo->dadosCliene->nome . ' - ' . $titulo->dadosCliene->cpf_cnpj;
            $status = '';
            $vencimento = parserData($titulo->titulo_vencimento);
            $valor_original = parserValor($titulo->titulo_valor_original);
            $valor_saldo = parserValor($titulo->titulo_valor_saldo);
            if(!empty($titulo->tituloNasajon)){
                if($titulo->tituloNasajon->tem_prorrogacao == 'true'){
                    $status = 'protestado';
                    $vencimento .= '<a href="#" class="campo_obrigatorio" data-toggle="popover" data-html="true" title="Titulo Prorrogado" data-content="<b>Vencimento Original:</b> '.parserData($titulo->tituloNasajon->vencimento_original).'"> *</a>';
                }else{
                    $status = 'aberto';
                }
            }
            else if(!empty($titulo->titulosFaturados)){
                $status = 'faturado';
            }
            $titulos[] = [
                'estabelecimento' => $empresas[intval($titulo->titulo_estabelecimento)],
                'cliente' => "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\"\" data-original-title=\"{$cliente}\">{$cliente}</div></div>",
                'titulo' => $titulo->titulo_numero,
                'data_emissao' => parserData($titulo->titulo_emissao),
                'data_vencimento' => $vencimento,
                'valor_original' => $valor_original,
                'valor_saldo' => $valor_saldo,
                'id' => encrypt($titulo->id),
                'status' => $status
            ];
        }
        unset($HistoricoFinanceiroClienteObj);

        return view('programs.historico_financeiro.modal.titulos')->with(['titulos' => $titulos]);
    }

    public function indexHistorico(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\HistoricoDeCobranca") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\HistoricoDeCobranca');

        $check_gerentes = false;
        $check_vendedor_representante = false;

        $gerentes = [];
        if(!in_array(Auth::user()->tipo_usuario->nome, ["Diretor", "Administrador", "Interno"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }
            $userObj = User::whereIn('id', $subordinadosObj)->orderBy('name')->get();
            foreach ($userObj as $key => $user) {
                if(strtolower($user->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if(strtolower($user->tipo_usuario->nome) === "vendedor interno" || strtolower($user->tipo_usuario->nome) === "representante"){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }
            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->orderBy('name')->get();
            foreach ($subordinadosObj as $key => $userObj) {
                if($userObj->id === Auth::id() || empty($userObj->tipo_usuario)){
                    continue;
                }
                if(strtolower($userObj->tipo_usuario->nome) === "gerente comercial"){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }else if(strtolower($userObj->tipo_usuario->nome) === "vendedor interno" || strtolower($userObj->tipo_usuario->nome) === "representante"){
                    $vendedor_representante[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
            unset($subordinadosObj);
        }

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;

        if(strtolower(Auth::user()->tipo_usuario->nome) === "gerente comercial"){
            $check_vendedor_representante = true;
        }else if(strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
            $check_vendedor_representante = true;
        } else if(
            strtolower(Auth::user()->tipo_usuario->nome) !== "vendedor interno" &&
            strtolower(Auth::user()->tipo_usuario->nome) !== "representante"
        ){
            $check_gerentes = true;
            $check_vendedor_representante = true;
        }

        $retorno = [];
        
        $retorno['retorno'] = RetornoCobranca::all()->pluck('motivo', 'id');
        $retorno['gerentes'] = $gerentes;
        $retorno['check_gerentes'] = $check_gerentes;
        $retorno['vendedor_representante'] = $vendedor_representante;
        $retorno['check_vendedor_representante'] = $check_vendedor_representante;
        $retorno['hoje'] = Carbon::Now()->format('d/m/Y');

        return view('programs.historico_cobranca.index')->with($retorno);
    }

    public function filterHistorico(Request $request){

        $fields = $request->only('data_inicio', 'data_fim', 'cliente', 'gerentes', 'vendedor_representante');

        $users = [];

        if(in_array(Auth::user()->tipo_usuario->nome, ['Vendedor Interno', 'Representante'])){
            $users = [Auth::user()->codigo_representante];
        }
        else{
            if (
                isset($fields['gerentes']) && 
                !is_null($fields['gerentes']) &&
                (!isset($fields['vendedor_representante']) ||
                empty($fields['vendedor_representante']) )
            ) {
                $gerente = Crypt::decrypt($fields['gerentes']);

                $subordinados = UserController::varreSubordinados($gerente);
                $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
            }
            else if (
                (isset($fields['vendedor_representante']) &&
                !empty($fields['vendedor_representante']) )
            ) {
                $usuario = Crypt::decrypt($fields['vendedor_representante']);
                $users = [User::find($usuario)->codigo_representante];
            }
            else if(Auth::user()->hasRole('Gerencia Comercial')){
                $subordinados = UserController::varreSubordinados(Auth::id());
                $users = User::whereIn('id', $subordinados)->get()->pluck('codigo_representante')->filter()->toArray();
            }
        }

        if(empty($users) && Auth::user()->tipo_usuario->nivel > 0){
            $return = [
                "status" => 'success',
                "message" => 'Valores retornados com sucesso!',
                "error" => [],
                'response' => [
                    'linhas' => [],
                    'totais' => []
                ]
            ];
    
            return response()->json($return, 220);
        }

        $query = HistoricoFinanceiroClienteTitulo::with(['historicoFinanceiro', 'historicoFinanceiro.RetornoCobranca', 'tituloNasajon', 'tituloNasajon.notaDetalhes', 'tituloNasajon.notaDetalhes.revisao_vendedor_comissao', 'titulosFaturados', 'titulosFaturados.notaDetalhes', 'titulosFaturados.notaDetalhes.revisao_vendedor_comissao', 'tituloNasajon998'])
        ->where('titulo_valor_saldo', '>', 0);

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $dataInicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $query->where('created_at', ">=", $dataInicio->format('Y-m-d').' 00:00:00');
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $dataFim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $query->where('created_at', "<=", $dataFim->format('Y-m-d').' 23:59:59');
        }
        if(isset($fields['cliente']) && !empty($fields['cliente'])){

            $clienteObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente']))->first();

            if(!is_null($clienteObj)){

                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }
            else{
                $return = [
                    "status" => 'error',
                    "message" => 'Cliente não encontrado',
                    "error" => ['cliente' => 'Cliente não encontrado'],
                    'response' => []
                ];
        
                return response()->json($return, 422);
            }

            $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

            if (!is_null($grupoEmpresarialObj)){
                $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                    if(isset($grupoEmpresarialObj->participantes)){
                        foreach($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                        }
                    }
                    $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                });
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $cpfs_cnpjs = [];

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $query->whereIn('cliente', $clientesNasajonObj->pluck('codigo'));

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }
        
        $HistoricoFinanceiroClienteTituloObj = $query->get();

        if(!empty($users)){
            if(in_array('998', $users)){
                $HistoricoFinanceiroClienteTituloObj = $HistoricoFinanceiroClienteTituloObj->filter(function ($historico){
                    return isset($historico->tituloNasajon998);
                });
            }else{
                $HistoricoFinanceiroClienteTituloObj = $HistoricoFinanceiroClienteTituloObj->filter(function ($historico) use ($users){
                    return (
                        (
                            isset($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao) &&
                            in_array($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo, $users)
                        ) || (
                            isset($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao) &&
                            in_array($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo, $users) &&
                            $historico->titulosFaturados->codigo == $historico->titulo_estabelecimento
                        )
                    );
                });
            }
        }

        $retornosObj = $HistoricoFinanceiroClienteTituloObj->sortByDesc('created_at')->unique('titulo_id')->groupBy(function($item){
            return $item->historicoFinanceiro->retorno_possivel_id;
        });
        
        $retorno = [];
        $totais = [
            'clientes' => [],
            'valor_cobrado' => 0,
            'valor_recuperado' => 0,
        ];

        $retornosObj->each(function ($motivo, $motivo_id) use (&$retorno, &$totais){
            $motivo->each(function($titulo) use (&$retorno, &$totais, $motivo_id){
                if(!isset($retorno[$motivo_id])){

                    $retorno[$motivo_id] = [
                        'clientes' => [],
                        'motivo' => $titulo->historicoFinanceiro->RetornoCobranca->motivo,
                        'valor_cobrado' => 0,
                        'valor_recuperado' => 0,
                        'sucesso' => 0
                    ];
                }


                $retorno[$motivo_id]['clientes'][] = $titulo->cliente;
                $retorno[$motivo_id]['valor_cobrado'] += $titulo->titulo_valor_saldo;

                $totais['clientes'][] = $titulo->cliente;

                $recuperado = $titulo->titulo_valor_saldo;   
                
                if(!is_null($titulo->tituloNasajon)){
                    $recuperado = $titulo->titulo_valor_saldo - $titulo->tituloNasajon->saldotitulo;
                }
                if(!is_null($titulo->tituloNasajon998)){
                    $recuperado = $titulo->titulo_valor_saldo - $titulo->tituloNasajon998->saldotitulo;
                }

                $totais['valor_cobrado'] += $titulo->titulo_valor_saldo > 0 ? $titulo->titulo_valor_saldo : 0;
                $totais['valor_recuperado'] += $recuperado > 0 ? $recuperado : 0;

                $retorno[$motivo_id]['valor_recuperado'] += $recuperado > 0 ? $recuperado : 0;

            });
        });

        foreach($retorno as $motivo_id => $motivo){
            $sucesso = $motivo['valor_recuperado']/$motivo['valor_cobrado']*100;

            $retorno[$motivo_id]['clientes'] = count(array_unique($motivo['clientes']));
            $retorno[$motivo_id]['sucesso'] = ($sucesso > 0 ? parserValor($sucesso) . '%' : '');
            $retorno[$motivo_id]['valor_recuperado'] = ($motivo['valor_recuperado'] > 0 ? parserValor($motivo['valor_recuperado']) : '');
            $retorno[$motivo_id]['valor_cobrado'] = parserValor($motivo['valor_cobrado']);
            $retorno[$motivo_id]['filtro'] = Crypt::encrypt(array_merge($fields, ['motivo_id' => $motivo_id, 'users' => $users]));
        }
        $totais['sucesso'] = $totais['valor_cobrado'] > 0 ? $totais['valor_recuperado'] / $totais['valor_cobrado'] : 0;
        $totais['sucesso'] = $totais['sucesso'] > 0 ? parserValor($totais['sucesso'] * 100) . '%' : '';

        $totais['clientes'] = count(array_unique($totais['clientes']));
        $totais['valor_cobrado'] = $totais['valor_cobrado'] > 0 ? parserValor($totais['valor_cobrado']) : '';
        $totais['valor_recuperado'] = $totais['valor_recuperado'] > 0 ? parserValor($totais['valor_recuperado']) : '';
        $totais['filtro'] = Crypt::encrypt(array_merge($fields, ['users' => $users]));

        $return = [
            "status" => 'success',
            "message" => 'Valores retornados com sucesso!',
            "error" => [],
            'response' => [
                'linhas' => array_values($retorno),
                'totais' => $totais
            ]
        ];

        return response()->json($return, 220);
    }

    public function modalHistorico(Request $request){
        
        $filtro = $request->filtro;

        try {
            $fields = Crypt::decrypt($filtro);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $return = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade no servidor, atualize a página!',
                "error" => [],
                'response' => []
            ];
    
            return response()->json($return, 422);
        }
        
        $query = HistoricoFinanceiroClienteTitulo::with('CriadoPor', 'historicoFinanceiro', 'historicoFinanceiro.retornoCobranca', 'dadosCliene', 'tituloNasajon', 'tituloNasajon.notaDetalhes', 'tituloNasajon.notaDetalhes.revisao_vendedor_comissao', 'tituloNasajon.notaDetalhes.revisao_vendedor_comissao.usuario', 'titulosFaturados', 'titulosFaturados.notaDetalhes', 'titulosFaturados.notaDetalhes.revisao_vendedor_comissao', 'titulosFaturados.notaDetalhes.revisao_vendedor_comissao.usuario', 'tituloNasajon998')
        ->where('titulo_valor_saldo', '>' , 0);

        if(isset($fields['motivo_id']) && !empty($fields['motivo_id'])){
            $query->whereHas('historicoFinanceiro', function($query) use ($fields){
                $query->where('retorno_possivel_id', $fields['motivo_id']);
            });
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $dataInicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $query->where('created_at', ">=", $dataInicio->format('Y-m-d').' 00:00:00');
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $dataFim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $query->where('created_at', "<=", $dataFim->format('Y-m-d').' 23:59:59');
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){

            $clienteObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'),'ilike', trim($fields['cliente']))->first();

            if(!is_null($clienteObj)){

                if(strlen($clienteObj->cpf_cnpj) == 18){
                    $cpf_cnpj = substr($clienteObj->cpf_cnpj, 0, 10);
                }
                else{
                    $cpf_cnpj = $clienteObj->cpf_cnpj;
                }

                $grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
                ->where('raiz_cnpj', $cpf_cnpj)
                ->orWhereHas('participantes', function($query) use ($cpf_cnpj){
                    $query->where('raiz_cnpj', $cpf_cnpj);
                })
                ->first();
            }

            $clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

            if (!is_null($grupoEmpresarialObj)){
                $clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
                    if(isset($grupoEmpresarialObj->participantes)){
                        foreach($grupoEmpresarialObj->participantes as $participante){
                            $query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
                        }
                    }
                    $query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
                });
            }
            else {
                $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
            }

            $cpfs_cnpjs = [];

            $clientesNasajonObj = $clientesNasajonQuery->orderBy('cpf_cnpj')
                ->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
                ->get();

            $query->whereHas('historicoFinanceiro', function($query) use ($clientesNasajonObj){
                $query->whereIn('cliente_raiz_cnpj', $clientesNasajonObj->pluck('cpf_cnpj')->map(function($cpf_cnpj){
                    if(strlen($cpf_cnpj) == 18){
                        return substr($cpf_cnpj, 0, 10);
                    }
                    else{
                        return $cpf_cnpj;
                    }
                }));
            });

            unset($clientesNasajonObj, $clientesNasajonQuery, $clienteObj);
        }

        $historicoFinanceiroClienteObj = $query->get()->sortByDesc('created_at')->unique('titulo_id');

        if(!empty($fields['users'])){
            if(in_array('998', $fields['users'])){
                $historicoFinanceiroClienteObj = $historicoFinanceiroClienteObj->filter(function ($historico){
                    return isset($historico->tituloNasajon998);
                });
            }else{
                $historicoFinanceiroClienteObj = $historicoFinanceiroClienteObj->filter(function ($historico) use ($fields){
                    return (
                        (
                            isset($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao) &&
                            in_array($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo, $fields['users'])) ||
                        (
                            isset($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao) &&
                            in_array($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo, $fields['users']) &&
                            $historico->titulosFaturados->codigo == $historico->titulo_estabelecimento
                        )
                    );
                });
            }
        }

        $retorno = [];
        $totais = [
            'valor_cobrado' => 0,
            'valor_recuperado' => 0
        ];
        $vendedor998 = User::where('codigo_representante', '998')->first();
        $historicoFinanceiroClienteObj->each(function($historico) use (&$retorno, &$totais, $vendedor998){
            $linha = [];

            $cobrado = $historico->titulo_valor_saldo;

            $recuperado = $cobrado;   
            
            if(!empty($historico->tituloNasajon)){
                $recuperado = $cobrado - $historico->tituloNasajon->saldotitulo;
            }

            if(!is_null($historico->tituloNasajon998)){
                $recuperado = $cobrado - $historico->tituloNasajon998->saldotitulo;
            }

            $sucesso = $recuperado / $cobrado * 100;

            $linha['usuario'] = $historico->CriadoPor->name;
            $linha['cliente'] = $historico->dadosCliene->nome . ' - ' . $historico->dadosCliene->cpf_cnpj;
            $linha['contato'] = $historico->historicoFinanceiro->contato;
            $linha['titulo_id'] = $historico->titulo_id;
            $linha['titulo_numero'] = $historico->titulo_numero;
            $linha['valor_cobrado'] = parserValor($cobrado);
            $linha['valor_recuperado'] = $recuperado > 0 ? parserValor($recuperado) : '';
            $linha['sucesso'] = $sucesso > 0 ? parserValor($sucesso) . '%' : '';

            $totais['valor_cobrado'] += $cobrado;
            $totais['valor_recuperado'] += $recuperado > 0 ? $recuperado : 0;

            $vendedor = [];

            if(isset($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao) && !empty($historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao)){
                $vendedor[] = $historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo . ' - ' . $historico->tituloNasajon->notaDetalhes->revisao_vendedor_comissao->usuario->name;
            }
            else if(isset($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao) && !empty($historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao)){
                $vendedor[] = $historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao->vendedor_codigo . ' - ' . $historico->titulosFaturados->notaDetalhes->revisao_vendedor_comissao->usuario->name;
            }

            if(isset($historico->tituloNasajon998)){
                $vendedor[] = '998 - '.$vendedor998->name;
            }

            $linha['vendedor'] = implode(', ', $vendedor);

            $retorno[] = $linha;
        });

        $totais['sucesso'] = $totais['valor_cobrado'] > 0 ? $totais['valor_recuperado'] / $totais['valor_cobrado'] : 0; 
        $totais['sucesso'] = $totais['sucesso'] > 0 ? parserValor($totais['sucesso'] * 100).'%' : '';
        $totais['valor_cobrado'] = $totais['valor_cobrado'] > 0 ? parserValor($totais['valor_cobrado']) : '';
        $totais['valor_recuperado'] = $totais['valor_recuperado'] > 0 ? parserValor($totais['valor_recuperado']) : '';

        return view('programs.historico_cobranca.modal.index')->with(['retorno' => $retorno, 'totais' => $totais]);
    }

    public function modalTituloDetalhes(Request $request){

        $fields = $request->only('id');

        $tituloObj = HistoricoFinanceiroClienteTitulo::select('titulo_numero', 'titulo_estabelecimento')->where('titulo_id', $fields['id'])->first();

        $tituloAbertoObj = TitulosEmAbertoNasajon::with('cliente')->where('titulo_id', $fields['id'])->first();

        $retorno = [];

        $estabelecimentos = returnEmpresasNasajonView();

        if(!is_null($tituloAbertoObj)){
            $retorno['estabelecimento'] = $estabelecimentos[intval($tituloObj['titulo_estabelecimento'])];
            $retorno['cliente'] = $tituloAbertoObj->cliente->nome . ' - ' . $tituloAbertoObj->cliente->cpf_cnpj;
            $retorno['titulo'] = $tituloAbertoObj->numero;
            $retorno['parcela'] = $tituloAbertoObj->parcela;
            $retorno['vencimento'] = parserData($tituloAbertoObj->vencimento);

            if($tituloAbertoObj->tem_prorrogacao){
                $retorno['vencimento_original'] = parserData($tituloAbertoObj->vencimento_original);
            }

            $retorno['valor'] = parserValor($tituloAbertoObj->valor);
            $retorno['saldo'] = parserValor($tituloAbertoObj->saldotitulo);
            $retorno['banco'] = $tituloAbertoObj->banco_nome;
            $retorno['agencia'] = !empty($tituloAbertoObj->conta_agencia) ? $tituloAbertoObj->conta_agencia . '-' . $tituloAbertoObj->conta_agencia_digito : '';
            $retorno['conta'] = !empty($tituloAbertoObj->conta_numero) ? $tituloAbertoObj->conta_numero . '-' . $tituloAbertoObj->conta_digito : '';
        }
        else{
            $tituloBaixadoObj = ContasReceberBaixadoNasajon::with('cliente')
                ->where('numero', $tituloObj->titulo_numero)
                ->first();

            $retorno['estabelecimento'] = $estabelecimentos[intval($tituloObj['titulo_estabelecimento'])];
            $retorno['cliente'] = $tituloBaixadoObj->cliente->nome . ' - ' . $tituloBaixadoObj->cliente->cpf_cnpj;
            $retorno['titulo'] = $tituloBaixadoObj->numero;
            $retorno['parcela'] = $tituloBaixadoObj->parcela;
            $retorno['vencimento'] = parserData($tituloBaixadoObj->vencimento);

            if($tituloBaixadoObj->tem_prorrogacao){
                $retorno['vencimento_original'] = parserData($tituloBaixadoObj->vencimento_original);
            }

            $retorno['data_pagamento'] = parserData($tituloBaixadoObj->data_pagamento);
            $retorno['valor'] = parserValor($tituloBaixadoObj->valor);
            $retorno['saldo'] = parserValor($tituloBaixadoObj->saldotitulo);
            $retorno['banco'] = $tituloBaixadoObj->banco_nome;
            $retorno['agencia'] = !empty($tituloBaixadoObj->conta_agencia) ? $tituloBaixadoObj->conta_agencia . !empty($tituloBaixadoObj->conta_agencia_digito?'-'.$tituloBaixadoObj->conta_agencia_digito:''):'';
            $retorno['conta'] = !empty($tituloBaixadoObj->conta_numero) ? $tituloBaixadoObj->conta_numero . '-' . $tituloBaixadoObj->conta_digito : '';
        }

        return view('programs.historico_cobranca.modal.titulo')->with($retorno);

    }
}
