<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;

use App\ContratoCliente;
use App\User;
use App\ClienteNasajon;

use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\EmailController;

class ContratoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    //private $caminho_contrato_cliente = '/contrato/cliente/CONTRATO_FORNECIMENTO.pdf';
    private $caminho_contrato_cliente = '/contrato/cliente/Contrato_fornecimento_digitalizado.pdf';
    private $caminho_salvar_contrato_cliente = '/contrato/cliente/';

    public function indexCliente(Request $request){
        return view('programs.contrato.cliente')->with(['caminho_contrato_cliente' => Storage::url($this->caminho_contrato_cliente)]);
    }

    public function contratoFornecimento(Request $request){
        return view('programs.contrato.fornecimento');
    }

    public function aceitoCliente(Request $request){
        //$caminho = $this->caminho_salvar_contrato_cliente.Auth::id().'/CONTRATO_FORNECIMENTO.pdf';
        $caminho = $this->caminho_salvar_contrato_cliente.Auth::id().'/Contrato_fornecimento_digitalizado.pdf';
        $caminho_contrato_cliente = $this->caminho_contrato_cliente;
        
        if(Storage::disk('public')->exists($caminho)){
            Storage::disk('public')->delete($caminho);
        }

        Storage::disk('public')->copy($caminho_contrato_cliente, $caminho);
        
        $clienteNasajonObj = ClienteNasajon::select()->where('cpf_cnpj', $this->formatCnpjCpf(Auth::user()->username))->where('bloqueado', 'false')->first();

        $contratoClienteObj = new ContratoCliente;
        $contratoClienteObj->user_id = Auth::id();
        $contratoClienteObj->ip = $request->ip();
        $contratoClienteObj->email = Auth::user()->email;
        $contratoClienteObj->caminho_contrato = $caminho;
        $contratoClienteObj->cpf_cnpj_cliente = $clienteNasajonObj->cpf_cnpj;
        $contratoClienteObj->created_by = Auth::id();
        $contratoClienteObj->save();

        $userObj = User::find(Auth::id());
        $userObj->contrato = true;
        $userObj->save();

        $EmailObj = new EmailController();
		$email_send = [Auth::user()->email];
        $variaveis = [
            'data' => date('d/m/Y')
        ];
        $returnEmail = $EmailObj->sendEmailToken('00', "contrato_fornecimento", $email_send, $variaveis, ["public".$caminho => ['as' => 'Contrato_fornecimento_digitalizado.pdf', 'mime' => 'application/pdf']], []);

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'rota' => route('home'),
            ]
        ];

        return response()->json($response);
    }

    public function recusadoCliente(Request $request){
        $this->middleware('guest')->except('logout');

    }

    public function formatCnpjCpf($value){
        $cnpj_cpf = preg_replace("/\D/", '', $value);

        if (strlen($cnpj_cpf) === 11) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
        } 

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
}
