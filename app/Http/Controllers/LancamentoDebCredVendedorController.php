<?php

namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;

use App\Helpers;
use App\User;
use App\LancamentoDebCredVendedor;
use App\PedidoVenda;
use App\MotivoFinanceiro;
use App\ComissaoDataFechamento;

use App\Http\Controllers\ComissaoDuplicatasController;

use Illuminate\Http\Request;

use App\Http\Requests\LancamentoDebCredVendedorAdicionarRequest;
use App\Http\Requests\LancamentoDebCredVendedorEditarRequest;
use App\Http\Requests\LancamentoDebCredFilterRequest;
use App\Http\Requests\ComissaoDuplicatasFiltroRequest;

class LancamentoDebCredVendedorController extends Controller
{
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\LancamentoDebCredVendedor") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\LancamentoDebCredVendedor');

        $vendedores = $this->dadosVendedor();
        $tipos = $this->dadosTipo();

        return view('programs.lancamento_deb_cred_vendedor.index')->with(['vendedor' => $vendedores, 'tipos' => $tipos]);
    }

    public function modalAdicionar(){
        $vendedores = $this->dadosVendedor();
        $tipos = $this->dadosTipo();
        $motivos = $this->dadosMotivos();

        return view('programs.lancamento_deb_cred_vendedor.modal.adicionar')->with(['vendedor' => $vendedores, 'tipos' => $tipos, 'motivo' => $motivos]);
    }

    public function modalEditar(Request $request){
        $vendedores = $this->dadosVendedor();
        $tipos = $this->dadosTipo();
        $motivos = $this->dadosMotivos();

        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', '<', date('Y-m-d'))->orderBy('data_fim', 'desc')->first();

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

        $query = LancamentoDebCredVendedor::select('id', 'num_documento', 'data_lancamento', 'codigo_vendedor', 'codigo_motivo', 'tipo', 'valor');
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->get();
        foreach ($result as $key => $lancamento) {
            $data = new Carbon($lancamento->data_lancamento);
            $data = $data->format('d/m/Y');
            $dados = [
                'id' => encrypt($lancamento->id),
                'data' => $data,
                'vendedor' => $lancamento->codigo_vendedor,
                'motivo' => $lancamento->codigo_motivo,
                'tipo' => trim($lancamento->tipo),
                'valor' => parserValor($lancamento->valor),
                'documento' => $lancamento->num_documento,
                'editavel' => $comissaoDataFechamentoObj->data_fim->lte($lancamento->data_lancamento) || Auth::user()->tipo_usuario_id == 1 || Auth::user()->hasRole('ADM - Comissoes'),
            ];
        } 
        return view('programs.lancamento_deb_cred_vendedor.modal.editar')->with(['vendedor' => $vendedores, 'tipos' => $tipos, 'motivo' => $motivos, 'dados' => $dados]);
    }

    public function modalDeletar(Request $request){
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

        $query = LancamentoDebCredVendedor::select('id','num_documento', 'data_lancamento', 'codigo_vendedor', 'codigo_motivo', 'tipo', 'valor');
        $query->with(['vendedor' => function($query){
            $query->select('id', 'name');
        }]);
        $query->with(['motivofinanceiro' => function($query){
            $query->select('id', 'motivo');
        }]);
        $query->where('id', '=', $id);
        if(is_null($query)){
            return response()->json([
                'status' => 'error',
                'message' => 'Motivo não econtrado',
                'error' => '',
                'response' => ''
            ]);
        }
        $result = $query->get();
        foreach ($result as $key => $lancamento) {
            $data = new Carbon($lancamento->data_lancamento);
            $data = $data->format('d/m/Y');

            $vendedor = $lancamento->vendedor->name;

            $motivo = $lancamento->motivofinanceiro->motivo;
            $dados = [
                'id' => encrypt($lancamento->id),
                'data' => $data,
                'vendedor' => $vendedor,
                'motivo' => $motivo,
                'tipo' => trim($lancamento->tipo) == "C"?'Crédito':'Débito',
                'valor' => parserValor($lancamento->valor),
                'documento' => $lancamento->num_documento
            ];
        }
        return view('programs.lancamento_deb_cred_vendedor.modal.deletar')->with(['dados' => $dados]);
    }

    public function adicionar(LancamentoDebCredVendedorAdicionarRequest $request){
        $fields = $request->only('documento', 'data', 'vendedor', 'motivo', 'tipo', 'valor');
        $LancamentoDebCredVendedorObj = new LancamentoDebCredVendedor;
        $LancamentoDebCredVendedorObj->num_documento = $fields['documento'];
        $LancamentoDebCredVendedorObj->data_lancamento = $fields['data'];
        $LancamentoDebCredVendedorObj->codigo_vendedor = $fields['vendedor'];
        $LancamentoDebCredVendedorObj->codigo_motivo = $fields['motivo'];
        $LancamentoDebCredVendedorObj->tipo = $fields['tipo'];
        $LancamentoDebCredVendedorObj->valor = str_replace(',', '.', $fields['valor']); 
        $LancamentoDebCredVendedorObj->created_by = Auth::id();
        $LancamentoDebCredVendedorObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function editar(LancamentoDebCredVendedorEditarRequest $request){
        $fields = $request->only('id','documento', 'data', 'vendedor', 'motivo', 'tipo', 'valor');
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
        $LancamentoDebCredVendedorObj = LancamentoDebCredVendedor::find($id);
        $LancamentoDebCredVendedorObj->num_documento = $fields['documento'];
        $LancamentoDebCredVendedorObj->data_lancamento = $fields['data'];
        $LancamentoDebCredVendedorObj->codigo_vendedor = $fields['vendedor'];
        $LancamentoDebCredVendedorObj->codigo_motivo = $fields['motivo'];
        $LancamentoDebCredVendedorObj->tipo = $fields['tipo'];
        $LancamentoDebCredVendedorObj->valor = str_replace(',', '.', str_replace(".", "", $fields['valor'])); 
        $LancamentoDebCredVendedorObj->updated_by = Auth::id();
        $LancamentoDebCredVendedorObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function deletar(Request $request){
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
        
        $LancamentoDebCredVendedorObj = LancamentoDebCredVendedor::find($id);
        $LancamentoDebCredVendedorObj->deleted_by = Auth::id();
        $LancamentoDebCredVendedorObj->save();
        $LancamentoDebCredVendedorObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function filter(LancamentoDebCredFilterRequest $request){
        $fields = $request->only('vendedor', 'data');

        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', '<', date('Y-m-d'))->orderBy('data_fim', 'desc')->first();

        $query = LancamentoDebCredVendedor::select('id','num_documento', 'data_lancamento', 'codigo_vendedor', 'codigo_motivo', 'tipo', 'valor', 'parcela');
        $query->with(['vendedor' => function($query){
            $query->select('id', 'name');
        }]);
        $query->with(['motivofinanceiro' => function($query){
            $query->select('id', 'motivo');
        }]);
        if(!empty($fields['vendedor'])){
            $query->where('codigo_vendedor', '=', $fields['vendedor']);
        }
        if(!empty($fields['data'])){
            $query->whereBetween('data_lancamento', ['01/'.$fields['data'], $this->ultimoDiaMes('01/'.$fields['data'])]);
        }
        $result = $query->get();
        $reponse = [];
        foreach ($result as $key => $lancamento) {
            $data = new Carbon($lancamento->data_lancamento);
            $data = $data->format('d/m/Y');

            $vendedor = $lancamento->vendedor->name;

            $motivo = $lancamento->motivofinanceiro->motivo;

            $reponse[] = [
                'id' => encrypt($lancamento->id),
                'data' => $data,
                'vendedor' => $this->ajusteCampoTabela($vendedor),
                'motivo' => $this->ajusteCampoTabela($motivo),
                'tipo' => trim($lancamento->tipo) == 'C'?'Crédito':'Débito',
                'valor' => parserValor($lancamento->valor),
                'documento' => $lancamento->num_documento . (!empty($lancamento->parcela)? ' - Parcela ' . $lancamento->parcela: ''),
                'editavel' => $comissaoDataFechamentoObj->data_fim->lte($lancamento->data_lancamento) || Auth::user()->tipo_usuario_id == 1 || Auth::user()->hasRole('ADM - Comissoes')
            ];
        }
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $reponse
        ];
        return response()->json($retorno);
    }

    private function dadosVendedor(){
        $dropdown_usuarios = ['' => 'Selecione o vendedor'];
        // if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
        //     if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false){
        //         $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::id()))->orderBy('name', 'asc')->get();
        //     }
        //     else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
        //         $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->orderBy('name', 'asc')->get();
        //         $status_documento['cancelados'] = 'Cancelados';
        //     }
        //     else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
        //         $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))->orderBy('name', 'asc')->get();
        //     }
        //     foreach ($users as $user) {
        //         if(is_null($user->tipo_usuario)){
        //             continue;
        //         }
        //         if (!is_null($user->codigo_representante)){
        //             $dropdown_usuarios[$user->id] = strtoupper($user->name);
        //         }
        //     }
        // }

        $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->where('codigo_representante', '!=', '')->orderBy('codigo_representante', 'asc')->get();

        foreach ($users as $user) {
            if(is_null($user->tipo_usuario)){
                continue;
            }
            if (!is_null($user->codigo_representante)){
                $dropdown_usuarios[$user->id] = $user->codigo_representante . ' - ' . strtoupper($user->name);
            }
        }

        return $dropdown_usuarios;
    }

    private function dadosTipo(){
        $lancamentos = [
            '' => 'Selecione',
            'D' => 'Débito',
            'C' => 'Crédito'
        ];
        return $lancamentos;
    }

    private function ajusteCampoTabela($campo){
        $retorno = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='$campo'>$campo</div></div>";
        return $retorno;
    }

    private function dadosMotivos(){
        $query = MotivoFinanceiro::select('id', 'motivo');
        $result = $query->get();
        $retorno = ['' => 'Selecione'];
        foreach ($result as $key => $motivo) {
            $retorno [$motivo->id] = $motivo->motivo;
        }
        return $retorno;
    }

    function ultimoDiaMes($newData){
        list($newDia, $newMes, $newAno) = explode("/", $newData);
        return date("d/m/Y", mktime(0, 0, 0, $newMes+1, 0, $newAno));
     }

     public function lancarDebitoComissaoNegativa(){

        $inicio = Carbon::now();
        
        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', $inicio->format('Y-m-d'))->first();

        if(is_null($comissaoDataFechamentoObj) && $inicio->format('d') != '25'){
            echo "Não é o dia da virada da comissão." . PHP_EOL;
            return false;
        }

        $comissaoDuplicatasControllerObj = new ComissaoDuplicatasController;

        Auth::loginUsingId(1);
        
        $filtro = [
            'data' => Carbon::now()->format('m/Y'),
            'tipo' => ''
        ];

        $request = new ComissaoDuplicatasFiltroRequest($filtro);

        $resultado = collect($comissaoDuplicatasControllerObj->filter($request, true)['titulos']);

        $contador = 0;

        $resultado = $resultado->each(function ($comissao) use ($inicio, &$contador){
            if(parserNumber($comissao['valor_comissao']) < 0){

                $lancamentoDebCredVendedorObj = new LancamentoDebCredVendedor;

                $lancamentoDebCredVendedorObj->data_lancamento = $inicio->copy()->addDay()->format('Y-m-d');
                $lancamentoDebCredVendedorObj->num_documento = '';
                $lancamentoDebCredVendedorObj->codigo_vendedor = $comissao['representante_not_parse'];
                $lancamentoDebCredVendedorObj->codigo_motivo = 15;
                $lancamentoDebCredVendedorObj->tipo = 'D';
                $lancamentoDebCredVendedorObj->valor = parserNumber($comissao['valor_comissao'])*-1;
                $lancamentoDebCredVendedorObj->created_by = 1;

                $lancamentoDebCredVendedorObj->save();

                $contador++;
                
            }
        });

        echo "Descontos lançados: " . $contador . PHP_EOL . 'Demorou ' . $inicio->diffForHumans() . PHP_EOL;

     }
}
