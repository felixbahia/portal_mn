<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\PesquisaSatisfacaoFormulario;
use App\PesquisaSatisfacaoCliente;
use App\PesquisaSatisfacaoFormularioResposta;
use App\PesquisaSatisfacaoFormularioTipoResposta;
use App\ClienteNovo;

use App\Http\Requests\PesquisaSatisfacaoFormularioSalvarRequest;
use App\Http\Requests\PesquisaSatisfacaoFormularioEditarRequest;
use App\Http\Requests\PesquisaSatisfacaoFormularioGravarPesquisarRequest;

class PesquisaSatisfacaoFormularioController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\PesquisaSatisfacaoFormulario") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PesquisaSatisfacaoFormulario');
        return view('programs.pesquisa_satisfacao_formulario.index');
    }

    public function carregaFormulario(){
        $retorno = [];
        $formularios = PesquisaSatisfacaoFormulario::with('tipoRespostas')->orderBy('ordem_pergunta')->get();
        
        foreach($formularios as $key => $formulario){
            $retorno[] = [
                'pergunta' => $formulario->pergunta,
                'ordem_pergunta' => $formulario->ordem_pergunta,
                'tipo_resposta' => $formulario->tipoRespostas->resposta,
                'id' => encrypt($formulario->id)
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'dados' => $retorno
            ]
        ];

        return response()->json($response);
    }

    public function modalAdicionarQuestao(){
        $tipo_resposta_array = [];
        $tipo_resposta = PesquisaSatisfacaoFormularioTipoResposta::get();

        foreach($tipo_resposta as $resposta){
            $tipo_resposta_array[$resposta->id] = $resposta->resposta;
        }

        return view('programs.pesquisa_satisfacao_formulario.modal.adicionar')->with(['tipo_resposta' => $tipo_resposta_array]);
    }

    public function gravarQuestao(PesquisaSatisfacaoFormularioSalvarRequest $request){
        $campos = $request->only(["questao","tipo_questao","ordem"]);

        $formulario = new PesquisaSatisfacaoFormulario;
        $formulario->pergunta = $campos["questao"];
        $formulario->ordem_pergunta = $campos["ordem"];
        $formulario->pesquisa_satisfacao_formulario_tipo_respostas_id = $campos["tipo_questao"];
        $formulario->created_by = Auth::id();

        if($formulario->save()){
            $response = [
                "status" => 'success',
                "message" => 'Gravado com Sucesso.',
                "error" => [],
                "response" => ['ok']
            ];
        }else{
            $response = [
                "status" => 'error',
                "message" => 'Falha ao gravar, tente novamente mais tarde.',
                "error" => [],
                "response" => ['error']
            ];
        }

        return response()->json($response);
    }

    public function modalEditarQuestao(Request $request){
        $campos = $request->only(["id"]);

        try{
            $id = decrypt($campos['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $tipo_resposta_array = [];
        $formulario = PesquisaSatisfacaoFormulario::find($id);
        $tipo_resposta = PesquisaSatisfacaoFormularioTipoResposta::get();

        foreach($tipo_resposta as $resposta){
            $tipo_resposta_array[$resposta->id] = $resposta->resposta;
        }
        
        return view('programs.pesquisa_satisfacao_formulario.modal.editar')->with(['dados' => $formulario,'id' => $campos['id'],'tipo_resposta' => $tipo_resposta_array]);
    }

    public function editarQuestao(PesquisaSatisfacaoFormularioEditarRequest $request){
        $campos = $request->only(["questao","tipo_questao","ordem","id"]);

        try{
            $id = decrypt($campos['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $formulario = PesquisaSatisfacaoFormulario::find($id);
        $formulario->pergunta = $campos['questao'];
        $formulario->ordem_pergunta = $campos['ordem'];
        $formulario->pesquisa_satisfacao_formulario_tipo_respostas_id = $campos['tipo_questao'];
        $formulario->updated_by = Auth::id();

        if($formulario->save()){
            $response = [
                "status" => 'success',
                "message" => 'Gravado com Sucesso.',
                "error" => [],
                "response" => ['ok']
            ];
        }else{
            $response = [
                "status" => 'error',
                "message" => 'Falha ao gravar, tente novamente mais tarde.',
                "error" => [],
                "response" => ['error']
            ];
        }

        return response()->json($response);
    }

    public function excluirDocumento(Request $request){
        $filter = $request->only(['id']);

        try{
            $fields = decrypt($filter['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }
        
        try{
            $pesquisa = PesquisaSatisfacaoFormulario::findOrFail($fields);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'erro',
                'message' => 'Ocorreu um erro!',
                'error' => $e->getMessage(),
                'response' => []
            ]);
        }

        $pesquisa->deleted_by = Auth::user()->id;
        $pesquisa->save();
        $pesquisa->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Pergunta excluida com sucesso.',
            'error' => '',
            'response' => ['ok']
        ]);
    }

    public function formularioPesquisa($link){
        $link = substr($link,0,32);
        $dados_cliente = [];
        $cliente = PesquisaSatisfacaoCliente::where('link_formulario',$link)->first();
        
        if(empty($cliente)){
            return abort(404);
        }

        $estrutura_formulario = PesquisaSatisfacaoFormulario::orderBy('ordem_pergunta')->with('tipoRespostas')->get();
        $pesquisa_satisfacao = PesquisaSatisfacaoFormularioResposta::where('pesquisa_satisfacao_clientes_id',$cliente->id)->first(); 

        if(!empty($pesquisa_satisfacao)){
            return view('programs.pesquisa_satisfacao_formulario.formulario_pesquisa_realizada');
        }

        $notas = '';
        $dados_cliente['nome'] = $cliente->nome;
        $dados_cliente['id'] = encrypt($cliente->id);

        foreach($cliente->clienteNotas as $nota){
            if(!empty($notas)){
                $notas.=' / ';
            }
            $notas .= $nota->numero;
        }

        $dados_cliente['notas'] = $notas;
        
        return view('programs.pesquisa_satisfacao_formulario.formulario_pesquisa')->with(['estrutura_formulario' => $estrutura_formulario,'cliente' => $dados_cliente]);

    }

    public function gravarFormulario(PesquisaSatisfacaoFormularioGravarPesquisarRequest $request){
        $filter = $request->only(['resposta','pergunta','id','tipo_pergunta']);

        try{
            $id = decrypt($filter['id']);
        }catch(Exception $e){
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '', 
                'response' => '',
            ];
            return response()->json($return);
        }

        $pesquisa_satisfacao = PesquisaSatisfacaoFormularioResposta::where('pesquisa_satisfacao_clientes_id',$id)->first();        
        
        if(empty($pesquisa_satisfacao)){
            $cliente = PesquisaSatisfacaoCliente::with('clienteNotas.notasNasajon.revisao_vendedor_comissao.usuario.supervisor')->find($id);

            foreach($filter['pergunta'] as $key => $pergunta){

                if($filter['tipo_pergunta'][$key] == '4' && $filter['resposta'][$key] == '0'){
                    $emailControllerObj = new EmailController;
                    $cliente_documento = $cliente->nome.' - '.$cliente->documento;
                    
                    foreach($cliente->clienteNotas as $nota){

                        if(!empty($nota->notasNasajon->revisao_vendedor_comissao->usuario->supervisor) && isset($nota->notasNasajon->revisao_vendedor_comissao->usuario->supervisor)){
                            $email = $nota->notasNasajon->revisao_vendedor_comissao->usuario->supervisor->email;
                            
                            if(!empty($email)){
                                $email = "<p>Olá,</p>";
                                $email .= "<p>Informamos que o cliente ".$cliente_documento.", respondeu de forma negativa a pesquisa de satisfação referente a nota ".$nota->numero.", atribuindo nota zero a empresa.</p>";
                                $mail_envio = $emailControllerObj->sendEmailToken('00', 'pesquisa_satisfacao_negativa_para_gerentes', $email, ['nome_cliente' => $cliente_documento,'corpo' => $email]);
                            }
                        }

                    }

                }

                if($filter['tipo_pergunta'][$key] == '3'){
                    ClienteNovo::where('cpf_cnpj',$cliente->documento)->update(['forte_cliente' => $filter['resposta'][$key]]);
                }   

                $pesquisa_satisfacao = new PesquisaSatisfacaoFormularioResposta;
                $pesquisa_satisfacao->pergunta = $pergunta;
                $pesquisa_satisfacao->respostas = $filter['resposta'][$key];
                $pesquisa_satisfacao->pesquisa_satisfacao_clientes_id = $id;
                $pesquisa_satisfacao->pesquisa_satisfacao_formulario_tipo_respostas_id = $filter['tipo_pergunta'][$key];
                $pesquisa_satisfacao->save();

            }

            return response()->json([
                'status' => 'success',
                'message' => 'Muito obrigado por responder nossa pesquisa, ela é muito importante para melhorarmos nossos serviços.',
                'error' => '',
                'response' => ['ok']
            ]);

        }else{

            return response()->json([
                'status' => 'success',
                'message' => 'A pesquisa já foi realizada!',
                'error' => '',
                'response' => ['ok']
            ]);

        }

    }
}
