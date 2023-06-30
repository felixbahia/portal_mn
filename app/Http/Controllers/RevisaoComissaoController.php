<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\ListagemDePrecosController;
use App\Http\Controllers\UserController;

use App\Http\Requests\ListaDePrecosRequest;

use App\AliquotaPreco;
use App\CepEndereco;
use App\Cliente;
use App\EstabelecimentoCidadeFob;
use App\MargemPrazo;
use App\ParametrosAprovacao;
use App\PedidoPortal;
use App\PedidoVenda;
use App\User;
use App\PedidosVendaNasajon;
use App\NotasNasajon;
use App\ChequesPedidosPrepagos;
use App\ProdutoEspecificacao;
use App\FaturamentoNotaNasajon;
use App\VendedorComissaoNota;

use Carbon\Carbon;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

use Auth;


class RevisaoComissaoController extends Controller
{

    private $codigo_cliente_balcao = ['0000010069999'];
    private $codigos_tecidos_textil_mn = ['0716668790001', '0050758840001', '0050758840002', '0050758840003', '0063112740004', '0063112740005', '0063112740001', '0063112740002', '0063112740003'];
    private $estabelecimentos_prologos = [];

    public function index(Request $request){

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[0]);
        unset($estabelecimentos[5]);

        if(Auth::user()->hasPermissionTo("programas App\RevisaoComissao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RevisaoComissao');

        $dropdown_usuarios = [];
        $dropdown_gerentes = [];
        $dropdown_diretores = [];
        if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador' || Auth::user()->id == 92){
            if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                ->whereIn('id', UserController::varreSubordinados(Auth::id()))
                ->whereHas('tipo_usuario', function ($query){
                    $query->where('nome', '!=', 'Vendedor Interno');
                })
                ->orderBy('name', 'asc')
                ->get();
            }
            else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador' || Auth::user()->id == 92){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                ->whereHas('tipo_usuario', function ($query){
                    $query->where('nome', '!=', 'Vendedor Interno');
                })
                ->orderBy('name', 'asc')
                ->get();

                $status_pedido['cancelados'] = 'Cancelados';
            }
            else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $users = User::with('tipo_usuario')
                ->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')
                ->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))
                ->whereHas('tipo_usuario', function ($query){
                    $query->where('nome', '!=', 'Vendedor Interno');
                })
                ->orderBy('name', 'asc')
                ->get();
            }
            foreach ($users as $user) {
                if(is_null($user->tipo_usuario)){
                    continue;
                }
                if (!is_null($user->codigo_representante)){
                    $dropdown_usuarios[$user->id] = strtoupper($user->name);
                }
                if (strpos(strtolower($user->tipo_usuario->nome), "gerente") !== false){
                    $dropdown_gerentes[$user->id] = strtoupper($user->name);
                }
                else if (strpos(strtolower($user->tipo_usuario->nome), "diretor") !== false){
                    $dropdown_diretores[$user->id] = strtoupper($user->name);
                }
            }

        }

        return view('programs.revisao_comissao.index')->with(['estabelecimentos' => $estabelecimentos, 'dropdown_diretores' => $dropdown_diretores, 'dropdown_gerentes' => $dropdown_gerentes, 'dropdown_usuarios' => $dropdown_usuarios]);
    }

    public function filter(Request $request){
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $fields = $request->only('estabelecimento', 'pedido', 'pedido_gerado', 'nome', 'data_inicio', 'data_fim', 'diretor', 'gerente', 'usuario');
        $estabelecimentos = returnEmpresasNasajonView();

        $pedidoPortalQuery = PedidoPortal::
            with('usuario_detalhes', 'condicao_pagamento_detalhes', 'cliente', 'pedidoNasajon.nota.revisao_vendedor_comissao')
            ->where('status_pedido', 3)
            ->whereNotIn('cod_cliente', $this->codigo_cliente_balcao)
            ->whereNotIn('cod_cliente', $this->codigos_tecidos_textil_mn)
            ->whereNotIn('usuario', [28, 69])
            ->whereHas('usuario_detalhes', function ($query){
                $query->where('tipo_usuario_id', '<>', 16);
            });

        if(Auth::user()->tipo_usuario->nivel > 2 && Auth::user()->id != 92){
            $pedidoPortalQuery->where('usuario', Auth::id());
        }

        if(!empty($fields['estabelecimento'])){
            $pedidoPortalQuery->where('estabelecimento', $fields['estabelecimento']);
        }

        if(!empty($fields['nome'])){
            $cliente = Cliente::where('NOME', 'like', '%' . $fields['nome'] . '%')->get();

            $pedidoPortalQuery->whereIn('cod_cliente', $cliente->pluck('CODCAD'));
        }

        if(!empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio']);
        }

        if(!empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim']);
        }

        if(isset($data_inicio) && isset($data_fim)){
            $data_periodo = [
                $data_inicio->toDateString() . ' 00:00:00',
                $data_fim->toDateString() . ' 23:59:59',
            ];
        }
        else if(isset($data_inicio) && !isset($data_fim)){
            $data_periodo = [
                $data_inicio->toDateString() . ' 00:00:00',
                date('Y-m-d') . ' 23:59:59',
            ];
        }
        else if(!isset($data_inicio) && isset($data_fim)){
            $data_periodo = [
                $data_fim->subMonth()->toDateString(),
                $data_fim->toDateString(),
            ];
        }

        if(isset($data_periodo)){
            $pedidoPortalQuery->whereBetween('data_pedido', $data_periodo);
        }

        $users = [];

        if(isset($fields['diretor']) && !empty($fields['diretor'])){
            $users = UserController::varreSubordinados($fields['diretor']);
        }
        else if(isset($fields['gerente']) && !empty($fields['gerente'])){
            $users = UserController::varreSubordinados($fields['gerente']);
        }
        else if(strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false){
            $users = UserController::varreSubordinados(Auth::user()->responsavel);
        }
        else if (
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false ||
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false ||
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") !== false
        ){
            $users = UserController::varreSubordinados(Auth::id());
        }

        if(isset($fields['pedido'])){
            $pedidoPortalQuery->where('id', $fields['pedido']);
        }

        if(isset($fields['pedido_gerado'])){
            $pedidoPortalQuery->where('pedido_gerado', $fields['pedido_gerado']);
        }

        if(isset($fields['usuario']) && !empty($fields['usuario'])){
            $pedidoPortalQuery->where(function($query) use ($fields){
                $query->where('usuario', $fields['usuario'])
                    ->OrWhere('created_by', $fields['usuario']);
            });
        }
        else{
            if (
                (
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false ||
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false ||
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false ||
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") !== false
                ) && (
                    empty($fields['gerente']) &&
                    empty($fields['diretor'])
                ) && (strtolower(Auth::user()->tipo_usuario->nome) !== "diretor" && strtolower(Auth::user()->tipo_usuario->nome) !== 'administrador')
            ) {
                $pedidoPortalQuery->where(function ($query) use ($users){
                    $query->whereIn('usuario', $users)
                        ->orWhereIn('created_by', $users);
                });
            }
            else if (
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") === false &&
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") === false &&
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") === false
            ) {
                $pedidoPortalQuery->where(function ($query) use ($users) {
                    $query->where('usuario', Auth::id())
                        ->orWhere('created_by', Auth::id());
                });
            }
        }

        $pedidoPortalObj = $pedidoPortalQuery
        ->whereNotNull('pedido_gerado')
        ->orderBy('id', 'desc')->get();

        $return = [];

        $pedidos_nasajon = $pedidoPortalObj->filter();

        $pedidoVendasNasajon = PedidosVendaNasajon::with('cliente_detalhes')
        ->whereHas('nota')
        ->whereIn('numero', $pedidos_nasajon->pluck('pedido_gerado'))
        ->whereHas('cliente_detalhes', function($query) use ($pedidos_nasajon){
            $query->whereIn('codigo', $pedidos_nasajon->pluck('cod_cliente')->unique());
        })
        ->where('situacao_descricao', '!=', 'Cancelado')
        ->get();

        foreach($pedidoPortalObj as $pedido){
            $linha = $pedidoVendasNasajon->search(function ($item, $key) use ($pedido){

                return $item->numero == $pedido->pedido_gerado && $item->cliente_detalhes->codigo == $pedido->cod_cliente;

            });

            if($linha !== false){
                $return[] = [
                    'estabelecimento' => $estabelecimentos[$pedido->estabelecimento],
                    'pedido' => $pedido->id,
                    'id' => $pedidoVendasNasajon[$linha]->id,
                    'cliente' => $pedido->cliente->nome??'',
                    'data' => parserData($pedido->data_pedido),
                    'condicao_pagamento' => empty($pedido->condicao_pagamento_detalhes)? '' : $pedido->condicao_pagamento_detalhes->descricao,
                    'vendedor' => $pedido->usuario_detalhes->name,
                    'origem' => 'NASAJON',
                    'vendedor_id' => empty($pedido->pedidoNasajon->nota->revisao_vendedor_comissao)? Null : $pedido->pedidoNasajon->nota->revisao_vendedor_comissao->vendedor,
                    'user_id' => $pedido->usuario,
                ];

            }
        }

        return response()->json($return, '220');

    }

	public function retornaComissao(Request $request){
		$fields = $request->only('pedido', 'origem', 'numero_nota', 'estabelecimento_data', 'vendedor');
       
        if(empty($fields['origem']) || strcasecmp($fields['origem'],'PROLOGOS') == 0){
            $info_pedido = [];
            $estabelecimento = $fields['estabelecimento_data'][0];
            $data = Carbon::createFromFormat('d/m/Y', $fields['estabelecimento_data'][1]);

            // $tabela = 'DUM' . $data->format(str_pad($estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

            // $busca_nota = DB::connection('srv_prologos')->table($tabela)->select('CODVND', 'COMISSAO_VND')->where('NF_NUMNF', $fields['numero_nota'])->first();
            $tabela = null;
            $busca_nota = null;

            $vendedoresObj = User::select('codigo_representante', 'name')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

            $vendedores = [];
            $info_pedido = [];

            $vendedoresObj->each(function($item) use (&$vendedores){
                if(isset($item->codigo_representante)){
                    $vendedores[$item->codigo_representante] = $item->codigo_representante . ' - ' . $item->name;
                }
            });

            $info_pedido['vendedores_array'] = $vendedores;

            $info_pedido["vendedor"] = $busca_nota->CODVND??null;
            $info_pedido['comissao_pedido'] = parserValor($busca_nota->COMISSAO_VND??0);

            $info_pedido['hash'] = Crypt::encrypt([
                'tabela' => $tabela,
                'nota' => $fields['numero_nota'],
                'origem' => 'prologos'
            ]);
        }else if(strcasecmp($fields['origem'], 'NASAJON') == 0){

            $vendedoresObj = User::with('vendedor_nasajon')->select('codigo_representante', 'name')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

            $vendedores = [];
            $info_pedido = [];

            $vendedoresObj->each(function($item) use (&$vendedores){
                if(isset($item->vendedor_nasajon->id)){
                    $vendedores[$item->vendedor_nasajon->id] = $item->codigo_representante . ' - ' . $item->name;
                }
            });

            $info_pedido['vendedores_array'] = $vendedores;

            $userObj = User::find($fields['vendedor']);
            $codigo_vendedor = '';
            if(!empty($userObj)){
                $codigo_vendedor = $userObj->codigo_representante;
            }else{
                $codigo_vendedor = '001';
            }
            $userObj = User::where('codigo_representante', $codigo_vendedor)->first()->vendedor_nasajon;
            $info_pedido["vendedor"] = $userObj->id;
            unset($userObj);
            $nota = FaturamentoNotaNasajon::with([
                'revisao_comissao' => function($query) use ($codigo_vendedor){
                    $query->where('vendedor_codigo', $codigo_vendedor);
                }])
                ->where('Id_Nota', $fields['numero_nota'])
                ->where('Estabelecimento', $fields['estabelecimento_data'][0])
                ->first();
            
            $info_pedido['comissao_pedido'] = parserValor($nota->revisao_comissao->percentual_comissao??0);
            $info_pedido['hash'] = Crypt::encrypt([
                'id' => $nota->Id_Nota,
                'origem' => 'nasajon',
                'vendedor' => $nota->revisao_comissao->vendedor ?? '',
                'df_vendedor' => $nota->revisao_comissao->df_vendedor ?? ''
            ]);
        }

        return view('programs.comissao.mudar_comissao_sem_pedido')->with(['info_pedido' => $info_pedido]);
	}

    public function retornarPedido(Request $request){
        $fields = $request->only('pedido', 'origem', 'id', 'vendedor_id', 'user_id');
        
        $info_pedido = $this->retornarDadosPedidoNasajon($fields);
        return view('programs.revisao_comissao.info_pedido_nasajon')->with(['info_pedido' => $info_pedido]);
    }

    public function retornarDadosPedidoPrologos($fields){

        $listagemDePrecosController = new ListagemDePrecosController();
        $estabelecimentos = returnEmpresasPrologusView();

        $nacional = [0,3,4,5];
        $internacional = [1,2,6,7];

        $coluna_a = null;
        $coluna_b = null;
        $coluna_c = null;
        $preco_base = null;

        $pedidoPortalObj = PedidoPortal::
            with('usuario_detalhes', 'itens_pedido', 'status_pedido_detalhes', 'condicao_pagamento_detalhes', 'margemPrazoPedido')
            ->find($fields['pedido']);

        $pedidoVendaObj = PedidoVenda::where('NUMPED', $pedidoPortalObj->pedido_gerado)->first(); 
        
        $aliquotasObj = AliquotaPreco::where('origem', $pedidoPortalObj->origem)->where('estado', $pedidoPortalObj->cliente->ESTADO)->get();

        $margemPrazoObj = MargemPrazo::where('estabelecimento', $pedidoPortalObj->estabelecimento)->first();

        $info_pedido = [];

        $parametrosAprovacaoObj = ParametrosAprovacao::with('tipoUsuario')->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT))->get();

        $desconto_maximo_gerente = 1 - $parametrosAprovacaoObj[($parametrosAprovacaoObj->search(function ($item, $key){ return strtolower($item->tipoUsuario->nome) == 'gerente'; }))]->percentual_desconto / 100;

        $fator_comissao = $margemPrazoObj->preco_b - 1;

        if(!is_null($pedidoPortalObj)){

            $info_pedido['estabelecimento'] = $estabelecimentos[$pedidoPortalObj->estabelecimento];
            $info_pedido['id'] = $pedidoPortalObj->id;
            $info_pedido['pedido_gerado'] = $pedidoPortalObj->pedido_gerado;
            $info_pedido['cliente'] = $pedidoPortalObj->cliente->NOME;
            $info_pedido['estado'] = $pedidoPortalObj->cliente->ESTADO;

            if ($pedidoPortalObj->tipo_venda == 'isento'){
                $aliquota_internacional = $aliquotasObj[
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === true;    
                })]->icms_venda_cliente_isento;

                $aliquota_nacional = $aliquotasObj[
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === false;    
                })]->icms_venda_cliente_isento;

                $frete = $aliquotasObj[
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === false;    
                })]->frete_adicional;
            }
            else {
                $aliquota_internacional = $aliquotasObj[
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === true;
                })]->icms_venda;
    
                $aliquota_nacional  =$aliquotasObj[ 
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === false;    
                })]->icms_venda;

                $frete = $aliquotasObj[
                    $aliquotasObj->search( function ($item, $key) {
                    return $item->internacional === false;    
                })]->frete_adicional;

            }

            if(!in_array($pedidoPortalObj->cod_cliente, $this->codigo_cliente_balcao)) {

                if (isset($pedidoPortalObj->cliente->CEP) && !empty(preg_replace("/[-_]/", "", $pedidoPortalObj->cliente->CEP))) {
                    $cepEnderecoObj = CepEndereco::whereRaw("substring(LPAD(cep::text, 8, '0'), 0, 6) = '" . substr(str_pad(preg_replace('/[_-]/','',$pedidoPortalObj->cliente->CEP). 8, '0', STR_PAD_LEFT), 0, 5) . "'")->first();
                }

                $estabelecimentoCidadeFob = EstabelecimentoCidadeFob::where("cidade", $cepEnderecoObj->cidadeBusca->cidade??'')
                    ->where('uf', $pedidoPortalObj->cliente->ESTADO??'')
                    ->where('estabelecimento', str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                    ->first();
            }

            $info_pedido['localizacao_cliente'] = $pedidoPortalObj->cliente->ESTADO . (!is_null($cepEnderecoObj->cidadeBusca->cidade) ? ' - ' . $cepEnderecoObj->cidadeBusca->cidade : '');
            
            if(!is_null($pedidoPortalObj->frete_preco)){
                $info_pedido['frete_preco'] = $pedidoPortalObj->frete_preco;

                if($pedidoPortalObj->frete_preco == 'cif'){
                    $info_pedido['frete_aplicado'] = $frete . "%";
                }
                else{
                    $info_pedido['frete_aplicado'] = '0%';
                }

                if($pedidoPortalObj->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';
                }
                else{
                    $info_pedido['frete_pedido'] = 'fob';                    
                }

            }
            else{
                if($pedidoPortalObj->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';

                    if(!is_null($pedidoPortalObj->transportadora_redespacho)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        
                    }
                    else{
                        if(isset($estabelecimentoCidadeFob) && !is_null($estabelecimentoCidadeFob)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        }
                        else{
                            $info_pedido['frete_preco'] = 'cif';
                            $info_pedido['frete_aplicado'] = $frete . '%';
                        }
                    }
                }
                else{
                    $info_pedido['frete_pedido'] =  'fob';
                    $info_pedido['frete_preco'] = 'fob';
                    $info_pedido['frete_aplicado'] = '0%';
                }                    
            }

            $info_pedido['prazo_medio'] = $pedidoPortalObj->condicao_pagamento_detalhes->media;
            $info_pedido['fator_prazo'] = $pedidoPortalObj->condicao_pagamento_detalhes->media * 0.04;
            $info_pedido['adicional_prazo'] = ($pedidoPortalObj->margemPrazoPedido->fator_diario * $pedidoPortalObj->condicao_pagamento_detalhes->media*100) . '%';

            $listagem_precos_parametros = new ListaDePrecosRequest ([
                'frete' => $info_pedido['frete_preco'],
                'estabelecimento' => $pedidoPortalObj->estabelecimento,
                'estado' => $pedidoPortalObj->cliente->estado_detalhe->uf,
                'moeda' => 'real',
                'tipo_cliente' => (
                    $pedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico',
                'prazo_medio' => $pedidoPortalObj->condicao_pagamento_detalhes->media        
            ]);

            $itens = [];

            $comissao_valor_total = 0;

            $PedidoVendaObj = PedidoVenda::where('NUMPED', $pedidoPortalObj->pedido_gerado)->first();

            $data_fim = new Carbon($PedidoVendaObj->DATA_FIM);

            $base_dum = $data_fim->format(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

            $x = 0;
            
            $NotaQuery[] = DB::connection('srv_prologos')->table('DUM' . $base_dum)->where('NF_NUMNF', $PedidoVendaObj->NUMULTNF);
            $DevolucoesQuery[] = DB::connection('srv_prologos')->table('DUR' . $base_dum)->where('NFORIGEM', $PedidoVendaObj->NUMULTNF);

            $junho_2019 = Carbon::createFromFormat('Y-m-d', "2019-06-01");

            while($data_fim->lessThan($junho_2019)){

                $data_fim = $data_fim->addMonthsNoOverflow(1);

                $base_dum = $data_fim->format(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

                $NotaQuery[] = DB::connection('srv_prologos')->table('DUM' . $base_dum)->where('NF_NUMNF', $PedidoVendaObj->NUMULTNF)->union($NotaQuery[$x]);
                $DevolucoesQuery[] = DB::connection('srv_prologos')->table('DUR' . $base_dum)->where('NFORIGEM', $PedidoVendaObj->NUMULTNF)->union($DevolucoesQuery[$x++]);

            }

            $NotaObj = $NotaQuery[$x]->first();
            $DevolucoesObj = $DevolucoesQuery[$x]->get();

            if(!is_null($DevolucoesObj)){
    
                $data_fim = new Carbon($PedidoVendaObj->DATA_FIM);

                $base_dum = $data_fim->format(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

                $x = 0;

                $DevolucoesItensQuery[] = DB::connection('srv_prologos')->table('DUI' . $base_dum)->whereIn('NUMDOC', $DevolucoesObj->pluck('NUMDOC'));

                while($data_fim->lessThan($junho_2019)){

                    $data_fim = $data_fim->addMonthsNoOverflow(1);

                    $base_dum = $data_fim->format(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

                    $DevolucoesItensQuery[] = DB::connection('srv_prologos')->table('DUI' . $base_dum)->whereIn('NUMDOC', $DevolucoesObj->pluck('NUMDOC'))->union($DevolucoesItensQuery[$x++]);
                }

                $DevolucoesItensObj = $DevolucoesItensQuery[$x]->get();

            }

            foreach($pedidoPortalObj->itens_pedido as $item){

                $listagem_precos_parametros['produto'] = $item->cod_produto;

                $precos = $listagemDePrecosController->filter($listagem_precos_parametros, false, false, true, true, false, false, false);

                if(
                    !is_null($item->coluna_a) ||
                    !is_null($item->coluna_b) ||
                    !is_null($item->coluna_c) ||
                    !is_null($item->preco_base) 
                ){
                    $coluna_a = $item->coluna_a;
                    $coluna_b = $item->coluna_b;
                    $coluna_c = $item->coluna_c;
                    $preco_base = $item->preco_base;
                }
                else if(!empty($precos)){
                    $coluna_a = parserNumber($precos[0]['coluna_a']);
                    $coluna_b = parserNumber($precos[0]['coluna_b']);
                    $coluna_c = parserNumber($precos[0]['coluna_c']);
                    $preco_base = $item->info_produto->PRCVND_PREFIX_A??'';
                }

                if(
                    !is_null($coluna_a) &&
                    !is_null($coluna_b) &&
                    !is_null($coluna_c) &&
                    !is_null($preco_base)
                ){
                    $preco_unitario = (float) $item->preco_unitario;

                    if ( $coluna_a * (1 + $fator_comissao ) <= $preco_unitario ){
                        $porcentagem_acrescida = ((($preco_unitario / $coluna_a) - $fator_comissao) - 1) * 100;
                        $acrescimo_comissao = ceil($porcentagem_acrescida / 3);
        
                        $comissao_porcentagem = $pedidoPortalObj->usuario_detalhes->comissao_a + $acrescimo_comissao;

                        if(strtotime($NotaObj->DTEMIS) < strtotime('2019-04-25')){
                            $comissao_porcentagem = $comissao_porcentagem > 5 ? 5 : $comissao_porcentagem;
                        }
                        else{
                            $comissao_porcentagem = $comissao_porcentagem > 15 ? 15 : $comissao_porcentagem;
                        }

                        $coluna_preco = '0';
        
                    }
                    else if(
                        $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                        $coluna_a * $desconto_maximo_gerente < $preco_unitario
                    ){
                        $comissao_porcentagem = $pedidoPortalObj->usuario_detalhes->comissao_a;
                    }
                    else if($preco_unitario < ($coluna_a * $desconto_maximo_gerente)){

                        $porcentagem_desconto = 1 - ($preco_unitario / $coluna_a);

                        $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
        
                        $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
        
                        $comissao_porcentagem = $pedidoPortalObj->usuario_detalhes->comissao_a - $desconto_comissao;
        
                        $comissao_porcentagem = $comissao_porcentagem >= 2 ? $comissao_porcentagem : 2;
                    }

                    if(in_array($item->info_produto->PROCEDENCIA, $internacional)){
                        $aliquota_base = '12%';
                        $aliquota_aplicada = $aliquota_internacional . '%';
                    }
                    else if(in_array($item->info_produto->PROCEDENCIA, $nacional)){
                        $aliquota_base = '4%';
                        $aliquota_aplicada = $aliquota_nacional .'%';
                    }

                    $dois_e_meio = $coluna_a * $desconto_maximo_gerente;
                    $dois = $coluna_a * ($desconto_maximo_gerente - 0.015);

                    if($pedidoPortalObj->estabelecimento != 3){
                        $ipi_aplicado = ($item->info_produto->ALIQIPI??0) . '%';
                    }
                    else{
                        $ipi_aplicado = '0%';
                    }
                    
                    $itemPedidoVenda = $PedidoVendaObj->itens_pedido->filter( function ($item_venda) use ($item){
                        return strtolower($item->cod_produto) == strtolower(utf8_decode($item_venda->CODIGO));
                    })->first();

                    $quantidade = $itemPedidoVenda->QTDFAT??0;
                    $preco = $itemPedidoVenda->PU_ITEM_LIQ??0;

                    $devolucoes = 0;
 
                    $total = ($quantidade) * $preco;
                    $comissao_valor = ($total) * ($comissao_porcentagem/100);

                    $itens[] = [
                        'codigo' => $item->cod_produto,
                        'nome' => $item->info_produto->DESCR,
                        'quantidade' => parserValor($item->quantidade),
                        'quantidade_pedido' => parserValor($quantidade),
                        'devolvidos' => $devolucoes,
                        'total' => parserValor($total),
                        'preco_unitario_digitado' => parserValor($item->preco_unitario),
                        'preco_unitario_pedido' => parserValor($preco),
                        'comissao_calculada' => $item->comissao . "%",
                        'comissao_recalculada' => $comissao_porcentagem . "%",
                        'preco_base' => parserValor($preco_base),
                        'aliquota_base' => $aliquota_base,
                        'aliquota_aplicada' => $aliquota_aplicada,
                        'coluna_a' => parserValor($coluna_a),
                        'coluna_b' => parserValor($coluna_b),
                        'coluna_c' => parserValor($coluna_c),
                        'dois_e_meio' => parserValor($dois_e_meio),
                        'dois' => parserValor($dois),
                        'ipi_aplicado' => $ipi_aplicado,
                        'comissao_valor' => parserValor($comissao_valor),
                    ];

                    $comissao_valor_total += $comissao_valor;
                }
            }
        
            $info_pedido['itens'] = $itens;

            $info_pedido['comissao_pedido'] = parserValor(round(($comissao_valor_total / $NotaObj->TOTMERCADORIA)*10000 )/100);
            $info_pedido['valor_total_produtos_nota'] = $NotaObj->TOTMERCADORIA;
            $info_pedido['valor_total_produtos_pedido'] = $NotaObj->TOTMERCADORIA;
            $info_pedido['comissao_pedido_valor'] = parserValor($comissao_valor_total);
            $info_pedido['valor_total'] = parserValor($NotaObj->TOTMERCADORIA);
            $info_pedido['comissao_nota'] = $NotaObj->COMISSAO_VND;

            $data_faturamento = new Carbon($NotaObj->DTORDEM_FATURAR);

            $tabela = 'DUM' . $data_faturamento->format(str_pad($pedidoPortalObj->estabelecimento, 2, '0', STR_PAD_LEFT) . '_ym2');

            $info_pedido['hash'] = Crypt::encrypt([
                'nota' => $NotaObj->NF_NUMNF,
                'tabela' => $tabela,
                'estabelecimento' => $pedidoPortalObj->estabelecimento,
                'origem' => 'prologos'

            ]);

            $vendedoresObj = User::select('codigo_representante', 'name')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

            $vendedores = [];
            
            $vendedoresObj->each(function($item) use (&$vendedores){
                $vendedores[$item->codigo_representante] = $item->codigo_representante . ' - ' . $item->name;
            });

            $info_pedido["vendedor"] = $NotaObj->CODVND;

            $info_pedido['vendedores_array'] = $vendedores;

            $info_pedido["exibir_edicao"] = true;

            return $info_pedido;
        }
    }

    public function retornarDadosPedidoNasajon($fields){
        $info_pedido = [];
        $itens = [];
        $nacional = [0,3,4,5];
        $internacional = [1,2,6,7];
        $coluna_a = null;
        $coluna_b = null;
        $coluna_c = null;
        $preco_base = null;
        $result = PedidosVendaNasajon::
            with(['itens_pedido', 'itens_pedido.especificacao', 'nota', 'nota.revisao_vendedor_comissao' => function($query) use($fields){
                $query->where('vendedor', $fields['vendedor_id']);
            }, 'pedido_pre_pago'])
            ->where('id', $fields['id'])
            ->where('rascunho', 'false')
            ->where(function($query){
                $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
                $query->orWhereNull('grupodeoperacao');
            })
            ->first();

        $usuario_detalhes = User::find($fields['user_id']);

        $estabelecimento = $result->estabelecimento_codigo;

        $listagemDePrecosController = new ListagemDePrecosController();
        $estabelecimentos = returnEmpresasNasajonView();
        $clienteObj = $result->cliente_detalhes;
        $margemPrazoObj = MargemPrazo::where('estabelecimento', intval($estabelecimento))->first();
        $aliquotasObj = AliquotaPreco::where('origem', $this->getOrigemAttribute($estabelecimento))->where('estado', $clienteObj->uf)->get();
        $parametrosAprovacaoObj = ParametrosAprovacao::with('tipoUsuario')->where('estabelecimento', str_pad($estabelecimento, 2, '0', STR_PAD_LEFT))->get();

        $desconto_maximo_gerente = 1 - $parametrosAprovacaoObj[($parametrosAprovacaoObj->search(function ($item, $key){ return strtolower($item->tipoUsuario->nome) == 'gerente'; }))]->percentual_desconto / 100;
        if ($clienteObj['indicadorinscricaoestadual'] == 2 || $clienteObj['indicadorinscricaoestadual'] == 9){
            $aliquota_internacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === true;    
            })]->icms_venda_cliente_isento;

            $aliquota_nacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->icms_venda_cliente_isento;

            $frete = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->frete_adicional;
        }
        else {
            $aliquota_internacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === true;
            })]->icms_venda;

            $aliquota_nacional  =$aliquotasObj[ 
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->icms_venda;

            $frete = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->frete_adicional;

        }

        $devolucoes = 0;
        $fator_comissao = $margemPrazoObj->preco_b - 1;

        $info_pedido['frete_aplicado'] = '';
        $info_pedido['frete_preco'] = '';
        if(isset($result->pedido_portal)){

            if(!is_null($result->pedido_portal->frete_preco)){
                $info_pedido['frete_preco'] = $result->pedido_portal->frete_preco;
    
                if($result->pedido_portal->frete_preco == 'cif'){
                    $info_pedido['frete_aplicado'] = $frete . "%";
                }
                else{
                    $info_pedido['frete_aplicado'] = '0%';
                }
    
                if($result->pedido_portal->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';
                }
                else{
                    $info_pedido['frete_pedido'] = 'fob';                    
                }
    
            }
            else{
                if($result->pedido_portal->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';
    
                    if(!is_null($result->pedido_portal->transportadora_redespacho)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        
                    }
                    else{
                        if(isset($estabelecimentoCidadeFob) && !is_null($estabelecimentoCidadeFob)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        }
                        else{
                            $info_pedido['frete_preco'] = 'cif';
                            $info_pedido['frete_aplicado'] = $frete . '%';
                        }
                    }
                }
                else{
                    $info_pedido['frete_pedido'] =  'fob';
                    $info_pedido['frete_preco'] = 'fob';
                    $info_pedido['frete_aplicado'] = '0%';
                }                    
            }

            if(isset($result->pedido_portal->detalhesProjeto)){
                $info_pedido['id_projeto'] = $result->pedido_portal->detalhesProjeto->id;

                $nome_projeto = empty($result->pedido_portal->detalhesProjeto->nome_projeto)? '': $result->pedido_portal->detalhesProjeto->nome_projeto;
                $cliente_projeto = empty($result->pedido_portal->detalhesProjeto->cliente)? '' : $result->pedido_portal->detalhesProjeto->cliente->nome;
                
                $info_pedido['titulo_modal_projeto'] = 'Detalhes do Projeto: ' . $result->pedido_portal->detalhesProjeto->id . ' - '. $nome_projeto . ' - Estabelecimento: '. $estabelecimentos[intval($estabelecimento)] . ' - Cliente: ' . $cliente_projeto;

            }
        }

        $lista_precos_parametros = [
            'frete' => $info_pedido['frete_preco'],
            'estabelecimento' => intval($result->estabelecimento_codigo),
            'estado' => $clienteObj->uf,
            'moeda' => 'real',
            'tipo_cliente' => (
                $clienteObj->inscricaoestadual == 'ISENTO' ||
                intval($clienteObj->indicadorinscricaoestadual) == 2 ||
                intval($clienteObj->indicadorinscricaoestadual) == 9
            ) ? 'isento' : 'juridico',
            'prazo_medio' => intval($result->pedido_portal->condicao_pagamento_detalhes->media??null),
            'frete' => $info_pedido['frete_preco']
        ];

        $total_pedido = 0;
        $comissao_total = 0;
        foreach($result->itens_pedido as $value){
            $lista_precos_parametros['produto'] = $value->item_item;

            $result_produto = $value->especificacao;

            $valor_portal = 0;
            if(!isset($result->pedido_portal)){
                $precos = $listagemDePrecosController->filter(new ListaDePrecosRequest($lista_precos_parametros), false, false, true, true, false, false, false);
                $comissao = '0%';
            }else{
                $produto = $result->pedido_portal->itens_pedido->where('cod_produto', $value->item_item)->first();
                $precos = [
                    [
                        'coluna_a' => parserValor($produto->coluna_a),
                        'coluna_b' => parserValor($produto->coluna_b),
                        'coluna_c' => parserValor($produto->coluna_c)
                    ]
                ];
                $comissao = $produto->comissao . '%';
                $valor_portal = $produto->preco_unitario;
            }

            if(!empty($precos) && !empty(parserNumber($precos[0]['coluna_a']))){
                $coluna_a = parserNumber($precos[0]['coluna_a']);
                $coluna_b = parserNumber($precos[0]['coluna_b']);
                $coluna_c = parserNumber($precos[0]['coluna_c']);
                $preco_base = '';
            }

            if(
                !is_null($coluna_a) &&
                !is_null($coluna_b) &&
                !is_null($coluna_c) &&
                !is_null($preco_base)
            ){
                if(isset($result['pedido_pre_pago'])){
                    $value['valortotal'] = $value['valortotal'] * 2;
                }

                $preco_unitario = (float) $value['valortotal'] / $value['quantidadecomercial'];

                if ( $coluna_a * (1 + $fator_comissao ) <= $preco_unitario ){
                    $porcentagem_acrescida = ((($preco_unitario / $coluna_a) - $fator_comissao) - 1) * 100;
                    $acrescimo_comissao = ceil($porcentagem_acrescida / 3);
    
                    $comissao_porcentagem = $usuario_detalhes->comissao_a + $acrescimo_comissao;

                    if(strtotime($result->emissao) < strtotime('2019-04-25')){
                        $comissao_porcentagem = $comissao_porcentagem > 5 ? 5 : $comissao_porcentagem;
                    }
                    else{
                        $comissao_porcentagem = $comissao_porcentagem > 15 ? 15 : $comissao_porcentagem;
                    }

                    $coluna_preco = '0';
    
                }
                else if(
                    $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                    $coluna_a * $desconto_maximo_gerente < $preco_unitario
                ){
                    $comissao_porcentagem = $usuario_detalhes->comissao_a;
                }
                else if($preco_unitario < ($coluna_a * $desconto_maximo_gerente)){

                    $porcentagem_desconto = 1 - ($preco_unitario / $coluna_a);

                    $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
    
                    $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
    
                    $comissao_porcentagem = $usuario_detalhes->comissao_a - $desconto_comissao;
    
                    $comissao_porcentagem = $comissao_porcentagem >= 2 ? $comissao_porcentagem : 2;
                }

                if(in_array($result_produto->procedencia, $internacional)){
                    $aliquota_base = '12%';
                    $aliquota_aplicada = $aliquota_internacional . '%';
                }
                else if(in_array($result_produto->procedencia, $nacional)){
                    $aliquota_base = '4%';
                    $aliquota_aplicada = $aliquota_nacional .'%';
                }

                $dois_e_meio = $coluna_a * $desconto_maximo_gerente;
                $dois = $coluna_a * ($desconto_maximo_gerente - 0.015);

                if($result['estabelecimento_codigo'] != 3){
                    $ipi_aplicado = ($result_produto->produto_nasajon->ipi??0) . '%';
                }
                else{
                    $ipi_aplicado = '0%';
                }

                $quantidade = $value['quantidade_faturada']??0;
                if(!empty($value['quantidadecomercial'])){
                    $preco = $value->valortotal / $value->quantidadecomercial;
                }else{
                    $preco = 0;
                }

                $devolucoes = 0;
                $total = $quantidade * $preco;
                $comissao_valor = ($total) * ($comissao_porcentagem/100);
                if(!empty($value->quantidade_apurada)){
                    $valor_apurado = $value->valortotal / $value->quantidadecomercial;
                }else{
                    $valor_apurado = 0;
                }
                $itens[] = [
                    'codigo' => $value->item_item,
                    'nome' => $value->produto_especificacao,
                    'quantidade' => parserValor($value->quantidadecomercial),
                    'quantidade_pedido' => parserValor($value->quantidade_faturada),
                    'devolvidos' => $devolucoes,
                    'total' => parserValor($value->valortotal),
                    'preco_unitario_digitado' => parserValor($valor_portal),
                    'preco_unitario_pedido' => parserValor($valor_apurado),
                    'comissao_calculada' => $comissao,
                    'comissao_recalculada' => $comissao_porcentagem . "%",
                    'preco_base' => parserValor($preco_base),
                    'aliquota_base' => $aliquota_base,
                    'aliquota_aplicada' => $aliquota_aplicada,
                    'coluna_a' => parserValor($coluna_a),
                    'coluna_b' => parserValor($coluna_b),
                    'coluna_c' => parserValor($coluna_c),
                    'dois_e_meio' => parserValor($dois_e_meio),
                    'dois' => parserValor($dois),
                    'ipi_aplicado' => $ipi_aplicado,
                    'comissao_valor' => parserValor($comissao_valor),
                ];

                $comissao_total += $comissao_valor;
                $total_pedido += $value->valortotal;
            }
        }
        
        $vendedoresObj = User::select('codigo_representante', 'name')->with('vendedor_nasajon')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

        $vendedores = [];
        
        $vendedoresObj->each(function($item) use (&$vendedores){
            if(isset($item->vendedor_nasajon->id)){
                $vendedores[$item->vendedor_nasajon->id] = $item->codigo_representante . ' - ' . strtoupper($item->name);
            }
        });

        $encriptar = [
            'id' => $result->notafiscal_id,
            'estabelecimento' => $result->estabelecimento_codigo,
            'origem' => 'nasajon'
        ];

        $info_pedido["vendedor"] = $fields['vendedor_id'];
        $encriptar["vendedor"] = $fields['vendedor_id'];
        if(isset($result->nota->revisao_vendedor_comissao)){
            $info_pedido["comissao_nota"] = $result->nota->revisao_vendedor_comissao->percentual_comissao;
            $encriptar['linha_vendedor'] = $result->nota->revisao_vendedor_comissao->df_vendedor;
            $info_pedido["exibir_edicao"] = true;
        }
        else{
            $info_pedido["comissao_nota"] = null;
            $encriptar['linha_vendedor'] = null;
            $info_pedido["exibir_edicao"] = false;
        }
        if($result->nota->total_produto > 0){
            $comissao_pedido = round(($comissao_total / $result->nota->total_produto * 100), 2);
        }else{
            $comissao_pedido = 0;
        }
        $info_pedido["estabelecimento"] = $estabelecimentos[intval($result['estabelecimento_codigo'])];
        $info_pedido["id"] = $result->pedido_portal->id??null;
        $info_pedido["pedido_gerado"] = $result->numero;
        $info_pedido["id_pedido"] = $result->id;
        $info_pedido["cliente"] = $clienteObj->nome;
        $info_pedido["estado"] = $clienteObj->uf;
        $info_pedido['vendedores_array'] = $vendedores;
        $info_pedido["localizacao_cliente"] = $clienteObj->cidade;
        $info_pedido["prazo_medio"] = $result->pedido_portal->condicao_pagamento_detalhes->media??null;
        $info_pedido["fator_prazo"] = $result->pedido_portal->condicao_pagamento_detalhes->media??0 * 0.04;
        $info_pedido["comissao_pedido"] = parserValor($comissao_pedido);
        $info_pedido["valor_total_produtos_nota"] = $result->valor_total->total;
        $info_pedido["valor_total_produtos_pedido"] = $result->pedido_portal->valor_total->total??0;
        $info_pedido["comissao_pedido_valor"] = parserValor($comissao_total);
        $info_pedido["origem"] = 'nasajon';
        $info_pedido["itens"] = $itens;

        if(isset($result['pedido_pre_pago'])){
            $info_pedido["valor_total"] = parserValor($result->valor*2);
        }
        else{
            $info_pedido["valor_total"] = parserValor($result->valor);
        }
        
        $info_pedido["hash"] = Crypt::encrypt($encriptar);

        return $info_pedido;

    }

    function aplicarNovaComissao(Request $request){

        $fields = $request->only('hash', 'comissao');

        $valores = Crypt::decrypt($fields['hash']);

        $comissao = parserNumber($fields['comissao']);
        if($valores['origem'] == 'prologos'){
            return response()->json(['linhas_atualizadas' => 0], 422);
        }

        else if($valores['origem'] == 'nasajon'){

            $retorno = $this->altecaoComissaoVendedorNota($valores['id'],$valores['vendedor'],$comissao);
            
            $nota = NotasNasajon::with(['campanhas.pedidoPortal.pedidoPrePago.lancamentos'])->find($valores['id']);
            
            if(isset($nota->campanhas->pedidoPortal->pedidoPrePago->lancamentos) && strpos($nota->campanhas->pedidoPortal->tipo_venda, "pre_pago") !== false){
                if(!empty($nota->campanhas->pedidoPortal->pedidoPrePago->lancamentos)){
                    $pedidos_pre = ChequesPedidosPrepagos::where('pedido_prepago_id',$nota->campanhas->pedidoPortal->pedidoPrePago->lancamentos[0]->pedido_prepago_id)
                    ->update(['comissao' => $comissao]);
                }
            }

            if($retorno['status'] != 'error'){
                return response()->json($retorno, 200);
            }else{
                return response()->json($retorno, 422);
            }

        }

    }


    function aplicarNovoVendedor(Request $request){

        $fields = $request->only('hash', 'vendedor');

        $valores = Crypt::decrypt($fields['hash']);

        if($valores['origem'] == 'prologos'){
            return response()->json(['linhas_atualizadas' => 0], 422);
        }
        else if($valores['origem'] == 'nasajon'){
            $update = DB::connection('nasajon')->select("select integracoes.alteracao_vendedor_docfis('" . $valores['linha_vendedor'] . "', '". $request['vendedor'] ."')");

            $resposta = (json_decode(str_replace('")', '', str_replace('("', '', str_replace('""', '"', $update[0]->alteracao_vendedor_docfis)))));

            return response()->json(['linhas_atualizadas' => $resposta->codigo], 200);
        }

    }

    public function modificarComissaoNotaNasajon(Request $request){
        $fields = $request->only('hash', 'vendedor', 'comissao');

        $valores = Crypt::decrypt($fields['hash']);

        unset($fields['hash']);
        $comissao = parserNumber($request['comissao']);
        if(empty($comissao)){
            $comissao = 0;
        }
        $retorno = [];
        if($valores['vendedor'] == $fields['vendedor']){
            $retorno = $this->altecaoComissaoVendedorNota($valores['id'], $valores['vendedor'], $comissao);
        }else{
            $retorno = $this->excluirComissaoVendedorNota($valores['id'], $valores['vendedor']);
            if($this->verificaSeExisteVendedorNaNota($valores['id'], $fields['vendedor']) == false){
                $retorno = $this->adicionarComissaoVendedorNota($valores['id'], $fields['vendedor'], $comissao);
            }
        }

        if($retorno['status'] != 'error'){
            return response()->json($retorno, 200);
        }else{
            return response()->json($retorno, 422);
        }
    }

    public function getOrigemAttribute($estabelecimento){
        switch ($estabelecimento){
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

    public static function revisarDados(){
        $PedidoPortalObj = PedidoPortal::where('nasajon', true)
            ->where('status_pedido', 3)
            ->whereBetween('data_pedido', ['2019-07-07', '2019-08-03'])
            ->whereHas('usuario_detalhes', function($query){
                $query->where('tipo_usuario_id', 12);
            })
            ->where("usuario", '!=', 65)
            ->get();
        foreach($PedidoPortalObj as $pedido){
            $comissao = 0;

            foreach ($pedido->itens_pedido as $item){
                $comissao += ($item->quantidade * $item->preco_unitario) * ($item->comissao / 100);
            }
            $porcentagem_comissao = (float) (($comissao) / floatval($pedido->valor_total_produtos)) * 100;

            $PedidosVendaNasajonObj = PedidosVendaNasajon::query()
                ->where('rascunho', 'false')
                ->where(function($query){
                    $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
                    $query->orWhere(['grupodeoperacao' => NULL]);
                })
                ->where("numero", $pedido->pedido_gerado)
                ->where("operacao_codigo", $pedido->codigo_operacao)
                ->first();
            if(empty($PedidosVendaNasajonObj)){
                continue;
            }
            $FaturamentoNotaNasajon = FaturamentoNotaNasajon::query()
                ->where('Identificador Documento', $PedidosVendaNasajonObj->notafiscal_id)
                ->first();
            if(empty($FaturamentoNotaNasajon) || empty($FaturamentoNotaNasajon["Vendedor - Percentual Comissão"])){
                continue;
            }
            if($FaturamentoNotaNasajon["Vendedor - Percentual Comissão"] != $porcentagem_comissao){
                echo $FaturamentoNotaNasajon["Número Documento"].';'.$FaturamentoNotaNasajon["Vendedor - Percentual Comissão"] .';'.round($porcentagem_comissao, 2) . "\n";
                $comissao = (string) round($porcentagem_comissao, 2);
                $comissao = str_replace(',','.', $comissao );
                $update = DB::connection('nasajon')->select("SELECT integracoes.alteracao_comissao('" . $FaturamentoNotaNasajon["Identificador Documento"] . "', ". $comissao .")");
            }
        }
    }

    private function adicionarComissaoVendedorNota($id_nota, $id_vendedor, $comissao){
        $sql_comissao = "select * from integracoes.api_nota_vendedornovo(
            '{$id_nota}', /* id_nota */
            '{$id_vendedor}', /* id_vendedor */
            '100', /* participacao */
            '{$comissao}', /* comissao */
            true /* vendedor_principal */
        )";
        return $this->executarComissoa($sql_comissao);
    }

    private function altecaoComissaoVendedorNota($id_nota, $id_vendedor, $comissao){
        $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
            '{$id_nota}', /* id_nota */
            '{$id_vendedor}', /* id_vendedor */
            '100', /* participacao */
            '{$comissao}', /* comissao */
            true /* vendedor_principal */
        )";
        return $this->executarComissoa($sql_comissao);
    }

    private function excluirComissaoVendedorNota($id_nota, $id_vendedor){
        $sql_comissao = "select * from integracoes.api_nota_vendedorexcluir(
            '{$id_nota}', /* id_nota */
            '{$id_vendedor}' /* id_vendedor */
        );";
        return $this->executarComissoa($sql_comissao);
    }

    private function executarComissoa($sql_comissao){
        try{
            $return = DB::connection('nasajon')->select($sql_comissao);
        }catch(\Exception $e){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e, $sql_comissao],
                'response' => []
            ];
        }
        return $this->tratarRetornoApiNasajon($return);
    }

    private function tratarRetornoApiNasajon($retorno){
        $response = [];
        $retorno = reset($retorno);
        $mensagem = $retorno->mensagem;
        unset($retorno);
        $mensagem = json_decode($mensagem);
        if($mensagem->codigo == 'ERRO'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$mensagem],
                'response' => []
            ];
        }else{
            return [
                'status' => 'success',
                'message' => '',
                'error' => [$mensagem],
                'response' => []
            ];
        }
    }

    private function verificaSeExisteVendedorNaNota($nota, $vendedor){
        $VendedorComissaoNotaObj = VendedorComissaoNota::query();
        $VendedorComissaoNotaObj->where('id_docfis', $nota);
        $VendedorComissaoNotaObj->where('vendedor', $vendedor);
        $dados = $VendedorComissaoNotaObj->count();
        if($dados > 0){
            return true;
        }
        return false;
    }

}
