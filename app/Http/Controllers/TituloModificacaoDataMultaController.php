<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use App\TitulosEmAbertoNasajonPortal;
use App\TituloModificacaoDataMulta;
use App\TituloModificacaoContaRagazzi;
use App\User;
use App\ContasNasajon;
use App\TituloZeradoMultaJuro;
use App\FormaPagamentoNasajon;

class TituloModificacaoDataMultaController extends Controller
{
    public function atualizarDataMulta(){
        $data_atual = Carbon::now()->setTime(23,59,59);
        $data_dia_anterior = Carbon::now()->setTime(0,0,0)->subDays(7);

        $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajonPortal::select();
        $titulosEmAbertoNasajonPortalObj->where('titulo_emissao', '>=', '2022-07-01');
        $titulosEmAbertoNasajonPortalObj->whereBetween('titulo_emissao', [$data_dia_anterior, $data_atual]);
        $titulosEmAbertoNasajonPortalObj = $titulosEmAbertoNasajonPortalObj->get();

        foreach($titulosEmAbertoNasajonPortalObj as $titulo){
            $tituloModificacaoDataMultaObj = TituloModificacaoDataMulta::select()->where('titulo_uuid', $titulo->titulo_id)->first();
            if(empty($tituloModificacaoDataMultaObj)){
                $tituloModificacaoDataMultaObj = new TituloModificacaoDataMulta;
            }            
            $tituloModificacaoDataMultaObj->titulo_numero = $titulo->numero;
            $tituloModificacaoDataMultaObj->titulo_uuid = $titulo->titulo_id;
            $tituloModificacaoDataMultaObj->titulo_vencimento = $titulo->vencimento;
            $tituloModificacaoDataMultaObj->titulo_multa = $titulo->datainiciomulta;
            $tituloModificacaoDataMultaObj->save();

            $data_vencimento = Carbon::parse($titulo->vencimento)->setTime(0,0,0);
            $data_multa = $data_vencimento->addDays(11);

            $sql_mudanca_multa = "select * from financas.alterar_data_multa('".$titulo->titulo_id."', '".$data_multa->format('Y-m-d')."');";

            $estoque_em_terceiros = DB::connection('nasajon')->select($sql_mudanca_multa);
        }
    }

    public function atualizarConta(){
        $data_atual = Carbon::now()->setTime(23,59,59);
        $data_dia_anterior = Carbon::now()->setTime(0,0,0)->subDays(31);

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
        $conta_ragazzi_uuid = ContasNasajon::where('codigo','COBRANCA ADMINISTRATIVA')->first()->conta;
        $forma_pagamento_ragazzi_uuid = FormaPagamentoNasajon::where('codigo','005')->first()->formapagamento;

        $estabelecimentos = returnEmpresasNasajonView();

        $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajonPortal::select();
        //$titulosEmAbertoNasajonPortalObj->where('vencimento', '>=', '2022-10-01');
        $titulosEmAbertoNasajonPortalObj->where('vencimento', '<=', $data_dia_anterior);
        $titulosEmAbertoNasajonPortalObj->whereNotIn('codigo', ['25']);
        $titulosEmAbertoNasajonPortalObj->where(function($query){
            $query->where('banco_nome', 'not ilike', '%itau%');
        });
        $titulosEmAbertoNasajonPortalObj->with(['condicaoDePagamento' => function($query){
            $query->where('formapagamento_codigo', '000');
        }]);
        $titulosEmAbertoNasajonPortalObj = $titulosEmAbertoNasajonPortalObj->get();

        foreach($titulosEmAbertoNasajonPortalObj as $titulo){
            $tituloModificacaoContaRagazziObj = TituloModificacaoContaRagazzi::select()->where('titulo_uuid', $titulo->titulo_id)->first();
            if(empty($tituloModificacaoContaRagazziObj)){
                $tituloModificacaoContaRagazziObj = new TituloModificacaoContaRagazzi;
            }            
            $tituloModificacaoContaRagazziObj->titulo_numero = $titulo->numero;
            $tituloModificacaoContaRagazziObj->titulo_uuid = $titulo->titulo_id;
            $tituloModificacaoContaRagazziObj->titulo_vencimento = $titulo->vencimento;
            $tituloModificacaoContaRagazziObj->banco_codigo = $titulo->banco_codigo;
            $tituloModificacaoContaRagazziObj->banco_codigo_novo = 'COBRANCA ADMINISTRATIVA';
            $tituloModificacaoContaRagazziObj->save();

            $api_alterar_banco = DB::connection("nasajon")->select("SELECT * FROM integracoes.api_tituloreceber_alterarconta('".$titulo->titulo_id."','".$usuario_cadastro_uuid."','".$conta_ragazzi_uuid."',null,true,null,true,true,'".$forma_pagamento_ragazzi_uuid."')");
        }

        $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajonPortal::select();
        //$titulosEmAbertoNasajonPortalObj->where('vencimento', '>=', '2022-10-01');
        $titulosEmAbertoNasajonPortalObj->where('vencimento', '<=', $data_dia_anterior);
        $titulosEmAbertoNasajonPortalObj->whereNotIn('codigo', ['25']);
        $titulosEmAbertoNasajonPortalObj->where(function($query){
            $query->where('banco_nome', 'ilike', '%itau%');
        });
        $titulosEmAbertoNasajonPortalObj->with(['condicaoDePagamento' => function($query){
            $query->where('formapagamento_codigo', '000');
        }]);
        $titulosEmAbertoNasajonPortalObj = $titulosEmAbertoNasajonPortalObj->get();

        $dados_email = '';

        foreach($titulosEmAbertoNasajonPortalObj as $titulo){
            $tituloModificacaoContaRagazziObj = TituloModificacaoContaRagazzi::select()->where('titulo_uuid', $titulo->titulo_id)->first();
            if(empty($tituloModificacaoContaRagazziObj)){
                $tituloModificacaoContaRagazziObj = new TituloModificacaoContaRagazzi;
            }            
            $tituloModificacaoContaRagazziObj->titulo_numero = $titulo->numero;
            $tituloModificacaoContaRagazziObj->titulo_uuid = $titulo->titulo_id;
            $tituloModificacaoContaRagazziObj->titulo_vencimento = $titulo->vencimento;
            $tituloModificacaoContaRagazziObj->banco_codigo = $titulo->banco_codigo;
            $tituloModificacaoContaRagazziObj->banco_codigo_novo = 'COBRANCA ADMINISTRATIVA';
            $tituloModificacaoContaRagazziObj->save();

            $dados_email = $dados_email."Estabelecimento:".$estabelecimentos[intval($titulo->codigo)]."<br>Titulo : ".$titulo->numero."<br>Cliente : ".$titulo->nome_cliente." - ".$titulo->cnpj."<br>Emissão:".parserData($titulo->titulo_emissao)."<br>Vencimento:".parserData($titulo->vencimento)."<br>Conta Anterior:".$titulo->banco_nome."<br>Conta Anterior: JUDICIAL RAGAZZI <br><br>";

            $api_alterar_banco = DB::connection("nasajon")->select("SELECT * FROM integracoes.api_tituloreceber_alterarconta('".$titulo->titulo_id."','".$usuario_cadastro_uuid."','".$conta_ragazzi_uuid."',null,true,null,true,true,'".$forma_pagamento_ragazzi_uuid."')");
        }

        $this->emailContaRagazzi($dados_email);
    }

    public function emailContaRagazzi($titulos){
        try{
            $EmailObj = new EmailController();
            
            $variaveis = [
                'titulos' => $titulos,
            ];
            
            $EmailObj->sendEmailToken('00', 'alteracao_carteira_ragazzi', [], $variaveis);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [ 'mensagem' => $e],
                'response' => []
            ], 422);
		}
    }

    public function atualizarZeraMultaJuros(){
        $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajonPortal::select();
        $titulosEmAbertoNasajonPortalObj->whereNotIn('codigo', ['25']);
        $titulosEmAbertoNasajonPortalObj->where(function($query){
            $query->where('conta_numero', '13005536');
            $query->where('conta_digito', '9');
            $query->where('origem_texto', 'Renegociação');
            $query->where('multa', '>', 0);
            $query->where('titulo_emissao', '>=', '2022-08-12');
        });
        $titulosEmAbertoNasajonPortalObj = $titulosEmAbertoNasajonPortalObj->get();

        foreach($titulosEmAbertoNasajonPortalObj as $titulo){
            $tituloModificacaoDataMultaObj = TituloZeradoMultaJuro::select()->where('titulo_uuid', $titulo->titulo_id)->first();
            if(empty($tituloModificacaoDataMultaObj)){
                $tituloModificacaoDataMultaObj = new TituloZeradoMultaJuro;
            }            
            $tituloModificacaoDataMultaObj->titulo_numero = $titulo->numero;
            $tituloModificacaoDataMultaObj->titulo_uuid = $titulo->titulo_id;
            $tituloModificacaoDataMultaObj->titulo_vencimento = $titulo->vencimento;
            $tituloModificacaoDataMultaObj->percentualjurosdiario = $titulo->percentualjurosdiario;
            $tituloModificacaoDataMultaObj->juros = $titulo->juros;
            $tituloModificacaoDataMultaObj->multa = $titulo->multa;
            $tituloModificacaoDataMultaObj->zera_juros = false;
            $tituloModificacaoDataMultaObj->zera_multa = true;
            $tituloModificacaoDataMultaObj->save();

            $sql_zera_multa_juros = "select * from financas.zerar_multa('".$titulo->titulo_id."');";

            $estoque_em_terceiros = DB::connection('nasajon')->select($sql_zera_multa_juros);
        }
    }
}
