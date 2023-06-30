<?php

namespace App\Http\Controllers;

use Auth;
use App\Laudo;

use App\ProdutoGrupo;
use Illuminate\Http\Request;
use App\ProdutoEspecificacao;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\LaudoEditarRequest;
use App\Http\Requests\LaudoCadastrarRequest;
use App\Http\Requests\LaudoSalvarInstrucoesLavagemRequest;
use App\Http\Requests\BookVirtualSalvarInstrucoesLavagemRequest;

class LaudoController extends Controller
{ 
    public $path = 'public/instrucoes_lavagem/';

    public function cadastroIndex(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Laudo") === false){
            return abort(403);
        }
        $status =$this->situacao();
     

        $request->session()->flash('model', 'App\Laudo');

        return view('programs.laudos.cadastro.index')->with(['status' => $status]);
    }

    public function modalAdicionar() {
        return view('programs.laudos.cadastro.modal.adicionar');
    }
    
    public function modalEditar(Request $request){

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

        $produtoGrupoObj = ProdutoGrupo::find($id);
        if(empty($produtoGrupoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Laudo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
    
        $dados = [
            'id' => encrypt($produtoGrupoObj->id),
            'caracteristicas' => $produtoGrupoObj->caracteristicas,
            'tamanho_pecas' => $produtoGrupoObj->pecas,
            'origem' => $produtoGrupoObj->origem,
            'descricao' => $produtoGrupoObj->descricao,
            'status'=> $produtoGrupoObj->status,
            'gramatura_linear'=> parserQtd($produtoGrupoObj->gramatura_gml),
            'gramatura_gm2' => $produtoGrupoObj->gramatura_gm2,
            'rendimento'=> $produtoGrupoObj->rendimento,
            'encolhimento'=> $produtoGrupoObj->encolhimento,
            'titulo_trama'=> $produtoGrupoObj->titulo_trama,
            'titulo_urdume'=> $produtoGrupoObj->titulo_urdume,
            'informacao_adicional'=> $produtoGrupoObj->informacao_adicional,
            'caminho' =>empty($produtoGrupoObj->caminho)? '' : "<img src='" .Storage::url($produtoGrupoObj->caminho)  . "' width='30%'>" ,
            'nome_arquivo' => $produtoGrupoObj->nome_arquivo,
            'ligamento' => $produtoGrupoObj->ligamento,
            'construcao' => $produtoGrupoObj->construcao,
            'largura' => $produtoGrupoObj->largura,
        ];
 

        $busca = ProdutoEspecificacao::select();
        $busca->where('grupo', $produtoGrupoObj->descricao);
        $busca->where('ativo', 'true');
        $result = $busca->get();

        $produtos = [];
        foreach($result as $value){
            $produtos [] = [
                'codigo' => $value->codigo_produto,
                'descricao' => $value->descricao,
                'subgrupo' => $value->subgrupo,
                'marca' => $value->marca,
                'linha' => $value->linha
            ];
        }
        $id = encrypt($id);

        return view('programs.laudos.cadastro.modal.editar')->with(['dados' => $dados, 'produtos' => $produtos, 'id'=>$id]);

    }

    public function cadastroFilter(Request $request){
        $fields = $request->only('grupo','status');
       
        $query = ProdutoGrupo::select();
        if(!empty($fields['grupo'])){
            $query->where('descricao', $fields['grupo']);
   
        }
        if(!empty($fields['status'])){
            $query->where('status',$fields['status']);
         }
     
        $result = $query->get();

        $grupos = [];
        foreach($result as $value){
            $descricao = $value->descricao;
            $situacao ='A Preencher';
            if(!empty($value->status)){
                if($value->status == true){
                    $situacao ='Preenchido';
                }

            }
            $grupos[]= [
                'grupo' => $descricao,
                'id' => encrypt($value->id),
                'status' => $situacao
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $grupos
        ];
        return response()->json($retorno);
    }

    public function ProdutosGrupo(Request $request){
        $fields = $request->only('grupo');

        $busca = ProdutoEspecificacao::select();
        $busca->where('grupo', $fields['grupo']);
        $busca->where('ativo', 'true');
        $result = $busca->get();

        $produtos = [];
        foreach($result as $value){
           
            $produtos [] = [
                'codigo_produto' => $value->codigo_produto,
                'descricao' => $value->descricao,
                'subgrupo' => $value->subgrupo,
                'marca' => $value->marca,
                'linha' => $value->linha
            ];          
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $produtos
        ];
        return response()->json($response);


    }

    public function cadastroEditar(LaudoEditarRequest $request) {
        $fields = $request->only(
        'id'
        ,'grupo'
        ,'caracteristicas'
        ,'tamanho_pecas'
        ,'origem'
        ,'arquivo'
        ,'status'
        ,'gramatura_linear'
        ,'rendimento'
        ,'encolhimento'
        ,'titulo_trama'
        ,'titulo_urdume'
        ,'informacao_adicional'
        ,'ligamento'
        ,'construcao'
        ,'gramatura_gm2'
        ,'largura'
    );
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

        $ProdutoGrupoObj = ProdutoGrupo::find($id);
        $ProdutoGrupoObj->caracteristicas = $fields['caracteristicas'];
        $ProdutoGrupoObj->origem = strtoupper($fields['origem']);
        $ProdutoGrupoObj->pecas = $fields['tamanho_pecas'];
        $ProdutoGrupoObj->updated_by = Auth::id();
        $ProdutoGrupoObj->gramatura_gml =empty($fields['gramatura_linear'])? 0 : parserNumber($fields['gramatura_linear']);  
        $ProdutoGrupoObj->rendimento = $fields['rendimento'];  
        $ProdutoGrupoObj->encolhimento =empty($fields['encolhimento'])? 0 : parserNumber($fields['encolhimento']);    
        $ProdutoGrupoObj->titulo_trama = $fields['titulo_trama'];
        $ProdutoGrupoObj->titulo_urdume = $fields['titulo_urdume'];
        $ProdutoGrupoObj->informacao_adicional = $fields['informacao_adicional'];
        $ProdutoGrupoObj->status = true;
        $ProdutoGrupoObj->ligamento = $fields['ligamento'];
        $ProdutoGrupoObj->construcao = $fields['construcao'];
        $ProdutoGrupoObj->gramatura_gm2 = $fields['gramatura_gm2'];
        $ProdutoGrupoObj->largura = $fields['largura'];

        if(!empty($fields['arquivo'])){  
        
                $name_arquivo = $fields['grupo'];
                $arquivo = $fields['arquivo'];
                    
                $name_arquivo_foto = strtolower(str_replace(['°','º','ª','','\\',',', '(', ')', '%','.','/'], '', str_replace(' ', '_', $name_arquivo))).".".$arquivo->getClientOriginalExtension();
               
                if(Storage::exists($this->path.$ProdutoGrupoObj->id."/" .$name_arquivo_foto)){
                    Storage::delete($this->path.$ProdutoGrupoObj->id."/" . $name_arquivo_foto);
                    
                }
                $path_file = $arquivo->storeAs($this->path.$ProdutoGrupoObj->id, $name_arquivo_foto);
                $ProdutoGrupoObj->nome_arquivo = $name_arquivo_foto;
                $ProdutoGrupoObj->caminho = $path_file;
         }

        $ProdutoGrupoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);

    }

    public function situacao(){

        $situacao = [
            "false" => 'A Preencher',
            "true" => 'Preenchido',
        ];

        return $situacao;
    }

}
