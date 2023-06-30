<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\FornecedorContabil;
use App\BancoContabil;
use App\Lancamentos;
use App\Lotes;
use App\LotesLancamentos;
use App\Http\Controllers\LancamentosExportController;

use App\Http\Controllers\PrologosController;

use \PDO;

use Auth;

class LotesLancamentosController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
    * Display a listing of the resource.
    *
    * @return \Illuminate\Http\Response
    */
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\LotesLancamentos") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\LotesLancamentos');
        return view('programs.lotes_lancamentos.index');
    }
    
    /**
     * Filtro da listagem de lotes
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request){
        $return = [];
        $data_ini = $request->date_ini;
        $data_end = $request->date_end;
        $empresa = $request->empresa;
        if(!empty($data_ini)){
            $data_ini = explode("/", $data_ini);
            $data_ini = $data_ini[2]."-".$data_ini[1]."-".$data_ini[0];
            $data_ini = date("Y-m-d 00:00:00", strtotime($data_ini));
        }else{
            $data_ini = date("Y-m-d 00:00:00", strtotime("-1 month"));
        }
        if(!empty($data_end)){
            $data_end = explode("/", $data_end);
            $data_end = $data_end[2]."-".$data_end[1]."-".$data_end[0];
            $data_end = date("Y-m-d 23:59:59", strtotime($data_end));
        }else{
            $data_end = date("Y-m-d 23:59:59");
        }
        $where = [];
        if(strlen($empresa) > 0){
            $where[] = ["estabel", intval($empresa)];
        }
        /*
        $empresa_padrao = Auth::user()->empresa_padrao_id;
        $lotes = Lotes::whereBetween("created_at", [$data_ini, $data_end])->where("estabel", $empresa_padrao)->get();*/
        $lotes = Lotes::whereBetween("created_at", [$data_ini, $data_end])->where($where)->orderBy("created_at")->get();
        $empresa = returnEmpresasPrologusView();
        foreach($lotes as $key => $value){
            preg_match("/\d+\.\S{3}$/m", $value["nome_gerado"], $nome);
            $nome = implode($nome);
            $return[$key]["empresa"] = $empresa[intval($value["estabel"])];
            $return[$key]["nome"] = "<a href='".route("lotes_lancamentos.download_lote", ["id"=>$value["id"]])."' target='_blanck'>".$nome."</a>";
            $return[$key]["status"] = $this->replaceStatus($value["status"]);
            $return[$key]["qtd_gerados"] = "<a href=\"#\" id='bt-view_gerados' class='bt-view_gerados bt-view bt-view-text' data-status='0' data-route='".route("lotes_lancamentos.show_tables", ["staus"=>0, "id"=> $value["id"]])."'></a><span class=\"text-bt-view\">".intval($value["qtd_gerados"])."</span>";
            $return[$key]["qtd_erros"] = "<a href=\"#\" id='bt-view_erros' class='bt-view_erros bt-view bt-view-text' data-status='1' data-route='".route("lotes_lancamentos.show_tables", ["staus"=>1, "id"=> $value["id"]])."'></a><span class=\"text-bt-view\">".intval($value["qtd_erros"])."</span>";
            $return[$key]["btn_gerar"] = "<a href=\"#\" id='bt-gerar' class='bt-gerar' data-route='".route("lotes_lancamentos.regerar_file", ["id"=> $value["id"]])."'></a>";
        }
        return response()->json($return);
    }

    /**
     * Transforma o id de Status em texto
     *
     * @param int $status
     * @return array
     */
    private function replaceStatus($status){
        switch ($status) {
            case '0':
                $status = "Nenhuma divergência!";
            break;
            case '1':
                $status = "Contem divergência(s)!";
            break;
            case '2':
                $status = "Divergência(s) resolvidas!";
            break;
        }
        return $status;
    }

    /**
     * Mostra a tabela de registros do lote
     *
     * @param int $status
     * @param int $idLote
     * @return \Illuminate\Http\Response
     */
    public function showTables($status, $idLote){
        $dados = $this->getDadosTable($idLote, $status);
        return view('programs.lotes_lancamentos.lancamentos')->with("dados", $dados)->with("lote", $idLote)->with("status", $status);
    }
    

    /**
     * Altera lançamentos apartir da tabela de view
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function alterLancamentos(Request $request){
        $fields = $request->only("lancamentos_id", "lotes_id", "credito", "debito");

        $validatedData = $request->validate([
            'lancamentos_id'    => 'required|exists:lancamentos,id',
            'lotes_id'          => 'required|exists:lotes,id',
            'credito'           => 'required_without:debito|numeric|digits_between:1,18',
            'debito'            => 'required_without:credito|numeric|digits_between:1,18',
        ]);

        $LotesLancamentosObj = LotesLancamentos::where("lancamentos_id", $request["lancamentos_id"])->where("lotes_id", $request["lotes_id"])->firstOrFail();

        $lancamento = Lancamentos::find($request["lancamentos_id"]);
        $save_inputs = [ "status" => 2 ];
        $message = "";
        if(!empty($request["credito"])){
            if(BancoContabil::where("codbco", intval($lancamento->codbco))->where("estabel", intval($lancamento->estabel))->first() !== null){
                $message = "Banco e Estábelecimento já vinculádo a uma conta de crédito!\nVinculado conta de crédito vinculáda!";
                $BancoContabilTemp = BancoContabil::where("codbco", intval($lancamento->codbco))->where("estabel", intval($lancamento->estabel))->first();
                $save_inputs["contactb_debito"] = $BancoContabilTemp->contactb;
            }
            $BancoContabilObj = new BancoContabil;
            $BancoContabilObj->estabel = $lancamento->estabel;
            $BancoContabilObj->codbco = $lancamento->codbco;
            $BancoContabilObj->contactb = $request["credito"];
            $BancoContabilObj->save();

            $save_inputs["contactb_credito"] = $request["credito"];

            $lancamentosUpdate = Lancamentos::where([["estabel", $lancamento->estabel], ["codbco", $lancamento->codbco]])->where("id", "<>", $lancamento->id)->get();
            foreach ($lancamentosUpdate as $key => $value) {
                LotesLancamentos::where("lancamentos_id", $value->id)->where("lotes_id", $request["lotes_id"])->update(["status"=>2]);
                $value->update($save_inputs);
            }

        }
        if(!empty($request["debito"])){
            if(FornecedorContabil::where("codcad", $lancamento->codcad)->where("estabel", intval($lancamento->estabel))->first() !== null){
                $message = "Fornecedor já vinculádo a uma conta de débito!\nVinculado conta de débito vinculáda!";
                $fornecedorTemp = FornecedorContabil::where("codcad", $lancamento->codcad)->first();
                $save_inputs["contactb_debito"] = $fornecedorTemp->contactb;
            }else{
                $FornecedorContabilObj = new FornecedorContabil;
                $FornecedorContabilObj->codcad = $lancamento->codcad;
                $FornecedorContabilObj->estabel = $lancamento->estabel;
                $FornecedorContabilObj->contactb = $request["debito"];
                $FornecedorContabilObj->save();
                $save_inputs["contactb_debito"] = $request["debito"];
            }

            $lancamentosUpdate = Lancamentos::where("codcad", $lancamento->codcad)->where("id", "<>", $lancamento->id)->get();
            foreach ($lancamentosUpdate as $key => $value) {
                LotesLancamentos::where("lancamentos_id", $value->id)->where("lotes_id", $request["lotes_id"])->update(["status"=>2]);
                $value->update($save_inputs);
            }
        }
        $lancamento->fill($save_inputs)->save();
        $LotesLancamentosObj = LotesLancamentos::where("lancamentos_id", $request["lancamentos_id"])->where("lotes_id", $request["lotes_id"])->update(["status"=>2]);
        $return = [
            "lancamentos" => $lancamento,
            "message" => $message,
        ];
        return response()->json($return);

    }
    /**
     * Retorna dados tratados
     *
     * @param int $lote
     * @param int $status
     * @return array
     */
    public function getDadosTable($lote, $status){
        $where = [];
        if(intval($status) === 0){
            $LotesLancamentos = LotesLancamentos::where([["lotes_id", $lote], ["status", $status]])->get()->toArray();
        }elseif(intval($status) === 1){
            $LotesLancamentos = LotesLancamentos::where([["lotes_id", $lote]])->Where(function ($query) {
                $query->where('status', 1)
                      ->orWhere("status", 2);
            })->get()->toArray();
        }
        $dados = [];
        $id_lancamentos = [];
        $id_lotes = [];
        foreach($LotesLancamentos as $value){
            $id_lancamentos[] = $value["lancamentos_id"];
            $id_lotes[$value["lancamentos_id"]] = ["lotes_id"=>$value["lotes_id"],"status"=>$value["status"]];
        }
        $dados = Lancamentos::whereIn("id", $id_lancamentos)->get()->toArray();
        $PrologosControllerObj = new PrologosController();
        $bancos = $PrologosControllerObj->getBancos();
        foreach ($dados as $key => $value) {
            $dados[$key]["nome_banco"] = $bancos[intval($value["codbco"])]["nome"];
            $dados[$key]["agencia_banco"] = $bancos[intval($value["codbco"])]["agencia"];
            $dados[$key]["conta_banco"] = $bancos[intval($value["codbco"])]["numero_conta"];
            $dados[$key]["codbco"] =  str_pad(intval($value["codbco"]), 4, "0", STR_PAD_LEFT);
            $dados[$key]["lote_id"] = $id_lotes[$value["id"]]["lotes_id"];
            $dados[$key]["status"] = $id_lotes[$value["id"]]["status"];
            $dados[$key]["status_texto"] = $this->replaceStatus($id_lotes[$value["id"]]["status"]);
            $dados[$key]["nome_fornecedor"] = $PrologosControllerObj->getDadosCodcad($value["codcad"])["NOME"];
        }
        foreach($dados as $key => $value){
            foreach ($value as $k => $v) {
                if(in_array($k, ["datahora_baixa", "valor_baixa", "contactb_credito", "contactb_debito", "id", "status", "lote_id"])){
                    continue;
                }
                $v_title = $v;
                if(strlen(trim($v)) > 15){
                    $v = str_pad(substr($v, 0, 12), 15, ".");
                }
                $dados[$key][$k] = "<span data-toggle=\"tooltip\" title=\"{$v_title}\">{$v}</span>";
            }
        }
        return $dados;
    }

    /**
     * Retorna json de dados da tabela
     *
     * @param int $status
     * @param int $lote
     * @return \Illuminate\Http\Response
     */
    public function getTable($lote, $status){
        $dados = $this->getDadosTable($lote, $status);
        
        foreach($dados as $key => $value){
            $dados[$key]["datahora_baixa"] = date("d/m/Y", strtotime($value["datahora_baixa"]));
            if(empty($value["contactb_credito"])){
                $dados[$key]["contactb_credito"] = '<input type="text" name="contactb_credito" id="contactb_credito" data-row="'.$value["id"].'" data-lote_id="'.$value["lote_id"].'" value="'.$value["contactb_credito"].'" />';
            }
            if(empty($value["contactb_debito"])){
                $dados[$key]["contactb_debito"] = '<input type="text" name="contactb_debito" id="contactb_debito" data-row="'.$value["id"].'" data-lote_id="'.$value["lote_id"].'" value="'.$value["contactb_debito"].'" />';
            }
            if((empty($value["contactb_debito"]) || empty($value["contactb_credito"])) && intval($value["status"]) === 1 ){
                $dados[$key]["bt_salvar"] = '<button name="bt-salvar" id="bt-salvar" class="bt-salvar">Salvar</button>';
            }else{
                $dados[$key]["bt_salvar"] = '';
            }
        }

        return response()->json($dados);
    }

    /**
     * Download do lote
     *
     * @param int $id
     * @return Storage
     */
    public function downloadLote($id){
        $lote = Lotes::findOrfail($id);
        return Storage::download($lote->nome_gerado);
    }

    /**
     * Regerar arquivo
     *
     * @param int $id
     * @return void
     */
    public function regerarLote($id){
        $Lote = Lotes::findOrfail($id);
        $LotesLancamentosObj = LotesLancamentos::where("lotes_id", $id)->get();
        $empresa_padrao = Auth::user()->empresa_padrao_id;
        foreach($LotesLancamentosObj as $lotes_lacamentos){
            LotesLancamentos::where("lancamentos_id", $lotes_lacamentos->lancamentos_id)->where("lotes_id", $lotes_lacamentos->lotes_id)->delete();
            $lancamento = Lancamentos::find($lotes_lacamentos->lancamentos_id)->delete();
        }
        Storage::delete($Lote->nome_gerado);
        $estabel = $Lote->estabel;
        $Lote->delete();
        $LancamentosExportControllerObj = new LancamentosExportController;
        $LancamentosExportControllerObj->insertTables(str_pad($estabel, 2, "0", STR_PAD_LEFT));
        return response()->json(["status"=>"success"]);
    }
}