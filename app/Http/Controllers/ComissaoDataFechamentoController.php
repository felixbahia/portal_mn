<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ComissaoDataFechamento;

use App\Http\Requests\ComissaoDataFechamentoNovoRequest;
use App\Http\Requests\ComissaoDataFechamentoEditarRequest;

use Auth;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class ComissaoDataFechamentoController extends Controller
{
    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\ComissaoDataFechamento") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComissaoDataFechamento');

        return view('programs.comissao_data_fechamento.index');
    }

    public function filter(Request $request){
        
        $fields = $request->only('data_inicio', 'data_fim');

        $query = ComissaoDataFechamento::query();

        if(isset($fields['data_inicio']) && !empty($fields['data_inicio'])){

            $data_inicio = Carbon::createFromFormat("m/Y", $fields['data_inicio']);

            $query->where(DB::Raw("TO_DATE(periodo, 'MM/YYYY')"), '>=', $data_inicio->format('Y-m-01'));
        }

        if(isset($fields['data_fim']) && !empty($fields['data_fim'])){

            $data_fim = Carbon::createFromFormat("m/Y", $fields['data_fim']);

            $query->where(DB::Raw("TO_DATE(periodo, 'MM/YYYY')"), '<=', $data_fim->format('Y-m-01 23:59:59'));
        }

        $comissaoDataFechamento = $query->get();

        $resultado = [];

        $comissaoDataFechamento->each(function($data) use(&$resultado){

            $linha = [];

            $linha['id'] = $data->id;
            $linha['periodo'] = $data->periodo;
            $linha['data_inicio'] = parserData($data->data_inicio);
            $linha['data_fim'] = parserData($data->data_fim);

            $resultado[] = $linha;
        });

        $return = [
			'status' => 'success',
			'message' => '',
			'error' => '',
			'response' => 
				$resultado
        ];
        
        return response()->json($return);

    }

    public function modalNovo(Request $request){
        return view('programs.comissao_data_fechamento.modal.novo');
    }

    public function modalEditar(Request $request){

        $fields = $request->only('id');

        $comissaoDataFechamentoObj = ComissaoDataFechamento::find($fields['id']);

        return view('programs.comissao_data_fechamento.modal.editar')
            ->with([
                'id' => $comissaoDataFechamentoObj->id, 
                'periodo' => $comissaoDataFechamentoObj->periodo,
                'data_inicio' => parserData($comissaoDataFechamentoObj->data_inicio),
                'data_fim' => parserData($comissaoDataFechamentoObj->data_fim)
            ]);
    }

    public function novo(ComissaoDataFechamentoNovoRequest $request){

        $fields = $request->only('periodo', 'data_inicio', 'data_fim');

        $comissaoDataFechamentoObj = new ComissaoDataFechamento;

        $comissaoDataFechamentoObj->periodo = $fields['periodo'];
        $comissaoDataFechamentoObj->data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $comissaoDataFechamentoObj->data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $comissaoDataFechamentoObj->created_by = Auth::id();

        $comissaoDataFechamentoObj->save();

        $mes = Carbon::createFromFormat('m/Y', $comissaoDataFechamentoObj->periodo);

        if($comissaoDataFechamentoObj->data_inicio->format('Y-m-d') != $mes->copy()->subMonth()->format('Y-m-26')){
            $comissaoDataFechamentoMesAnteriorObj = ComissaoDataFechamento::where('periodo', $mes->copy()->subMonth()->format('m/Y'))->first();

            if(empty($comissaoDataFechamentoMesAnteriorObj)){
                $comissaoDataFechamentoMesAnteriorObj = new ComissaoDataFechamento;
                $comissaoDataFechamentoMesAnteriorObj->periodo =  $mes->copy()->subMonth()->format('m/Y');
                $comissaoDataFechamentoMesAnteriorObj->data_inicio = $mes->copy()->subMonths(2)->format('Y-m-26');
                $comissaoDataFechamentoMesAnteriorObj->created_by = Auth::id();
            }
            else{
                $comissaoDataFechamentoMesAnteriorObj->updated_by = Auth::id();
            }

            $comissaoDataFechamentoMesAnteriorObj->data_fim = Carbon::parse($comissaoDataFechamentoObj->data_inicio)->subDay()->format('Y-m-d');
    
            $comissaoDataFechamentoMesAnteriorObj->save();
        }
        else{
            $comissaoDataFechamentoMesAnteriorObj = ComissaoDataFechamento::where('periodo', $mes->copy()->subMonth()->format('m/Y'))->first();

            if(!empty($comissaoDataFechamentoMesAnteriorObj)){
                $comissaoDataFechamentoMesAnteriorObj->data_fim = Carbon::parse($comissaoDataFechamentoObj->data_inicio)->subDay()->format('Y-m-d');
                $comissaoDataFechamentoMesAnteriorObj->updated_by = Auth::id();
                $comissaoDataFechamentoMesAnteriorObj->save();
            }
        }

        if($comissaoDataFechamentoObj->data_fim->format('Y-m-d') != $mes->format('Y-m-25')){
            $comissaoDataFechamentoProximoMesObj = ComissaoDataFechamento::where('periodo', $mes->copy()->addMonthNoOverflow()->format('m/Y'))->first();

            if(empty($comissaoDataFechamentoProximoMesObj)){
                $comissaoDataFechamentoProximoMesObj = new ComissaoDataFechamento;
                $comissaoDataFechamentoProximoMesObj->periodo =  $mes->copy()->addMonthNoOverflow()->format('m/Y');
                $comissaoDataFechamentoProximoMesObj->data_fim = $mes->copy()->addMonthNoOverflow()->format('Y-m-25');
                $comissaoDataFechamentoProximoMesObj->created_by = Auth::id();
            }
            else{
                $comissaoDataFechamentoProximoMesObj->updated_by = Auth::id();
            }

            $comissaoDataFechamentoProximoMesObj->data_inicio = Carbon::parse($comissaoDataFechamentoObj->data_fim)->addDay()->format('Y-m-d');
    
            $comissaoDataFechamentoProximoMesObj->save();
        }
        else{
            $comissaoDataFechamentoProximoMesObj = ComissaoDataFechamento::where('periodo', $mes->copy()->addMonthNoOverflow()->format('m/Y'))->first();

            if(!empty($comissaoDataFechamentoProximoMesObj)){
                $comissaoDataFechamentoProximoMesObj->data_inicio = Carbon::parse($comissaoDataFechamentoObj->data_fim)->addDay()->format('Y-m-d');
                $comissaoDataFechamentoProximoMesObj->updated_by = Auth::id();
                $comissaoDataFechamentoProximoMesObj->save();
            }
        }

        $return = [
			'status' => 'success',
			'message' => 'Salvo com sucesso!',
			'error' => '',
			'response' => ''
        ];
        
        return response()->json($return);
    }

    public function editar(ComissaoDataFechamentoEditarRequest $request){
        $fields = $request->only('id', 'data_inicio', 'data_fim');

        $comissaoDataFechamentoObj = ComissaoDataFechamento::find($fields['id']);

        $comissaoDataFechamentoObj->data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $comissaoDataFechamentoObj->data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');
        $comissaoDataFechamentoObj->updated_by = Auth::id();

        $comissaoDataFechamentoObj->save();

        $mes = Carbon::createFromFormat('m/Y', $comissaoDataFechamentoObj->periodo);

        if($comissaoDataFechamentoObj->data_inicio->format('Y-m-d') != $mes->copy()->subMonth()->format('Y-m-26')){
            $comissaoDataFechamentoMesAnteriorObj = ComissaoDataFechamento::where('periodo', $mes->copy()->subMonth()->format('m/Y'))->first();

            if(empty($comissaoDataFechamentoMesAnteriorObj)){
                $comissaoDataFechamentoMesAnteriorObj = new ComissaoDataFechamento;
                $comissaoDataFechamentoMesAnteriorObj->periodo =  $mes->copy()->subMonth()->format('m/Y');
                $comissaoDataFechamentoMesAnteriorObj->data_inicio = $mes->copy()->subMonth()->format('Y-m-26');
                $comissaoDataFechamentoMesAnteriorObj->created_by = Auth::id();
            }
            else{
                $comissaoDataFechamentoMesAnteriorObj->updated_by = Auth::id();
            }
            
            $comissaoDataFechamentoMesAnteriorObj->data_fim = Carbon::parse($comissaoDataFechamentoObj->data_inicio)->subDay()->format('Y-m-d');
    
            $comissaoDataFechamentoMesAnteriorObj->save();
        }
        else{
            $comissaoDataFechamentoMesAnteriorObj = ComissaoDataFechamento::where('periodo', $mes->copy()->subMonth()->format('m/Y'))->first();

            if(!empty($comissaoDataFechamentoMesAnteriorObj)){
                $comissaoDataFechamentoMesAnteriorObj->data_fim = Carbon::parse($comissaoDataFechamentoObj->data_inicio)->subDay()->format('Y-m-d');
                $comissaoDataFechamentoMesAnteriorObj->updated_by = Auth::id();
                $comissaoDataFechamentoMesAnteriorObj->save();
            }
        }

        if($comissaoDataFechamentoObj->data_fim->format('Y-m-d') != $mes->format('Y-m-25')){
            $comissaoDataFechamentoProximoMesObj = ComissaoDataFechamento::where('periodo', $mes->copy()->addMonthNoOverflow()->format('m/Y'))->first();

            if(empty($comissaoDataFechamentoProximoMesObj)){
                $comissaoDataFechamentoProximoMesObj = new ComissaoDataFechamento;
                $comissaoDataFechamentoProximoMesObj->periodo =  $mes->copy()->addMonthNoOverflow()->format('m/Y');
                $comissaoDataFechamentoProximoMesObj->data_fim = $mes->copy()->addMonthNoOverflow()->format('Y-m-25');
                $comissaoDataFechamentoProximoMesObj->created_by = Auth::id();
            }
            else{
                $comissaoDataFechamentoProximoMesObj->updated_by = Auth::id();
            }

            $comissaoDataFechamentoProximoMesObj->data_inicio = Carbon::parse($comissaoDataFechamentoObj->data_fim)->addDay()->format('Y-m-d');
    
            $comissaoDataFechamentoProximoMesObj->save();
        }
        else{
            $comissaoDataFechamentoProximoMesObj = ComissaoDataFechamento::where('periodo', $mes->copy()->addMonthNoOverflow()->format('m/Y'))->first();

            if(!empty($comissaoDataFechamentoProximoMesObj)){
                $comissaoDataFechamentoProximoMesObj->data_inicio = Carbon::parse($comissaoDataFechamentoObj->data_fim)->addDay()->format('Y-m-d');
                $comissaoDataFechamentoProximoMesObj->updated_by = Auth::id();
                $comissaoDataFechamentoProximoMesObj->save();
            }
        }

        $return = [
			'status' => 'success',
			'message' => 'Salvo com sucesso!',
			'error' => '',
			'response' => ''
        ];
        
        return response()->json($return);
    }

    public function excluir(Request $request){

        $fields = $request->only('id');

        $comissaoDataFechamentoObj = ComissaoDataFechamento::find($fields['id']);
        $comissaoDataFechamentoObj->deleted_by = Auth::id();
        $comissaoDataFechamentoObj->save();
        $comissaoDataFechamentoObj->delete();

        $periodo = Carbon::createFromFormat('m/Y', $comissaoDataFechamentoObj->periodo);

        $comissaoDataFechamentoMesAnteriorObj = ComissaoDataFechamento::
            where('periodo', $periodo->copy()->subMonth()->format('m/Y'))
            ->first();

        $comissaoDataFechamentoMesPosteriorObj = ComissaoDataFechamento::
            where('periodo', $periodo->copy()->addMonthNoOverFlow()->format('m/Y'))
            ->first();

        if(!empty($comissaoDataFechamentoMesAnteriorObj)){
            $comissaoDataFechamentoMesAnteriorObj->data_fim = $periodo->copy()->subMonth()->format('Y-m-25');
            $comissaoDataFechamentoMesAnteriorObj->updated_by = Auth::id();
            $comissaoDataFechamentoMesAnteriorObj->save();
        }

        if(!empty($comissaoDataFechamentoMesPosteriorObj)){
            $comissaoDataFechamentoMesPosteriorObj->data_inicio = $periodo->format('Y-m-26');
            $comissaoDataFechamentoMesPosteriorObj->updated_by = Auth::id();
            $comissaoDataFechamentoMesPosteriorObj->save();
        }

        $return = [
			'status' => 'success',
			'message' => 'Excluído com sucesso!',
			'error' => '',
			'response' => ''
        ];
    }

}
