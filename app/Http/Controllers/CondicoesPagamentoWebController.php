<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CondicoesPagamentoWeb;
use App\ClientesVencimentos;
use App\ClienteNasajon;
use App\Http\Requests\CondicoesPagamentoWebRequest;
use Auth;

use App\ParcelamentoNasajon;
use App\ParcelasParcelamentoNasajon;
use App\FormaPagamentoNasajon;

use Illuminate\Support\Facades\DB;


class CondicoesPagamentoWebController extends Controller
{

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\CondicoesPagamentoWebController") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\CondicoesPagamentoWebController');
        return view('programs.condicoes_pagamento_web.index');
    }

    public function filtro(Request $request){
    	$condicoesObj = CondicoesPagamentoWeb::with(['clientes', 'parcelas', 'forma_pagamento']);
    	if (isset($request->descricao) && !empty($request->descricao)){
            $condicoesObj->whereRaw('TRANSLATE(descricao,\'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ\',\'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC\') ilike \'%' . strtolower(trim($request->descricao)) . '%\'');
    	}
        if (isset($request->liberado_representante) && strlen($request->liberado_representante) > 0){
            $condicoesObj->where('liberado_representante', $request->liberado_representante);
        }
        else if (Auth::user()->tipo_usuario_id == 12){
            $condicoesObj->where('liberado_representante', true);
        }
        $condicoesObj->where('nasajon', true);
        if(isset($request->estabelecimento)){
            $condicoesObj->where('ativo', true);

            $estabelecimento = intval($request->estabelecimento);

            $condicoesObj->where('nasajon', true);
        }

    	$result = $condicoesObj->get();
        $condicoes = [];
        foreach ($result as $key => $condicao) {
            $clientes_array = [];
            if ($condicao->clientes->isNotEmpty()){
                foreach ($condicao->clientes as $v){
                    $clientes_array_verificacao = ClienteNasajon::where('codigo', $v->cliente)->first();
                    if(!empty($clientes_array_verificacao)){
                        $clientes_array[] = $clientes_array_verificacao->nome;
                    }                    
                }
            }
            $clientes = (empty($clientes_array) ? "Todos" : implode(", ", $clientes_array));
            $condicoes[] = [
                'id' => $condicao->id,
                'nasajon_prologos' => 'Nasajon',
                'descricao' => $condicao->descricao,
                'vencimentos' => isset($condicao->parcelas) ? implode('/', $condicao->parcelas->parcelas->pluck('quantidadediapagamento')->toArray()) : '',
                'media' => $condicao->media,
                'clientes' => "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\"{$clientes}\">{$clientes}</div></div>",
                'liberado_representante' => $condicao->liberado_representante ? 'Todos' : 'Restrita',
                'ativo' => ($condicao->ativo == true) ? 'Ativo' : 'Inativo'
            ];
    	}
    	return response()->json($condicoes);
    }

    public function formNovaCondicao(){
        $parcelas = [];
        $condicoes_combo = [];
        
        $FormaPagamentoNasajon = FormaPagamentoNasajon::orderBy('descricao')->get();
        $forma_pagamento = [];
        foreach ($FormaPagamentoNasajon as $forma) {
            $forma_pagamento[$forma->formapagamento] = $forma->descricao;
        }
        unset($FormaPagamentoNasajon);
        

    	return view("programs.condicoes_pagamento_web.formAdicionar")->with(['condicoes' => $condicoes_combo, 'parcelas' => $parcelas, 'forma_pagamento' => $forma_pagamento]);

    }

    public function formEditaCondicao(Request $request){
        $condicao             = CondicoesPagamentoWeb::with('clientes', 'clientes.clienteNasajon', 'parcelas', 'parcelas.parcelas')->find($request->id);
        $condicaoPagamentoobj = CondicoesPagamentoWeb::all();
        $condicao_cadastrada  = [];

        $parcelasCondicao     = '';

        if($condicao->nasajon == true){
            $parcelasCondicao = implode('/', $condicao->parcelas->parcelas->pluck('quantidadediapagamento')->toArray());
        }

        foreach ($condicaoPagamentoobj as $value) {
            $condicao_cadastrada[$value->id_web] = $value->descricao;
        }

        if($condicao->nasajon_parcela == true){
            $parcela_label = implode('/', $condicao->parcelas->parcelas()->pluck('quantidadediapagamento')->toArray());
            $parcela = $condicao->nasajon_parcela;
        }
        else{
            $parcela_label = '';
            $parcela = '';
        }

        $FormaPagamentoNasajon = FormaPagamentoNasajon::orderBy('descricao')->get();
        $forma_pagamento = [];

        foreach ($FormaPagamentoNasajon as $forma) {
            $forma_pagamento[$forma->formapagamento] = $forma->descricao;
        }

        unset($FormaPagamentoNasajon);
        return view("programs.condicoes_pagamento_web.formEditar")->with(['condicao' => $condicao, 'parcelaEdit' => $parcelasCondicao , 'parcela' => $parcela, 'parcela_label' => $parcela_label, 'forma_pagamento' => $forma_pagamento, 'condicoes' => $condicao_cadastrada]);

    }

    public function autoCompleteParcelas(Request $request){ 
        $fields = $request->only(["term"]);
        $term   = $fields['term'];

        $return = [];
        
        $parcelas = ParcelamentoNasajon::where('nome','ilike','%'.$term.'%')
        ->limit(20)
        ->get();

        foreach ($parcelas as $key => $value){
            $return[] = [
                'label' => $value->nome,
                'value' => $value->parcelamento
            ];
        }
        return response()->json($return);
    }

    public function formDeletaCondicao(Request $request){

        $condicao = CondicoesPagamentoWeb::with('clientes.clienteNasajon', 'parcelas')->get()->find($request->id);

        $parcelas = '';
        $clientes_array = [];

        $condicao->liberado_representante = $condicao->liberado_representante?'Todos':'Restrita';

        if($condicao->nasajon_parcela == true){
            $parcelas = implode('/', $condicao->parcelas->parcelas()->pluck('quantidadediapagamento')->toArray());
        }

        foreach ($condicao->clientes as $cliente){
            $clientes_array[] = $cliente->clienteNasajon->nome;
        }

        $clientes = empty($clientes_array)?"Todos":implode("<br>", $clientes_array);

        return view('programs.condicoes_pagamento_web.excluir')->with(['condicao' => $condicao, 'parcelas' => $parcelas, 'clientes' => $clientes]);

    }

    public function novaCondicao(CondicoesPagamentoWebRequest $request){

        $fields = $request->only(['nasajon_forma_pagamento', 'nasajon_parcelas', 'id_web', 'descricao', 'liberado_representante', 'liberado_clientes', 'cod_cliente', 'ativo', 'clientes']);
        
        $nasajon = true;

        if(empty($fields['ativo'])){
            $ativo = false;
        }
        else{
            $ativo = true;
        }

        $parcelas = ParcelamentoNasajon::with('parcelas')->find($fields['nasajon_parcelas']);
        $condicoesObj = CondicoesPagamentoWeb::create([
            "descricao" => $fields['descricao'],
            "nasajon" => true,
            'nasajon_forma_pagamento' => $fields['nasajon_forma_pagamento'],
            'nasajon_parcela' => $fields['nasajon_parcelas'],
            "media" => intval(ceil(array_sum($parcelas->parcelas->pluck('quantidadediapagamento')->toArray()) / count($parcelas->parcelas))),
            'liberado_representante' => (isset($fields['liberado_representante']) && $fields['liberado_representante'] === '1') ? true : false,
            "created_by" => Auth::id(),
            'ativo' => $ativo
        ]);
        if (isset($fields['clientes'])){
            foreach ($fields['clientes'] as $value){
                $cliente = [
                    "condicao_id" => $condicoesObj->id,
                    "cliente" => $value,
                    "created_by" => Auth::id()
                ];
                ClientesVencimentos::create($cliente);
            }
        }
        $condicoes = [];
        return response()->json($condicoes);
    }

    public function editaCondicao(CondicoesPagamentoWebRequest $request){

        $fields = $request->only(['id', 'nasajon', 'nasajon_forma_pagamento', 'nasajon_parcelas', 'id_web', 'descricao', 'liberado_representante', 'liberado_clientes', 'cod_cliente', 'ativo', 'clientes']);

        if(empty($fields['ativo'])){
            $ativo = false;
        }
        else{
            $ativo = true;
        }

        $condicaoObj = CondicoesPagamentoWeb::findOrFail($fields['id']);
        $condicaoObj->descricao = $fields['descricao'];
        $condicaoObj->liberado_representante = isset($fields['liberado_representante']) && $fields['liberado_representante'] === '1' ? true : false;
        $condicaoObj->ativo = $ativo;

        $parcelas = ParcelamentoNasajon::with('parcelas')->find($fields['nasajon_parcelas']);
        $condicaoObj->nasajon = true;
        $condicaoObj->nasajon_forma_pagamento = $fields['nasajon_forma_pagamento'];
        $condicaoObj->nasajon_parcela = $fields['nasajon_parcelas'];
        $condicaoObj->media =  intval(ceil(array_sum($parcelas->parcelas->pluck('quantidadediapagamento')->toArray()) / count($parcelas->parcelas)));

        $condicaoObj->ativo = $ativo;
        $condicaoObj->modified_by = Auth::id();
        $condicaoObj->save();

        ClientesVencimentos::where('condicao_id', $condicaoObj->id)->delete();

        if (isset($fields['clientes'])){
            foreach ($fields['clientes'] as $value){    
                $cliente = [
                    "condicao_id" => $condicaoObj->id,
                    "cliente" => $value,
                    "created_by" => Auth::id()
                ];
                $retorno = ClientesVencimentos::create($cliente);
            }
        }

        $condicoes = [];

        return response()->json($condicoes);

    }

    public function excluiCondicao(Request $request){

        CondicoesPagamentoWeb::destroy($request->id);

        return response()->json(["id" => $request->id ]);

    }

    public function retornaVencimentos(Request $request){
        $ParcelamentoNasajonObj = ParcelamentoNasajon::find($request->CODVCT);
        return response()->json(['vencimentos' => '', 'descricao' => empty($ParcelamentoNasajonObj)? '' : $ParcelamentoNasajonObj->nome]);
    }

    public function autoComplete(Request $request){
        $find = $request->only(["term", "estabelecimento"]);

        $estabelecimento = isset($find['estabelecimento']) ? intval($find['estabelecimento']) : 0;
        $return = [];
        $term = $find['term'];
        $term = tirarAcentos(trim($term));
        
        $condicoesObj = CondicoesPagamentoWeb::select('id', 'descricao');
        $condicoesObj->where(DB::raw('TRANSLATE(descricao,\'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ\',\'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC\')'), 'ilike', strtolower(trim(utf8_decode($term))));
        $condicoesObj->distinct('descricao')
            ->where('ativo', true)
            ->orderBy('descricao')
            ->with('clientes');
        if (Auth::user()->tipo_usuario_id == 12){
            $condicoesObj->where('liberado_representante', true);
        }
        $condicoesObj->where('nasajon', true);
        $condicoes_unica = $condicoesObj->first();

        $condicoesObj = CondicoesPagamentoWeb::select('id', 'descricao');
        $condicoesObj->where(DB::raw('TRANSLATE(descricao,\'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ\',\'aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC\')'), 'ilike', '%'.strtolower(trim(utf8_decode($term))) . '%');
        $condicoesObj->distinct('descricao')
            ->where('ativo', true)
            ->orderBy('descricao')
            ->with('clientes');
        if (Auth::user()->tipo_usuario_id == 12){
            $condicoesObj->where('liberado_representante', true);
        }
        if(!empty($condicoes_unica)){
            $condicoesObj->where('id', "!=", $condicoes_unica->id);
            $return[] = ['value' => $condicoes_unica->id, 'label' => $condicoes_unica->descricao];
        }
        $condicoesObj->where('nasajon', true);
        $condicoes = $condicoesObj->limit(15)->get();


        foreach ($condicoes as $value){
            $return[] = ['value' => $value->id, 'label' => $value->descricao];
        }

        return response()->json($return);
    }

    public function dialog(Request $request){
        $fields = $request->only(['estabelecimento']);
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        return view('programs.condicoes_pagamento_web.dialog')->with(['estabelecimento' => $estabelecimento]);
    }

    public function descrParaId(Request $request){

        $fields = $request->only('descr');

        $condicaoObj = CondicoesPagamentoWeb::with('clientes')->where('descricao', $fields['descr'])->get()->first();

        if (!is_null($condicaoObj)){

            if ($condicaoObj->clientes->isEmpty() || in_array($fields['cod_cliente'], $condicaoObj->clientes->toArray())){
                return response()->json(['id' => $condicaoObj->id, 'media' => $condicaoObj->media], 200);
            }
            else{
                return null;
            }
        }

        else {
            return null;
        }

    }
}
