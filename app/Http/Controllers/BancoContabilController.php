<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BancoContabilRequest;

use App\BancoContabil;
use \PDO;

use Auth;

class BancoContabilController extends Controller
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
        if(Auth::user()->hasPermissionTo("programas App\BancoContabil") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BancoContabil');
        return view('programs.banco_contabil.index');
    }

    /**
    * Show the form for creating a new resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function create() {
        return view('programs.banco_contabil.create');
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(Request $request) {

        $validatedData = $request->validate([
            'estabel' => 'required|numeric|between:0,5|unique:banco_contactb,estabel,NULL,NULL,codbco,' . (string)$request['codbco'],
            'codbco' => 'required|numeric|digits_between:1,4|unique:banco_contactb,codbco,NULL,NULL,estabel,' . $request['estabel'],
            'contactb' => 'required|numeric|digits_between:1,18',
        ], [
            'estabel.required' => __('validation.required', ['attribute' => 'Estábelecimento']),
            'estabel.between' => __('validation.between.string', ['attribute' => 'Estábelecimento']),
            'estabel.unique' => __('validation.unique', ['attribute' => 'Estábelecimento']),
            'estabel.numeric' => __('validation.numeric', ['attribute' => 'Estábelecimento']),
            'codbco.required' => __('validation.required', ['attribute' => 'Código banco']),
            'codbco.digits_between' => __('validation.digits_between', ['attribute' => 'Código banco']),
            'codbco.unique' => __('validation.unique', ['attribute' => 'Código banco']),
            'codbco.numeric' => __('validation.numeric', ['attribute' => 'Código banco']),
            'contactb.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'contactb.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ]);
        $BancoContabilObj = new BancoContabil;
        $BancoContabilObj->estabel = intval($request->estabel);
        $BancoContabilObj->codbco = intval($request->codbco);
        $BancoContabilObj->contactb = $request->contactb;
        $BancoContabilObj->save();

        return response()->json(['saved' => $BancoContabilObj ]);
    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id) {
        return redirect('banco_contabil'); 
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($estabel, $codbco) {
        $dados = BancoContabil::where('estabel', '=', intval($estabel))->where('codbco', '=',intval($codbco))->firstOrFail();
        $dados_array = [];
        $dados_array["estabel"] = (string) $dados->getOriginal('estabel');
        $dados_array["codbco"] = (string) $dados->getOriginal('codbco');
        $dados_array["contactb"] = (string) $dados->getOriginal('contactb');
        return view('programs.banco_contabil.edit')->with('dados', $dados_array);
    }

    /**
    * Update the specified resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function update(Request $request, $estabel, $codbco) {
        $BancoContabilObj = BancoContabil::where('estabel', '=', intval($estabel))->where('codbco', '=',intval($codbco))->firstOrFail();

        $validatedData = $request->validate([
            'estabel' => 'required|numeric|between:0,5|unique:banco_contactb,estabel,'.$request['estabel'].',estabel,codbco,' . $request['codbco'],
            'codbco' => 'required|numeric|digits_between:1,4|unique:banco_contactb,codbco,'.$request['codbco'].',codbco,estabel,' . $request['estabel'],
            'contactb' => 'required|numeric|digits_between:1,18',
        ], [
            'estabel.required' => __('validation.required', ['attribute' => 'Estábelecimento']),
            'estabel.between' => __('validation.between.string', ['attribute' => 'Estábelecimento']),
            'estabel.unique' => __('validation.unique', ['attribute' => 'Estábelecimento']),
            'estabel.numeric' => __('validation.numeric', ['attribute' => 'Estábelecimento']),
            'codbco.required' => __('validation.required', ['attribute' => 'Código banco']),
            'codbco.digits_between' => __('validation.digits_between', ['attribute' => 'Código banco']),
            'codbco.unique' => __('validation.unique', ['attribute' => 'Código banco']),
            'codbco.numeric' => __('validation.numeric', ['attribute' => 'Código banco']),
            'contactb.required' => __('validation.required', ['attribute' => 'Conta Contábil']),
            'contactb.numeric' => __('validation.numeric', ['attribute' => 'Conta Contábil']),
        ]);

        $input = $request->only(['estabel', 'codbco', 'contactb']);
        $update = [
            "estabel" => intval($input['estabel']),
            "codbco" => intval($input['codbco']),
            "contactb" => intval($input['contactb'])
        ];
        $BancoContabilObj = DB::table('banco_contactb')->where('estabel', '=', intval($estabel))->where('codbco', '=',intval($codbco))->update($update);

        return response()->json(['saved' => $BancoContabilObj ]);
    }

    /**
    * Remove the specified resource from storage.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function destroy($estabel, $codbco) {
        $BancoContabilObj = BancoContabil::where('estabel', '=', intval($estabel))->where('codbco', '=',intval($codbco))->firstOrFail();
        $BancoContabil = DB::table('banco_contactb')->where('estabel', '=', intval($estabel))->where('codbco', '=',intval($codbco))->delete();

        return response()->json(['saved' => $BancoContabil ]);
    }

    public function filter(Request $request){
        
        $fields = $request->only(['estabel', 'codbco', 'contactb']);
        $where = [];
        if(strlen($fields['estabel']) > 0){
            $estabel = $fields['estabel'];
            if(strlen($estabel) > 1){
                $estabel = intval($estabel);
            }
            $where[] = ['estabel', $estabel];
        }
        if(!empty($fields['codbco'])){
            $codbco = $fields['codbco'];
            if(strlen($codbco) > 1){
                $codbco = intval($codbco);
            }
            $where[] = ['codbco', $codbco];
        }
        if(!empty($fields['contactb'])){
            $where[] = ['contactb', $fields['contactb']];
        }
        $results = BancoContabil::where($where)->get();
        $empresa = returnEmpresasPrologusView();
        $return = [];
        foreach($results as $key => $result){
            foreach($result as $k => $value){
                if(empty($value)){
                    $results[$key][$k] = "";
                }
            }
            $estabel = (string) $result->getOriginal('estabel');
            $codbco = (string) $result->getOriginal('codbco');
            $contactb = (string) $result->getOriginal('contactb');
            $return[$key]['estabel'] = $empresa[$estabel];
            $return[$key]['codbco'] = str_pad($codbco, 4, "0", STR_PAD_LEFT);
            $return[$key]['contactb'] = $contactb;
            $return[$key]["edit"] = "<a href=\"#\" data-route=\"".route('banco_contabil.edit',["estabel"=>$estabel,"codbco"=>$codbco,"contactb"=>$contactb])."\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\"></a>";
            $return[$key]["delete"] = "<a href=\"#\" data-route=\"".route('banco_contabil.destroy',["estabel"=>$estabel,"codbco"=>$codbco,"contactb"=>$contactb])."\" data-estabel=\"{$estabel}\" data-codbco=\"{$codbco}\" data-contactb=\"{$contactb}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\"></a>";
        }
        return json_encode($return);
    }

}
