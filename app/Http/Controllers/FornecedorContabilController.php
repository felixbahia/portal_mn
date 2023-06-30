<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\FornecedorContabilRequest;

use App\Http\Controllers\PrologosController;

use App\FornecedorContabil;
use \PDO;

use Auth;

class FornecedorContabilController extends Controller
{
    public function __construct() {
        $this->middleware(['auth']);
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\FornecedorContabil") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\FornecedorContabil');
        return view('programs.cnpj_contabil.index');
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        return view('programs.cnpj_contabil.create');
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request) {

        $validatedData = $request->validate([
            'codcad' => 'required|numeric|unique:fornecedor_contactb,codcad,NULL,NULL,estabel,' . $request['estabel'],
            'estabel' => 'required|numeric|between:0,5|unique:fornecedor_contactb,estabel,NULL,NULL,codcad,' . $request['codcad'],
            'conta_contabil' => 'required|numeric|digits_between:1,18',
        ], [
            'estabel.required' => __('validation.required', ['attribute' => 'Estábelecimento']),
            'estabel.between' => __('validation.between.string', ['attribute' => 'Estábelecimento']),
            'estabel.unique' => __('validation.unique', ['attribute' => 'Estábelecimento']),
            'estabel.numeric' => __('validation.numeric', ['attribute' => 'Estábelecimento']),
            'codcad.required' => __('validation.required', ['attribute' => 'Código']),
            'codcad.digits_between' => __('validation.digits_between', ['attribute' => 'Código']),
            'codcad.unique' => __('validation.unique', ['attribute' => 'Código']),
            'codcad.numeric' => __('validation.numeric', ['attribute' => 'Código']),
            'conta_contabil.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'conta_contabil.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ]);

        $FornecedorContabilObj = new FornecedorContabil;
        $FornecedorContabilObj->codcad = $request->codcad;
        $FornecedorContabilObj->contactb = $request->conta_contabil;
        $FornecedorContabilObj->estabel = $request->estabel;
        $FornecedorContabilObj->save();

        return response()->json(['saved' => $FornecedorContabilObj ]);
    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {
        return redirect('fornecedor_contabil'); 
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($codcad, $estabel) {
        $dados = FornecedorContabil::where("codcad", $codcad)->where("estabel", intval($estabel))->firstOrFail();
        $PrologosControllerObj = new PrologosController();
        $dadosPrologos = $PrologosControllerObj->getDadosCodcad($codcad);
        $dados_array = [];
        $dados_array["codcad"] = (string) $dados->getOriginal('codcad');
        $dados_array["nome"] = (string) $dadosPrologos["NOME"];
        $dados_array["contactb"] = (string) $dados->getOriginal('contactb');
        $dados_array["estabel"] = (string) $dados->getOriginal('estabel');
        return view('programs.cnpj_contabil.edit')->with('dados', $dados_array);;
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request, $codcad, $estabel) {
        $FornecedorContabilObj = FornecedorContabil::where("codcad", $codcad)->where("estabel", intval($estabel))->firstOrFail();

        $validatedData = $request->validate([
            'codcad' => 'required|numeric|unique:fornecedor_contactb,codcad,'.$request['codcad'].',codcad,estabel,' . $request['estabel'],
            'estabel' => 'required|numeric|between:0,5|unique:fornecedor_contactb,estabel,'.$request['estabel'].',estabel,codcad,' . $request['codcad'],
            'contactb' => 'required|numeric|digits_between:1,18',
        ], [
            'estabel.required' => __('validation.required', ['attribute' => 'Estábelecimento']),
            'estabel.between' => __('validation.between.string', ['attribute' => 'Estábelecimento']),
            'estabel.unique' => __('validation.unique', ['attribute' => 'Estábelecimento']),
            'estabel.numeric' => __('validation.numeric', ['attribute' => 'Estábelecimento']),
            'codcad.required' => __('validation.required', ['attribute' => 'Código']),
            'codcad.digits_between' => __('validation.digits_between', ['attribute' => 'Código']),
            'codcad.unique' => __('validation.unique', ['attribute' => 'Código']),
            'codcad.numeric' => __('validation.numeric', ['attribute' => 'Código']),
            'contactb.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'contactb.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ]);

        $input = $request->only(['codcad', 'estabel', 'contactb']);
        $update = [
            "estabel" => intval($input['estabel']),
            "codcad" => $input['codcad'],
            "contactb" => $input['contactb']
        ];
        $FornecedorContabilObj = DB::table('fornecedor_contactb')->where("codcad", $codcad)->where("estabel", intval($estabel))->update($update);

        return response()->json(['saved' => $FornecedorContabilObj ]);
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($codcad, $estabel) {
        $FornecedorContabilObj = FornecedorContabil::where("codcad", $codcad)->where("estabel", intval($estabel))->firstOrFail();
        $FornecedorContabilObj->delete();

        return response()->json(['saved' => $FornecedorContabilObj ]);
    }

    public function filter(Request $request){
        
        $fields = $request->only(['estabel', 'codcad', 'conta_contabil', 'nome_codcad']);
        $PrologosControllerObj = new PrologosController();
        $where = [];
        $whereIn = [];
        if(!empty($fields['codcad'])){
            $where[] = ['codcad', 'like', "%".$fields['codcad']."%"];
        }
        if(!empty($fields['nome_codcad'])){
            $codCadsNome = $PrologosControllerObj->getCodcadNome(trim($fields['nome_codcad']));
            foreach($codCadsNome as $value){
                $whereIn[] = [$value];
            }
        }
        if(!empty($fields['conta_contabil'])){
            $where[] = ['contactb', $fields['conta_contabil']];
        }
        if(strlen($fields['estabel']) > 0){
            $estabel = $fields['estabel'];
            if(strlen($estabel) > 1){
                $estabel = intval($estabel);
            }
            $where[] = ['estabel', $estabel];
        }
        if(count($whereIn) > 0){
            $results = FornecedorContabil::where($where)->whereIn("codcad", $whereIn)->get();
        }else{
            $results = FornecedorContabil::where($where)->get();
        }
        $empresa = returnEmpresasPrologusView();
        $return = [];
        foreach($results as $key => $result){
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][$k] = "";
                }
            }
            $codcad = (string) $result->getOriginal('codcad');
            $conta_contabil = (string) $result->getOriginal('contactb');
            $estabel = (string) $result->getOriginal('estabel');
            $dados = $PrologosControllerObj->getDadosCodcad($codcad);
            $return[$key]['codcad'] = $codcad;
            $return[$key]['nome'] = $dados["NOME"];
            $return[$key]['contactb'] = $conta_contabil;
            $return[$key]['estabel'] = $empresa[$estabel];
            $return[$key]["edit"] = "<a href=\"#\" data-route=\"".route('fornecedor_contabil.edit',["codcad"=>$codcad, "estabel"=>$estabel])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            $return[$key]["delete"] = "<a href=\"#\" data-route=\"".route('fornecedor_contabil.destroy',["codcad"=>$codcad, "estabel"=>$estabel])."\" data-codcad=\"{$codcad}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        }
        return json_encode($return);
    }

    public function filterCodcad(Request $request){
        $codcad = $request->only("codcad")["codcad"];
        if(!empty($codcad)){
            $dados = $PrologosControllerObj->getDadosCodcad($codcad);
            if(!empty($dados) && !empty($dados["NOME"])){
                $return = ["status"=>"success", "data"=>$dados["NOME"]];
                return response()->json($return);
            }
            $return = ["status"=>"error"];
            return response()->json($return);
        }
    }
}
