<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\RegrasSeparacaoEmail;
use App\RegrasSeparacao;
use App\Email;

use App\Http\Requests\RegrasSeparacaoRequest;

class RegrasSeparacaoController extends Controller
{
    //
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\RegrasSeparacao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\RegrasSeparacao');

        $estabelecimentos = returnEmpresasNasajonView();

        return view("programs.regras_separacao.index")->with(['estabelecimentos' => $estabelecimentos]);

    }

    public function filter(Request $request){

    	$fields = $request->only(['estabelecimento']);

    	$query = RegrasSeparacao::with(['emails'])
    		->select('estabelecimento');

        if($fields['estabelecimento']){
    		$query->where('estabelecimento', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT));
        }

    	$query_result = $query->groupBy('estabelecimento')
    		->get();

        $result = [];
        
        $estabelecimentos = returnEmpresasNasajonView();

        foreach ($query_result as $value) {
            $result[] = [
                'estabelecimento_nome' => $estabelecimentos[intval($value['estabelecimento'])],
                'estabelecimento' => $value['estabelecimento'],
                'emails' => isset($value->emails->emails_send)?implode(", ", explode(";", $value->emails->emails_send)):''
            ];
            
        }

    	return response()->json($result);

    }

    public function modal_adicionar(){

        $estabelecimentos = returnEmpresasNasajonView();

        $estabelecimentos_cadastrados = RegrasSeparacao::with(['emails'])
            ->select('estabelecimento')
            ->groupBy('estabelecimento')
            ->get();

        foreach ($estabelecimentos_cadastrados as $value) {
            unset($estabelecimentos[$value->estabelecimento]);
        }

		return view("programs.regras_separacao.adicionar")->with('estabelecimentos', $estabelecimentos);
    }

    public function modal_editar(Request $request){

        $fields = $request->only(['estabelecimento']);

        $regras = RegrasSeparacao::where('estabelecimento', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT))->orderBy('quantidade_pecas')->get();
        $linha = [];
        foreach ($regras as $value) {
            $linha[] = [
                'quantidade_pecas' => $value->quantidade_pecas,
                'tempo' => $value->tempo
            ];
            
        }
        $EmailObj = Email::where('estabelecimento', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT))->where('token_email', 'alerta_separacao_regra')->first();
        $emails = implode(', ', explode(";", $EmailObj->emails_send));

        $estabelecimentos = returnEmpresasNasajonView();

        $dados = [
            'estabelecimento' => $fields['estabelecimento'],
            'estabelecimento_nome' => $estabelecimentos[intval($fields['estabelecimento'])],
            'linha' => $linha,
            'emails' => $emails
        ];

		return view("programs.regras_separacao.editar")->with('dados', $dados);

    }

    public function adicionar(RegrasSeparacaoRequest $request){

        $fields = $request->only(['estabelecimento', 'quantidade_pecas', 'tempo', 'emails']);

        foreach ($fields['quantidade_pecas'] as $key => $value) {
            if (!empty($fields['quantidade_pecas'][$key]) && !empty($fields['tempo'][$key])){
                $RegrasSeparacaoObj = new RegrasSeparacao;
                $RegrasSeparacaoObj->estabelecimento = str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT);
                $RegrasSeparacaoObj->quantidade_pecas = $fields['quantidade_pecas'][$key];
                $RegrasSeparacaoObj->tempo = $fields['tempo'][$key];
                $RegrasSeparacaoObj->created_by = Auth::id();
                $RegrasSeparacaoObj->save();
            }
        }

        if(!empty($fields['emails'])){
            $email_busca = [
                'estabelecimento' => str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT),
                'token_email' => 'alerta_separacao_regra'
            ];
            $EmailObj = Email::where($email_busca)->first();
            $EmailObj->emails_send = implode(";", explode(',', trim($fields['emails'])));
            $EmailObj->save();
        }
        
        $result = [
            'estabelecimento' => $fields['estabelecimento'],
            'emails' => $fields['emails']
        ];

        return response()->json($result);
    }

    public function editar(RegrasSeparacaoRequest $request){

        $fields = $request->only(['estabelecimento']);

        RegrasSeparacao::where('estabelecimento', str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT))->delete();

        return $this->adicionar($request);

    }

}