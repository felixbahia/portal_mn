<?php

namespace App\Http\Controllers;

use App\Segmento;

use Illuminate\Http\Request;
use App\Http\Requests\SegmentoSalvarNovoRequest;
use App\Http\Requests\SegmentoSalvarEdicaoRequest;
use App\Http\Requests\SegmentoExcluirRequest;

use Auth;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class SegmentoController extends Controller
{
    protected $storage = 'public/segmentos/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Segmentos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Segmentos');

        return view('programs.segmentos.index');
    }

    public function filter(Request $request){

        $fields = $request->only('descricao');

        $query = Segmento::query();

        if(isset($fields['descricao']) && !empty($fields['descricao'])){
            $query->where('descricao', 'ilike', '%' . $fields['descricao'] . '%');
        }

        $query->orderBy('posicao');

        $segmentosObj = $query->get();

        $resposta = [];

        $segmentosObj->each(function ($segmento) use (&$resposta){
            $linha = [];

            $linha['id'] = Crypt::encrypt($segmento->id);
            $linha['descricao'] = $segmento->descricao;
            $linha['posicao'] = $segmento->posicao;
            $linha['imagem'] = !is_null($segmento->imagem) ? $segmento->imagem : '';
            $resposta[] = $linha;

        });

        return response()->json([
            'status' => 'success',
            'message' =>'Segmentos recuperados com sucesso',
            'error' => [],
            'response' => $resposta
        ], 200);

    }

    public function modalNovo(){
        return view('programs.segmentos.modal.novo');
    }

    public function modalEditar(Request $request){

        $fields = $request->only('id');

        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'status' => 'error',
                'message' =>'Segmento inválido',
                'error' => [
                    'id' => 'Segmento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $segmentoObj = Segmento::find($id);
        $imagem = Storage::url($this->storage.$segmentoObj->imagem);

        $retorno = [];

        $retorno['id'] = Crypt::encrypt($segmentoObj->id);
        $retorno['descricao'] = $segmentoObj->descricao;
        $retorno['posicao'] = $segmentoObj->posicao;
        $retorno['cor_codigo'] =$segmentoObj->cor_codigo;
        $retorno['mostrar'] =$segmentoObj->mostrar;
        $retorno['imagem'] = $imagem == Storage::url($this->storage) ? '' : $imagem;

        return view('programs.segmentos.modal.editar')->with($retorno);
    }

    public function salvarNovo(SegmentoSalvarNovoRequest $request){

        $fields = $request->only('descricao', 'imagem', 'posicao', 'cor_codigo', 'mostrar');

        $segmentoObj = new Segmento;

       if(!empty($fields['imagem'])){
            $arquivo = $fields['imagem'];
            $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $fields['descricao']))). '.' . $arquivo->getClientOriginalExtension();
            $arquivo->storeAs($this->storage, $nome_arquivo);
            $segmentoObj->imagem = $nome_arquivo;
       }
       
        $segmentoObj->descricao = strtoupper($fields['descricao']);
        $segmentoObj->posicao = $fields['posicao'];
        $segmentoObj->cor_codigo = $fields['cor_codigo'];
        $segmentoObj->mostrar = $fields['mostrar'];
        $segmentoObj->created_by = Auth::id();

        $segmentoObj->save();

        return response()->json([
            'status' => 'success',
            'message' =>'Segmento salvo com sucesso',
            'error' => [],
            'response' => []
        ], 200);

    }

    public function salvarEdicao(SegmentoSalvarEdicaoRequest $request){
        $fields = $request->only('id', 'descricao', 'imagem', 'posicao', 'cor_codigo', 'mostrar');
        
        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'status' => 'error',
                'message' =>'Segmento inválido',
                'error' => [
                    'id' => 'Segmento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $segmentoObj = Segmento::find($id);

        if(!empty($fields['imagem'])){
            $arquivo = $fields['imagem'];
            $nome_arquivo = strtolower(str_replace(',', '', str_replace(' ', '_', $fields['descricao']))). '.' . $arquivo->getClientOriginalExtension();
            $arquivo->storeAs($this->storage, $nome_arquivo);
            $segmentoObj->imagem = $nome_arquivo;
        }

        $segmentoObj->descricao =  strtoupper($fields['descricao']);
        $segmentoObj->posicao = $fields['posicao'];
        $segmentoObj->cor_codigo = $fields['cor_codigo'];
        $segmentoObj->mostrar = $fields['mostrar'];
        $segmentoObj->updated_by = Auth::id();

        $segmentoObj->save();

        return response()->json([
            'status' => 'success',
            'message' =>'Segmento salvo com sucesso',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function excluir(SegmentoExcluirRequest $request){
        $fields = $request->only('id');
        
        try {
            $id = Crypt::decrypt($fields['id']);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'status' => 'error',
                'message' =>'Segmento inválido',
                'error' => [
                    'id' => 'Segmento inválido'
                ],
                'response' => [
                ]
            ], 422);
        }

        $segmentoObj = Segmento::find($id);
        Storage::delete($this->storage.$segmentoObj->imagem);

        $segmentoObj->deleted_by = Auth::id();

        $segmentoObj->save();
        $segmentoObj->delete();

        return response()->json([
            'status' => 'success',
            'message' =>'Segmento excluído com sucesso',
            'error' => [],
            'response' => []
        ], 200);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => [],
                'response' => []
            ],422);
        }

        $SegmentoObj = Segmento::find($id);

        $saida = [
            'id' => encrypt($SegmentoObj->id),
            'descricao' => $SegmentoObj->descricao
        ];
        return view('programs.segmentos.modal.deletar')->with(['segmento' => $saida]);
    }
}
