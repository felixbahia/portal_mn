<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\UnidadeNegocio;
use App\MapaVendaExcecao;
use App\NotasNasajon;
use App\ClienteNasajon;

use App\Http\Requests\MapaVendaExcecaoRequest;

class MapaVendaExcecaoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ExcecaoMapaVenda") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ExcecaoMapaVenda');

        $estabelecimentos = $this->dadosEstabelecimentos();

        return view('programs.mapa_venda.excecao.index')->with(['estabelecimentos' => $estabelecimentos]);
    }

    public function modalAdicionar() {
        $estabelecimentos = $this->dadosEstabelecimentos();
        $unidades_negocios = $this->getUnidadeNegocio();

        return view('programs.mapa_venda.excecao.modal.adicionar')->with(['estabelecimentos' => $estabelecimentos, 'unidades_negocios' => $unidades_negocios]);
    }

    public function adicionar(MapaVendaExcecaoRequest $request){
        $fields = $request->only('estabelecimento', 'numero_nota', 'data_emissao', 'equipe');
        $data_emissao = Carbon::createFromFormat('d/m/Y', $fields['data_emissao']);
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $numero_nota = str_pad($fields["numero_nota"], 9, '0', STR_PAD_LEFT);

        $MapaVendaExcecaoObj = new MapaVendaExcecao;
        $MapaVendaExcecaoObj->estabelecimento_codigo = $estabelecimento;
        $MapaVendaExcecaoObj->numero_nota = $numero_nota;
        $MapaVendaExcecaoObj->data_emissao = $data_emissao;
        $MapaVendaExcecaoObj->unidades_negocios_id = $fields['equipe'];
        $MapaVendaExcecaoObj->created_by = Auth::id();
        $MapaVendaExcecaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filtro(Request $request){
        $fields = $request->only('estabelecimento', 'numero_nota', 'codigo_cliente', 'cliente', 'data_inicio', 'data_fim');

        $empresa = returnEmpresasNasajonView();

        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);

        $query = MapaVendaExcecao::select();
        $query->with(['notaDetalhes' => function($query) use ($fields){
            if(!empty($fields['codigo_cliente'])){
                $query->where('cliente_documento',$fields['codigo_cliente']);
            }
        }]);
        
        if(!empty($fields['data_inicio']) && !empty($fields['data_fim'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $query->whereBetween('data_emissao', [$data_inicio,$data_fim]);
        }

        if(!empty($fields['estabelecimento'])){
            $query->where('estabelecimento_codigo',$estabelecimento);
        }
        if(!empty($fields['nota'])){
            $query->where('numero_nota',$fields['numero_nota']);
        }
        

        $query->orderBy('estabelecimento_codigo');
        $result = $query->get();

        $retorno = [];
        foreach($result as $nota){
            if(!empty($nota->notaDetalhes)){
                $retorno [] = [
                    'id' => encrypt($nota->id),
                    'estabelecimento' => $empresa[intval($nota->estabelecimento_codigo)],
                    'nota' => $nota->numero_nota,
                    'cliente' => $nota->notaDetalhes->cliente_nome." - ".$nota->notaDetalhes->cliente_documento,
                    'data_emissao' => parserData($nota->notaDetalhes->emissao),
                    'equipe' => $nota->detalhesUnidadeNegocio->unidade,
                ];
            }
        }
        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];
        return response()->json($response);
    }

    public function modalEditar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $mapaVendaExcecaoObj = MapaVendaExcecao::find($id);

        if(is_null($mapaVendaExcecaoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
              
        $dados = [
            'id' => encrypt($mapaVendaExcecaoObj->id),
            'estabelecimento' => intval($mapaVendaExcecaoObj->estabelecimento_codigo),
            'nota' => $mapaVendaExcecaoObj->numero_nota,
            'cliente' => $mapaVendaExcecaoObj->notaDetalhes->cliente_nome." - ".$mapaVendaExcecaoObj->notaDetalhes->cliente_documento,
            'data_emissao' => parserData($mapaVendaExcecaoObj->notaDetalhes->emissao),
            'equipe' => $mapaVendaExcecaoObj->unidades_negocios_id,
        ];

        $estabelecimentos = $this->dadosEstabelecimentos();
        $unidades_negocios = $this->getUnidadeNegocio();

        return view('programs.mapa_venda.excecao.modal.editar')->with(['estabelecimentos' => $estabelecimentos, 'unidades_negocios' => $unidades_negocios, 'dados'=> $dados]);
    }

    public function editar(Request $request){
        $fields = $request->only('id', 'estabelecimento', 'numero_nota', 'data_emissao', 'equipe');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        $data_emissao = Carbon::createFromFormat('d/m/Y', $fields['data_emissao']);
        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $numero_nota = str_pad($fields["numero_nota"], 9, '0', STR_PAD_LEFT);

        $MapaVendaExcecaoObj = MapaVendaExcecao::find($id);
        $MapaVendaExcecaoObj->estabelecimento_codigo = $estabelecimento;
        $MapaVendaExcecaoObj->numero_nota = $numero_nota;
        $MapaVendaExcecaoObj->data_emissao = $data_emissao;
        $MapaVendaExcecaoObj->unidades_negocios_id = $fields['equipe'];
        $MapaVendaExcecaoObj->created_by = Auth::id();
        $MapaVendaExcecaoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function modalDeletar(Request $request) {
        $id = $request->only(['id'])['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $mapaVendaExcecaoObj = MapaVendaExcecao::find($id);

        if(is_null($mapaVendaExcecaoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
              
        $empresa = returnEmpresasNasajonView();

        $dados = [
            'id' => encrypt($mapaVendaExcecaoObj->id),
            'estabelecimento' => $empresa[intval($mapaVendaExcecaoObj->estabelecimento_codigo)],
            'nota' => $mapaVendaExcecaoObj->numero_nota,
            'cliente' => $mapaVendaExcecaoObj->notaDetalhes->cliente_nome." - ".$mapaVendaExcecaoObj->notaDetalhes->cliente_documento,
            'data_emissao' => parserData($mapaVendaExcecaoObj->notaDetalhes->emissao),
            'equipe' => $mapaVendaExcecaoObj->detalhesUnidadeNegocio->unidade,
        ];


        return view('programs.mapa_venda.excecao.modal.deletar')->with(['dados'=> $dados]);
    }

    public function deletar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $MapaVendaExcecaoObj = MapaVendaExcecao::find($id);
        $MapaVendaExcecaoObj->deleted_by = Auth::id();
        $MapaVendaExcecaoObj->save();
        $MapaVendaExcecaoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function getClienteNota(Request $request){
        $fields = $request->only('estabelecimento', 'numero_nota');

        $estabelecimento = str_pad($fields["estabelecimento"], 2, '0', STR_PAD_LEFT);
        $numero_nota = str_pad($fields["numero_nota"], 9, '0', STR_PAD_LEFT);
        
        $query = NotasNasajon::select();
        $query->where('estabelecimento_codigo', $estabelecimento);
        $query->where('numero', $numero_nota);

        $result = $query->first();

        if(empty($result)){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => ['numero_nota' => 'Nota não encontrada.'],
                'response' => ''
            ],422);
        }else if(empty($result->cliente_documento)){
            $retorno = [
                'cliente' => 'Cliente Não Encontrado',
                'data_emissao' => parserData($result->emissao),
                'codigo_cliente' => '000000000000000000'
            ];

            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => $retorno
            ]);
        }else{
            $cliente = ClienteNasajon::where('cpf_cnpj', $result->cliente_documento)->first();
            $retorno = [
                'cliente' => $result->cliente_nome,
                'data_emissao' => parserData($result->emissao),
                'codigo_cliente' => $cliente->codigo
            ];

            return response()->json([
                'status' => 'sucess',
                'message' => '',
                'error' => '',
                'response' => $retorno
            ]);
        }
    }

    private function getUnidadeNegocio(){
        $query = UnidadeNegocio::select();
        $query->orderBy('unidade');
        $result = $query->get();

        $unidades_negocios = [];
        foreach($result as $value){
            $unidades_negocios[$value->id] = $value->unidade;
        }

        return $unidades_negocios;
    }

    private function dadosEstabelecimentos(){
        $estabelecimentos = ['' => 'Escolha um estabelecimento'];
        $estabelecimentos =array_merge($estabelecimentos, returnEmpresasNasajonView());
        return $estabelecimentos; 
    }
}
