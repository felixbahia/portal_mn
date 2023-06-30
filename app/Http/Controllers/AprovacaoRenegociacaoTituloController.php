<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\AprovacaoRenegociacao;
use App\RenegociacaoTitulo;
use App\MotivoRecusaRenegociacaoTitulo;
use App\MotivoRecusaRenegociacaoTituloCliente;
use App\ClienteBlackList;
use App\ClienteBlackListTitulo;
use App\ClienteBlackListHistorico;

use App\Http\Controllers\RenegociacaoTituloController;
use App\Http\Controllers\EmailController;
use App\Http\Requests\AprovacaoRenegociacaoTituloRecusaClienteRequest;
use App\Http\Requests\AprovacaoRenegociacaoTituloRecusaDiretoriaRequest;

class AprovacaoRenegociacaoTituloController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\AprovacaoRenegociacao") === false){
            return abort(403);
        }
		$request->session()->flash('model', 'App\AprovacaoRenegociacao');

        return view('programs.aprovacao_renegociacao_titulo.index');
    }

    public function filtro(Request $request){
        $query = AprovacaoRenegociacao::select();
        $query->with(['renegociacaoTitulo']);
        $query->where('aprovacao_diretoria', false);
        $query->where('aprovacao_automatica', false);
        $result = $query->get();

        $retorno = [];
        foreach($result as $aprovacao){
            $data_inicial = Carbon::parse($aprovacao->renegociacaoTitulo->data_inicial);
            $data_final = Carbon::parse($aprovacao->renegociacaoTitulo->data_final);
            $intervalo_total_dias = $data_inicial->diffInDays($data_final);

            $motivo = $this->getMotivo($aprovacao);

            $retorno [] = [
                'id' => encrypt($aprovacao->id),
                'renegociacao_titulos_id' => encrypt($aprovacao->renegociacao_titulos_id),
                'cliente' => $aprovacao->renegociacaoTitulo->cliente->nome.' - '.$aprovacao->renegociacaoTitulo->cliente_cpf_cnpj, 	
                'data' => parserData($aprovacao->renegociacaoTitulo->data_inicial),
                'titulos' => $aprovacao->renegociacao_titulos_id,
                'valor_titulos' => parserValor($aprovacao->renegociacaoTitulo->valor_total_titulos), 
                'juros_por_mes' => parserValor($aprovacao->renegociacaoTitulo->juros_mes)."%",
                'valor_renegociacao' => parserValor($aprovacao->renegociacaoTitulo->valor_total_titulos_com_juros),
                'parcelas' => $aprovacao->renegociacaoTitulo->parcela_quantidade,
                'periodo_dias' => $intervalo_total_dias,
                'motivo' => $motivo,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];

        return response()->json($response);
    }

    public function aprovacaoDiretoria(Request $request){
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $aprovacaoRenegociacaoObj = AprovacaoRenegociacao::find($id);
        if($aprovacaoRenegociacaoObj->aprovacao_diretoria == false && $aprovacaoRenegociacaoObj->renegociacaoTitulo->confissao_divida == true){

            foreach($aprovacaoRenegociacaoObj->renegociacaoTitulo->avalistas as $avalista){
                if($avalista->tipo_signatario == 'representante_legal'){
                    $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                    $dados_signatario[$avalista->id]['email'] = $avalista->email;
                    $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                    $dados_signatario[$avalista->id]['tipo'] = 'representante_legal';
                }
                else{
                    $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                    $dados_signatario[$avalista->id]['email'] = $avalista->email;
                    $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                    $dados_signatario[$avalista->id]['tipo'] = 'fiador';
                }

                if($avalista->estado_civil == 'casado' || $avalista->estado_civil == 'uniao_estavel'){
                    $dados_signatario[$avalista->cpf_venia_conjugal]['nome'] = $avalista->nome_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['cpf'] = $avalista->cpf_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['email'] = $avalista->email_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['tipo'] = 'venia_conjugal';
                }

                if($dados_signatario[$avalista->id]['cpf'] == '176.331.198-88'){
                    unset($dados_signatario[$avalista->id]);
                }
            }

            $RenegociacaoTituloController = new RenegociacaoTituloController;

            $path_pdf = $RenegociacaoTituloController->gerarArquivoPdf($aprovacaoRenegociacaoObj->renegociacao_titulos_id);
            $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
            $retorno = $AssinaturaEletronicaClicksign->solicitarAssinaturaEmail($dados_signatario, $path_pdf);

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }

            $aprovacaoRenegociacaoObj->renegociacaoTitulo->clicksign_documentos_id = decrypt($retorno->getData()->response->id);
            $aprovacaoRenegociacaoObj->renegociacaoTitulo->save();

            $aprovacaoRenegociacaoObj->aprovacao_diretoria = true;
            $aprovacaoRenegociacaoObj->data_aprovacao_diretoria = Carbon::now();
            $aprovacaoRenegociacaoObj->diretoria_users_id = Auth::id();
            $aprovacaoRenegociacaoObj->updated_by = Auth::id();
            $aprovacaoRenegociacaoObj->save();
            $aprovacaoRenegociacaoObj->renegociacaoTitulo->status_renegociacao_titulos_id = 2;
            $aprovacaoRenegociacaoObj->renegociacaoTitulo->save();

        }else{

            $aprovacaoRenegociacaoObj->aprovacao_diretoria = true;
            $aprovacaoRenegociacaoObj->data_aprovacao_diretoria = Carbon::now();
            $aprovacaoRenegociacaoObj->diretoria_users_id = Auth::id();
            $aprovacaoRenegociacaoObj->updated_by = Auth::id();
            $aprovacaoRenegociacaoObj->save();
            $aprovacaoRenegociacaoObj->renegociacaoTitulo->status_renegociacao_titulos_id = 2;
            $aprovacaoRenegociacaoObj->renegociacaoTitulo->save();

            $renegociacaoTituloController = new RenegociacaoTituloController;
            
            $renegociacaoTituloController->geracaoRenegociacaoTitulo($aprovacaoRenegociacaoObj->renegociacao_titulos_id, '');
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }

    public function recusaDiretoria(AprovacaoRenegociacaoTituloRecusaDiretoriaRequest $request){
        $fields = $request->only(['id', 'motivo']);
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $aprovacaoRenegociacaoObj = AprovacaoRenegociacao::find($id);
        $aprovacaoRenegociacaoObj->deleted_by = Auth::id();
        $aprovacaoRenegociacaoObj->save();
        $aprovacaoRenegociacaoObj->renegociacaoTitulo->status_renegociacao_titulos_id = 4;
        $aprovacaoRenegociacaoObj->renegociacaoTitulo->save();
        $aprovacaoRenegociacaoObj->delete();

        $motivoRecusaRenegociacaoTituloObj = new MotivoRecusaRenegociacaoTitulo;
        $motivoRecusaRenegociacaoTituloObj->renegociacao_titulos_id = $aprovacaoRenegociacaoObj->renegociacao_titulos_id;
        $motivoRecusaRenegociacaoTituloObj->descricao = $fields['motivo'];
        $motivoRecusaRenegociacaoTituloObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }

    public function aprovacaoCliente(Request $request){
        $fields = $request->only(['id', 'id_avalista', 'tipo', 'estabelecimento_codigo', 'cpf_cnpj', 'venia_conjugal']);
        try{
            $id = decrypt($fields['id']);
            $id_avalista = empty($fields['id_avalista'])? '' : decrypt($fields['id_avalista']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
        if(in_array($renegociacaoTituloObj->status_renegociacao_titulos_id, [6,7])){
            $renegociacaoTituloObj->status_renegociacao_titulos_id = 8;
            $renegociacaoTituloObj->save();
        }
        

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }

    public function recusaCliente(AprovacaoRenegociacaoTituloRecusaClienteRequest $request){
        $fields = $request->only(['id', 'id_avalista', 'motivo', 'tipo', 'venia_conjugal']);

        try{
            $id = decrypt($fields['id']);
            $id_avalista = empty($fields['id_avalista'])? '' : decrypt($fields['id_avalista']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
        if($fields['tipo'] === 'previa'){
            $renegociacaoTituloObj->status_renegociacao_titulos_id = 7;
        }else{
            $renegociacaoTituloObj->status_renegociacao_titulos_id = 5;
        }
        $renegociacaoTituloObj->save();

        $motivoRecusaRenegociacaoTituloClienteObj = new MotivoRecusaRenegociacaoTituloCliente;
        $motivoRecusaRenegociacaoTituloClienteObj->renegociacao_titulos_id = $id;
        if($fields['tipo'] === 'aprovacao'){
            $motivoRecusaRenegociacaoTituloClienteObj->renegociacao_titulo_avalistas_id = $id_avalista;
        }
        $motivoRecusaRenegociacaoTituloClienteObj->descricao = $fields['motivo'];
        $motivoRecusaRenegociacaoTituloClienteObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }


    public function modalRecusaDiretoria(Request $request){
        $fields = $request->only(['id', 'renegociacao_titulos_id']);
        try{
            $renegociacao_titulos_id = decrypt($fields['renegociacao_titulos_id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($renegociacao_titulos_id);

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $total = 0;
        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulos[] = [
                'titulo' => empty($titulo->detalhesTituloAberto)? $titulo->detalhesTitulosPagos->numero : $titulo->detalhesTituloAberto->numero,
                'valor_original' => empty($titulo->detalhesTituloAberto)? parserValor($titulo->detalhesTitulosPagos->valor) : parserValor($titulo->detalhesTituloAberto->valor),
            ];

            $total += empty($titulo->detalhesTituloAberto)? $titulo->detalhesTitulosPagos->valor : $titulo->detalhesTituloAberto->valor;
        }

        $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
        $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
        $intervalo_total_dias = $data_inicial->diffInDays($data_final);

        $dados = [
            'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
            'data' => parserData($renegociacaoTituloObj->data_inicial),
            'titulos' => $titulos,
            'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
            'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
            'valor_total_juros' => parserValor($renegociacaoTituloObj->valorTotalComJuros->total),
            'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
            'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
            'periodo' => $intervalo_total_dias.' dias',
        ];

        return view('programs.aprovacao_renegociacao_titulo.modal.recusa_diretoria')->with(['id' => $fields['id'],'dados' => $dados, 'total' => $total]);
    }

    public function modalRecusaCliente(Request $request){
        $fields = $request->only(['id', 'id_avalista', 'tipo']);
        try{
            $renegociacao_titulos_id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($renegociacao_titulos_id);

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $total = 0;
        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulos[] = [
                'titulo' => $titulo->titulo_numero,
                'valor_original' => parserValor($titulo->titulo_valor),
            ];

            $total += $titulo->titulo_valor;
        }

        $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
        $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
        $intervalo_total_dias = $data_inicial->diffInDays($data_final);

        $dados = [
            'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
            'data' => parserData($renegociacaoTituloObj->data_inicial),
            'titulos' => $titulos,
            'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
            'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
            'valor_total_juros' => parserValor($renegociacaoTituloObj->valorTotalComJuros->total),
            'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
            'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
            'periodo' => $intervalo_total_dias.' dias',
        ];

        $id_avalista = isset($fields['id_avalista'])? $fields['id_avalista'] : '';

        return view('programs.aprovacao_renegociacao_titulo.modal.recusa_cliente')->with(['id' => $fields['id'], 'id_avalista' => $id_avalista,'dados' => $dados, 'total' => $total, 'tipo' => $fields['tipo']]);
    }

    public function emailAprovacaoPreviaCliente($id_renegocicao, $email){
        $EmailObj = new EmailController();

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id_renegocicao);

            $hash = [
                'id' => $id_renegocicao,
            ];
    
            $variaveis = [
                'nome_cliente' => $renegociacaoTituloObj->cliente->nome,
                'link_previa' => route('aprovacao_renegociacao_titulo.index_aprovacao_previa_cliente', ['hash' => encrypt($hash)])
            ];
    
            $email_send[] = $email;
    
            $retorno = $EmailObj->sendEmailToken('00', "renegociacao_titulo_previa", $email_send, $variaveis);   
            
            try{
                if($retorno['status'] === 'error'){
                    throw new \Exception('Não foi possivel enviar o e-mail');
                }
            } catch (\Exception $e) {
                return $retorno;
            }
    }

    public function indexAprovacaoPreviaCliente($hash, Request $request){
        try{
            $hash = decrypt($hash);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $id = $hash['id'];

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        foreach($renegociacaoTituloObj->titulos as $titulo){
            if(is_null($titulo->encargo_total)){
                $data_inicial = Carbon::parse($titulo->data_vencimento);
                $data_final = Carbon::parse($renegociacaoTituloObj->data_inicial_renegociacao);
                if($data_inicial->gte($data_final)){
                    $intervalo_total_dias = 0;
                }else{
                    $intervalo_total_dias = $data_inicial->diffInDays($data_final);
                }
                
                $juros_atualizado = $renegociacaoTituloObj->juro_atualizacao_titulo / 30 / 100 * $intervalo_total_dias;
                $juros_atualizado_valor = $titulo->titulo_valor * $juros_atualizado;

                $total_valor_atualizado = $titulo->titulo_valor + $juros_atualizado_valor + $renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo;

                $titulos[] = [
                    'estabelecimento' =>$estabelecimentos[intval($titulo->estabelecimento_codigo)],
                    'titulo' => $titulo->titulo_numero,
                    'parcela' => empty($titulo->parcela)? '' : $titulo->parcela,
                    'data_emissao' => empty($titulo->data_emissao)? '' : parserData($titulo->data_emissao),
                    'data_vencimento' => empty($titulo->data_vencimento)? '' : parserData($titulo->data_vencimento),
                    'novo_vencimento' => empty($renegociacaoTituloObj->data_inicial_renegociacao)? '' : parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                    'valor_original' => empty($titulo->valor_original)? '' : parserValor($titulo->valor_original),
                    'intervalo_total_dias' => empty($intervalo_total_dias)? '' : $intervalo_total_dias,
                    'juros' => empty($titulo->valor_juros)? '': parserValor($titulo->valor_juros),
                    'valor_saldo' => empty($titulo->valor_saldo)? '' : parserValor($titulo->valor_saldo),
                    'nota' => empty($titulo->nota_numero)? '' : $titulo->nota_numero,
                    'taxa_encargo' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                    'valor_encargo_diario' => $intervalo_total_dias > 0 ? parserValor($juros_atualizado_valor/$intervalo_total_dias) : '',
                    'juros_atualizado_valor' => empty($juros_atualizado_valor)? '' : parserValor($juros_atualizado_valor),
                    'tarifa_bancaria' => parserValor($renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo),
                    'total_valor_atualizado' => parserValor($total_valor_atualizado),
                ];

                $datas = [];
                foreach($renegociacaoTituloObj->parcelas as $parcela){
                    $datas[] = [
                        'numero' => $parcela->numero,
                        'data' => parserData($parcela->data_parcela),
                        'valor' => parserValor($parcela->valor),
                    ];
                }
            
                $parcelas = [];
                $cnpj_establecimento = [];
                foreach($renegociacaoTituloObj->parcelas as $parcela_cnpj_estabelecimento){
                    $parcelas[] = [
                        'estabelecimento_codigo' => $estabelecimentos[5],
                        'data_parcela' => parserData($parcela_cnpj_estabelecimento->data_parcela),
                        'numero' => $parcela_cnpj_estabelecimento->numero,
                        'encargos' => parserValor($parcela_cnpj_estabelecimento->encargos),
                        'tarifa_bancaria' => parserValor($parcela_cnpj_estabelecimento->tarifa_bancaria),
                        'juros' => parserValor($parcela_cnpj_estabelecimento->juros)."%",
                        'valor' => parserValor($parcela_cnpj_estabelecimento->valor),
                    ];
                }

                $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
                $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
                $intervalo_total_dias = $data_inicial->diffInDays($data_final);

                $dados = [
                    'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
                    'data' => parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                    'titulos' => $titulos,
                    'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
                    'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
                    'valor_total_juros' => parserValor($renegociacaoTituloObj->valorTotalComJuros->total),
                    'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
                    'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
                    'periodo' => $intervalo_total_dias.' dias',
                    'datas' => $datas,
                    'motivo' => in_array($renegociacaoTituloObj->status_renegociacao_titulos_id, [6])? '' : $renegociacaoTituloObj->statusDetalhes->descricao,
                    'juro_atualizacao_titulo' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                    'tarifa_bancaria_atualizacao_titulo' => parserValor($renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo),
                    'encargos' => parserValor($renegociacaoTituloObj->encargos),
                    'tarifa_bancaria_renegociacao' => parserValor($renegociacaoTituloObj->tarifa_bancaria_renegociacao),
                ];
            }else{
                $titulos[] = [
                    'estabelecimento' =>$estabelecimentos[intval($titulo->estabelecimento_codigo)],
                    'titulo' => $titulo->titulo_numero,
                    'parcela' => empty($titulo->parcela)? '' : $titulo->parcela,
                    'data_emissao' => empty($titulo->data_emissao)? '' : parserData($titulo->data_emissao),
                    'data_vencimento' => empty($titulo->data_vencimento)? '' : parserData($titulo->data_vencimento),
                    'valor_original' => empty($titulo->valor_original)? '' : parserValor($titulo->valor_original),
                    'total_valor_atualizado' => parserValor($titulo->valor_total),
                ];

                $parcelas = [];
                $cnpj_establecimento = [];
                foreach($renegociacaoTituloObj->parcelas as $parcela_cnpj_estabelecimento){
                    $parcelas[] = [
                        'estabelecimento_codigo' => $estabelecimentos[5],
                        'data_parcela' => parserData($parcela_cnpj_estabelecimento->data_parcela),
                        'numero' => $parcela_cnpj_estabelecimento->numero,
                        'valor' => parserValor($parcela_cnpj_estabelecimento->valor),
                    ];
                }

                $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
                $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
                $intervalo_total_dias = $data_inicial->diffInDays($data_final);

                $dados = [
                    'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
                    'data' => parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                    'titulos' => $titulos,
                    'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
                    'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
                    'valor_total_juros' => parserValor($renegociacaoTituloObj->valorTotalComJuros->total),
                    'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
                    'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
                    'periodo' => $intervalo_total_dias.' dias',
                    'motivo' => in_array($renegociacaoTituloObj->status_renegociacao_titulos_id, [6])? '' : $renegociacaoTituloObj->statusDetalhes->descricao,
                    'juro_atualizacao_titulo' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                    'tarifa_bancaria_atualizacao_titulo' => parserValor($renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo),
                    'encargos' => parserValor($renegociacaoTituloObj->encargos),
                    'tarifa_bancaria_renegociacao' => parserValor($renegociacaoTituloObj->tarifa_bancaria_renegociacao),
                ];
            }
        }

        return view('programs.aprovacao_renegociacao_titulo.aprovacao_previa')->with(['dados' => $dados, 'id' => encrypt($id), 'parcelas' => $parcelas, 'cnpj_establecimento' => $cnpj_establecimento]);
    }

    private function getMotivo(AprovacaoRenegociacao $aprovacao_renegociacao){
        $motivo = "";

        if (!empty($aprovacao_renegociacao->motivo_desconto_no_valor_do_titulo)){
            $motivo = empty($motivo)? 'Há desconto no valor do título' : $motivo.', Há desconto no valor do título';
        }
        if (!empty($aprovacao_renegociacao->motivo_juros_baixo_permitido)){
            $motivo = empty($motivo)? 'O juro está baixo do permitido' : $motivo.', O juro está baixo do permitido';
        }
        if (!empty($aprovacao_renegociacao->motivo_periodo_maior_permitido)){
            $motivo = empty($motivo)? 'Período está maior que o permitido' : $motivo.', Período está maior que o permitido';
        }
        if (!empty($aprovacao_renegociacao->motivo_parcela_com_valor_fixo)){
            $motivo = empty($motivo)? 'Mudança no valor da parcela' : $motivo.', Mudança no valor da parcela';
        }
        if (!empty($aprovacao_renegociacao->motivo_sem_fiador)){
            $motivo = empty($motivo)? 'Sem fiador' : $motivo.', Sem fiador';
        }
        if (!empty($aprovacao_renegociacao->motivo_fiador_casado_sem_venia_conjugal)){
            $motivo = empty($motivo)? 'Não informado vênia conjugal' : $motivo.', Não informado vênia conjugal';
        }
        if (!empty($aprovacao_renegociacao->motivo_parcelas_maior_permitido)){
            $motivo = empty($motivo)? 'Parcela maior que permitido' : $motivo.', Parcela maior que permitido';
        }


        $motivo = $motivo.'.';

        return $motivo;
    }

    private function verificarClienteBlackList($cliente_cnpj, $titulo_numero, $titulo_id){
        $clienteBlackListObj = ClienteBlackList::select();
        $clienteBlackListObj->where('cpf_cnpj', $cliente_cnpj);
        $clienteBlackListObj = $clienteBlackListObj->first();

        if(empty($clienteBlackListObj)){
            $clienteBlackListObj = new ClienteBlackList; 
        }

        $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
        $clienteBlackListObj->created_by = Auth::id();
        $clienteBlackListObj->status_cliente_black_lists_id = 3;
        $clienteBlackListObj->save();

        $clienteBlackListTituloObj = new ClienteBlackListTitulo;
        $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListTituloObj->titulo_uuid_nasajon = $titulo_id;
        $clienteBlackListTituloObj->titulo_numero = $titulo_numero;
        $clienteBlackListTituloObj->created_by = Auth::id();
        $clienteBlackListTituloObj->save();

        $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
        $clienteBlackListHistoricoObj->cpf_cnpj = $cliente_cnpj;
        $clienteBlackListHistoricoObj->titulo = $titulo_numero;
        $clienteBlackListHistoricoObj->motivo = 'Título Renegociado';
        $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListHistoricoObj->created_by = Auth::id();
        $clienteBlackListHistoricoObj->save();
    }

    public function emailAprovacaoDiretoria(){
        $EmailObj = new EmailController();
    
        $variaveis = [
            'link_aprovacao' => route('aprovacao_renegociacao_titulo.index')
        ];

        $email_send = [];

        $retorno = $EmailObj->sendEmailToken('00', "renegociacao_titulo_aguardado_aprovacao_diretoria", $email_send, $variaveis);   
        
        try{
            if($retorno['status'] === 'error'){
                throw new \Exception('Não foi possivel enviar o e-mail');
            }
        } catch (\Exception $e) {
            return $retorno;
        }
    }
}
