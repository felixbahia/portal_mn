<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\EmailController;

use App\ClienteNasajon;
use App\ClientePrePago;
use App\TitulosEmAbertoNasajonPortal;
use Carbon\Carbon;
use App\User;
use App\TipoUsuario;
use App\VendedorNasajon;
use App\NasajonEstabelecimento;
use App\DevolucaoNota;

use Hash;
use Illuminate\Support\Facades\DB;

class EmailRagaziController extends Controller
{
    public function enviar(){
        $ClientePrePagoObj = ClientePrePago::all()->pluck('cpf_cnpj');
        $ClienteNasajonObj = ClienteNasajon::whereIn('cpf_cnpj', $ClientePrePagoObj)->get()->pluck('id');
		$ClienteNasajonObjInterCompany = ClienteNasajon::select('id')
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
			->get()->pluck('id');
        $NasajonEstabelecimento = NasajonEstabelecimento::whereNotNull('id_pessoa')->get()->pluck('id_pessoa');

        $DevolucaoNotaObj = DevolucaoNota::select('nota_id');
        $DevolucaoNotaObj->where('devolucao_nota_status_id', '!=', 7);
        $DevolucaoNotas = $DevolucaoNotaObj->get()->pluck('nota_id');

        $TitulosEmAbertoNasajonObj = TitulosEmAbertoNasajonPortal::whereDoesntHave('vendedorTitulo', function($query){
            $query->where('vendedor_codigo', '=', '998');
        })
        ->where('vencimento', '<', Carbon::now()->subdays(31)->setTime(0,0,0))
        ->whereNotIn('nota_id', $DevolucaoNotas->unique())
        ->whereNotIn('id_cliente', $ClienteNasajonObj->unique())
        ->whereNotIn('id_cliente', $ClienteNasajonObjInterCompany->unique())
        ->whereNotIn('id_cliente', $NasajonEstabelecimento->unique())
        ->where('banco_nome', '!=', 'Carteira')
        ->whereNotNull('banco_nome')
        ->orderBy('vencimento', 'desc')
        ->get();

        $clientes = [];
        $vendedor = VendedorNasajon::where('codigo', '998')->first();
        $TitulosEmAbertoNasajonObj->each(function($titulo) use (&$clientes, $vendedor){
            if(!in_array($titulo->id_cliente, $clientes)){
                $clientes[] = $titulo->id_cliente;
            }
            
            $sql_vendedor = "select * from integracoes.api_tituloreceber_vendedornovo(
                '{$titulo->titulo_id}',
                '{$vendedor->id}',
                100,
                '1'
            );";
            try{
                $titulo_novo = DB::connection('nasajon')->select($sql_vendedor);
            }catch(\Exception $e){
                Log::error($e->getMessage());
                Log::error($sql_vendedor);
            }
        });

        $ClienteNasajonObj = ClienteNasajon::where('email', '!=', '')
        ->where('email', '!=', '0')
        ->where('email', '!=', '.')
        ->whereIn('id', $clientes)
        ->orderBy('email')
        ->distinct('email')
        ->get();
        $ClienteNasajonObj->each(function($cliente){
            $EmailObj = new EmailController();
            $email_send = [$cliente->email];
            $variaveis = [];
    
            $returnEmail = $EmailObj->sendEmailToken('00', "email_ragazi", $email_send, $variaveis);
        });
    }

    public function enviar_cliente(){
        $clientes = ClienteNasajon::query()
            ->where(function($query){
                $query
                ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '06311274%'")
                ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') not ILIKE '05075884%'");
            })
            ->whereHas('notasVenda', function($query){
                $data = Carbon::now()->setTime(0,0,0)->subYears(1);
                $query->where('emissao', '>', $data);
            })
            ->where('email', '!=', '')
            ->where('email', '!=', '0')
            ->where('email', '!=', '.')
            ->where('bloqueado', 'false')
            ->orderBy('email')
			->get();
        $tipoUsuarioObj = TipoUsuario::where('nome', 'Cliente')->first();

        $clientes->each(function($cliente) use ($tipoUsuarioObj){
            $username = preg_replace('/[_\-\/\.]/','', $cliente->cpf_cnpj);
            if(User::where('username', $username)->doesntExist()){
                $user = new User;
                $user->username = $username;
                $user->name = $cliente->nome;
                $user->email = $cliente->email;
                $user->setor = 'Cliente';

                $user->tipo_usuario_id = $tipoUsuarioObj->id;

                $alfanumericos = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $alfanumericos = str_shuffle($alfanumericos);
                $senha = substr($alfanumericos, 0, 8);

                $user->password = Hash::make($senha);

                if($user->save()){

                    $user->assignRole('Cliente');

                    $emailControllerObj = new EmailController;
        
                    $mail_result = $emailControllerObj->sendEmailToken('01', 'criacao_usuario_tecidos', [$cliente->email], ['nome' => $cliente->nome, 'usuario' => $user->username, 'senha' => $senha]);
                }
            }
        });
    }

    public function enviarRegraOutubro2022(){
        $ClientePrePagoObj = ClientePrePago::all()->pluck('cpf_cnpj');
        $ClienteNasajonObj = ClienteNasajon::whereIn('cpf_cnpj', $ClientePrePagoObj)->get()->pluck('id');
		$ClienteNasajonObjInterCompany = ClienteNasajon::select('id')
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
			->get()->pluck('id');
        $NasajonEstabelecimento = NasajonEstabelecimento::whereNotNull('id_pessoa')->get()->pluck('id_pessoa');

        $DevolucaoNotaObj = DevolucaoNota::select('nota_id');
        $DevolucaoNotaObj->where('devolucao_nota_status_id', '!=', 7);
        $DevolucaoNotas = $DevolucaoNotaObj->get()->pluck('nota_id');

        $TitulosEmAbertoNasajonObj = TitulosEmAbertoNasajonPortal::whereDoesntHave('vendedorTitulo', function($query){
            $query->where('vendedor_codigo', '=', '998');
        })
        ->where('vencimento', '<', Carbon::now()->subdays(31)->setTime(0,0,0))
        ->whereNotIn('nota_id', $DevolucaoNotas->unique())
        ->whereNotIn('id_cliente', $ClienteNasajonObj->unique())
        ->whereNotIn('id_cliente', $ClienteNasajonObjInterCompany->unique())
        ->whereNotIn('id_cliente', $NasajonEstabelecimento->unique())
        ->where('titulo_emissao', '>=', '2022-10-01')
        ->where('banco_nome', '!=', 'Carteira')
        ->whereNotNull('banco_nome')
        ->orderBy('vencimento', 'desc')
        ->get();

        $clientes = [];
        $vendedor = VendedorNasajon::where('codigo', '998')->first();
        $TitulosEmAbertoNasajonObj->each(function($titulo) use (&$clientes, $vendedor){
            if(!in_array($titulo->id_cliente, $clientes)){
                $clientes[] = $titulo->id_cliente;
            }
            
            $sql_vendedor = "select * from integracoes.api_tituloreceber_vendedornovo(
                '{$titulo->titulo_id}',
                '{$vendedor->id}',
                100,
                '1'
            );";
            try{
                $titulo_novo = DB::connection('nasajon')->select($sql_vendedor);
            }catch(\Exception $e){
                Log::error($e->getMessage());
                Log::error($sql_vendedor);
            }
        });

        $ClienteNasajonObj = ClienteNasajon::where('email', '!=', '')
        ->where('email', '!=', '0')
        ->where('email', '!=', '.')
        ->whereIn('id', $clientes)
        ->orderBy('email')
        ->distinct('email')
        ->get();
        $ClienteNasajonObj->each(function($cliente){
            $EmailObj = new EmailController();
            $email_send = [$cliente->email];
            $variaveis = [];
    
            $returnEmail = $EmailObj->sendEmailToken('00', "email_ragazi", $email_send, $variaveis);
        });
    }
}
