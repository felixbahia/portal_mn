<?php

namespace App\Http\Controllers;

use App\DevolucaoNota;
use App\DevolucaoNotaMotivo;
use App\DevolucaoNotaStatus;
use App\User;

use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;
use Auth;

class ConsultaDeDevolucaoController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\ConsultaDeDevolucao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaDeDevolucao');

        $gerentes = [];
        $representantes = [];

        $show_gerentes = true;
        $show_representantes = true;

        if(Auth::user()->tipo_usuario->nivel < 1){
            User::whereIn('tipo_usuario_id', [14, 19])->get()->each(function($gerente) use (&$gerentes){
                $gerentes[Crypt::encrypt($gerente->id)] = $gerente->name;
            });
        }
        else{
            if(Auth::user()->tipo_usuario_id == 19){
                $gerentes[Crypt::encrypt(Auth::user()->id)] = Auth::user()->name;
                Auth::user()->subordinados->each(function($usuario) use (&$representantes){
                    $representantes[Crypt::encrypt($usuario->id)] = $usuario->name;
                });
                
                $show_gerentes = false;

            }
            else if(Auth::user()->tipo_usuario_id == 13){
                $gerentes[Crypt::encrypt(Auth::user()->supervisor->id)] = Auth::user()->supervisor->name;
                Auth::user()->supervisor->subordinados->each(function($usuario) use (&$representantes){
                    $representantes[Crypt::encrypt($usuario->id)] = $usuario->name;
                });

                $show_gerentes = false;

            }
            else{
                $show_gerentes = false;
                $show_representantes = false;
            }
        }

        $status = DevolucaoNotaStatus::all()->pluck('descricao', 'id')->toArray();
        $motivos = DevolucaoNotaMotivo::all()->pluck('descricao', 'id')->toArray();

        return view('programs.consulta_devolucoes.index')->with(
            [
                'gerentes' => $gerentes,
                'representantes' => $representantes,
                'status' => $status,
                'motivos' => $motivos,
                'show_gerentes' => $show_gerentes,
                'show_representantes' => $show_representantes
            ]
        );

    }

    public function filter(Request $request){

        $fields = $request->only('data_inicio', 'data_fim', 'devolucao_nota_status_id', 'motivo', 'gerente', 'representante');

        $devolucaoNotaQuery = DevolucaoNota::with(
                'status_detalhes',
                'nota_nasajon',
                'nota_nasajon.revisao_vendedor_comissao',
                'nota_nasajon.revisao_vendedor_comissao.usuario',
                'cliente'
            )
            ->select( 
                'valor',
                'nota_id',
                'devolucao_nota_status_id'
            );

        if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
            $devolucaoNotaQuery->where('devolucao_nota_status_id', $fields['devolucao_nota_status_id']);
        }
        else{
            $devolucaoNotaQuery->where('devolucao_nota_status_id', '!=', 11);
        }

        if(isset($fields['motivo']) && !empty($fields['motivo'])){
            $devolucaoNotaQuery->where('motivo', $fields['motivo']);
        }

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){
            $devolucaoNotaQuery->where('data_emissao', '>=', Carbon::createFromFormat("d/m/Y", $fields['data_inicio']));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){
            $devolucaoNotaQuery->where('data_emissao', '<=', Carbon::createFromFormat("d/m/Y",$fields['data_fim']));
        }

        $devolucaoNotaObj = $devolucaoNotaQuery->get();

        if(Auth::user()->tipo_usuario_id == 19 && (!isset($fields['representante']) || empty($fields['representante']))){
            $subordinados = UserController::varreSubordinados(Auth::id());

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($subordinados){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return in_array($nota->nota_nasajon->revisao_vendedor_comissao->usuario->id, $subordinados);
                }
            });
        }
        else if(Auth::user()->tipo_usuario_id == 13 && (!isset($fields['representante']) || empty($fields['representante']))){
            $subordinados = UserController::varreSubordinados(Auth::user()->responsavel);

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($subordinados){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return in_array($nota->nota_nasajon->revisao_vendedor_comissao->usuario->id, $subordinados);
                }
            });
        }
        else if(Auth::user()->tipo_usuario->nivel > 1 && !in_array(Auth::user()->tipo_usuario_id, [19,13])){
            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return $nota->nota_nasajon->revisao_vendedor_comissao->usuario->id == Auth::id();
                }
            }); 
        }

        if((isset($fields['gerente']) && !empty($fields['gerente'])) && (!isset($fields['representante']) || empty($fields['representante']))){
            try {
                $gerente = Crypt::decrypt($fields['gerente']);
            }
            catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, favor atualizar a página',
                    "error" => ['Ocorreu uma instabilidade.'],
                    "response" => []
                ];
                return response()->json($response, 422);
            }

            $subordinados = UserController::varreSubordinados($gerente);

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($subordinados){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                        return in_array($nota->nota_nasajon->revisao_vendedor_comissao->usuario->id, $subordinados);
                    }
                }
            });
        }

        if(isset($fields['representante']) && !empty($fields['representante'])){
            try {
                $representante = Crypt::decrypt($fields['representante']);
            }
            catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, favor atualizar a página',
                    "error" => ['Ocorreu uma instabilidade.'],
                    "response" => []
                ];
                return response()->json($response, 422);
            }
            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($representante){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return $nota->nota_nasajon->revisao_vendedor_comissao->usuario->id == $representante;
                }
            });
        }

        $retorno = [];
        $total = [
            'quantidade' => 0,
            'valor' => 0
        ];

        $devolucaoNotaObj = $devolucaoNotaObj->groupBy('devolucao_nota_status_id');

        $devolucaoNotaObj->each(function($status) use (&$retorno, &$total){
            $linha = [];

            $linha['status'] = $status->first()->status_detalhes->descricao;
            $linha['devolucao_nota_status_id'] = $status->first()->devolucao_nota_status_id;
            $linha['valor'] = parserValor($status->sum('valor'));
            $linha['quantidade'] = $status->count();

            $total['quantidade'] += $status->count();
            $total['valor'] += $status->sum('valor');

            $retorno[] = $linha;
        });

        if($total['quantidade'] == 0){
            $total['quantidade'] = '';
        }

        if($total['valor'] > 0){
            $total['valor'] = parserValor($total['valor']);
        }
        else{
            $total['valor'] = '';
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'linhas' => $retorno,
                'total' => $total,
                'hash' => Crypt::encrypt($fields)
            ]
        ];

        return response()->json($response, 200);

    }

    public function modalAnalitica(Request $request){

        $fields = $request->only('devolucao_nota_status_id', 'hash', 'tela');

        try {
            $pesquisa = Crypt::decrypt($fields['hash']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            $response = [
                "status" => 'error',
                "message" => 'Ocorreu uma instabilidade, favor atualizar a página',
                "error" => ['Ocorreu uma instabilidade.'],
                "response" => []
            ];
            return response()->json($response, 422);
        }
        
        $devolucaoNotaQuery = DevolucaoNota::with(
                'motivo_devolucao',
                'status_detalhes',
                'aprovadores',
                'aprovadores.aprovador_detalhes',
                'nota_nasajon',
                'produtos',
                'produtos.produto_na_nota',
                'nota_nasajon.revisao_vendedor_comissao',
                'nota_nasajon.revisao_vendedor_comissao.usuario',
                'nota_nasajon.revisao_vendedor_comissao.usuario.supervisor'
        );
        
        if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
            $devolucaoNotaQuery->where('devolucao_nota_status_id', $fields['devolucao_nota_status_id']);
        }

        if(isset($pesquisa['motivo']) && !empty($pesquisa['motivo'])){
            $devolucaoNotaQuery->where('motivo', $pesquisa['motivo']);
        }

        if(isset($pesquisa['motivos']) && !empty($pesquisa['motivos'])){
            $devolucaoNotaQuery->whereIn('motivo', $pesquisa['motivos']);
        }
        
        if(isset($pesquisa['data_inicio']) && !empty($pesquisa['data_inicio'])){
            $devolucaoNotaQuery->where('data_emissao', '>=', Carbon::createFromFormat("d/m/Y",$pesquisa['data_inicio']));
        }

        if(isset($pesquisa['data_fim']) && !empty($pesquisa['data_fim'])){
            $devolucaoNotaQuery->where('data_emissao', '<=', Carbon::createFromFormat("d/m/Y",$pesquisa['data_fim']));
        }

        if(isset($pesquisa['data_inicio_updated']) && !empty($pesquisa['data_inicio_updated'])){
            $devolucaoNotaQuery->whereHas('lancamentoDebCredVendedor', function ($query) use($pesquisa){
                $query->where('codigo_motivo', 24)
                ->whereBetween('data_lancamento', [$pesquisa['data_inicio_updated'], $pesquisa['data_fim_updated']]);
            });
        }

        if(isset($pesquisa['aprovador']) && !empty($pesquisa['aprovador'])){
            $devolucaoNotaQuery->whereHas('aprovadores', function($query) use ($pesquisa){
                $query->where('devolucao_nota_status_id', 4)
                    ->where('aprovador', $pesquisa['aprovador']);
            });
        }

        $devolucaoNotaObj = $devolucaoNotaQuery->get();

        if(isset($pesquisa['produto']) && !empty($pesquisa['produto'])){
            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($devolucao) use ($pesquisa){
                return $devolucao->produtos->pluck('produto_na_nota')->flatten()->pluck('cod_produto')->contains($pesquisa['produto']);
            });
        }

        if((isset($pesquisa['gerente']) && !empty($pesquisa['gerente'])) && (!isset($pesquisa['representante']) || empty($pesquisa['representante']))){
            try {
                $gerente = Crypt::decrypt($pesquisa['gerente']);
            }
            catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, favor atualizar a página',
                    "error" => ['Ocorreu uma instabilidade.'],
                    "response" => []
                ];
                return response()->json($response, 422);
            }

            $subordinados = UserController::varreSubordinados($gerente);

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($subordinados){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return in_array($nota->nota_nasajon->revisao_vendedor_comissao->usuario->id, $subordinados);
                }
            });
        }

        if(isset($pesquisa['representante']) && !empty($pesquisa['representante'])){
            try {
                $representante = Crypt::decrypt($pesquisa['representante']);
            }
            catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                $response = [
                    "status" => 'error',
                    "message" => 'Ocorreu uma instabilidade, favor atualizar a página',
                    "error" => ['Ocorreu uma instabilidade.'],
                    "response" => []
                ];
                return response()->json($response, 422);
            }

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($representante){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return $nota->nota_nasajon->revisao_vendedor_comissao->usuario->id == $representante;
                }
            });
        }

        if(isset($pesquisa['representantes']) && !empty($pesquisa['representantes'])){
            $representantes = $pesquisa['representantes'];

            $devolucaoNotaObj = $devolucaoNotaObj->filter(function($nota) use ($representantes){
                if(isset($nota->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return in_array($nota->nota_nasajon->revisao_vendedor_comissao->usuario->id, $representantes);
                }
            });
        }

        $retorno = [];

        if(!isset($fields['tela']) || empty($fields['tela'])){

            $devolucaoNotaObj->each(function ($devolucao) use(&$retorno, $pesquisa){

                $linha = [];
    
                $linha['processo'] = $devolucao->id;
                $linha['nota_fiscal'] = $devolucao->nota_fiscal;
                $linha['data_emissao'] = parserData($devolucao->data_emissao);
                $linha['total_parcial'] = $devolucao->valor_parcial?'Parcial':'Total';
                $linha['valor'] = parserValor($devolucao->valor);
                $linha['cliente'] = $devolucao->cliente->nome . ' - ' . $devolucao->cliente->cpf_cnpj;
    
                if(isset($devolucao->nota_nasajon->revisao_vendedor_comissao)){
                    $linha['representante'] = $devolucao->nota_nasajon->revisao_vendedor_comissao->vendedor_codigo . ' - ' .$devolucao->nota_nasajon->revisao_vendedor_comissao->usuario->name;
                }
                else{
                    $linha['representante'] = '';
                }
                
                $linha['id'] = Crypt::encrypt($devolucao->id);
    
                $retorno[] = $linha;
                
            });
    
            return view('programs.consulta_devolucoes.modal.index')->with('retorno', $retorno);

        }
        else if(isset($fields['tela']) && $fields['tela'] == 'motivo'){

            $motivos = $devolucaoNotaObj->groupBy('motivo');

            $motivos->each(function ($motivo, $key) use(&$retorno, $pesquisa, $fields){

                $pesquisa['motivo'] = $key;
                $hash = Crypt::encrypt($pesquisa);

                $linha = [];

                if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
                    $linha['status_descricao'] = $motivo->first()->status_detalhes->descricao;
                    $linha['status'] = $motivo->first()->status_detalhes->id;
                }
                else{
                    $linha['status_descricao'] = 'Total';
                    $linha['status'] = '';
                }

                $linha['motivo_descricao'] = $motivo->first()->motivo_devolucao->descricao;
                $linha['motivo'] = $key;
                $linha['quantidade'] = $motivo->count();
                $linha['valor'] = parserValor($motivo->sum('valor'));
                $linha['hash'] = $hash;
    
                $retorno[] = $linha;
                
            });
    
            return view('programs.consulta_devolucoes.modal.motivo')->with('retorno', $retorno);
        }
        else if(isset($fields['tela']) && $fields['tela'] == 'gerente'){

            $gerentes = $devolucaoNotaObj
                ->filter(function($devolucao){
                    return !empty($devolucao->nota_nasajon->revisao_vendedor_comissao->usuario->responsavel);
                })
                ->groupBy(function($devolucao){
                    return $devolucao->nota_nasajon->revisao_vendedor_comissao->usuario->responsavel;
                });

            $gerentes->each(function ($gerente, $key) use(&$retorno, $pesquisa, $fields){

                $pesquisa['gerente'] = Crypt::encrypt($key);
                $hash = Crypt::encrypt($pesquisa);

                $linha = [];
                
                if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
                    $linha['status_descricao'] = $gerente->first()->status_detalhes->descricao;
                    $linha['status'] = $gerente->first()->status_detalhes->id;
                }
                else{
                    $linha['status_descricao'] = 'Total';
                    $linha['status'] = '';
                }

                $linha['gerente_nome'] = $gerente->first()->nota_nasajon->revisao_vendedor_comissao->usuario->supervisor->name;
                $linha['gerente'] = $key;
                $linha['quantidade'] = $gerente->count();
                $linha['valor'] = parserValor($gerente->sum('valor'));
                $linha['hash'] = $hash;
    
                $retorno[] = $linha;
                
            });
    
            return view('programs.consulta_devolucoes.modal.gerente')->with('retorno', $retorno);
        }
        else if(isset($fields['tela']) && $fields['tela'] == 'representante'){
            $representantes = $devolucaoNotaObj->groupBy(function($devolucao){
                if(isset($devolucai->nota_nasajon->revisao_vendedor_comissao->usuario)){
                    return $devolucao->nota_nasajon->revisao_vendedor_comissao->usuario->id;
                }
            });

            $representantes->each(function ($representante, $key) use(&$retorno, $pesquisa, $fields){

                $linha = [];

                foreach($representante as $teste){
                    if(empty($retorno[$teste->nota_nasajon->revisao_vendedor_comissao->usuario->name])){
                        $pesquisa['representante'] = Crypt::encrypt($teste->nota_nasajon->revisao_vendedor_comissao->usuario->id);
                        $hash = Crypt::encrypt($pesquisa);

                        if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
                            $linha['status_descricao'] = $teste->status_detalhes->descricao;
                            $linha['status'] = $teste->status_detalhes->id;
                        }
                        else{
                            $linha['status_descricao'] = 'Total';
                            $linha['status'] = '';
                        }
            
                        $linha['representante_nome'] = $teste->nota_nasajon->revisao_vendedor_comissao->usuario->name;
                        $linha['representante'] = $key;
                        $linha['quantidade'] = 1;
                        $linha['valor'] = $teste->valor;
                        $linha['hash'] = $hash;
        
                        $retorno[$teste->nota_nasajon->revisao_vendedor_comissao->usuario->name] = $linha;
                    }else{
                        $retorno[$teste->nota_nasajon->revisao_vendedor_comissao->usuario->name]['quantidade']++;
                        $retorno[$teste->nota_nasajon->revisao_vendedor_comissao->usuario->name]['valor'] += $teste->valor;
                    }
                    
                }
                
                
            });
            foreach($retorno as $index => $value){
                $retorno[$index]['valor'] = parserValor($retorno[$index]['valor']);
            }
            return view('programs.consulta_devolucoes.modal.representante')->with('retorno', $retorno);
        }
        else if(isset($fields['tela']) && $fields['tela'] == 'separador'){
            $separadores = $devolucaoNotaObj
                ->filter(function($devolucao){
                    return $devolucao->aprovadores->contains('devolucao_nota_status_id', 4);
                })
                ->groupBy(function($devolucao){
                    return $devolucao->aprovadores->firstWhere('devolucao_nota_status_id', 4)->aprovador_detalhes->id;
                });

            $separadores->each(function ($separador, $key) use(&$retorno, $pesquisa, $fields){

                $pesquisa['aprovador'] = $key;
                $hash = Crypt::encrypt($pesquisa);

                $linha = [];

                if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
                    $linha['status_descricao'] = $separador->first()->status_detalhes->descricao;
                    $linha['status'] = $separador->first()->status_detalhes->id;
                }
                else{
                    $linha['status_descricao'] = 'Total';
                    $linha['status'] = '';
                }

                $linha['aprovador_nome'] = $separador->first()->aprovadores->firstWhere('devolucao_nota_status_id', 4)->aprovador_detalhes->name;
                $linha['aprovador'] = $key;
                $linha['quantidade'] = $separador->count();
                $linha['valor'] = parserValor($separador->sum('valor'));
                $linha['hash'] = $hash;
    
                $retorno[] = $linha;
                
            });
    
            return view('programs.consulta_devolucoes.modal.separador')->with('retorno', $retorno);
        }
        else if(isset($fields['tela']) && $fields['tela'] == 'produto'){
            $produtos = $devolucaoNotaObj->pluck('produtos')->flatten()->groupBy(function($produto){
                if(isset($produto->produto_na_nota)){
                    return $produto->produto_na_nota->cod_produto;
                }
            });

            $status_descricao = $devolucaoNotaObj->first()->status_detalhes->descricao;
            $status = $devolucaoNotaObj->first()->status_detalhes->id;

            $produtos->each(function ($produto, $key) use(&$retorno, $pesquisa, $status, $status_descricao, $fields){

                $pesquisa['produto'] = $key;
                $hash = Crypt::encrypt($pesquisa);

                $linha = [];

                if(isset($fields['devolucao_nota_status_id']) && !empty($fields['devolucao_nota_status_id'])){
                    $linha['status'] = $status;
                    $linha['status_descricao'] = $status_descricao;
                }
                else{
                    $linha['status_descricao'] = 'Total';
                    $linha['status'] = '';
                }

                $linha['produto_codigo'] = $produto->first()->produto_na_nota->produto_detalhes->codigo_produto;
                $linha['produto_descricao'] = $produto->first()->produto_na_nota->produto_detalhes->descricao;
                $linha['marca'] = $produto->first()->produto_na_nota->produto_detalhes->marca;
                $linha['produto'] = $key;
                $linha['quantidade'] = parserValor($produto->sum('quantidade'));
                $linha['valor'] = parserValor($produto->sum(function($devolucao){
                    return $devolucao->quantidade * $devolucao->produto_na_nota->valor_unitario;
                }));
                $linha['hash'] = $hash;
    
                $retorno[] = $linha;
                
            });
    
            return view('programs.consulta_devolucoes.modal.produto')->with('retorno', $retorno);
        }
    }
}
