<?php

namespace App\Http\Controllers;

use App\TitulosAbertosNasajonVirada;
use App\TitulosEmAbertoNasajonPortal;
use App\VendedorComissaoNota;
use App\ComissaoGerentesVendedores;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class TitulosAbertosNasajonViradaController extends Controller
{
    public function snapshotTitulosAbertos(){
        
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $agora = Carbon::Now();

        echo 'Processo de importação de títulos abertos para a virada: Iniciado ' . $agora->format('d/m/Y H:i:s') . PHP_EOL;

        $vendedorComissaoNotaObj = new VendedorComissaoNota;
        $titulosEmAbertoNasajonPortalObj = new TitulosEmAbertoNasajonPortal;

        $vendedorComissaoNotaTable = $vendedorComissaoNotaObj->getTable();
        $titulosEmAbertoNasajonPortalTable = $titulosEmAbertoNasajonPortalObj->getTable();

        $titulosAbertosObj = TitulosEmAbertoNasajonPortal::select(
                $titulosEmAbertoNasajonPortalTable . '.codigo', 
                $titulosEmAbertoNasajonPortalTable . '.cod_cliente', 
                $titulosEmAbertoNasajonPortalTable . '.nome_cliente', 
                $titulosEmAbertoNasajonPortalTable . '.numero', 
                $titulosEmAbertoNasajonPortalTable . '.parcela', 
                $titulosEmAbertoNasajonPortalTable . '.vencimento', 
                $titulosEmAbertoNasajonPortalTable . '.valor', 
                $titulosEmAbertoNasajonPortalTable . '.conta_agencia', 
                $titulosEmAbertoNasajonPortalTable . '.conta_agencia_digito', 
                $titulosEmAbertoNasajonPortalTable . '.conta_numero', 
                $titulosEmAbertoNasajonPortalTable . '.conta_digito', 
                $titulosEmAbertoNasajonPortalTable . '.id_estabelecimento', 
                $titulosEmAbertoNasajonPortalTable . '.titulo_emissao', 
                $titulosEmAbertoNasajonPortalTable . '.nota_numero', 
                $titulosEmAbertoNasajonPortalTable . '.nota_id', 
                $titulosEmAbertoNasajonPortalTable . '.nota_emissao', 
                $titulosEmAbertoNasajonPortalTable . '.banco_codigo', 
                $titulosEmAbertoNasajonPortalTable . '.banco_nome', 
                $titulosEmAbertoNasajonPortalTable . '.cnpj', 
                $titulosEmAbertoNasajonPortalTable . '.id_cliente', 
                $titulosEmAbertoNasajonPortalTable . '.titulo_de_terceiro', 
                $titulosEmAbertoNasajonPortalTable . '.documento_terceiro', 
                $titulosEmAbertoNasajonPortalTable . '.nome_terceiro', 
                $titulosEmAbertoNasajonPortalTable . '.saldotitulo', 
                $titulosEmAbertoNasajonPortalTable . '.nossonumero', 
                $titulosEmAbertoNasajonPortalTable . '.identificadorbancario', 
                $titulosEmAbertoNasajonPortalTable . '.vencimento_original', 
                $titulosEmAbertoNasajonPortalTable . '.tem_prorrogacao', 
                $titulosEmAbertoNasajonPortalTable . '.titulo_id', 
                $titulosEmAbertoNasajonPortalTable . '.multa', 
                $titulosEmAbertoNasajonPortalTable . '.desconto', 
                $titulosEmAbertoNasajonPortalTable . '.observacao', 
                $titulosEmAbertoNasajonPortalTable . '.enviado_para_banco', 
                $titulosEmAbertoNasajonPortalTable . '.juros', 
                $titulosEmAbertoNasajonPortalTable . '.datainiciomulta', 
                $titulosEmAbertoNasajonPortalTable . '.vendedor_codigo', 
                $titulosEmAbertoNasajonPortalTable . '.enviado_para_cartorio', 
                $titulosEmAbertoNasajonPortalTable . '.enviado_para_cartorio_data', 
                $titulosEmAbertoNasajonPortalTable . '.origem', 
                $titulosEmAbertoNasajonPortalTable . '.origem_texto',
                $vendedorComissaoNotaTable . '.percentual_comissao',
                DB::Raw('NOW() as created_at')
            )
            ->leftjoin($vendedorComissaoNotaTable, $vendedorComissaoNotaTable . '.id_docfis', '=', $titulosEmAbertoNasajonPortalTable . '.nota_id')
            ->where('vencimento', '>', Carbon::Now()->subDays(15)->format('Y-m-d'))
            ->get();

        TitulosAbertosNasajonVirada::truncate();

        $titulosAbertosObj->chunk(500)->each(function($chunk){
            TitulosAbertosNasajonVirada::insert($chunk->toArray());
        });

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function duplicaTitulosDeVendedoresParaGerentes(){

        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $agora = Carbon::Now();

        echo 'Processo de duplicação de títulos abertos para gerente: Iniciado ' . $agora->format('d/m/Y H:i:s') . PHP_EOL;

        $titulosAbertosVendedores = TitulosAbertosNasajonVirada::
            with('vendedor', 'vendedor.supervisor')
            ->whereHas('vendedor', function($query){
                $query->whereHas('supervisor');
            })
            ->get();

        $titulosAbertosVendedores->each(function($titulo){
            $tituloGerente = $titulo->replicate();
            $tituloGerente->vendedor_codigo = $titulo->vendedor->supervisor->codigo_representante;
            $tituloGerente->save();
        });

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;

    }

    public function atualizaComissoesTitulosAbertosVirada(){
        set_time_limit(300);
        ini_set('memory_limit','1024M');

        $agora = Carbon::Now();

        echo 'Processo de alterar comissões de títulos abertos para vendedores e gerentes: Iniciado ' . $agora->format('d/m/Y H:i:s') . PHP_EOL;

        $comissoesobj = ComissaoGerentesVendedores::all();

        $comissoesobj->each(function($comissao){

            $data = new Carbon($comissao->ano . '-' . $comissao->mes . '-01');

            TitulosAbertosNasajonVirada::whereBetween('nota_emissao', [$data->format('Y-m-d'), $data->endOfMonth()->format('Y-m-d')])
                ->where('vendedor_codigo', $comissao->codigo_representante)
                ->update(['percentual_comissao' => $comissao->porcentagem]);
        });

        echo 'Demorou ' . $agora->diff(Carbon::now())->format('%i minutos e %s segundos') . PHP_EOL;
    }
}
