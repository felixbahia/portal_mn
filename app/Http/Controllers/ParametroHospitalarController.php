<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\ParametroHospitalar;

use App\Http\Requests\ParametroHospitalarRequest;

class ParametroHospitalarController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ParametroHospitalar") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ParametroHospitalar');

        $dados = ParametroHospitalar::select()->get();

        return view('programs.parametro_hospitalar.index')->with(['dados' => $dados]);
    }

    public function modalAdicionar(Request $request){
        return view('programs.parametro_hospitalar.modal.adicionar');
    }

    public function modalEditar(Request $request){
        $query = ParametroHospitalar::select();
        $result = $query->get();

        $primeira_vez = true;

        foreach($result as $dado){
            $dados[$dado->margens]['id'] = encrypt($dado->id);
            $dados[$dado->margens]['mark_up'] = parserValor($dado->mark_up);
            $dados[$dado->margens]['frete'] = parserValor($dado->frete);
            
            if($primeira_vez){
                $dados['desconto_pagamento_antecipado_0'] = parserValor($dado->desconto_pagamento_antecipado_0);
                $dados['desconto_pagamento_antecipado_30'] = parserValor($dado->desconto_pagamento_antecipado_30);
                $dados['desconto_pagamento_antecipado_60'] = parserValor($dado->desconto_pagamento_antecipado_60);
                $dados['desconto_pagamento_antecipado_61'] = parserValor($dado->desconto_pagamento_antecipado_61);
                $dados['desconto_inscricao_estadual'] = parserValor($dado->desconto_inscricao_estadual);
                $primeira_vez = false;
            }
        }
        return view('programs.parametro_hospitalar.modal.editar')->with(['dados' => $dados]);
    }

    public function adicionar(ParametroHospitalarRequest $request){
        $fields = $request->only('mark_up_grande_sp','frete_grande_sp', 'mark_up_grande_rj', 'frete_grande_rj', 'mark_up_sudeste','frete_sudeste','mark_up_sul','frete_sul','mark_up_centro_oeste','frete_centro_oeste','mark_up_nordeste','frete_nordeste','mark_up_norte','frete_norte','condicao_0','condicao_30','condicao_60','condicao_61','desconto_inscricao_estadual');
        
        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'GRANDE SP';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_grande_sp']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_grande_sp']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'GRANDE RJ';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_grande_rj']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_grande_rj']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'SUDESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_sudeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_sudeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'SUL';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_sul']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_sul']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'CENTRO OESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_centro_oeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_centro_oeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'NORDESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_nordeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_nordeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = new ParametroHospitalar;
        $parametroHospitalar->margens = 'NORTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_norte']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_norte']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(ParametroHospitalarRequest $request){
        $fields = $request->only('mark_up_grande_sp','frete_grande_sp', 'mark_up_grande_rj', 'frete_grande_rj', 'mark_up_sudeste','frete_sudeste','mark_up_sul','frete_sul','mark_up_centro_oeste','frete_centro_oeste','mark_up_nordeste','frete_nordeste','mark_up_norte','frete_norte','condicao_0','condicao_30','condicao_60','condicao_61','desconto_inscricao_estadual','id_grande_sp', 'id_grande_rj', 'id_sudeste', 'id_sul', 'id_centro_oeste', 'id_nordeste', 'id_norte');

        try{
            $id_grande_sp = decrypt($fields['id_grande_sp']);
            if(empty($fields['id_grande_rj'])){
                $id_grande_rj = '';
            }else{
                $id_grande_rj = decrypt($fields['id_grande_rj']);
            }
            $id_sudeste = decrypt($fields['id_sudeste']);
            $id_sul = decrypt($fields['id_sul']);
            $id_centro_oeste = decrypt($fields['id_centro_oeste']);
            $id_nordeste = decrypt($fields['id_nordeste']);
            $id_norte = decrypt($fields['id_norte']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }
        
        $parametroHospitalar = ParametroHospitalar::find($id_grande_sp);
        $parametroHospitalar->margens = 'GRANDE SP';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_grande_sp']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_grande_sp']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();
        
        if(!empty($id_grande_rj)){
            $parametroHospitalar = ParametroHospitalar::find($id_grande_rj);
        }else{
            $parametroHospitalar = new ParametroHospitalar;
        }
        $parametroHospitalar->margens = 'GRANDE RJ';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_grande_rj']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_grande_rj']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = ParametroHospitalar::find($id_sudeste);
        $parametroHospitalar->margens = 'SUDESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_sudeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_sudeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = ParametroHospitalar::find($id_sul);
        $parametroHospitalar->margens = 'SUL';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_sul']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_sul']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = ParametroHospitalar::find($id_centro_oeste);
        $parametroHospitalar->margens = 'CENTRO OESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_centro_oeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_centro_oeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = ParametroHospitalar::find($id_nordeste);
        $parametroHospitalar->margens = 'NORDESTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_nordeste']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_nordeste']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $parametroHospitalar = ParametroHospitalar::find($id_norte);
        $parametroHospitalar->margens = 'NORTE';
        $parametroHospitalar->mark_up = $this->formtFloat($fields['mark_up_norte']);
        $parametroHospitalar->frete = $this->formtFloat($fields['frete_norte']);
        $parametroHospitalar->desconto_pagamento_antecipado_0 = $this->formtFloat($fields['condicao_0']);
        $parametroHospitalar->desconto_pagamento_antecipado_30 = $this->formtFloat($fields['condicao_30']);
        $parametroHospitalar->desconto_pagamento_antecipado_60 = $this->formtFloat($fields['condicao_60']);
        $parametroHospitalar->desconto_pagamento_antecipado_61 = $this->formtFloat($fields['condicao_61']);
        $parametroHospitalar->desconto_inscricao_estadual = $this->formtFloat($fields['desconto_inscricao_estadual']);
        $parametroHospitalar->created_by = Auth::id();
        $parametroHospitalar->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    function formtFloat($value){
        $value = str_replace(",", ".", str_replace(".", "", $value));
        $value = floatval($value);
        
        return $value;
    }
}
