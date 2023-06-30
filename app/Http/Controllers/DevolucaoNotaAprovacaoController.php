<?php

namespace App\Http\Controllers;

use App\DevolucaoNota;
use App\ClienteNasajon;
use App\DevolucaoNotaMotivo;
use App\DevolucaoNotaProduto;
use App\DevolucaoNotaStatus;
use App\DevolucaoNotaLog;
use App\DevolucaoNotaAprovador;
use App\NotasNasajon;
use App\TransportadorNasajon;
use App\User;
use App\LancamentoDebCredVendedor;
use App\NotasImportadasEntrada;
use App\ContasNasajon;
use App\PedidoFormaPagamentoNasajon;
use App\DevolucaoNotaTituloCancelado;
use App\DevolucaoNotaTituloAbertoDescontado;
use App\DevolucaoNotasDocumento;

use App\Http\Controllers\EmailController;
use App\Http\Controllers\BaixaTituloController;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;

use App\Http\Requests\DevolucaoNotaAprovacaoReprovarRequest;
use App\Http\Requests\DevolucaoNotaAprovacaoAprovarRequest;
use App\Http\Requests\DevolucaoNotaAprovacaoCancelarRequest;
use App\Http\Requests\DevolucaoNotaAprovacaoEspecificarFreteRequest;
use App\Http\Requests\DevolucaoNotaAprovacaoEditarRequest;
use App\Http\Requests\DevolucaoNotaConfirmaRecebimentoFrete;
use App\Http\Requests\BaixarTituloNaNasajonRequest;

use Auth;

class DevolucaoNotaAprovacaoController extends Controller
{

    public $storage = 'public/devolucao_nota';
    private $storage_files = 'public/devolucao_nota_documentos/';

    private $liberacao_logistica = [863, 576]; 

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DevolucaoNotaAprovacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DevolucaoNotaAprovacao');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $status = DevolucaoNotaStatus::whereNotIn('id', [8,10])->orderBy('id', 'asc')->get()->pluck('descricao', 'id');

        $status->prepend('Todos não finalizados', 'todos_abertos');

        $gerentes = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;

        if(!in_array(Auth::user()->tipo_usuario_id, [18,15, 1, 11, 20, 21])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(Auth::user()->tipo_usuario_id == 13){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }

            $userObj = User::whereIn('id', $subordinadosObj)->get();
            if(Auth::user()->tipo_usuario_id == 19 && !empty(Auth::user()->codigo_representante)){
                $vendedor_representante[Crypt::encrypt(Auth::user()->id)] = strtoupper(Auth::user()->name);
            }

            foreach ($userObj as $key => $user) {
                if($user->tipo_usuario_id == 19){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if($user->tipo_usuario_id == 16 && !empty($user->codigo_representante) || 
                $user->tipo_usuario_id == 12 && !empty($user->codigo_representante) || $user->id == 1){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }

            unset($subordinadosObj);
        } 
        else {
            $subordinadosObj = User::with(["tipo_usuario"])->get();
            foreach ($subordinadosObj as $key => $userObj) {
                if($userObj->tipo_usuario_id == 19){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }else if(!empty($userObj->codigo_representante)){
                    $vendedor_representante[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
            $gerentes[Crypt::encrypt(0)] = 'OUTROS';

            asort($vendedor_representante);
            unset($subordinadosObj);
        }

        if(Auth::user()->tipo_usuario_id == 19){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(Auth::user()->tipo_usuario_id == 13){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(
            Auth::user()->tipo_usuario_id != 16 &&
            Auth::user()->tipo_usuario_id != 12 &&
            !Auth::user()->hasRole('Cliente')
        ){
            $check_gerentes = true;
            $check_supervisores = true;
            $check_vendedor_representante = true;
        }
        
        $variaveis_view = [
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'representantes'                => $vendedor_representante,
            'estabelecimentos'              => $estabelecimentos,
            'status'                        => $status
        ];

        return view('programs.devolucao_nota_aprovacao.index')->with($variaveis_view);
    }

    public function filter(Request $request){

        $fields = $request->only('estabelecimento', 'gerentes', 'vendedor_representante', 'nota_fiscal', 'cliente', 'status', 'sem_frete', 'data_inicio', 'data_fim', 'ordem_devolucao');

        $query = DevolucaoNota::with('nota_nasajon', 'status_detalhes', 'aprovadores', 'cliente');

        if(isset($fields['nota_fiscal']) && !empty($fields['nota_fiscal'])){
            $query->where(DB::Raw('trim(leading \'0\' from nota_fiscal)'), ltrim($fields['nota_fiscal'], 0));
        }

        if(isset($fields['estabelecimento'])){
            $query->where("estabelecimento", str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['ordem_devolucao'])){
            $query->where("id", $fields['ordem_devolucao']);
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $cliente = ClienteNasajon::where(DB::Raw("CONCAT(nome, ' - ', cpf_cnpj)"), 'ilike', '%' . $fields['cliente'] . '%')->get();

            $query->whereIn('cliente_cpf_cnpj', $cliente->pluck('cpf_cnpj'));
        }

        if(isset($fields['status']) && !empty($fields['status'])){
            if($fields['status'] == 11){
                $query->withTrashed()
                    ->where(function($query){
                        $query
                            ->whereNotNull('deleted_at')
                            ->orWhere('devolucao_nota_status_id', 11);
                    });
            }
            else if($fields['status'] == 'todos_abertos'){
                $query->whereNotIn('devolucao_nota_status_id', [7, 8, 11]);
            }
            else{
                $query->where('devolucao_nota_status_id', $fields['status']);
            }
        }
        else{
            $query->whereNotIn('devolucao_nota_status_id', [8]);
        }

        if(isset($fields['sem_frete']) && $fields['sem_frete'] == true){
            $query->whereDoesntHave('aprovadores', function($query){
                $query->where('devolucao_nota_status_id', 12);
            });
        }

        if(Auth::user()->hasRole('Cliente')){
            if(strlen(Auth::user()->username) == 14){
                $documento = substr(Auth::user()->username, 0, 2) . '.' . substr(Auth::user()->username, 2, 3) . '.' . substr(Auth::user()->username, 5, 3) . '/' . substr(Auth::user()->username, 8, 4) . '-' . substr(Auth::user()->username, 12, 2);
            }
            else if(strlen(Auth::user()->username) == 11){
                $documento = substr(Auth::user()->username, 0, 3) . '.' . substr(Auth::user()->username, 3, 3) . '.' . substr(Auth::user()->username, 6, 3) . '-' . substr(Auth::user()->username, 9, 2);
            }

            $query->where('cliente_cpf_cnpj', $documento);
        }


        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);

            $query->where('created_at', '>=', $data_inicio->format('Y-m-d 00:00:00'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);

            $query->where('created_at', '<=', $data_fim->format('Y-m-d 23:59:59'));
        }

        $devolucoesObj = $query->get();

        if(Auth::user()->tipo_usuario_id == 19){
            $gerente = Auth::id();
        }
        else if(Auth::user()->tipo_usuario_id == 13 && Auth::id() != 105){
            $gerente = Auth::user()->responsavel;
        }
        else if(in_array(Auth::user()->tipo_usuario_id, [16, 12])){
            $vendedor = Auth::id();
        }
        
        if(
            isset($fields['vendedor_representante']) && !empty($fields['vendedor_representante'])
        ){
            try {
                $vendedor = Crypt::decrypt($fields['vendedor_representante']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!',
                        'response' => [
                            'dados' => [] 
                        ]
                    ], 422);
            }

        }
        else if(
            (isset($fields['gerentes']) && !empty($fields['gerentes'])) &&
            (!isset($fields['vendedor_representante']) || is_null($fields['vendedor_representante']))
        ){
            try {
                $gerente = Crypt::decrypt($fields['gerentes']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!',
                        'response' => [
                            'dados' => [] 
                        ]
                    ], 422);
            }
        }


        if(isset($vendedor)){
            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            $users = User::
                where('id', $vendedor)
                ->whereNotNull('codigo_representante')
                ->first();

            $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                return $users->codigo_representante == $linha->nota_nasajon['revisao_vendedor_comissao']['vendedor_codigo'];
            });
            
        }
        if(isset($gerente) && !isset($vendedor)){
            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            if($gerente == 0){
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $users = User::
                    where(function ($query) use($gerentes){
                        $query->whereNotIn('responsavel', $gerentes)
                            ->whereNotIn('id', $gerentes);
                    })
                    ->whereNotNull('codigo_representante')
                    ->get();

                $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                    if(isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo)){
                        return $users->pluck('codigo_representante')->contains($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || !isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || empty($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || empty($linha->nota_nasajon->revisao_vendedor_comissao);
                    }
                });
            }
            else{
                $users = User::where('responsavel', $gerente)->orWhere('id', $gerente)->get();

                $devolucoesObj = $devolucoesObj->filter(function($linha) use($users, $gerente){
                    if(isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo)){
                        return $users->pluck('codigo_representante')
                            ->contains($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) ||
                            $users->pluck('codigo_representante')
                            ->contains($gerente);
                    }
                });
            }
        }

        $response = [];

        $estabelecimentos = returnEmpresasNasajonView();

        $hoje = Carbon::now();

        $devolucoesObj->each(function($devolucao) use (&$response, $estabelecimentos, $hoje){
            $linha = [];

            $id = Crypt::encrypt($devolucao->id);

            $linha['id'] = $id;
            $linha['numero'] = $devolucao->id;
            $linha['nota_fiscal'] = $devolucao->nota_fiscal;
            $linha['nota_id'] = $devolucao->nota_id;
            $linha['emissao'] = (!empty($devolucao->nota_nasajon->emissao)) ? parserData($devolucao->nota_nasajon->emissao) : '';
            $linha['estabelecimento'] = $estabelecimentos[intval($devolucao->estabelecimento)];
            $linha['cliente'] = (!empty($devolucao->cliente->nome)) ? $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj : '';
            $linha['valor'] = parserValor($devolucao->valor);

            if(!empty($devolucao->motivo_devolucao)){
                $linha['motivo'] = $devolucao->motivo_devolucao->descricao;
            }
            else{
                $linha['motivo'] = '';
            }

            if(!is_null($devolucao->deleted_at)){
                $linha['status_exibir'] = 'Cancelado';
                $linha['status'] = 11;
            }
            else{
                $linha['status_exibir'] = $devolucao->status_detalhes->descricao;
                $linha['status'] = $devolucao->devolucao_nota_status_id;
            }

            if(
                $devolucao->devolucao_nota_status_id > 4 ||
                $devolucao->aprovadores->pluck('devolucao_nota_status_id')->contains(13) 
            ){
                $linha['status_exibir'] .= ' - Devolução recebida';
            }
            else{
                $linha['status_exibir'] .= ' - Devolução não recebida';
            }

            if($devolucao->valor_parcial){
                $linha['valor_parcial'] = 'Parcial';
            }
            else{
                $linha['valor_parcial'] = 'Completo';
            }

            $linha['mostrar_botoes'] = false;
            $linha['botao_cancelar'] = false;
            $linha['botao_frete'] = false;

            if(
                $devolucao->status_detalhes->selecionavel
                &&  
                    (   Auth::user()->hasPermissionTo("action App\DevolucaoNotaAprovacao " . $devolucao->status_detalhes->chave) || 
                        (Auth::user()->tipo_usuario_id == 1 || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || in_array(Auth::id(), [46, 105]))
                    )
            ){
                if(
                    $devolucao->devolucao_nota_status_id == 2 &&
                    !empty($devolucao->nota_nasajon->emissao) &&
                    Carbon::now()->diffInDays($devolucao->nota_nasajon->emissao) > 30 &&
                    !(Auth::user()->tipo_usuario_id == 1 || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || in_array(Auth::id(), [46, 105]))
                ){
                    $linha['mostrar_botoes'] = false;
                }
                else{
                    if($devolucao->devolucao_nota_status_id == 2){
                        if(in_array(Auth::id(), [46, 105])){
                            $linha['mostrar_botoes'] = true;
                        }
                    }else{
                        $linha['mostrar_botoes'] = true;
                    }
                }

            }


            if(
                (in_array(Auth::id(), $this->liberacao_logistica) || Auth::id() == 9334 || Auth::user()->tipo_usuario_id == 1) && 
                !in_array($devolucao->devolucao_nota_status_id, [7, 11])
            ){
                $linha['botao_cancelar'] = true;

                if(
                    !$devolucao->aprovadores->pluck('devolucao_nota_status_id')->contains(11) &&
                    $devolucao->aprovadores->pluck('devolucao_nota_status_id')->contains(2)

                ){
                    $linha['botao_frete'] = true;
                }
            }

            if(!in_array($devolucao->devolucao_nota_status_id, [7, 8, 11])){
                $linha['parado'] = intval($devolucao->updated_at->diffInDays($hoje));
            }
            else{
                $linha['parado'] = '';
            }
            
            $response[] = $linha;
        });

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => [
                    'dados' => $response 
                ]
            ], 220
        );

    }

    public function aprovarModal(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::
            with([
                'nota_nasajon',
                'nota_nasajon.item',
                'nota_nasajon.faturamento',
                'nota_nasajon.faturamento_nota_devolucao' => function($query){
                    $query->where('TIPO', 'DEVOLUÇÃO');
                },
                'titulosAbertos' => function($query){
                    $query->orderBy('vencimento', 'desc');
                },
                'nota_nasajon.faturamento_nota_devolucao',
                'produtos',
                'produtos.produto_na_nota.produto_detalhes',
                'status_detalhes',
                'aprovadores',
                'aprovadores.status',
                'aprovadores.aprovador_detalhes',
                'logs',
                'cliente',
                'nota_remessa_nasajon',
                'documentos',
            ])
            ->find($id);
           
        if(empty($devolucaoNotaObj)){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Requisição não encontrada!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $motivos = [];
        $documentos_diversos = [];
        $documento_isento = null;

        if(!empty($devolucaoNotaObj->documentos)){
            foreach($devolucaoNotaObj->documentos as $documentos){
                if($documentos->tipo_documento != 'cliente_isento'){
                    $documentos_diversos[] =  [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'descricao' => $documentos->nome_arquivo,
                        'id' => encrypt($documentos->id)
                    ];
                }
                if($documentos->tipo_documento === 'cliente_isento'){
                    $documento_isento = Storage::url($this->storage_files . $documentos->caminho);
                }
            }
        }

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::all();

        $devolucaoNotaMotivoObj->each(function ($motivo) use(&$motivos){
            $motivos[$motivo->id] = $motivo['descricao']; 
        });

        $retorno = [];

        $retorno['documentos_diversos'] = $documentos_diversos;
        $retorno['documento_isento'] = $documento_isento;
        $retorno['motivos'] = $motivos;
        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['estabelecimento_codigo'] = $devolucaoNotaObj->estabelecimento;
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['valor'] = isset($devolucaoNotaObj->nota_nasajon->valor)?parserValor($devolucaoNotaObj->nota_nasajon->valor):'';
        $retorno['emissao'] = isset($devolucaoNotaObj->nota_nasajon->emissao)?parserData($devolucaoNotaObj->nota_nasajon->emissao):'';
        $retorno['motivo'] = $devolucaoNotaObj->motivo;
        $retorno['status'] = $devolucaoNotaObj->devolucao_nota_status_id;
        $retorno['transportador'] = $devolucaoNotaObj->transportador;
        $retorno['transportador_email'] = $devolucaoNotaObj->transportador_email;
        
        if($devolucaoNotaObj->valor_parcial){
            $retorno['valor_parcial'] = 'Valor parcial';
        }
        else{
            $retorno['valor_parcial'] = 'Devolução completa';
        }

        if(isset($devolucaoNotaObj->nota_nasajon->faturamento['Código da Operação']) && in_array($devolucaoNotaObj->nota_nasajon->faturamento['Código da Operação'], ['VENDAORDEMTORO', 'VENDAAORDEM'])){
            $retorno['tipo_venda'] = 'Venda por conta e ordem';
        }
        else{
            $retorno['tipo_venda'] = 'Venda normal';
        }

        $retorno['nome_contato'] = $devolucaoNotaObj->nome_contato;
        $retorno['telefone_contato'] = $devolucaoNotaObj->telefone_contato;
        $retorno['email_contato'] = $devolucaoNotaObj->email_contato;
        $retorno['nota_cliente_numero'] = $devolucaoNotaObj->nota_cliente_numero;
    
        $responsabilidade = [
            'textil' => 'MN Têxtil',
            'cliente' => 'Cliente',
            'representante' => 'Representante']
        ;

        if(!is_null($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['responsabilidade_frete_exibir'] = $responsabilidade[$devolucaoNotaObj->responsabilidade_frete];
        }
        else{
            $retorno['responsabilidade_frete_exibir'] = '';
        }

        if(!empty($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['frete_valor'] = parserValor($devolucaoNotaObj->frete_valor);
        }
        else{
            $retorno['frete_valor'] = '';
        }

        if(!is_null($devolucaoNotaObj->arquivo)){
            if(Storage::exists($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo)){
                $retorno['arquivo'] = Storage::url($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->laudo_imagem)){
            if(Storage::exists( $this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem)){
                $retorno['laudo_tecnico'] = Storage::url($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem);
            }
        }

        if(!is_null($devolucaoNotaObj->nota_cliente_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo)){
                $retorno['nota_cliente_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->romaneio_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo)){
                $retorno['romaneio_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo);
            }
        }

        $retorno['observacao'] = $devolucaoNotaObj->observacao;

        $retorno['produtos'] = [];

        $retorno['aprovadores'] = [];

        $retorno['nota_remessa_id'] = $devolucaoNotaObj->nota_remessa??'';

        $retorno['notas_devolucao'] = [];

        $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->each(function($faturamento) use(&$retorno){

            $notaImportadaEntrada = NotasImportadasEntrada::where('documento_numero', ltrim($faturamento['Número Documento'], '0'))
                ->where('estabelecimento', $faturamento['Estabelecimento'])
                ->first();

            if(!empty($notaImportadaEntrada)){
                $retorno['notas_devolucao'][] = [
                    'numero' => $faturamento['Número Documento'],
                    'id' => $notaImportadaEntrada->id,
                ];
            }
        });

        $devolucaoNotaObj->aprovadores->each( function ($aprovador) use(&$retorno){

            $linha = [];

            $linha['status'] = $aprovador->status->descricao;
            $linha['aprovador'] = $aprovador->aprovador_detalhes->name;
            $linha['data'] = $aprovador->created_at->format('d/m/Y H:i:s');

            $retorno['aprovadores'][] = $linha;

        });

        $ultimoLogReprovacao = $devolucaoNotaObj->logs->where('status_antigo', $devolucaoNotaObj->devolucao_nota_status_id)->where('acao', 'Reprovado')->sortByDesc('created_at');

        if($ultimoLogReprovacao->isNotEmpty()){
            $retorno['motivo_reprovacao'] = $ultimoLogReprovacao->first()->mensagem;
        }

        if($devolucaoNotaObj->valor_parcial === true || $devolucaoNotaObj->aprovadores->whereIn('devolucao_nota_status_id', [4,5])->isNotEmpty()){

            $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
            
            $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                ->where('id', '!=', $id)
                ->get();

            $devolucaoNotaObj->produtos->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){
            
                $devolvidos = 0;

                $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                    ->pluck('itens_faturamento')
                    ->flatten()
                    ->where('Item - Código', $item->produto_na_nota->produto_detalhes->codigo_produto);
                
                if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                    $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                }
                
                $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
            
                if($requisicaoDevolucaoItemObj->isNotEmpty()){
                    $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                }

                $linha = [];

                $linha['id'] = $item->produto_id;
                $linha['codigo'] = $item->produto_na_nota->produto_detalhes->codigo_produto;
                $linha['grupo'] = $item->produto_na_nota->produto_detalhes->grupo;
                $linha['descricao'] = $item->produto_na_nota->produto_detalhes->descricao;
                $linha['quantidade'] = parserValor($item->produto_na_nota->quantidade - $devolvidos);
                $linha['quantidade_devolvida'] = parserValor($item->quantidade);
                $linha['quantidade_recebida'] = parserValor($item->quantidade_recebida);

                $retorno['produtos'][] = $linha;
            });
        }
        else if($devolucaoNotaObj->valor_parcial === false && $devolucaoNotaObj->devolucao_nota_status_id == 4){
    
            $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
            
            $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                ->where('id', '!=', $id)
                ->get();

            $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){
                $devolvidos = 0;

                $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                    ->pluck('itens_faturamento')
                    ->flatten()
                    ->where('Item - Código', $item->produto_detalhes->codigo_produto);
                
                if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                    $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                }
                
                $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
            
                if($requisicaoDevolucaoItemObj->isNotEmpty()){
                    $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                }

                $linha = [];
                $linha['id'] = $item->id_item_nota;
                $linha['codigo'] = $item->produto_detalhes->codigo_produto;
                $linha['grupo'] = $item->produto_detalhes->grupo;
                $linha['descricao'] = $item->produto_detalhes->descricao;
                $linha['quantidade'] = parserValor($item->quantidade);
                $linha['quantidade_devolvida'] = (($item->quantidade - $devolvidos) > 0) ? parserValor($item->quantidade - $devolvidos) : parserValor($item->quantidade);
    
                $retorno['produtos'][] = $linha;
            });
        }
        
        $retorno['mostrar_botao_devolucao'] = false;

        if(!$devolucaoNotaObj->aprovadores->pluck('devolucao_nota_status_id')->contains(13)){
            $retorno['mostrar_botao_devolucao'] = true;
        }
        
        $retorno['mostrar_quantidades_devolvidas'] = false;

        if($devolucaoNotaObj->aprovadores->where('devolucao_nota_status_id', 4)->isNotEmpty()){
            $retorno['mostrar_quantidades_devolvidas'] = true;
        }

        if($devolucaoNotaObj->devolucao_nota_status_id == 15){
            $titulos_pagos = [];
            $titulos_abertos = [];
            $titulo_credito = [];
            $titulos_cancelados = [];
            $total_titulos_pagos = 0;
            $credito    = 0;
            $saldo_total   = 0;
            $acumulador = 0;
            $data_atual  = Carbon::now();
            $total_titulos_abertos = [
                'valor' => 0,
                'valor_baixado' => 0,
                'saldo' => 0,
            ];
            
            $valor_pago = $devolucaoNotaObj->tituloPagamentos->sum('valor');
           
            $saldo_total   = $devolucaoNotaObj->titulosAbertos->sum('valor');
            $saldo         = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->sum('valor');
            $saldo_vencido = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->sum('valor');
    
            $quantidade_titulos_abertos               = $devolucaoNotaObj->titulosAbertos->count();
            $quantidade_titulos_abertos_nao_vencidos  = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->count();
            $quantidade_titulos_abertos_vencidos      = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->count();

            $valor_abt_venc = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->sum('abatimento');
            $valor_abt      = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->sum('abatimento');
    
            $novo_saldo   = $saldo - $devolucaoNotaObj->valor;
            $novo_vencido = $devolucaoNotaObj->valor - $saldo_vencido;
            $resta_saldo  = $devolucaoNotaObj->valor;   

             $devolucaoNotaObj->tituloPagamentos->each(function($titulo) use(&$titulos_pagos, &$total_titulos_pagos){
                $titulos_pagos[] =[
                    'titulo' => $titulo->numero,
                    'parcela' => $titulo->parcela,
                    'data_emissao' => parserData($titulo->emissao),
                    'data_vencimento' => parserData($titulo->vencimento),
                    'data_pagamento' => parserData($titulo->data_pagamento),
                    'valor' => parserValor($titulo->valor),
                ];

                $total_titulos_pagos += $titulo->valor;
            });

            if($devolucaoNotaObj->valor_parcial === false){
                
                $devolucaoNotaObj->titulosAbertos->each(function($titulo) use(&$titulos_abertos, $saldo_total, &$credito, &$total_titulos_abertos, &$novo_saldo, $novo_vencido, $quantidade_titulos_abertos, $devolucaoNotaObj){
                                    
                    if($novo_saldo > 0){ 
                     $valor_baixado = $devolucaoNotaObj->valor / $quantidade_titulos_abertos;

                    }else{
                        $valor_baixado = $titulo->valor;
                        $credito = $devolucaoNotaObj->valor - $saldo_total;
                        
                        if($credito==0){
                            $novo_saldo = 0;

                        }                        
                       
                    }
                    $titulos_abertos[$titulo->parcela] =[
                        'titulo' => $titulo->numero,
                        'parcela' => $titulo->parcela,
                        'data_emissao' => parserData($titulo->titulo_emissao),
                        'data_vencimento' => parserData($titulo->vencimento),
                        'valor' => parserValor($titulo->valor),
                        'valor_baixado' => parserValor($titulo->valor),
                        'saldo' => '',
                    ];
    
                    $total_titulos_abertos['valor'] += $titulo->valor;
                    $total_titulos_abertos['valor_baixado'] += $titulo->valor;
                    $total_titulos_abertos['saldo'] += 0;
                });
               
            }else{ 
                $devolucaoNotaObj->titulosAbertos->each(function($titulo) use(&$titulos_abertos, &$acumulador, &$total_titulos_abertos, $data_atual,&$resta_saldo,&$novo_saldo,$saldo, $quantidade_titulos_abertos_nao_vencidos, $quantidade_titulos_abertos_vencidos,$quantidade_titulos_abertos,$valor_abt_venc,$saldo_vencido,$valor_abt,$devolucaoNotaObj){
                
                    if($resta_saldo>0){   
                        if($data_atual->gt($titulo->vencimento)){
                            
                            $valor_abater     = $valor_abt_venc + $titulo->valor;
                            $valor_abater     = parserValor($valor_abater);               
                            $valor_abater     = parserNumber($valor_abater); 
                                                                                  
                            if($valor_abater > $resta_saldo){
                                $valor_baixado = $resta_saldo;
                                $resta_saldo = 0;
                            }else{
                                $valor_baixado    = $valor_abater;
                                $resta_saldo      = $resta_saldo - $valor_baixado;   
                            }
                          
                            if($valor_baixado<0){
                                $valor_baixado = (-1) * $valor_baixado;
                            } 

                            $titulos_abertos[$titulo->parcela] =[
                                'titulo' => $titulo->numero,
                                'parcela' => $titulo->parcela,
                                'data_emissao' => parserData($titulo->titulo_emissao),
                                'data_vencimento' => parserData($titulo->vencimento),
                                'valor' => parserValor($titulo->valor),
                                'valor_baixado' => parserValor($valor_baixado),
                                'saldo' => empty($titulo->valor - $valor_baixado)? '' : parserValor($titulo->valor - $valor_baixado),
                                
                            ]; 
                            
                            $novo_saldo = -$resta_saldo; 
                                                       
     
                        }elseif($data_atual->lte($titulo->vencimento)){
                                
                                $valor_abater     = $valor_abt + $resta_saldo;
                                $valor_abater     = $valor_abater / $quantidade_titulos_abertos_nao_vencidos;
                               
                                $valor_abater     = parserValor($valor_abater);               
                                $valor_abater     = parserNumber($valor_abater);

                                if($resta_saldo>$saldo){
                                    $valor_baixado   = $titulo->valor;
                                    $novo_saldo     = $saldo - $resta_saldo;
                                   
                                }else{
                                    $valor_baixado    = $valor_abater;
                                    $novo_saldo      = $titulo->valor - $valor_abater;  
    
                                }
                                $acumulador += $valor_baixado;

                                if($acumulador > $devolucaoNotaObj->valor){
                                    $valor_baixado = $valor_baixado - ($acumulador - $devolucaoNotaObj->valor);
                                }
                                
                                if($valor_baixado<0){
                                    $valor_baixado = (-1) * $valor_baixado;
                                }    

                                $titulos_abertos[$titulo->parcela] =[
                                    'titulo' => $titulo->numero,
                                    'parcela' => $titulo->parcela,
                                    'data_emissao' => parserData($titulo->titulo_emissao),
                                    'data_vencimento' => parserData($titulo->vencimento),
                                    'valor' => parserValor($titulo->valor),
                                    'valor_baixado' => parserValor($valor_baixado),
                                    'saldo' => empty($titulo->valor - $valor_baixado)? '' : parserValor($titulo->valor - $valor_baixado),
                                ];

                               
                        }                                
                            $total_titulos_abertos['valor'] += $titulo->valor;
                            $total_titulos_abertos['valor_baixado'] += $valor_baixado;
                            $total_titulos_abertos['saldo'] += $titulo->valor - $valor_baixado;

                    }    
                    
                });
            }
           
            $total_titulos_abertos['valor'] = empty($total_titulos_abertos['valor'])? '' : parserValor($total_titulos_abertos['valor']);
            $total_titulos_abertos['valor_baixado'] = empty($total_titulos_abertos['valor_baixado'])? '' : parserValor($total_titulos_abertos['valor_baixado']);
            $total_titulos_abertos['saldo'] = empty($total_titulos_abertos['saldo'])? '' : parserValor($total_titulos_abertos['saldo']);

            if($novo_saldo < 0){
                $titulo_credito[] =[
                    'titulo' => $devolucaoNotaObj->nota_fiscal.'.1CRD',
                    'parcela' => 1,
                    'data_emissao' => date('d/m/Y'),
                    'valor' => empty($credito) ? parserValor((-1) * $novo_saldo) :   parserValor($credito),
                ];
            }

            $retorno['titulos'] = [
                'titulos_pagos' => $titulos_pagos,
                'titulos_abertos' => $titulos_abertos,
                'total_titulos_pagos' => empty($total_titulos_pagos)? '' : parserValor($total_titulos_pagos),
                'total_titulos_abertos' => $total_titulos_abertos,
                'titulo_credito' => $titulo_credito,
            ];

            $retorno['devolucao_completa'] = $devolucaoNotaObj->valor_parcial == false? true : false;
            $retorno['valor_devolucao'] = parserValor($devolucaoNotaObj->valor);
        }else{
            $retorno['titulos'] = [];
        }

        return view('programs.devolucao_nota_aprovacao.modal.aprovar')->with($retorno);
    }

    public function aprovacao(DevolucaoNotaAprovacaoAprovarRequest $request){

        $fields = $request->only('id', 'motivo', 'valor_parcial', 'descricao_documento','documento', 'nome_contato', 'telefone_contato', 'email_contato', 'produtos', 'mensagem', 'responsabilidade_frete', 'transportador', 'transportador_email', 'nota_cliente_numero', 'conferencia_devolucao_completa', 'produtos', 'nota_remessa','tipo_documento');
        
        $id = Crypt::decrypt($fields['id']);

        $devolucaoNotaObj = DevolucaoNota::with(
                'nota_nasajon',
                'nota_nasajon.item',
                'nota_nasajon.revisao_vendedor_comissao',
                'nota_nasajon.revisao_vendedor_comissao.usuario',
                'pedido',
                'pedido.forma_pagamento',
                'pedido.forma_pagamento.condicao',
                'aprovadores',
                'motivo_devolucao',
                'motivo_devolucao.status',
                'status_detalhes',
                'produtos',
                'cliente',
                'tituloPagamentos',
                'titulosAbertos'
            )
            ->find($id);
        
        if(isset($fields['mensagem']) && !empty($fields['mensagem'])){
            $mensagem = $request['mensagem'];
        }
        else{
            $mensagem = null;
        }

        $original = $devolucaoNotaObj->getOriginal();
        
        $statusAtual = $devolucaoNotaObj->motivo_devolucao->status->firstWhere('devolucao_nota_status_id', $devolucaoNotaObj->devolucao_nota_status_id);
       
        if(!empty($statusAtual)){
            $novoStatus = $devolucaoNotaObj->motivo_devolucao->status->firstWhere('ordem', $statusAtual->ordem+1);
        }else{
            $novoStatus = null;
        }

        if(!empty($novoStatus)){
            $devolucaoNotaObj->devolucao_nota_status_id = $novoStatus->devolucao_nota_status_id;
        }
        else{
            $devolucaoNotaObj->devolucao_nota_status_id = 7;
        }

        $devolucaoNotaLogObj = new DevolucaoNotaLog;
        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->usuario = Auth::id();
        $devolucaoNotaLogObj->mensagem = $mensagem;

        $devolucaoNotaObj->updated_by = Auth::user()->id;

        if($original['devolucao_nota_status_id'] == 1){
            $devolucaoNotaObj->laudo_imagem = 'laudo_devolucao_' . $devolucaoNotaObj->id . '.' . $request->file('laudo_imagem')->extension();
            $request->file('laudo_imagem')->storeAs($this->storage . '/laudo', $devolucaoNotaObj->laudo_imagem);

            $devolucaoNotaLogObj->acao = 'Laudo enviado';
            $devolucaoNotaLogObj->status_antigo = $original['devolucao_nota_status_id'];
            $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;
        }
        else if($original['devolucao_nota_status_id'] == 2){
            $devolucaoNotaObj->nome_contato = $request['nome_contato'];
            $devolucaoNotaObj->telefone_contato = $request['telefone_contato'];
            $devolucaoNotaObj->email_contato = $request['email_contato'];
            $devolucaoNotaObj->responsabilidade_frete = $fields['responsabilidade_frete'];

            $devolucaoNotaLogObj->acao = 'Aprovação da Requisição';
            $devolucaoNotaLogObj->responsabilidade_frete = $fields['responsabilidade_frete'];
            $devolucaoNotaLogObj->status_antigo = $original['devolucao_nota_status_id'];
            $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;

            $emailControllerObj = new EmailController;

            if(in_array($devolucaoNotaObj->estabelecimento, ['03', '04']) && !in_array($devolucaoNotaObj->motivo,[15,16]) && $devolucaoNotaObj->cliente->indicadorinscricaoestadual != 2){
                $mail_result = $emailControllerObj->sendEmailToken($devolucaoNotaObj->estabelecimento, 'email_instrucoes_devolucao', [$devolucaoNotaObj->email_contato], ['nome_cliente' => $devolucaoNotaObj->nota_nasajon->nome_cliente]);
            }
            else if(!in_array($devolucaoNotaObj->motivo,[15,16]) && $devolucaoNotaObj->cliente->indicadorinscricaoestadual != 2){
                $variaveis_email = [
                    'nome_cliente' => $devolucaoNotaObj->cliente->nome,
                    'razaosocial_estabelecimento' => $devolucaoNotaObj->estabelecimentoDetalhes->empresaDetalhes->razaosocial . ' - ' . $devolucaoNotaObj->estabelecimentoDetalhes->descricao,
                    'endereco_estabelecimento' => $devolucaoNotaObj->estabelecimentoDetalhes->tipologradouro . ' ' .$devolucaoNotaObj->estabelecimentoDetalhes->logradouro . ', ' . $devolucaoNotaObj->estabelecimentoDetalhes->numero . (!is_null($devolucaoNotaObj->estabelecimentoDetalhes->complemento)? ' – ' . $devolucaoNotaObj->estabelecimentoDetalhes->complemento: '') . ' – Bairro: ' . $devolucaoNotaObj->estabelecimentoDetalhes->bairro. ' – ' . $devolucaoNotaObj->estabelecimentoDetalhes->cidade . ' – ' . $devolucaoNotaObj->estabelecimentoDetalhes->endereco['cidadeBusca']['uf'],
                ];

                $mail_result = $emailControllerObj->sendEmailToken('00', 'email_instrucoes_devolucao_estabelecimentos', [$devolucaoNotaObj->email_contato], $variaveis_email);
            }
        }
        else if($original['devolucao_nota_status_id'] == 3){ // Confirmação de nota
            $devolucaoNotaObj->transportador = $fields['transportador'];
            $devolucaoNotaObj->transportador_email = $fields['transportador_email'];
            $devolucaoNotaObj->nota_cliente_numero = $fields['nota_cliente_numero'];

            $variaveis_email = [];

            if($request->hasFile('nota_cliente_arquivo')){

                if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo)){
                    Storage::delete($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo);
                }

                $devolucaoNotaObj->nota_cliente_arquivo = 'nota_cliente_'. $devolucaoNotaObj->id . '.' . $request->file('nota_cliente_arquivo')->extension();
                $request->file('nota_cliente_arquivo')->storeAs($this->storage . '/nota_cliente', $devolucaoNotaObj->nota_cliente_arquivo);

                $variaveis_email['nota_arquivo'] = 'Segue em anexo a nota para a coleta no cliente.';

                $email_anexo = [$this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo => ['as' => 'nota_cliente_' . $devolucaoNotaObj->id . '_' . date('d-m-Y') . '.' . $request->file('nota_cliente_arquivo')->extension()]];

            }
            else{
                $variaveis_email['nota_arquivo'] = null;
                $email_anexo = [];
            }

            $devolucaoNotaLogObj->acao = 'Confirmação de nota';
            $devolucaoNotaLogObj->mensagem = $mensagem;

            $emailControllerObj = new EmailController;
            
            $transportadorObj = TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'),$devolucaoNotaObj->transportador)->first();

            $variaveis_email['nome_transportadora'] = $transportadorObj->nome;

            if(!empty($mensagem)){
                $variaveis_email['observacao'] = $mensagem;
            }
            else{
                $variaveis_email['observacao'] = '';
            }

            switch ($devolucaoNotaObj->responsabilidade_frete) {
                case 'cliente':
                    $variaveis_email['email_responsavel_frete'] = $devolucaoNotaObj->email_contato;         
                default:
                    $variaveis_email['email_responsavel_frete'] = 'despacho@tecidosmn.com.br';
                    break;
            }

            $mail_result = $emailControllerObj->sendEmailToken($devolucaoNotaObj->estabelecimento, 'email_instrucoes_devolucao_transportadora', [$devolucaoNotaObj->email_contato], $variaveis_email, $email_anexo);
        
        }
        else if($original['devolucao_nota_status_id'] == 4){ // Conferência da expedição 

            $devolucaoNotaLogObj->acao = 'Conferência da expedição';

            if($request->file('romaneio_arquivo') !== null){
                if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo)){
                    Storage::delete($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo);
                }
    
                $devolucaoNotaObj->romaneio_arquivo = 'romaneio_'. $devolucaoNotaObj->id . '.' . $request->file('romaneio_arquivo')->extension();
                $request->file('romaneio_arquivo')->storeAs($this->storage . '/nota_cliente', $devolucaoNotaObj->romaneio_arquivo);

                $devolucaoNotaLogObj->mensagem .= ' - Enviado arquivo de romaneio';

            }

            if($devolucaoNotaObj->valor_parcial === true){
                if(isset($fields['conferencia_devolucao_completa'])){
                    $devolucaoNotaObj->produtos->each(function ($produto){
                        $produto->quantidade_recebida = $produto->quantidade;
                        $produto->save();
                    });
                }
                else{
                    foreach($fields['produtos'] as $produto){
                        $devolucaoNotaProdutoObj = DevolucaoNotaProduto::where('produto_id', $produto['id'])->first();
                        $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);
                        $devolucaoNotaProdutoObj->save();
                    }
                }
            }
            else{
                if(isset($fields['conferencia_devolucao_completa'])){
                    $verifica_produto = DevolucaoNotaProduto::where('devolucao_nota_id',$devolucaoNotaObj->id)
                    ->delete();
                    $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use($devolucaoNotaObj){
                        
                        $produto = new DevolucaoNotaProduto;

                        $produto->devolucao_nota_id = $devolucaoNotaObj->id;
                        $produto->produto_id = $item->id_item_nota;
                        $produto->quantidade = $item->quantidade;
                        $produto->quantidade_recebida = $item->quantidade;
                        $produto->created_by = Auth::id();

                        $produto->save();
                    });
                }
                else{
                    $verifica_produto = DevolucaoNotaProduto::where('devolucao_nota_id',$devolucaoNotaObj->id)
                    ->delete();
                    foreach($fields['produtos'] as $produto){
                        $notaVendaItemNasajonObj = $devolucaoNotaObj->nota_nasajon->item->where('id_item_nota', $produto['id'])->first();
                        $devolucaoNotaProdutoObj = new DevolucaoNotaProduto;

                        $devolucaoNotaProdutoObj->devolucao_nota_id = $devolucaoNotaObj->id;
                        $devolucaoNotaProdutoObj->produto_id = $notaVendaItemNasajonObj->id_item_nota;
                        $devolucaoNotaProdutoObj->quantidade = $notaVendaItemNasajonObj->quantidade;
                        $devolucaoNotaProdutoObj->quantidade_recebida = $produto['quantidade_recebida'];
                        $devolucaoNotaProdutoObj->created_by = Auth::id();
                        $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);

                        $devolucaoNotaProdutoObj->save();
                    }
                }
            }

            $devolucaoNotaObj->push();

        }
        else if($original['devolucao_nota_status_id'] == 6){ // Fiscal 
            $devolucaoNotaLogObj->acao = 'Conferência Fiscal';

            if(isset($fields['nota_remessa']) && !empty($fields['nota_remessa'])){

                $notaRemessaNasajonObj = NotasNasajon::
                where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($fields['nota_remessa'], 0))
                    ->where('estabelecimento_codigo', $devolucaoNotaObj->estabelecimento)
                    ->first();

                $devolucaoNotaObj->nota_remessa = $notaRemessaNasajonObj->id;
            }

            $devolucaoNotaObj->updated_by = Auth::user()->id;

        }else if($original['devolucao_nota_status_id'] == 15){ // Financeiro
            $this->aprovacaoFaseFinanceiro($devolucaoNotaObj, $devolucaoNotaLogObj, $original);            
        }else{
            $devolucaoNotaObj->updated_by = Auth::user()->id;

            $devolucaoNotaLogObj->acao = 'Aprovação de etapa - ' . $devolucaoNotaObj->status_detalhes->status;  
            $devolucaoNotaLogObj->mensagem = $mensagem;
            $devolucaoNotaLogObj->status_antigo = $original['devolucao_nota_status_id'];
            $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;
            $devolucaoNotaLogObj->usuario = Auth::id();
        }

        $devolucaoNotaObj->save();

        $documentos = $request->file('documento');
        $mensagem_sem_transportadora = '';
        $documento_isento = $request->file('arquivo_cliente_isento');

        if($request->hasFile('documento'))
        {
            foreach($documentos as $key => $documento){
                $dococumentos_devolucao = new DevolucaoNotasDocumento;
                $dococumentos_devolucao->devolucao_nota_id = $devolucaoNotaObj->id;
                $dococumentos_devolucao->tipo_documento = $fields['tipo_documento'][$key];
                $dococumentos_devolucao->nome_arquivo = $fields['descricao_documento'][$key];
                $dococumentos_devolucao->created_by = Auth::user()->id;
                $dococumentos_devolucao->save();
                $file = $dococumentos_devolucao->id.'.' .$documento->getClientOriginalExtension();
                $dococumentos_devolucao->caminho = $file;
                $documento->storeAs($this->storage_files, $file);
                $dococumentos_devolucao->save();
                
                if(!empty($devolucaoNotaObj->transportador_email) && 
                $devolucaoNotaObj->pedido->estabelecimento_codigo == '04' &&
                $fields['tipo_documento'][$key] == 'carta_correcao_remessa' || 
                !empty($devolucaoNotaObj->transportador_email) && 
                $devolucaoNotaObj->pedido->estabelecimento_codigo == '03' &&
                $fields['tipo_documento'][$key] == 'carta_correcao_remessa' || 
                !empty($devolucaoNotaObj->transportador_email) && 
                $devolucaoNotaObj->pedido->estabelecimento_codigo == '04' &&
                $fields['tipo_documento'][$key] == 'nf_remessa' || 
                !empty($devolucaoNotaObj->transportador_email) && 
                $devolucaoNotaObj->pedido->estabelecimento_codigo == '03' &&
                $fields['tipo_documento'][$key] == 'nf_remessa'){
                    $nome_transportadora = (!empty(TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'),$devolucaoNotaObj->transportador)->first())) ? TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'),$devolucaoNotaObj->transportador)->first()->nome : '';
                    $mensagem = 'Prezado '.$nome_transportadora.',<br><br>';
                    $mensagem .= 'Segue documento em anexo '.$fields['descricao_documento'][$key].', referente a devolução da nota fiscal '.$devolucaoNotaObj->nota_nasajon->numero.'.';
                    $emailControllerObj = new EmailController;
                    $returnEmail = $emailControllerObj->sendEmailToken('00', "documentos_devolucao_transportadora", $devolucaoNotaObj->transportador_email, ['corpo' => $mensagem], [$this->storage_files. $file => ['as' => 'documento_devolucao']], []);

                    $dococumentos_devolucao->enviado_transportadora = true;
                    $dococumentos_devolucao->save();

                    $mensagem_sem_transportadora = '<br><b class=\'text-success\'> Obs: Documentos enviados a Transportadora.</b>';
                }
            }
        }

        if(
            $devolucaoNotaObj->devolucao_nota_status_id == 7 && 
            !empty($devolucaoNotaObj->pedido->forma_pagamento->condicao) && 
            in_array($devolucaoNotaObj->pedido->forma_pagamento->condicao->descricao, [
                'A VISTA',
                'CARTAO DE DEBITO',
                'Cartão BNDES',
                'Cartão Crédito',
                'Dinheiro',
                'USAR CREDITO',
                'À VISTA'
                ]
            )
        ){
			$representante = $devolucaoNotaObj->nota_nasajon->revisao_vendedor_comissao->usuario;
			if($representante->tipo_usuario_id == 16){
				$comissao = $representante->comissao_a / 100;
			}
			else if($representante->tipo_usuario_id == 19 || $representante->tipo_usuario_id == 14){
				$comissao = 0.0011;
			}else{
				$comissao = ($devolucaoNotaObj->nota_nasajon->revisao_vendedor_comissao->percentual_comissao/100);
			}

            $lancamentoObj = new LancamentoDebCredVendedor;
            $lancamentoObj->data_lancamento = Carbon::now();
            $lancamentoObj->valor = round($devolucaoNotaObj->valor * $comissao, 2);
            $lancamentoObj->num_documento = $devolucaoNotaObj->id;
            $lancamentoObj->codigo_vendedor = $representante->id;
            $lancamentoObj->codigo_motivo = 24;
            $lancamentoObj->nota_uuid = $devolucaoNotaObj->nota_nasajon->id_nota;
            $lancamentoObj->tipo = 'D';
            $lancamentoObj->created_by = Auth::id();
    
            $lancamentoObj->save();
        }

        $mudancas = $devolucaoNotaObj->getChanges();

        $keys = [
            'nome_contato',
            'telefone_contato',
            'email_contato',
        ]; 

        foreach(array_intersect(\array_keys($mudancas), $keys) as $campo){
            if($original[$campo] != $mudancas[$campo]){
                $devolucaoNotaLogObj[$campo . '_antigo'] = $original[$campo];
                $devolucaoNotaLogObj[$campo . '_novo'] = $mudancas[$campo];
            }
        }

        $devolucaoNotaLogObj->save();

        $devolucaoNotaAprovadorObj = new DevolucaoNotaAprovador;

        $devolucaoNotaAprovadorObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaAprovadorObj->devolucao_nota_status_id = $original['devolucao_nota_status_id'];
        $devolucaoNotaAprovadorObj->aprovador = Auth::id();
        $devolucaoNotaAprovadorObj->created_by = Auth::id();

        $devolucaoNotaAprovadorObj->save();


        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!'.$mensagem_sem_transportadora,
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function reprovarModal(Request $request){
        $fields = $request->only('id', 'motivo_reprovacao');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }
        
        $devolucaoNotaObj = DevolucaoNota::with('nota_nasajon', 'cliente')->find($id);

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];

        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['nota_id'] = $devolucaoNotaObj->nota_id;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);
        $retorno['status'] = $devolucaoNotaObj->devolucao_nota_status_id;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['motivo'] = $devolucaoNotaObj->motivo_devolucao->descricao;
        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;
        $retorno['valor_devolvido'] = parserValor($devolucaoNotaObj->valor);
        $retorno['produtos'] = [];

        if($devolucaoNotaObj->devolucao_nota_status_id == 4){

            $devolucaoNotaObj->load(
                    'nota_nasajon.faturamento_nota_devolucao',
                    'produtos',
                    'produtos.produto_na_nota',
                    'produtos.produto_na_nota.produto_detalhes'
                );

            if($devolucaoNotaObj->valor_parcial === true){

                $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
                
                $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                    ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                    ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                    ->where('id', '!=', $id)
                    ->get();

                $devolucaoNotaObj->produtos->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){
                
                    $devolvidos = 0;

                    $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                        ->pluck('itens_faturamento')
                        ->flatten()
                        ->where('Item - Código', $item->produto_na_nota->produto_detalhes->codigo_produto);
                    
                    if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                        $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                    }
                    
                    $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
                
                    if($requisicaoDevolucaoItemObj->isNotEmpty()){
                        $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                    }

                    $linha = [];

                    $linha['id'] = $item->produto_id;
                    $linha['codigo'] = $item->produto_na_nota->produto_detalhes->codigo_produto;
                    $linha['grupo'] = $item->produto_na_nota->produto_detalhes->grupo;
                    $linha['descricao'] = $item->produto_na_nota->produto_detalhes->descricao;
                    $linha['quantidade'] = parserValor($item->produto_na_nota->quantidade - $devolvidos);
                    $linha['quantidade_devolvida'] = parserValor($item->quantidade);
                    $linha['quantidade_recebida'] = parserValor($item->quantidade_recebida);

                    $retorno['produtos'][] = $linha;
                });
            }
            else if($devolucaoNotaObj->valor_parcial === false){
        
                $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
                
                $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                    ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                    ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                    ->where('id', '!=', $id)
                    ->get();

                $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){

                    $devolvidos = 0;

                    $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                        ->pluck('itens_faturamento')
                        ->flatten()
                        ->where('Item - Código', $item->produto_detalhes->codigo_produto);
                    
                    if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                        $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                    }
                    
                    $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
                
                    if($requisicaoDevolucaoItemObj->isNotEmpty()){
                        $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                    }

                    $linha = [];

                    $linha['id'] = $item->id_item_nota;
                    $linha['codigo'] = $item->produto_detalhes->codigo_produto;
                    $linha['grupo'] = $item->produto_detalhes->grupo;
                    $linha['descricao'] = $item->produto_detalhes->descricao;
                    $linha['quantidade'] = parserValor($item->quantidade - $devolvidos);
                    $linha['quantidade_devolvida'] = parserValor($item->quantidade - $devolvidos);
        
                    $retorno['produtos'][] = $linha;
                });
            }
        }

        return view('programs.devolucao_nota_aprovacao.modal.reprovar')->with($retorno);

    }

    public function reprovacao(DevolucaoNotaAprovacaoReprovarRequest $request){

        $fields = $request->only('id', 'motivo_reprovacao', 'produtos');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }
        Auth::user()->id;

        $devolucaoNotaObj = DevolucaoNota::with('status_detalhes', 'pedido', 'pedido.userPortal', 'pedido.userPortal.supervisor')->find($id);
        $chave = $devolucaoNotaObj->status_detalhes->chave;

        $devolucaoNotaLogObj = new DevolucaoNotaLog;

        $devolucaoNotaObj->devolucao_nota_status_id = '8';

        if(in_array($devolucaoNotaObj->devolucao_nota_status_id, [1,2])){

            $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
            $devolucaoNotaLogObj->acao = 'Recusa da requisição';
            $devolucaoNotaLogObj->mensagem = 'Requisição recusada com o motivo: ' . $fields['motivo_reprovacao'];
            $devolucaoNotaLogObj->usuario = Auth::id();
        }
        else if($devolucaoNotaObj->devolucao_nota_status_id == 4){
            $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
            $devolucaoNotaLogObj->acao = 'Recusa na expedição';
            $devolucaoNotaLogObj->mensagem = 'Recebimento recusado com o motivo: ' . $fields['motivo_reprovacao'];
            $devolucaoNotaLogObj->usuario = Auth::id();

            if($devolucaoNotaObj->valor_parcial === true){
                foreach($fields['produtos'] as $produto){
                    $devolucaoNotaProdutoObj = $devolucaoNotaObj->produtos->where('produto_id', $produto['id'])->first();
                    $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);
                    $devolucaoNotaProdutoObj->save();
                }
            }
            else{
                $verifica_produto = DevolucaoNotaProduto::where('devolucao_nota_id',$devolucaoNotaObj->id)
                ->delete();
                foreach($fields['produtos'] as $produto){
                    $notaVendaItemNasajonObj = $devolucaoNotaObj->nota_nasajon->item->where('id_item_nota', $produto['id'])->first();
                    $devolucaoNotaProdutoObj = new DevolucaoNotaProduto;

                    $devolucaoNotaProdutoObj->devolucao_nota_id = $devolucaoNotaObj->id;
                    $devolucaoNotaProdutoObj->produto_id = $notaVendaItemNasajonObj->id_item_nota;
                    $devolucaoNotaProdutoObj->quantidade = $notaVendaItemNasajonObj->quantidade;
                    $devolucaoNotaProdutoObj->quantidade_recebida = $produto['quantidade_recebida'];
                    $devolucaoNotaProdutoObj->created_by = Auth::id();
                    $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);

                    $devolucaoNotaProdutoObj->save();
                }
            }

            $devolucaoNotaObj->push();            
        }
        else{
            $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
            $devolucaoNotaLogObj->acao = 'Reprovado';
            $devolucaoNotaLogObj->mensagem = $fields['motivo_reprovacao'];
            $devolucaoNotaLogObj->usuario = Auth::id();
            $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;
        }
        
        if($request->hasFile('laudo_imagem')){
            $devolucaoNotaObj->laudo_imagem = 'laudo_devolucao_' . $devolucaoNotaObj->id . '.' . $request->file('laudo_imagem')->extension();
            $request->file('laudo_imagem')->storeAs($this->storage . '/laudo', $devolucaoNotaObj->laudo_imagem);

            $devolucaoNotaLogObj->mensagem .= ' - Laudo vinculado à reprovação.';
        }

        $devolucaoNotaObj->motivo_reprovacao = $fields['motivo_reprovacao'];
        $devolucaoNotaObj->updated_by = Auth::user()->id;
        $devolucaoNotaObj->save();

        DevolucaoNotaAprovador::where('devolucao_nota_id', $id)
            ->whereNull('deleted_at')
            ->update([
                'deleted_by' => Auth::id(),
                'deleted_at' => date('Y-m-d H:i:s')
            ]);

        $devolucaoNotaLogObj->save();

        $emails = [];

        if(isset($devolucaoNotaObj->pedido->userPortal->email) && !empty($devolucaoNotaObj->pedido->userPortal->email)){
            $emails[] = $devolucaoNotaObj->pedido->userPortal->email;
        }

        if(isset($devolucaoNotaObj->pedido->userPortal->supervisor->email) && !empty($devolucaoNotaObj->pedido->userPortal->supervisor->email) && $devolucaoNotaObj->pedido->userPortal->supervisor->id != Auth::id()){
            $emails[] = $devolucaoNotaObj->pedido->userPortal->supervisor->email;
        }

        $emailControllerObj = new EmailController;

        $mail_result = $emailControllerObj->sendEmailToken(
            '00',
            'email_devolucao_reprovacao',
            $emails,
            [
                'nome_cliente' => $devolucaoNotaObj->nota_nasajon->nome_cliente . ' - ' . $devolucaoNotaObj->nota_nasajon->documento_cliente,
                'processo' => $devolucaoNotaObj->id,
                'motivo' => $devolucaoNotaLogObj->mensagem
            ]
        );

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Recusado com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function enviaEmailClienteNota(){

		$emailControllerObj = new EmailController;

        $devolucoesObj = DevolucaoNota::
            with('motivo_devolucao')
            ->where('devolucao_nota_status_id', 3)
            ->get();

        $devolucoesObj->each(function($devolucao) use ($emailControllerObj){
            $mail_result = $emailControllerObj->sendEmailToken('00', 'devolucao_nota_cliente', [$devolucao->email_contato], ['cliente' => $devolucao->cliente->nome, 'devolucao' => $devolucao->id, 'nota' => $devolucao->nota_fiscal, 'motivo' => $devolucao->motivo_devolucao->descricao]);
        });
    }

    public function cancelarModal(Request $request){
        $fields = $request->only('id', 'motivo_reprovacao');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }
        
        $devolucaoNotaObj = DevolucaoNota::with('nota_nasajon', 'cliente')->find($id);

        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];

        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['nota_id'] = $devolucaoNotaObj->nota_id;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);
        $retorno['status'] = $devolucaoNotaObj->devolucao_nota_status_id;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['motivo'] = $devolucaoNotaObj->motivo_devolucao->descricao;
        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;
        $retorno['valor_devolvido'] = parserValor($devolucaoNotaObj->valor);

        return view('programs.devolucao_nota_aprovacao.modal.cancelar')->with($retorno);

    }

    public function cancelamento(DevolucaoNotaAprovacaoCancelarRequest $request){
        $fields = $request->only('id', 'motivo_reprovacao');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::find($id);

        $devolucaoNotaLogObj = new DevolucaoNotaLog;

        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Cancelado';
        $devolucaoNotaLogObj->mensagem = $fields['motivo_reprovacao'];
        $devolucaoNotaLogObj->usuario = Auth::id();
        $devolucaoNotaLogObj->status_novo = 11;

        $devolucaoNotaObj->devolucao_nota_status_id = 11;
        $devolucaoNotaObj->motivo_reprovacao = $fields['motivo_reprovacao'];
        $devolucaoNotaObj->updated_by = Auth::user()->id;

        $devolucaoAprovadorObj = new DevolucaoNotaAprovador;

        $devolucaoAprovadorObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoAprovadorObj->devolucao_nota_status_id = 11;
        $devolucaoAprovadorObj->aprovador = Auth::id();
        $devolucaoAprovadorObj->created_by = Auth::id();

        if($devolucaoNotaObj->save()){
            $devolucaoNotaLogObj->save();
            $devolucaoAprovadorObj->save();
        }

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Cancelado com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function freteModal(Request $request){
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::with('cliente')->find($id);


        $estabelecimentos = returnEmpresasNasajonView();

        $retorno = [];

        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['nota_id'] = $devolucaoNotaObj->nota_id;
        $retorno['responsabilidade_frete'] = $devolucaoNotaObj->responsabilidade_frete;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['emissao'] = parserData($devolucaoNotaObj->nota_nasajon->emissao);
        $retorno['status'] = $devolucaoNotaObj->devolucao_nota_status_id;
        $retorno['valor'] = parserValor($devolucaoNotaObj->nota_nasajon->valor);
        $retorno['motivo'] = $devolucaoNotaObj->motivo_devolucao->descricao;
        $retorno['valor_parcial'] = $devolucaoNotaObj->valor_parcial;
        $retorno['valor_devolvido'] = parserValor($devolucaoNotaObj->valor);
        $retorno['frete_valor'] = empty($devolucaoNotaObj->frete_valor)?'':parserValor($devolucaoNotaObj->frete_valor);
        

        return view('programs.devolucao_nota_aprovacao.modal.frete')->with($retorno);
    }

    public function frete(DevolucaoNotaAprovacaoEspecificarFreteRequest $request){

        $fields = $request->only('id', 'responsabilidade_frete', 'frete_valor');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::find($id);
    
        $devolucaoNotaLogObj = new DevolucaoNotaLog;
    
        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Especificado o frete';
        $devolucaoNotaLogObj->mensagem = 'Responsabilidade do frete delegada a: ' . $fields['responsabilidade_frete'] . ' - Valor: ' . $fields['frete_valor'];
        $devolucaoNotaLogObj->usuario = Auth::id();
    
        $devolucaoNotaObj->responsabilidade_frete = $fields['responsabilidade_frete'];
        $devolucaoNotaObj->frete_valor = parserNumber($fields['frete_valor']);
        $devolucaoNotaObj->timestamps = false;
        $devolucaoNotaObj->updated_by = Auth::user()->id;
    
        $devolucaoAprovadorObj = new DevolucaoNotaAprovador;
    
        $devolucaoAprovadorObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoAprovadorObj->devolucao_nota_status_id = 12;
        $devolucaoAprovadorObj->aprovador = Auth::id();
        $devolucaoAprovadorObj->created_by = Auth::id();
    
        if($devolucaoNotaObj->save()){
            $devolucaoNotaLogObj->save();
            $devolucaoAprovadorObj->save();
        }
    
        return response()->json(
            [
                'status' => 'success',
                'message' => 'Cancelado com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function indexGerentes(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\DevolucaoNotaAprovacaoGerente") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\DevolucaoNotaAprovacaoGerente');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $gerentes = [];
        $vendedor_representante = [];

        $check_gerentes = false;
        $check_supervisores = false;
        $check_vendedor_representante = false;

        if(!in_array(Auth::user()->tipo_usuario_id, ["18","15", "1", "11", "20"])){
            $subordinadosObj = UserController::varreSubordinados(Auth::id());
            if(strtolower(Auth::user()->tipo_usuario_id) === "13"){
                $subordinadosObj = UserController::varreSubordinados(Auth::user()->responsavel);
            }

            $userObj = User::whereIn('id', $subordinadosObj)->get();
            if(Auth::user()->tipo_usuario_id == 19 && !empty(Auth::user()->codigo_representante)){
                $vendedor_representante[Crypt::encrypt(Auth::user()->id)] = strtoupper(Auth::user()->name);
            }

            foreach ($userObj as $key => $user) {
                if(strtolower($user->tipo_usuario_id) === "19"){
                    $gerentes[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }else if(strtolower($user->tipo_usuario_id) === "16" && !empty($user->codigo_representante) || 
                strtolower($user->tipo_usuario_id) === "12" && !empty($user->codigo_representante) || $user->id == 1){
                    $vendedor_representante[Crypt::encrypt($user->id)] = strtoupper($user->name);
                }
            }

            unset($subordinadosObj);
        } else {
            $subordinadosObj = User::with(["tipo_usuario"])->get();
            foreach ($subordinadosObj as $key => $userObj) {
                if(strtolower($userObj->tipo_usuario_id) === "19"){
                    $gerentes[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }else if(!empty($userObj->codigo_representante)){
                    $vendedor_representante[Crypt::encrypt($userObj->id)] = strtoupper($userObj->name);
                }
            }
            $gerentes[Crypt::encrypt(0)] = 'OUTROS';

            asort($vendedor_representante);
            unset($subordinadosObj);
        }

        if(strtolower(Auth::user()->tipo_usuario_id) == '19'){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(strtolower(Auth::user()->tipo_usuario_id) == '13'){
            $check_vendedor_representante = true;
            $gerentes = [];
        }else if(
            strtolower(Auth::user()->tipo_usuario_id) !== "16" &&
            strtolower(Auth::user()->tipo_usuario_id) !== "12"
        ){
            $check_gerentes = true;
            $check_supervisores = true;
            $check_vendedor_representante = true;
        }

        $variaveis_view = [
            'gerentes'                      => $gerentes,
            'check_gerentes'                => $check_gerentes,
            'check_supervisores'            => $check_supervisores,
            'vendedor_representante'        => $vendedor_representante,
            'check_vendedor_representante'  => $check_vendedor_representante,
            'representantes'                => $vendedor_representante,
            'estabelecimentos'              => $estabelecimentos,
        ];

        return view('programs.devolucao_nota_aprovacao.indexGerentes')->with($variaveis_view);
    }

    public function filterGerente(Request $request){

        $fields = $request->only('estabelecimento', 'gerentes', 'vendedor_representante', 'nota_fiscal', 'cliente', 'status', 'sem_frete', 'comercial', 'tipo');

        $query = DevolucaoNota::with('nota_nasajon', 'status_detalhes', 'aprovadores', 'cliente')
            ->where('devolucao_nota_status_id', 2);

        if(isset($fields['nota_fiscal']) && !empty($fields['nota_fiscal'])){
            $query->where(DB::Raw('trim(leading \'0\' from nota_fiscal)'), ltrim($fields['nota_fiscal'], 0));
        }

        if(isset($fields['estabelecimento'])){
            $query->where("estabelecimento", str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        if(isset($fields['cliente']) && !empty($fields['cliente'])){
            $cliente = ClienteNasajon::where(DB::Raw("CONCAT(nome, ' - ', cpf_cnpj)"), 'ilike', '%' . $fields['cliente'] . '%')->get();

            $query->whereIn('cliente_cpf_cnpj', $cliente->pluck('cpf_cnpj'));
        }

        if(isset($fields['sem_frete']) && $fields['sem_frete'] == true){
            $query->whereDoesntHave('aprovadores', function($query){
                $query->where('devolucao_nota_status_id', 12);
            });
        }

        $devolucoesObj = $query->get();

        if(Auth::user()->tipo_usuario_id == 19){
            $gerente = Auth::id();
        }
        else if(Auth::user()->tipo_usuario_id == 13){
            $gerente = Auth::user()->responsavel;
        }
        else if(in_array(Auth::user()->tipo_usuario_id, [16, 12])){
            $vendedor = Auth::id();
        }
        
        if(
            isset($fields['vendedor_representante']) && !empty($fields['vendedor_representante'])
        ){
            try {
                $vendedor = Crypt::decrypt($fields['vendedor_representante']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!',
                        'response' => [
                            'dados' => [] 
                        ]
                    ], 422);
            }

        }
        else if(
            (isset($fields['gerentes']) && !empty($fields['gerentes'])) &&
            (!isset($fields['vendedor_representante']) || is_null($fields['vendedor_representante']))
        ){
            try {
                $gerente = Crypt::decrypt($fields['gerentes']);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!',
                        'response' => [
                            'dados' => [] 
                        ]
                    ], 422);
            }
        }
        else if(
            (
                (!isset($fields['gerentes']) || empty($fields['gerentes'])) &&
                (!isset($fields['vendedor_representante']) || is_null($fields['vendedor_representante']))
            ) && 
            Auth::user()->tipo_usuario_id == 19
        ){
            $gerente == Auth::user()->id;
        }


        if(isset($vendedor)){
            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            $users = User::
                where('id', $vendedor)
                ->whereNotNull('codigo_representante')
                ->first();

            $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                return $users->codigo_representante == $linha->nota_nasajon['revisao_vendedor_comissao']['vendedor_codigo'];
            });
            
        }
        if(isset($gerente) && !isset($vendedor)){
            $devolucoesObj->load('nota_nasajon.revisao_vendedor_comissao');

            if($gerente == 0){
                $gerentes = User::whereHas('tipo_usuario', function($query){ $query->where('nome', 'ilike', 'gerente comercial'); })->get()->pluck('id');

                $users = User::
                    where(function ($query) use($gerentes){
                        $query->whereNotIn('responsavel', $gerentes)
                            ->whereNotIn('id', $gerentes);
                    })
                    ->whereNotNull('codigo_representante')
                    ->get();

                $devolucoesObj = $devolucoesObj->filter(function($linha) use($users){
                    if(isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo)){
                        return $users->pluck('codigo_representante')->contains($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || !isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || empty($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) || empty($linha->nota_nasajon->revisao_vendedor_comissao);
                    }
                });
            }
            else{
                $users = User::where('responsavel', $gerente)->orWhere('id', $gerente)->get();

                $devolucoesObj = $devolucoesObj->filter(function($linha) use($users, $gerente){
                    if(isset($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo)){
                        return $users->pluck('codigo_representante')
                            ->contains($linha->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo) ||
                            $users->pluck('codigo_representante')
                            ->contains($gerente);
                    }
                });
            }
        }
        else if(!isset($gerente) && !isset($vendedor) && !in_array(Auth::user()->tipo_usuario_id, [1, 15])){
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Dados recuperados com sucesso!',
                    'error' => [],
                    'response' => [
                        'dados' => [] 
                    ]
                ], 220
            );
        }

        $response = [];

        $estabelecimentos = returnEmpresasNasajonView();

        $hoje = Carbon::now();

        $devolucoesObj->each(function($devolucao) use (&$response, $estabelecimentos, $hoje, $fields){

            $linha = [];

            $id = Crypt::encrypt($devolucao->id);

            $linha['id'] = $id;
            $linha['numero'] = $devolucao->id;
            $linha['nota_fiscal'] = $devolucao->nota_fiscal;
            $linha['emissao'] = parserData($devolucao->nota_nasajon->emissao);
            $linha['nota_id'] = $devolucao->nota_id;
            $linha['estabelecimento'] = $estabelecimentos[intval($devolucao->estabelecimento)];
            $linha['cliente'] = $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj;
            $linha['valor'] = parserValor($devolucao->valor);

            if(!empty($devolucao->motivo_devolucao)){
                $linha['motivo'] = $devolucao->motivo_devolucao->descricao;
            }
            else{
                $linha['motivo'] = '';
            }

            if(!is_null($devolucao->deleted_at)){
                $linha['status_exibir'] = 'Cancelado';
                $linha['status'] = 11;
            }
            else{
                $linha['status_exibir'] = $devolucao->status_detalhes->descricao;
                $linha['status'] = $devolucao->devolucao_nota_status_id;
            }

            if($devolucao->valor_parcial){
                $linha['valor_parcial'] = 'Parcial';
            }
            else{
                $linha['valor_parcial'] = 'Completo';
            }

            $linha['mostrar_aprovacao'] = false;
            $linha['mostrar_reprovacao'] = false;

            if(
                $devolucao->devolucao_nota_status_id == 2 &&
                Carbon::now()->diffInDays($devolucao->nota_nasajon->emissao) > 30 &&
                !(Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Diretoria') || Auth::user()->hasRole('Diretoria Comercial') || in_array(Auth::id(), [46, 105]))
            ){
                $linha['mostrar_aprovacao'] = false;
                $linha['mostrar_reprovacao'] = false;
            }
            else if(
                $devolucao->status_detalhes->selecionavel
            ){
                if(isset($fields['tipo']) && !empty($fields['tipo'])){
                    if(in_array(Auth::id(), [46, 105])){
                        $linha['mostrar_aprovacao'] = true;
                        $linha['mostrar_reprovacao'] = true;
                    }
                }else{
                    $linha['mostrar_aprovacao'] = true;
                    $linha['mostrar_reprovacao'] = true;
                }
            }

            if(!in_array($devolucao->devolucao_nota_status_id, [7, 8, 11])){
                $linha['parado'] = $devolucao->updated_at->diffInDays($hoje);
            }
            else{
                $linha['parado'] = '';
            }
            
            $response[] = $linha;
        });

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados recuperados com sucesso!',
                'error' => [],
                'response' => [
                    'dados' => $response 
                ]
            ], 220
        );
    }

    public function editarModal(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::
            with([
                'nota_nasajon',
                'nota_nasajon.item',
                'nota_nasajon.faturamento',
                'produtos',
                'produtos.produto_na_nota.produto_detalhes',
                'status_detalhes',
                'aprovadores',
                'aprovadores.status',
                'aprovadores.aprovador_detalhes',
                'logs',
                'cliente',
                'nota_remessa_nasajon',
                'documentos'
            ])
            ->find($id);

        if(empty($devolucaoNotaObj)){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Requisição não encontrada!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        
        $documentos_diversos = [];

        $documento_isento = [];

        if(!empty($devolucaoNotaObj->documentos)){
            foreach($devolucaoNotaObj->documentos as $documentos){
                if($documentos->tipo_documento != 'cliente_isento'){
                    $documentos_diversos[] =  [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'descricao' => $documentos->nome_arquivo,
                        'id_diversos' => encrypt($documentos->id)
                    ];
                }
                if($documentos->tipo_documento === 'cliente_isento'){
                    $documento_isento = [
                        'caminho' => Storage::url($this->storage_files . $documentos->caminho),
                        'id_isento' => encrypt($documentos->id)
                    ];
                }
            }
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $motivos = [];

        $devolucaoNotaMotivoObj = DevolucaoNotaMotivo::with('status', 'status.devolucao_nota_status')->get();

        $devolucaoNotaMotivoObj->each(function ($motivo) use(&$motivos){
            $motivos[$motivo->id] = $motivo['descricao']; 
        });

        $retorno = [];
        
        $retorno['status_lista'] = $devolucaoNotaMotivoObj->firstWhere('id', $devolucaoNotaObj->motivo)->status->pluck('devolucao_nota_status.descricao', 'devolucao_nota_status.id');
        $retorno['status_lista'][7] = 'Finalizar devolução';

        $retorno['documentos_diversos'] = $documentos_diversos;
        $retorno['documento_isento'] = $documento_isento;
        $retorno['motivos'] = $motivos;
        $retorno['id'] = Crypt::encrypt($devolucaoNotaObj->id);
        $retorno['estabelecimento'] = $estabelecimentos[intval($devolucaoNotaObj->estabelecimento)];
        $retorno['estabelecimento_codigo'] = $devolucaoNotaObj->estabelecimento;
        $retorno['nota_fiscal'] = $devolucaoNotaObj->nota_fiscal;
        $retorno['cliente'] = $devolucaoNotaObj->cliente->nome . ' - ' . $devolucaoNotaObj->cliente->cpf_cnpj;
        $retorno['valor'] = isset($devolucaoNotaObj->nota_nasajon->valor)?parserValor($devolucaoNotaObj->nota_nasajon->valor):'';
        $retorno['emissao'] = isset($devolucaoNotaObj->nota_nasajon->emissao)?parserData($devolucaoNotaObj->nota_nasajon->emissao):'';
        $retorno['motivo'] = $devolucaoNotaObj->motivo;
        $retorno['status'] = $devolucaoNotaObj->devolucao_nota_status_id;
        $retorno['transportador'] = $devolucaoNotaObj->transportador;
        $retorno['transportador_email'] = $devolucaoNotaObj->transportador_email;
        
        if($devolucaoNotaObj->valor_parcial){
            $retorno['valor_parcial'] = 'Valor parcial';
        }
        else{
            $retorno['valor_parcial'] = 'Devolução completa';
        }

        if(isset($devolucaoNotaObj->nota_nasajon->faturamento['Código da Operação']) && in_array($devolucaoNotaObj->nota_nasajon->faturamento['Código da Operação'], ['VENDAORDEMTORO', 'VENDAAORDEM'])){
            $retorno['tipo_venda'] = 'Venda por conta e ordem';
        }
        else{
            $retorno['tipo_venda'] = 'Venda normal';
        }

        $retorno['nome_contato'] = $devolucaoNotaObj->nome_contato;
        $retorno['telefone_contato'] = $devolucaoNotaObj->telefone_contato;
        $retorno['email_contato'] = $devolucaoNotaObj->email_contato;
        $retorno['nota_cliente_numero'] = $devolucaoNotaObj->nota_cliente_numero;
        $retorno['nota_remessa'] = $devolucaoNotaObj->nota_remessa_nasajon->numero??'';
    
        $responsabilidade = [
            'textil' => 'MN Têxtil',
            'cliente' => 'Cliente',
            'representante' => 'Representante']
        ;

        if(!is_null($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['responsabilidade_frete_exibir'] = $devolucaoNotaObj->responsabilidade_frete;
        }
        else{
            $retorno['responsabilidade_frete_exibir'] = '';
        }

        if(!empty($devolucaoNotaObj->responsabilidade_frete)){
            $retorno['frete_valor'] = parserValor($devolucaoNotaObj->frete_valor);
        }
        else{
            $retorno['frete_valor'] = '';
        }

        if(!is_null($devolucaoNotaObj->arquivo)){
            if(Storage::exists($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo)){
                $retorno['arquivo'] = Storage::url($this->storage . '/imagem/' . $devolucaoNotaObj->arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->laudo_imagem)){
            if(Storage::exists( $this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem)){
                $retorno['laudo_tecnico'] = Storage::url($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem);
            }
        }

        if(!is_null($devolucaoNotaObj->nota_cliente_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo)){
                $retorno['nota_cliente_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo);
            }
        }

        if(!is_null($devolucaoNotaObj->romaneio_arquivo)){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo)){
                $retorno['romaneio_arquivo'] = Storage::url($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo);
            }
        }

        $retorno['observacao'] = $devolucaoNotaObj->observacao;

        $retorno['produtos'] = [];

        $retorno['aprovadores'] = [];

        $devolucaoNotaObj->aprovadores->each( function ($aprovador) use(&$retorno){

            $linha = [];

            $linha['status'] = $aprovador->status->descricao;
            $linha['aprovador'] = $aprovador->aprovador_detalhes->name;
            $linha['data'] = $aprovador->created_at->format('d/m/Y H:i:s');

            $retorno['aprovadores'][] = $linha;

        });

        $ultimoLogReprovacao = $devolucaoNotaObj->logs->where('status_antigo', $devolucaoNotaObj->devolucao_nota_status_id)->where('acao', 'Reprovado')->sortByDesc('created_at');

        if($ultimoLogReprovacao->isNotEmpty()){
            $retorno['motivo_reprovacao'] = $ultimoLogReprovacao->first()->mensagem;
        }

        if($devolucaoNotaObj->produtos->isNotEmpty()){

            $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
            
            $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                ->where('id', '!=', $id)
                ->get();

            $devolucaoNotaObj->produtos->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){
            
                $devolvidos = 0;

                $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                    ->pluck('itens_faturamento')
                    ->flatten()
                    ->where('Item - Código', $item->produto_na_nota->produto_detalhes->codigo_produto);
                
                if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                    $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                }
                
                $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
            
                if($requisicaoDevolucaoItemObj->isNotEmpty()){
                    $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                }

                $linha = [];

                $linha['id'] = $item->produto_id;
                $linha['codigo'] = $item->produto_na_nota->produto_detalhes->codigo_produto;
                $linha['grupo'] = $item->produto_na_nota->produto_detalhes->grupo;
                $linha['descricao'] = $item->produto_na_nota->produto_detalhes->descricao;
                $linha['quantidade'] = parserValor($item->produto_na_nota->quantidade - $devolvidos);
                $linha['quantidade_devolvida'] = parserValor($item->quantidade);
                $linha['quantidade_recebida'] = parserValor($item->quantidade_recebida);

                $retorno['produtos'][] = $linha;
            });
        }
        else if($devolucaoNotaObj->valor_parcial === false){
    
            $devolucaoNotaNasajonObj = $devolucaoNotaObj->nota_nasajon->faturamento_nota_devolucao->where('TIPO', 'DEVOLUÇÃO');
            
            $outrasDevolucoesNotasObj = DevolucaoNota::with('produtos')
                ->where('estabelecimento', str_pad($devolucaoNotaObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                ->where('nota_fiscal', $devolucaoNotaObj->nota_fiscal)
                ->where('id', '!=', $id)
                ->get();

            $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use (&$retorno, $devolucaoNotaNasajonObj, $outrasDevolucoesNotasObj){

                $devolvidos = 0;

                $devolucaoNotaNasajonItemsObj = $devolucaoNotaNasajonObj
                    ->pluck('itens_faturamento')
                    ->flatten()
                    ->where('Item - Código', $item->produto_detalhes->codigo_produto);
                
                if($devolucaoNotaNasajonItemsObj->isNotEmpty()){
                    $devolvidos += $devolucaoNotaNasajonItemsObj->sum('Item - Quantidade')??0;
                }
                
                $requisicaoDevolucaoItemObj = $outrasDevolucoesNotasObj->pluck('produtos')->flatten()->where('produto_id', $item->id_item_nota);
            
                if($requisicaoDevolucaoItemObj->isNotEmpty()){
                    $devolvidos += $requisicaoDevolucaoItemObj->sum('quantidade');
                }

                $linha = [];

                $linha['id'] = $item->id_item_nota;
                $linha['codigo'] = $item->produto_detalhes->codigo_produto;
                $linha['grupo'] = $item->produto_detalhes->grupo;
                $linha['descricao'] = $item->produto_detalhes->descricao;
                $linha['quantidade'] = parserValor($item->quantidade - $devolvidos);
                $linha['quantidade_devolvida'] = parserValor($item->quantidade - $devolvidos);
                $linha['quantidade_recebida'] = parserValor($item->quantidade_recebida);
    
                $retorno['produtos'][] = $linha;
            });
        }
        
        $retorno['mostrar_quantidades_devolvidas'] = false;

        if($devolucaoNotaObj->aprovadores->where('devolucao_nota_status_id', 4)->isNotEmpty()){
            $retorno['mostrar_quantidades_devolvidas'] = true;
        }

        return view('programs.devolucao_nota_aprovacao.modal.editar')->with($retorno);

    }

    public function editar(DevolucaoNotaAprovacaoEditarRequest $request){

        $fields = $request->only('id', 'motivo', 'valor_parcial', 'nome_contato', 'descricao_documento','documento','telefone_contato', 'email_contato', 'produtos', 'mensagem', 'responsabilidade_frete', 'transportador', 'transportador_email', 'nota_cliente_numero', 'conferencia_devolucao_completa', 'produtos', 'nota_remessa', 'status');
        
        $id = Crypt::decrypt($fields['id']);

        $devolucaoNotaObj = DevolucaoNota::with(
                'nota_nasajon',
                'nota_nasajon.item',
                'nota_nasajon.revisao_vendedor_comissao',
                'nota_nasajon.revisao_vendedor_comissao.usuario',
                'pedido',
                'pedido.forma_pagamento',
                'pedido.forma_pagamento.condicao',
                'aprovadores',
                'motivo_devolucao',
                'motivo_devolucao.status',
                'status_detalhes',
                'produtos',
                'cliente'
            )
            ->find($id);

        if(isset($fields['mensagem']) && !empty($fields['mensagem'])){
            $mensagem = $request['mensagem'];
        }
        else{
            $mensagem = null;
        }

        $original = $devolucaoNotaObj->getOriginal();

        $statusAtual = $devolucaoNotaObj->motivo_devolucao->status->firstWhere('devolucao_nota_status_id', $devolucaoNotaObj->devolucao_nota_status_id);

        if(isset($fields['status'])){
            $devolucaoNotaObj->devolucao_nota_status_id = $fields['status'];
        }
        
        $documentos = $request->file('documento');
        $documento_isento = $request->file('arquivo_cliente_isento');
        $mensagem_sem_transportadora = '';
        
        if($request->hasFile('documento'))
        {
            foreach($documentos as $key => $documento){
                $dococumentos_devolucao = new DevolucaoNotasDocumento;
                $dococumentos_devolucao->devolucao_nota_id = $devolucaoNotaObj->id;
                $dococumentos_devolucao->tipo_documento = 'diversos';
                $dococumentos_devolucao->nome_arquivo = $fields['descricao_documento'][$key];
                $dococumentos_devolucao->created_by = Auth::user()->id;
                $dococumentos_devolucao->save();
                $file = $dococumentos_devolucao->id.'.' .$documento->getClientOriginalExtension();
                $dococumentos_devolucao->caminho = $file;
                $documento->storeAs($this->storage_files, $file);
                $dococumentos_devolucao->save();

                if(isset($devolucaoNotaObj->nota_nasajon->transportadora->email) && !empty($devolucaoNotaObj->nota_nasajon->transportadora->email)){
                    $mensagem = 'Prezado '.$devolucaoNotaObj->nota_nasajon->nome_transportadora.',<br><br>';
                    $mensagem .= 'Segue documento em anexo '.$fields['descricao_documento'][$key].', referente a devolução da nota fiscal '.$devolucaoNotaObj->nota_nasajon->numero.'.';
                    $emailControllerObj = new EmailController;
                    $returnEmail = $emailControllerObj->sendEmailToken('00', "documentos_devolucao_transportadora", $devolucaoNotaObj->nota_nasajon->transportadora->email, ['corpo' => $mensagem], [$this->storage_files. $file => ['as' => 'documento_devolucao']], []);
                    
                    $dococumentos_devolucao->enviado_transportadora = true;
                    $dococumentos_devolucao->save();

                    $mensagem_sem_transportadora = '<br><b class=\'text-success\'> Obs: Documentos enviados a Transportadora.</b>';
                }else{
                    $mensagem_sem_transportadora = '<br><b class=\'text-danger\'> Obs: Transportadora sem e-mail cadastrado.</b>';
                }
            }
        }
        
        if($request->hasFile('arquivo_cliente_isento'))
        {
            $dococumento_isento = new DevolucaoNotasDocumento;
            $dococumento_isento->devolucao_nota_id = $devolucaoNotaObj->id;
            $dococumento_isento->tipo_documento = 'cliente_isento';
            $dococumento_isento->nome_arquivo = 'cliente isento '.$devolucaoNotaObj->id;
            $dococumento_isento->created_by = Auth::user()->id;
            $dococumento_isento->save();
            $file = $dococumento_isento->id.'.' .$documento_isento->getClientOriginalExtension();
            $dococumento_isento->caminho = $file;
            $documento_isento->storeAs($this->storage_files, $file);
            $dococumento_isento->save();
        }

        $devolucaoNotaLogObj = new DevolucaoNotaLog;
        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->usuario = Auth::id();
        $devolucaoNotaLogObj->mensagem = $mensagem;

        $devolucaoNotaObj->updated_by = Auth::user()->id;

        if($request->hasFile('laudo_imagem')){

            if(Storage::exists($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem)){
                Storage::delete($this->storage . '/laudo/' . $devolucaoNotaObj->laudo_imagem);
            }

            $devolucaoNotaObj->laudo_imagem = 'laudo_devolucao_' . $devolucaoNotaObj->id . '.' . $request->file('laudo_imagem')->extension();
            $request->file('laudo_imagem')->storeAs($this->storage . '/laudo', $devolucaoNotaObj->laudo_imagem);
        }

        if(isset($request['nome_contato'])){
            $devolucaoNotaObj->nome_contato = $fields['nome_contato'];
        }

        if(isset($request['telefone_contato'])){
            $devolucaoNotaObj->telefone_contato = $fields['telefone_contato'];
        }
        
        if(isset($request['email_contato'])){
            $devolucaoNotaObj->email_contato = $fields['email_contato'];
        }

        if(isset($request['responsabilidade_frete'])){
            $devolucaoNotaObj->responsabilidade_frete = $fields['responsabilidade_frete'];
        }

        if(isset($request['transportador'])){
            $devolucaoNotaObj->transportador = $fields['transportador'];
            $transportadorObj = TransportadorNasajon::where(DB::Raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'),$devolucaoNotaObj->transportador)->first();

            if(!is_null($transportadorObj)){
                $variaveis_email['nome_transportadora'] = $transportadorObj->nome;
            }
        }

        if(isset($request['transportador_email'])){
            $devolucaoNotaObj->transportador_email = $fields['transportador_email'];
        }

        if(isset($request['nota_cliente_numero'])){
            $devolucaoNotaObj->nota_cliente_numero = $fields['nota_cliente_numero'];
        }

        $variaveis_email = [];

        if($request->hasFile('nota_cliente_arquivo')){

            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo)){
                Storage::delete($this->storage . '/nota_cliente/' . $devolucaoNotaObj->nota_cliente_arquivo);
            }

            $devolucaoNotaObj->nota_cliente_arquivo = 'nota_cliente_'. $devolucaoNotaObj->id . '.' . $request->file('nota_cliente_arquivo')->extension();
            $request->file('nota_cliente_arquivo')->storeAs($this->storage . '/nota_cliente', $devolucaoNotaObj->nota_cliente_arquivo);
        }

        if(!empty($mensagem)){
            $variaveis_email['observacao'] = $mensagem;
        }
        else{
            $variaveis_email['observacao'] = '';
        }

        switch ($devolucaoNotaObj->responsabilidade_frete) {
            case 'cliente':
                $variaveis_email['email_responsavel_frete'] = $devolucaoNotaObj->email_contato;         
            default:
                $variaveis_email['email_responsavel_frete'] = 'despacho@tecidosmn.com.br';
                break;
        }

        if($request->file('romaneio_arquivo') !== null){
            if(Storage::exists($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo)){
                Storage::delete($this->storage . '/nota_cliente/' . $devolucaoNotaObj->romaneio_arquivo);
            }

            $devolucaoNotaObj->romaneio_arquivo = 'romaneio_'. $devolucaoNotaObj->id . '.' . $request->file('romaneio_arquivo')->extension();
            $request->file('romaneio_arquivo')->storeAs($this->storage . '/nota_cliente', $devolucaoNotaObj->romaneio_arquivo);

        }

        if($devolucaoNotaObj->valor_parcial === true){
            if(isset($fields['conferencia_devolucao_completa'])){
                $devolucaoNotaObj->produtos->each(function ($produto){
                    $produto->quantidade_recebida = $produto->quantidade;
                    $produto->save();
                });
            }
            else{
                foreach($fields['produtos'] as $produto){
                    $devolucaoNotaProdutoObj = DevolucaoNotaProduto::where('produto_id', $produto['id'])->first();
                    $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);
                    $devolucaoNotaProdutoObj->save();
                }
            }
        }
        else{
            if(isset($fields['conferencia_devolucao_completa'])){
                DevolucaoNotaProduto::where('devolucao_nota_id', $devolucaoNotaObj->id)->delete();
                $devolucaoNotaObj->nota_nasajon->item->each(function ($item) use($devolucaoNotaObj){
                    
                    $produto = new DevolucaoNotaProduto;

                    $produto->devolucao_nota_id = $devolucaoNotaObj->id;
                    $produto->produto_id = $item->id_item_nota;
                    $produto->quantidade = $item->quantidade;
                    $produto->quantidade_recebida = $item->quantidade;
                    $produto->created_by = Auth::id();

                    $produto->save();
                });
            }
            else{
                DevolucaoNotaProduto::where('devolucao_nota_id', $devolucaoNotaObj->id)->delete();
                foreach($fields['produtos'] as $produto){
                    $notaVendaItemNasajonObj = $devolucaoNotaObj->nota_nasajon->item->where('id_item_nota', $produto['id'])->first();
                    $devolucaoNotaProdutoObj = new DevolucaoNotaProduto;

                    $devolucaoNotaProdutoObj->devolucao_nota_id = $devolucaoNotaObj->id;
                    $devolucaoNotaProdutoObj->produto_id = $notaVendaItemNasajonObj->id_item_nota;
                    $devolucaoNotaProdutoObj->quantidade = $notaVendaItemNasajonObj->quantidade;
                    $devolucaoNotaProdutoObj->quantidade_recebida = $produto['quantidade_recebida'];
                    $devolucaoNotaProdutoObj->created_by = Auth::id();
                    $devolucaoNotaProdutoObj->quantidade_recebida = parserNumber($produto['quantidade_recebida']);

                    $devolucaoNotaProdutoObj->save();
                }
            }
        }

        $devolucaoNotaObj->push();

        if(isset($fields['nota_remessa']) && !empty($fields['nota_remessa'])){

            $notaRemessaNasajonObj = NotasNasajon::
            where(DB::Raw('trim(leading \'0\' from numero)'), ltrim($fields['nota_remessa'], 0))
                ->where('estabelecimento_codigo', $devolucaoNotaObj->estabelecimento)
                ->first();

            $devolucaoNotaObj->nota_remessa = $notaRemessaNasajonObj->id;
        }

        $devolucaoNotaObj->updated_by = Auth::user()->id;

        $devolucaoNotaLogObj->acao = 'Editado';  
        $devolucaoNotaLogObj->mensagem = $mensagem;
        $devolucaoNotaLogObj->status_antigo = $original['status']??null;
        $devolucaoNotaLogObj->status_novo = $fields['status'];
        $devolucaoNotaLogObj->usuario = Auth::id();

        $devolucaoNotaObj->save();

        if(
            $devolucaoNotaObj->devolucao_nota_status_id == 7 && 
            !empty($devolucaoNotaObj->pedido->forma_pagamento->condicao) && 
            in_array($devolucaoNotaObj->pedido->forma_pagamento->condicao->descricao, [
                'A VISTA',
                'CARTAO DE DEBITO',
                'Cartão BNDES',
                'Cartão Crédito',
                'Dinheiro',
                'USAR CREDITO',
                'À VISTA'
                ]
            )
        ){
            $lancamentoObj = new LancamentoDebCredVendedor;
            
            $lancamentoObj->data_lancamento = Carbon::Now()->format('Y-m-d');
            $lancamentoObj->valor = round($devolucaoNotaObj->valor * ($devolucaoNotaObj->nota_nasajon->revisao_vendedor_comissao->percentual_comissao/100), 2);
            $lancamentoObj->num_documento = $devolucaoNotaObj->id;
            $lancamentoObj->codigo_vendedor = $devolucaoNotaObj->nota_nasajon->revisao_vendedor_comissao->usuario->id;
            $lancamentoObj->codigo_motivo = 24;
            $lancamentoObj->tipo = 'D';
            $lancamentoObj->created_by = Auth::id();
    
            $lancamentoObj->save();
        }

        $mudancas = $devolucaoNotaObj->getChanges();

        $keys = [
            'nome_contato',
            'telefone_contato',
            'email_contato',
        ]; 

        foreach(array_intersect(\array_keys($mudancas), $keys) as $campo){
            if($original[$campo] != $mudancas[$campo]){
                $devolucaoNotaLogObj[$campo . '_antigo'] = $original[$campo];
                $devolucaoNotaLogObj[$campo . '_novo'] = $mudancas[$campo];
            }
        }

        $devolucaoNotaLogObj->save();

        $devolucaoNotaAprovadorObj = new DevolucaoNotaAprovador;

        $devolucaoNotaAprovadorObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaAprovadorObj->devolucao_nota_status_id = $original['devolucao_nota_status_id'];
        $devolucaoNotaAprovadorObj->aprovador = Auth::id();
        $devolucaoNotaAprovadorObj->created_by = Auth::id();

        $devolucaoNotaAprovadorObj->save();


        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!'.$mensagem_sem_transportadora,
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function acusarRecebimento(DevolucaoNotaConfirmaRecebimentoFrete $request){
        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Registro inválido!',
                    'error' => [],
                    'response' => []
                ], 422
            );
        }

        $devolucaoNotaObj = DevolucaoNota::find($id);
    
        $devolucaoNotaLogObj = new DevolucaoNotaLog;
    
        $devolucaoNotaLogObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoNotaLogObj->acao = 'Devolução recebida';
        $devolucaoNotaLogObj->mensagem = 'Produtos recebidos';

        $devolucaoNotaLogObj->usuario = Auth::id();
    
        $devolucaoNotaObj->timestamps = false;
        $devolucaoNotaObj->updated_by = Auth::user()->id;
    
        $devolucaoAprovadorObj = new DevolucaoNotaAprovador;
    
        $devolucaoAprovadorObj->devolucao_nota_id = $devolucaoNotaObj->id;
        $devolucaoAprovadorObj->devolucao_nota_status_id = 13;
        $devolucaoAprovadorObj->aprovador = Auth::id();
        $devolucaoAprovadorObj->created_by = Auth::id();
    
        if($devolucaoNotaObj->save()){
            $devolucaoNotaLogObj->save();
            $devolucaoAprovadorObj->save();
        }
    
        return response()->json(
            [
                'status' => 'success',
                'message' => 'Dados salvos com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );
    }

    public function aprovacaoFaseFinanceiro(DevolucaoNota $devolucaoNotaObj, DevolucaoNotaLog $devolucaoNotaLogObj, $original){
        $titulos          = '';
        $titulos_abertos  = [];
        $titulos_pagos    = [];
        $titulos_creditos = [];
        $credito          = 0;
        $acumulador       = 0;
        $parcela_desconto = 0;
        $saldo_titulo     = 0;
        $data_atual       = Carbon::now();

        $valor_total = $devolucaoNotaObj->nota_nasajon->valor;

        $baixaTituloControllerObj = new BaixaTituloController;

        $contasNasajonObj = ContasNasajon::select()->where('codigo', 'DEVOLUCAO')->first();
        
        $valor_pago    = $devolucaoNotaObj->tituloPagamentos->sum('valor');
        
        if($devolucaoNotaObj->valor_parcial === false){
            $saldo         = $devolucaoNotaObj->titulosAbertos->sum('valor');
           
            $saldo_vencido = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->sum('valor');

        }else{
            $saldo         = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->sum('valor');
            $saldo_vencido = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->sum('valor');
        }

           
        $quantidade_titulos_abertos               = $devolucaoNotaObj->titulosAbertos->count();
        $quantidade_titulos_abertos_nao_vencidos  = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->count();
        $quantidade_titulos_abertos_vencidos      = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->count();

        $valor_abt_venc = $devolucaoNotaObj->titulosAbertos->where('vencimento', '<', $data_atual)->sum('abatimento');
        $valor_abt      = $devolucaoNotaObj->titulosAbertos->where('vencimento', '>', $data_atual)->sum('abatimento');

        $novo_saldo   = $saldo - $devolucaoNotaObj->valor;
        $novo_vencido = $devolucaoNotaObj->valor - $saldo_vencido;
        $resta_saldo  = $devolucaoNotaObj->valor;   

        $devolucaoNotaObj->tituloPagamentos->each(function($titulo) use(&$titulos_pagos){
            $titulos_pagos[] =[
                'titulo' => $titulo->numero,
                'parcela' => $titulo->parcela,
                'data_emissao' => parserData($titulo->emissao),
                'data_vencimento' => parserData($titulo->vencimento),
                'data_pagamento' => parserData($titulo->data_pagamento),
                'valor' => parserValor($titulo->valor),
            ];
        });

        if($devolucaoNotaObj->valor_parcial == false){
            $devolucaoNotaObj->titulosAbertos->each(function($titulo) use($data_atual,$devolucaoNotaObj){
                $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual; 
                $this->cancelaTituloCredito($titulo->titulo_id, $observacao);  
    
                $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                $devolucaoNotaTituloCanceladoObj->save();
                
                $titulos_cancelados[] = [
                    'titulo_numero' => $titulo->numero,
                    'titulo_valor' => $titulo->valor,
                ];
            });
           
        }else{
            if($novo_saldo > 0){
                    
                $devolucaoNotaObj->titulosAbertos->each(function($titulo) use(&$titulos_abertos, &$acumulador,&$total_titulos_abertos, $data_atual,&$resta_saldo,&$novo_saldo,$saldo, $quantidade_titulos_abertos_nao_vencidos, $quantidade_titulos_abertos_vencidos,$quantidade_titulos_abertos,$valor_abt_venc,$saldo_vencido,$valor_abt,$devolucaoNotaObj){
                    if($resta_saldo>0){   
                        
                        if($data_atual->gt($titulo->vencimento)){
                            
                            $valor_abater     = $valor_abt_venc + $titulo->valor;
                            $valor_abater     = parserValor($valor_abater);               
                            $valor_abater     = parserNumber($valor_abater);
                            $valor_baixado    = $valor_abater;
                        
                        
                            if($valor_abater > $resta_saldo){
                                $valor_baixado = $resta_saldo;
                                $resta_saldo = 0;
                                
                                $acumulador += $valor_baixado;

                                if($acumulador > $devolucaoNotaObj->valor){
                                    $valor_baixado = $valor_baixado - ($acumulador - $devolucaoNotaObj->valor);
                                }                                   
                                    
                                if($valor_baixado < 0){
                                    $valor_baixado = (-1) * $valor_baixado;
                                } 

                                $observacao = "Abatimento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$valor_baixado." data: ".$data_atual; 
                                $this->abatimentoTituloCredito($titulo->titulo_id, $valor_baixado, $observacao);

                                $devolucaoNotaTituloAbertoDescontadoObj = new DevolucaoNotaTituloAbertoDescontado;
                                $devolucaoNotaTituloAbertoDescontadoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                $devolucaoNotaTituloAbertoDescontadoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                $devolucaoNotaTituloAbertoDescontadoObj->titulo_numero = $titulo->numero;
                                $devolucaoNotaTituloAbertoDescontadoObj->titulo_valor = $titulo->valor;
                                $devolucaoNotaTituloAbertoDescontadoObj->desconto_valor = $valor_baixado;
                                $devolucaoNotaTituloAbertoDescontadoObj->parcela = $titulo->parcela;
                                $devolucaoNotaTituloAbertoDescontadoObj->data_emissao = $titulo->titulo_emissao;
                                $devolucaoNotaTituloAbertoDescontadoObj->data_vencimento = $titulo->vencimento;
                                $devolucaoNotaTituloAbertoDescontadoObj->created_by = Auth::id();
                                $devolucaoNotaTituloAbertoDescontadoObj->save();
                                
                                $titulos_abertos[] = [
                                    'titulo_numero' => $titulo->numero,
                                    'titulo_valor' => $titulo->valor,
                                    'desconto' => $valor_baixado,
                                ];




                            }else{
                                $resta_saldo       = $resta_saldo - $valor_baixado;  
                                
                                
                                $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual; 
                                $this->cancelaTituloCredito($titulo->titulo_id, $observacao);  

                                $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                                $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                                $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                                $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                                $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                                $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                                $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                                $devolucaoNotaTituloCanceladoObj->save();
                                
                                $titulos_cancelados[] = [
                                    'titulo_numero' => $titulo->numero,
                                    'titulo_valor' => $titulo->valor,
                                ];;

                            }
                            $novo_saldo = -$resta_saldo;  
                                                    
                        //titulo sem ser vencido    
                        }else{
                    
                                
                                $valor_abater     = $valor_abt + $resta_saldo;
                                $valor_abater     = $valor_abater / $quantidade_titulos_abertos_nao_vencidos;
                            
                                $valor_abater     = parserValor($valor_abater);               
                                $valor_abater     = parserNumber($valor_abater);

                                if($resta_saldo > $saldo){
                                    $valor_baixado   = $titulo->valor; 
                                    
                                    $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual; 
                                    $this->cancelaTituloCredito($titulo->titulo_id, $observacao);  

                                    $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                                    $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                    $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                    $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                                    $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                                    $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                                    $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                                    $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                                    $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                                    $devolucaoNotaTituloCanceladoObj->save();
                                    
                                    $titulos_cancelados[] = [
                                        'titulo_numero' => $titulo->numero,
                                        'titulo_valor' => $titulo->valor,
                                    ];;


                                    $novo_saldo     = $saldo - $resta_saldo;
                                
                                }else{
                                    $valor_baixado    = $valor_abater;

                                    $acumulador += $valor_baixado;

                                    if($acumulador > $devolucaoNotaObj->valor){
                                        $valor_baixado = $valor_baixado - ($acumulador - $devolucaoNotaObj->valor);
                                    }    
                                    
                                    if($valor_baixado<0){
                                        $valor_baixado = (-1) * $valor_baixado;
                                    }    
        
                                    
                                    $observacao = "Abatimento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$valor_baixado." data: ".$data_atual; 
                                    $this->abatimentoTituloCredito($titulo->titulo_id, $valor_baixado, $observacao);

                                    $devolucaoNotaTituloAbertoDescontadoObj = new DevolucaoNotaTituloAbertoDescontado;
                                    $devolucaoNotaTituloAbertoDescontadoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_numero = $titulo->numero;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_valor = $titulo->valor;
                                    $devolucaoNotaTituloAbertoDescontadoObj->desconto_valor = $valor_baixado;
                                    $devolucaoNotaTituloAbertoDescontadoObj->parcela = $titulo->parcela;
                                    $devolucaoNotaTituloAbertoDescontadoObj->data_emissao = $titulo->titulo_emissao;
                                    $devolucaoNotaTituloAbertoDescontadoObj->data_vencimento = $titulo->vencimento;
                                    $devolucaoNotaTituloAbertoDescontadoObj->created_by = Auth::id();
                                    $devolucaoNotaTituloAbertoDescontadoObj->save();
                                    
                                    $titulos_abertos[] = [
                                        'titulo_numero' => $titulo->numero,
                                        'titulo_valor' => $titulo->valor,
                                        'desconto' => $valor_baixado,
                                    ];


                                    $novo_saldo      = $titulo->valor - $valor_abater;  

                                }
                                
                            }                
                                                        
                            $total_titulos_abertos += $titulo->valor - $valor_baixado;

                        }               
                
                                
                });

            }else{

                $estabelecimento_uuid_nasajon = $devolucaoNotaObj->estabelecimentoDetalhes->estabelecimento;
                $cliente_uuid_nasajon = $devolucaoNotaObj->cliente->id;
                $data_emissao = Carbon::now();
                $data_vencimento = Carbon::now()->addYear();
                $numero_titulo = $devolucaoNotaObj->nota_fiscal;
                $pedidoFormaPagamentoNasajonObj = PedidoFormaPagamentoNasajon::select()->where('formapagamento_descricao', 'ilike', 'Usar Crédito')->first();
                $forma_pagamento_uuid_nasajon = $pedidoFormaPagamentoNasajonObj->formapagamento;
                $conta_uuid_nasajon = $contasNasajonObj->conta;
                $ordem_devolucao = $devolucaoNotaObj->id;
                
                if($novo_saldo == 0){
                    $devolucaoNotaObj->titulosAbertos->each(function($titulo) use(&$titulos_abertos, &$acumulador,&$total_titulos_abertos, $data_atual,&$resta_saldo,&$novo_saldo,$saldo, $quantidade_titulos_abertos_nao_vencidos, $quantidade_titulos_abertos_vencidos,$quantidade_titulos_abertos,$valor_abt_venc,$saldo_vencido,$valor_abt,$devolucaoNotaObj){
                    
                        $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual; 
                        $this->cancelaTituloCredito($titulo->titulo_id, $observacao); 
                        
                        $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                        $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                        $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                        $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                        $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                        $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                        $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                        $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                        $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                        $devolucaoNotaTituloCanceladoObj->save();
                                        
                        $titulos_cancelados[] = [
                            'titulo_numero' => $titulo->numero,
                            'titulo_valor' => $titulo->valor,
                        ];

                        
                    });    
                }else{
                    
                    $devolucaoNotaObj->titulosAbertos->each(function($titulo) use(&$acumulador,&$titulos_abertos, &$credito,&$total_titulos_abertos, $data_atual,&$resta_saldo,&$novo_saldo,$saldo, $quantidade_titulos_abertos_nao_vencidos, $quantidade_titulos_abertos_vencidos,$quantidade_titulos_abertos,$valor_abt_venc,$saldo_vencido,$valor_abt,$devolucaoNotaObj, $contasNasajonObj){
                    
                        if($resta_saldo>0){   
                            if($data_atual->gt($titulo->vencimento)){
                            
                                $valor_abater     = $valor_abt_venc +  $titulo->valor;
                                $valor_abater     = parserValor($valor_abater);               
                                $valor_abater     = parserNumber($valor_abater);

                                

                                if($valor_abater < $resta_saldo){
                                    
                                    $resta_saldo   = $resta_saldo - $valor_abater;  
                                    $valor_baixado = $valor_abater;
                                
                                    $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual; 
                                    $this->cancelaTituloCredito($titulo->titulo_id, $observacao); 
                                    
                                    $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                                    $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                    $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                    $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                                    $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                                    $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                                    $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                                    $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                                    $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                                    $devolucaoNotaTituloCanceladoObj->save();
                                                    
                                    $titulos_cancelados[] = [
                                        'titulo_numero' => $titulo->numero,
                                        'titulo_valor' => $titulo->valor,
                                    ];



                                }else{
                                    
                                    $valor_baixado    = $valor_abater;

                                    $acumulador += $valor_baixado;

                                    if($acumulador > $devolucaoNotaObj->valor){
                                        $valor_baixado = $valor_baixado - ($acumulador - $devolucaoNotaObj->valor);
                                    }    
                                    
                                    if($valor_baixado<0){
                                        $valor_baixado = (-1) * $valor_baixado;
                                    }    
        
                                    
                                    $observacao = "Abatimento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$valor_baixado." data: ".$data_atual; 
                                    $this->abatimentoTituloCredito($titulo->titulo_id, $valor_baixado, $observacao);

                                    $devolucaoNotaTituloAbertoDescontadoObj = new DevolucaoNotaTituloAbertoDescontado;
                                    $devolucaoNotaTituloAbertoDescontadoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_numero = $titulo->numero;
                                    $devolucaoNotaTituloAbertoDescontadoObj->titulo_valor = $titulo->valor;
                                    $devolucaoNotaTituloAbertoDescontadoObj->desconto_valor = $valor_baixado;
                                    $devolucaoNotaTituloAbertoDescontadoObj->parcela = $titulo->parcela;
                                    $devolucaoNotaTituloAbertoDescontadoObj->data_emissao = $titulo->titulo_emissao;
                                    $devolucaoNotaTituloAbertoDescontadoObj->data_vencimento = $titulo->vencimento;
                                    $devolucaoNotaTituloAbertoDescontadoObj->created_by = Auth::id();
                                    $devolucaoNotaTituloAbertoDescontadoObj->save();
                                    
                                    $titulos_abertos[] = [
                                        'titulo_numero' => $titulo->numero,
                                        'titulo_valor' => $titulo->valor,
                                        'desconto' => $valor_baixado,
                                    ];
                                    $resta_saldo       = $resta_saldo - $valor_baixado;                                  
                                    

                                }
                                                            
                                $novo_saldo = -$resta_saldo; 
                                
                                
                            }else{
                            
                                    $valor_abater     = $valor_abt + $titulo->valor;
                                    $valor_abater     = $valor_abater / $quantidade_titulos_abertos_nao_vencidos;
                                
                                    $valor_abater     = parserValor($valor_abater);               
                                    $valor_abater     = parserNumber($valor_abater);
                                    
            
                                    if($valor_abater < $resta_saldo){
                                        $resta_saldo   = $resta_saldo - $titulo->valor; 
                                        $valor_baixado = $resta_saldo;   
                                        $novo_saldo    = -$resta_saldo;
                                        
                                        $observacao = "Cancelamento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor: '.$devolucaoNotaObj->valor." data: ".$data_atual;  
                                        $this->cancelaTituloCredito($titulo->titulo_id, $observacao);  
                                        
                                        $devolucaoNotaTituloCanceladoObj = new DevolucaoNotaTituloCancelado;
                                        $devolucaoNotaTituloCanceladoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                        $devolucaoNotaTituloCanceladoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                        $devolucaoNotaTituloCanceladoObj->titulo_numero = $titulo->numero;
                                        $devolucaoNotaTituloCanceladoObj->titulo_valor = $titulo->valor;
                                        $devolucaoNotaTituloCanceladoObj->parcela = $titulo->parcela;
                                        $devolucaoNotaTituloCanceladoObj->data_emissao = $titulo->titulo_emissao;
                                        $devolucaoNotaTituloCanceladoObj->data_vencimento = $titulo->vencimento;
                                        $devolucaoNotaTituloCanceladoObj->created_by = Auth::id();
                                        $devolucaoNotaTituloCanceladoObj->save();
                                        
                                        $titulos_cancelados[] = [
                                            'titulo_numero' => $titulo->numero,
                                            'titulo_valor' => $titulo->valor,
                                        ];

                                    }else{
                                        $valor_abater     = $valor_abt + $resta_saldo;
                                        $valor_abater     = $valor_abater / $quantidade_titulos_abertos_nao_vencidos;
                                
                                        $valor_abater     = parserValor($valor_abater);               
                                        $valor_abater     = parserNumber($valor_abater);

                                        $valor_baixado   = $valor_abater;                                   
                                        
                                        
                                        $acumulador += $valor_baixado;

                                        if($acumulador > $devolucaoNotaObj->valor){
                                            $valor_baixado = $valor_baixado - ($acumulador - $devolucaoNotaObj->valor);
                                        }    
                                        
                                        if($valor_baixado<0){
                                            $valor_baixado = (-1) * $valor_baixado;
                                        }  
                                                                        
                                    
                                
                                        $observacao = "Abatimento referente a devolução NF: ".$devolucaoNotaObj->nota_fiscal.' Valor Baixa: '.$valor_baixado." data: ".$data_atual;
                                        $this->abatimentoTituloCredito($titulo->titulo_id, $valor_baixado, $observacao);

                                        $devolucaoNotaTituloAbertoDescontadoObj = new DevolucaoNotaTituloAbertoDescontado;
                                        $devolucaoNotaTituloAbertoDescontadoObj->devolucao_notas_id = $devolucaoNotaObj->id;
                                        $devolucaoNotaTituloAbertoDescontadoObj->titulo_uuid_nasajon = $titulo->titulo_id;
                                        $devolucaoNotaTituloAbertoDescontadoObj->titulo_numero = $titulo->numero;
                                        $devolucaoNotaTituloAbertoDescontadoObj->titulo_valor = $titulo->valor;
                                        $devolucaoNotaTituloAbertoDescontadoObj->desconto_valor = $valor_baixado;
                                        $devolucaoNotaTituloAbertoDescontadoObj->parcela = $titulo->parcela;
                                        $devolucaoNotaTituloAbertoDescontadoObj->data_emissao = $titulo->titulo_emissao;
                                        $devolucaoNotaTituloAbertoDescontadoObj->data_vencimento = $titulo->vencimento;
                                        $devolucaoNotaTituloAbertoDescontadoObj->created_by = Auth::id();
                                        $devolucaoNotaTituloAbertoDescontadoObj->save();
                                        
                                        $titulos_abertos[] = [
                                            'titulo_numero' => $titulo->numero,
                                            'titulo_valor' => $titulo->valor,
                                            'desconto' => $valor_baixado,
                                        ];

                                        $resta_saldo     = parserValor($resta_saldo);               
                                        $resta_saldo     = parserNumber($resta_saldo);  
                                                                            
                                        $resta_saldo   = $resta_saldo - $valor_baixado; 
                                        $novo_saldo    = -$resta_saldo;

                                    }
                                    
                                }                
                                
                            $total_titulos_abertos += $titulo->valor - $valor_baixado;


                        }

                    });
                }
                
            }  
            
            if($novo_saldo < 0){
                $credito = (-1) * $novo_saldo;

                if($credito > 0){ 
                    $titulo_credito_uuid_nasajon = $this->geracaoTituloCredito($estabelecimento_uuid_nasajon, $cliente_uuid_nasajon, $credito, $data_emissao, $data_vencimento, $numero_titulo, $forma_pagamento_uuid_nasajon, $conta_uuid_nasajon, $ordem_devolucao);

                    $devolucaoNotaObj->titulo_credito_uuid_nasajon = $titulo_credito_uuid_nasajon;
                    $devolucaoNotaObj->titulo_credito_numero = $numero_titulo.'.1CRD';
                    $devolucaoNotaObj->titulo_credito_valor = $credito;
                    $devolucaoNotaObj->save();

                    $titulos_creditos[] = [
                    'titulo_numero' => $numero_titulo.'.1CRD',
                    'titulo_valor' => $credito,
                    ];
                }

            //$this->baixaTituloCredito($titulo_credito_uuid_nasajon, $conta_uuid_nasajon, $data_emissao, $credito);
            }

            $usuario_id = User::where('id', 1)->first()->codigo_nasajon;      
            

            $devolucaoNotaObj->updated_by = Auth::user()->id;

            $devolucaoNotaLogObj->acao = 'Aprovação de etapa - ' . $devolucaoNotaObj->status_detalhes->status;  
            $devolucaoNotaLogObj->mensagem = '';
            $devolucaoNotaLogObj->status_antigo = $original['devolucao_nota_status_id'];
            $devolucaoNotaLogObj->status_novo = $devolucaoNotaObj->devolucao_nota_status_id;
            $devolucaoNotaLogObj->usuario = Auth::id();

            $titulos = $titulos."Valor da Devolução: ".parserValor($devolucaoNotaObj->valor)."<br>";
            $titulos = $titulos."Valor dos Títulos Pagos: ".parserValor($valor_pago)."<br>";
            $titulos = $titulos."Valor dos Títulos Abertos: ".parserValor($saldo)."<br>";
            if(!empty($credito)){
                $titulos = $titulos."Valor de Crédito: ".parserValor($credito)."<br>";
            }
            
            if(!empty($titulos_pagos)){
                $titulos = $titulos."<br><br>Títulos Pagos: <br>";
                foreach($titulos_pagos as $titulo_pago){
                    $titulos = $titulos."<p>";
                    $titulos = $titulos.'Número: '.$titulo_pago['titulo'].'<br>';
                    $titulos = $titulos.'Emissão: '.$titulo_pago['data_emissao'].'<br>';
                    $titulos = $titulos.'Vencimento: '.$titulo_pago['data_vencimento'].'<br>';
                    $titulos = $titulos.'Pagamento: '.$titulo_pago['data_pagamento'].'<br>';
                    $titulos = $titulos.'Valor: '.$titulo_pago['valor'].'<br>';
                    $titulos = $titulos."</p>";
                }
            }
            if(!empty($titulos_abertos)){
                $titulos = $titulos."<br><br>Títulos em Aberto: <br>";
                foreach($titulos_abertos as $titulo_aberto){
                    $titulos = $titulos."<p>";
                    $titulos = $titulos.'Número: '.$titulo_aberto['titulo_numero'].'<br>';
                    $titulos = $titulos.'Valor: '.parserValor($titulo_aberto['titulo_valor']).'<br>';
                    $titulos = $titulos.'Valor Baixa: '.parserValor($titulo_aberto['desconto']).'<br>';
                    $titulos = $titulos.'Saldo: '.parserValor($titulo_aberto['titulo_valor'] - $titulo_aberto['desconto']).'<br>';
                    $titulos = $titulos."</p>";
                }
            }
            
            if(!empty($titulos_cancelado)){
                $titulos = $titulos."<br><br>Títulos Cancelados: <br>";
                foreach($titulos_cancelado as $titulo_cancelado){
                    $titulos = $titulos."<p>";
                    $titulos = $titulos.'Número: '.$titulo_cancelado['titulo_numero'].'<br>';
                    $titulos = $titulos.'Valor: '.parserValor($titulo_cancelado['titulo_valor']).'<br>';
                    $titulos = $titulos."</p>";
                }
            }
        }

        $this->envioEmailAprovacaoFaseFinanceiro($devolucaoNotaObj, $titulos);
    }

    public function geracaoTituloCredito($estabelecimento_uuid_nasajon, $cliente_uuid_nasajon, $valor, $data_emissao, $data_vencimento, $numero_titulo, $forma_pagamento_uuid_nasajon, $conta_uuid_nasajon, $ordem_devolucao){
        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $observacao_credito = "Título de Crédito Gerado Automático pelo Sistema, pela Ordem Devolução: ".$ordem_devolucao;
        
        $sql_titulo_credito = "select * from integracoes.api_titulorecebermovo(
            uuid_generate_v4(), /* id */
            '{$estabelecimento_uuid_nasajon}', /* estabelecimento */
            '{$cliente_uuid_nasajon}', /* cliente */
            '{$valor}', /* valor */
            '{$data_emissao->format('Y-m-d')}', /* emissao */
            '{$data_vencimento->format('Y-m-d')}', /* vencimento */
            '{$numero_titulo}.1CRD', /* numero */
            '{$forma_pagamento_uuid_nasajon}', /* forma pagamento */
            '{$conta_uuid_nasajon}', /* conta */
            NULL, /* layout */
            NULL, /* data_multa */
            0.0, /* percentual multa */
            0.0, /* juros diarios */
            '{$usuario_cadastro_uuid}', /* usuario */
            '{$observacao_credito}', /* observacao */
            true /* tipo título crédito */
        );";

        try{
            $titulo_credito = DB::connection('nasajon')->select($sql_titulo_credito);
            $mensagem_nasajon = $titulo_credito[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);

            $titulo_credito_uuid_nasajon = $mensagem_nasajon['mensagem'];

            return $titulo_credito_uuid_nasajon;        
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

    public function baixaTituloCredito($titulo_credito_uuid_nasajon, $conta_uuid_nasajon, $data_baixa, $valor){
        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $observacao_baixa_credito = "Baixa Total de Título de Crédito pelo Portal, usuário:".Auth::user()->name." data: ".date('Y-m-d H:i:s').".";
        $quitartitulo = 'true';

        $sql_baixa_titulo = "select * from integracoes.api_baixartituloreceber(
            '".$titulo_credito_uuid_nasajon."',
            '".$conta_uuid_nasajon."',
            '".$data_baixa->format('Y-m-d')."',
            ".$valor.",
            0.0,
            0.0,
            0.0, 
            0.0,
            0.0,
            0.0,
            '".$observacao_baixa_credito."',
            '".$usuario_cadastro_uuid."',
            ".$quitartitulo."
        );";

        try{
            $baixar_nasajon = DB::connection('nasajon')->select($sql_baixa_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

    public function envioEmailAprovacaoFaseFinanceiro(DevolucaoNota $devolucaoNotaObj, $titulos){
        $emailControllerObj = new EmailController;

        $variaveis_email = [
            'nome_cliente' => $devolucaoNotaObj->cliente->nome,
            'processo' => $devolucaoNotaObj->id,
            'titulos' => $titulos, 
            'link_consulta' => route('consulta_devolucao.index'),
        ];

        $mail_result = $emailControllerObj->sendEmailToken('00', 'email_devolucao_aprovacao_financeiro', '', $variaveis_email);
    }

    public function abatimentoTituloCredito($titulo_credito_uuid_nasajon, $valor, &$observacao){

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $usuario_nome = Auth::user()->name;

        $observacao = $observacao.' usuário: '.$usuario_nome;
        
        $sql_abatimento_titulo = "select * from integracoes.api_incluir_abatimento_titulo_receber(
            '".$titulo_credito_uuid_nasajon."',
            '".$usuario_cadastro_uuid."',
            ".$valor.",
            '".$observacao."'  
        );"; 
        
      

        try{
            $abatimento_nasajon = DB::connection('nasajon')->select($sql_abatimento_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

    public function cancelaTituloCredito($titulo_credito_uuid_nasajon, &$observacao){

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $usuario_nome = Auth::user()->name;

        $observacao = $observacao.' usuário: '.$usuario_nome;
               
        $sql_cancela_titulo = "select * from integracoes.api_titulorecebercancelar(
            '".$titulo_credito_uuid_nasajon."',
            '".$usuario_cadastro_uuid."',
            '".$observacao."' 
        );";      

       

        try{
            $abatimento_nasajon = DB::connection('nasajon')->select($sql_cancela_titulo);
        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }


}
