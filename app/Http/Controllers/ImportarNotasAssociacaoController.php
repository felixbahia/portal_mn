<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Xml;

use App\NotasImportadasEntrada;
use App\NotasImportadasEntradaDevolucaoReferencia;

class ImportarNotasAssociacaoController extends Controller
{
    private $cfop_devolucao_array = ["1201","1202","1203","1204","1208","1209","1410","1411","1503","1504","1505","1506","1553","1660","1661","1662","1918","1919","2201","2202","2203","2204","2208","2209","2410","2411","2503","2504","2505","2506","2553","2660","2661","2662","2918","2919","3201","3202","3211","3503","3553","5201","5202","5208","5209","5210","5410","5411","5412","5413","5503","5553","5555","5556","5660","5661","5662","5918","5919","5921","6201","6202","6208","6209","6210","6410","6411","6412","6413","6503","6553","6555","6556","6660","6661","6662","6918","6919","6921","7201","7202","7210","7211","7553","7556"];
 
    public function importarNotasAssociadas(){
        
        $data_inicio = Carbon::createFromFormat('Y-m-d','2020-10-01')->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('Y-m-d','2020-10-31')->format('Y-m-d');

        $notas_importadas = NotasImportadasEntrada::where(
            function($query){
                $query->whereIn('documento_cfop',$this->cfop_devolucao_array)
                ->orWhereHas('itensNotas', function($query){
                    $query->whereIn('codigo_cfop',$this->cfop_devolucao_array);
                });
            }
        )
        ->whereBetween('data_emissao',[$data_inicio,$data_fim])
        ->get();

        foreach($notas_importadas as $notas){
            $xml = Xml::decode($notas->xml);

            $chaves = [];

            if(isset($xml['NFe']['infNFe']['ide']['NFref']['refNFe'])){
                $chaves[] = [
                    'chave' => $xml['NFe']['infNFe']['ide']['NFref']['refNFe']
                ];
            }else if(isset($xml['NFe']['infNFe']['ide']['NFref']['refNFP']['nNF'])){
                $chaves[] = [
                    'chave' => str_pad($xml['NFe']['infNFe']['ide']['NFref']['refNFP']['nNF'],9,'0', STR_PAD_LEFT)
                ];
            }else if(isset($xml['NFe']['infNFe']['ide']['NFref']['refNF']['nNF'])){
                $chaves[] = [
                    'chave' => str_pad($xml['NFe']['infNFe']['ide']['NFref']['refNF']['nNF'],9,'0', STR_PAD_LEFT)
                ];
            }else if(isset($xml['NFe']['infNFe']['ide']['NFref'][0])){
                foreach($xml['NFe']['infNFe']['ide']['NFref'][0] as $chave_xml){
                    $chaves[] = [
                        'chave' => $chave_xml
                    ];

                    break;
                }
            }

            $chaves_associacao = NotasImportadasEntradaDevolucaoReferencia::whereIn('chave_numero_nota',$chaves)->get()->toArray();
            
            if(empty($chaves_associacao)){
                foreach($chaves as $chave){
                    $referencia_devolucao = new NotasImportadasEntradaDevolucaoReferencia;
                    $referencia_devolucao->chave_numero_nota = $chave['chave'];
                    $referencia_devolucao->notas_importadas_entradas_id = $notas->id;
                    $referencia_devolucao->save();
                }
            }

        }

        return 'ok';
    }
}
