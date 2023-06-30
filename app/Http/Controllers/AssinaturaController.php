<?php

namespace App\Http\Controllers;

use App\Assinatura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AssinaturaValidaRequest;




class AssinaturaController extends Controller
{
    //
    public function index(Request $request)
    {
        if (Auth::user()->hasPermissionTo("programas App\Assinatura") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\Assinatura');
        $dados = [];
        $dados['NOME'] = Auth::user()->name;
        $dados['DEPARTAMENTO'] = Auth::user()->setor;
        $dados['EMAIL'] = Auth::user()->email;
        $dados['TELEFONE'] = '(11) 2095-9799';
        $dados['link_thunderbird'] = 'https://support.mozilla.org/pt-BR/kb/assinaturas#w_assinaturas-em-html';
        $dados['link_outlook'] = 'https://support.microsoft.com/pt-br/office/criar-e-adicionar-uma-assinatura-de-email-no-outlook-5ff9dcfd-d3f1-447b-b2e9-39f91b074ea3';

        return view('programs.assinatura.index')->with(["dados" => $dados]);;
    }

    public function atualizar(AssinaturaValidaRequest $request)
    {

        $campo = $request->only(['nome_edit', 'email_edit', 'setor_edit', 'telefone_edit']);

       
            $assinatura = Assinatura::select()->where('id', 1)->first();
            $assinatura_email = $assinatura->codigo_html;
            $saida = $this->parserBodyVarival($assinatura_email, $campo);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
    }

    private function parserBodyVarival($assinaturaObj, $variaveis_replace)
    {
        if (count($variaveis_replace) > 0) {
            $body = $assinaturaObj;
            foreach ($variaveis_replace as $key => $value) {
                $body = str_replace("[[{$key}]]", $value, $body);
            }
            $assinaturaObj = nl2br($body);
        }
        return $assinaturaObj;
    }
}
