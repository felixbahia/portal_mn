<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

use App\FornecedorContabil;
use App\BancoContabil;
use App\Lancamentos;
use App\Lotes;
use App\LotesLancamentos;

use \PDO;
date_default_timezone_set('America/Sao_Paulo');
class LancamentosExportController extends Controller
{
    public function __construct() {
    }
    /**
     * Inserção de lançamentos na tabela
     * É verificado os dados do mes anterior até do dia atual
     *
     * @return void
     */
    public function insertTables($empresa_padrao = false){
        $date_ini = "2018-04-03";
        $date_end = "2018-04-06";
        try{
            $conn = DB::connection('srv_prologos')->getPdo();
        } catch (\Exception $e) {
            dd($e);
        }

        $empresa_where = "";
        if(!is_bool($empresa_padrao) && $empresa_padrao !== false){
            $empresa_where = "AND ESTABEL = {$empresa_padrao}";
        }
        
        try{
            $dados = $conn->prepare("SELECT DTBAIXA, NPARC, CODCAD, VALBAIXA, CODBCO, ESTABEL, NCHEQUE, NDOC, JUROS, DESCONTO FROM dbo.TBPGP2 WHERE DTBAIXA BETWEEN '{$date_ini} 00:00:00' AND '{$date_end} 23:59:59' AND MOTIVO = 1 {$empresa_where} ORDER BY DTBAIXA DESC");
            $dados->execute();
            $lancamentos_temp = $dados->fetchAll(PDO::FETCH_ASSOC);
            unset($dados);
        } catch (\Exception $e) {
            dd($e);
        }

        try{
            $dados = $conn->prepare("SELECT CODBCODIF, NOME FROM dbo.TBBCO1;");
            $dados->execute();
            $bancos_temp = $dados->fetchAll(PDO::FETCH_ASSOC);
            unset($dados);
        } catch (\Exception $e) {
            dd($e);
        }

        try{
            $dados = $conn->prepare("SELECT CODCAD, NOME FROM dbo.TBCAD1");
            $dados->execute();
            $cadastros_temp = $dados->fetchAll(PDO::FETCH_ASSOC);
            unset($dados);
        } catch (\Exception $e) {
            dd($e);
        }
        $conn = null;

        $banco_contactb_temp = BancoContabil::all()->toArray();
        $fornecedor_contactb_temp = FornecedorContabil::all()->toArray();
        $lancamentos_banco = Lancamentos::all()->toArray();

        $lancamentos_tratados = [];

        $lancamentos = [];
        $bancos = [];
        $cadastros = [];
        $banco_contactb = [];
        $fornecedor_contactb = [];

        $string_hitorico_padao_cheque = "Cheque n :ncheque - :nome_banco";
        $string_hitorico_padao_eletronico = "Pgto Nf-e n :ndoc - :nome_cad";
        $string_hitorico_padao_juros = "Pgto juros Nf :ndoc - :nome_cad";
        $string_hitorico_padao_desconto = "Desconto obtido sob Nf :ndoc";

        foreach ($bancos_temp as $key => $value) {
            $bancos[intval($value["CODBCODIF"])] = [
                "codbco" => $value['CODBCODIF'],
                "nome" => $value['NOME'],
            ];
        }
        unset($bancos_temp);
        foreach ($lancamentos_temp as $key => $value) {
            $existe = 0;
            $edit = 0;
            $id = 0;
            foreach ($lancamentos_banco as $k => $v) {
                if(
                    intval($v['codcad']) === intval($value['CODCAD']) && 
                    intval($v['ndoc']) === intval($value['NDOC']) && 
                    intval($v['nparc']) === intval($value['NPARC']) && 
                    intval($v['estabel']) === intval($value['ESTABEL']) && 
                    strtotime($v['datahora_baixa']) === strtotime($value['DTBAIXA']) && 
                    $v['valor_baixa'] === $value['VALBAIXA']
                ){
                    if(intval($v["status"]) <> 0){
                        $edit = 1;
                        $id = $v["id"];
                    }else{
                        $existe = 1;
                    }
                }
            }
            if($existe === 1){
                unset($lancamentos_temp[$key]);
                continue;
            }
            $lancamentos[] = [
                "data_hora_baixa" => $value['DTBAIXA'],
                "codcad" => $value['CODCAD'],
                "valor_baixa" => $value['VALBAIXA'],
                "codbco" => intval($value['CODBCO']),
                "estabel" => $value['ESTABEL'],
                "ncheque" => $value['NCHEQUE'],
                "ndoc" => $value['NDOC'],
                "nparc" => $value['NPARC'],
                "juros" => $value['JUROS'],
                "desconto" => $value['DESCONTO'],
                "edit" => $edit,
                "id" => $id
            ];
            unset($lancamentos_temp[$key]);
        }
        foreach ($cadastros_temp as $key => $value) {
            $cadastros[intval($value["CODCAD"])] = [
                "codcad" => $value['CODCAD'],
                "nome" => $value['NOME']
            ];
            unset($cadastros_temp[$key]);
        }
        foreach ($banco_contactb_temp as $value) {
            $key = intval($value["estabel"])."-".intval($value["codbco"]);
            $banco_contactb[$key] = $value['contactb'];
            unset($banco_contactb_temp[$key]);
        }
        foreach ($fornecedor_contactb_temp as $value) {
            $key = intval($value["estabel"])."-".intval($value["codcad"]);
            $fornecedor_contactb[$key] = $value['contactb'];
            unset($fornecedor_contactb_temp[$key]);
        }

        if(!is_bool($empresa_padrao) && $empresa_padrao !== false){
            $lancamentosPorEmpresa = [
                "".intval($empresa_padrao).""=>[]
            ];
        }else{
            $lancamentosPorEmpresa = [
                "0"=>[],
                "1"=>[],
                "2"=>[],
                "3"=>[],
                "4"=>[],
                "5"=>[]
            ];
        }

        foreach($lancamentos as $key => $value){
            $lancamentosPorEmpresa[intval($value["estabel"])][] = $value;
        }
        unset($lancamentos);

        foreach($lancamentosPorEmpresa as $estabel => $lancamentos){
            $pathFile = "lotes/".date("Y")."/".date("m")."/".str_pad($estabel, 2, "0", STR_PAD_LEFT)."-".date("dmyHi").".txt";
            $LotesObj = new Lotes;
            $LotesObj->nome_gerado = $pathFile;
            $LotesObj->estabel = $estabel;
            $LotesObj->status = "0";
            $LotesObj->save();

            $count_success = 0;
            $count_error = 0;
            $temp = [];

            $lancamentos_gerados = [];
            $error = [];
            foreach($lancamentos as $key => $value){
                $contactb_debito = 0;
                $contactb_credito = 0;
                $historico = "";
                $status = 0;

                $key_contactb_debito = intval($value["estabel"])."-".intval($value["codcad"]);
                if(isset($fornecedor_contactb[$key_contactb_debito])){
                    $contactb_debito = $fornecedor_contactb[$key_contactb_debito];
                } else {
                    $status = 1;
                }

                $key_contactb_credito = intval($value["estabel"])."-".intval($value["codbco"]);
                if(isset($banco_contactb[$key_contactb_credito])){
                    $contactb_credito = $banco_contactb[$key_contactb_credito];
                }else{
                    $status = 1;
                }

                if(strpos($value["ncheque"], "D") !== false){
                    $cheque = str_replace("D","",$value["ncheque"]);
                    $historico = $string_hitorico_padao_cheque;
                    $historico = str_replace(":ncheque", $cheque, $historico);
                    $historico = str_replace(":nome_banco", $bancos[intval($value["codbco"])]['nome'], $historico);
                } else {
                    $historico = $string_hitorico_padao_eletronico;
                    $historico = str_replace(":ndoc", $value["ndoc"], $historico);
                    $historico = str_replace(":nome_cad", $cadastros[intval($value["codcad"])]['nome'], $historico);
                }
                if($value["edit"] === 0){
                    if($status === 0){
                        $lancamentos_gerados[] = [
                            'datahora_baixa' => $value["data_hora_baixa"],
                            'valor_baixa' => $value['valor_baixa'],
                            'contactb_debito' => $contactb_debito,
                            'contactb_credito' => $contactb_credito,
                            'historico' => $historico,
                            'codcad' => $value["codcad"],
                            'ncheque' => $value["ncheque"],
                            'ndoc' => $value["ndoc"],
                            'estabel' => $value["estabel"],
                            'codbco' => intval($value["codbco"]),
                        ];
                        $count_success++;
                    }else{
                        $count_error++;
                    }
                    $temp[] = [
                        "lotes_id" => $LotesObj->id,
                        "lancamentos_id" => "new",
                        "status" => $status
                    ];
                    
                    $LancamentosObj = new Lancamentos;
                    $LancamentosObj->datahora_baixa = $value["data_hora_baixa"];
                    $LancamentosObj->valor_baixa = number_format(($value['valor_baixa']),2,",",".");
                    $LancamentosObj->contactb_debito = $contactb_debito;
                    $LancamentosObj->contactb_credito = $contactb_credito;
                    $LancamentosObj->historico = $historico;
                    $LancamentosObj->status = $status;
                    $LancamentosObj->codcad = $value["codcad"];
                    $LancamentosObj->ncheque = $value["ncheque"];
                    $LancamentosObj->ndoc = $value["ndoc"];
                    $LancamentosObj->estabel = $value["estabel"];
                    $LancamentosObj->codbco = $value["codbco"];
                    $LancamentosObj->nparc = $value["nparc"];
                    $LancamentosObj->juros = $value["juros"];
                    $LancamentosObj->desconto = $value["desconto"];
                    $LancamentosObj->save();

                    $LotesLancamentosObj = new LotesLancamentos;
                    $LotesLancamentosObj->lotes_id = $LotesObj->id;
                    $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                    $LotesLancamentosObj->status = $status;
                    $LotesLancamentosObj->save();

                }else{
                    if($status === 0){
                        $lancamentos_gerados[] = [
                            'datahora_baixa' => $value["data_hora_baixa"],
                            'valor_baixa' => $value['valor_baixa'],
                            'contactb_debito' => $contactb_debito,
                            'contactb_credito' => $contactb_credito,
                            'historico' => $historico,
                            'codcad' => $value["codcad"],
                            'ncheque' => $value["ncheque"],
                            'ndoc' => $value["ndoc"],
                            'estabel' => $value["estabel"],
                            'codbco' => intval($value["codbco"]),
                        ];
                        $count_success++;
                    }else{
                        $count_error++;
                    }
                    $temp[] = [
                        "lotes_id" => $LotesObj->id,
                        "lancamentos_id" => $value["id"],
                        "status" => $status
                    ];
                    $LancamentosObj = Lancamentos::find($value["id"]);
                    $LancamentosObj->contactb_debito = $contactb_debito;
                    $LancamentosObj->contactb_credito = $contactb_credito;
                    $LancamentosObj->valor_baixa = number_format(($value['valor_baixa']),2,",",".");
                    $LancamentosObj->historico = $historico;
                    $LancamentosObj->status = $status;
                    $LancamentosObj->save();

                    $LotesLancamentosObj = new LotesLancamentos;
                    $LotesLancamentosObj->lotes_id = $LotesObj->id;
                    $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                    $LotesLancamentosObj->status = $status;
                    $LotesLancamentosObj->save();
                }

                if(floatval($value["juros"]) > 0){
                    $historico = $string_hitorico_padao_juros;
                    $historico = str_replace(":ndoc", $value["ndoc"], $historico);
                    $historico = str_replace(":nome_cad", $cadastros[intval($value["codcad"])]['nome'], $historico);

                    if($status === 0){
                        $lancamentos_gerados[] = [
                            'datahora_baixa' => $value["data_hora_baixa"],
                            'valor_baixa' => $value['juros'],
                            'contactb_debito' => "7111001",
                            'contactb_credito' => $contactb_credito,
                            'historico' => $historico,
                            'codcad' => $value["codcad"],
                            'ncheque' => $value["ncheque"],
                            'ndoc' => $value["ndoc"],
                            'estabel' => $value["estabel"],
                            'codbco' => intval($value["codbco"]),
                        ];
                        $count_success++;
                    }else{
                        $count_error++;
                    }
                    $temp[] = [
                        "lotes_id" => $LotesObj->id,
                        "lancamentos_id" => "new",
                        "status" => $status
                    ];
                    
                    $LancamentosObj = new Lancamentos;
                    $LancamentosObj->datahora_baixa = $value["data_hora_baixa"];
                    $LancamentosObj->valor_baixa = number_format(floatval($value["juros"]),2,",",".");
                    $LancamentosObj->contactb_debito = "7111001";
                    $LancamentosObj->contactb_credito = $contactb_credito;
                    $LancamentosObj->historico = $historico;
                    $LancamentosObj->status = $status;
                    $LancamentosObj->codcad = $value["codcad"];
                    $LancamentosObj->ncheque = $value["ncheque"];
                    $LancamentosObj->ndoc = $value["ndoc"];
                    $LancamentosObj->estabel = $value["estabel"];
                    $LancamentosObj->codbco = $value["codbco"];
                    $LancamentosObj->nparc = $value["nparc"];
                    $LancamentosObj->juros = $value["juros"];
                    $LancamentosObj->desconto = $value["desconto"];
                    $LancamentosObj->save();

                    $LotesLancamentosObj = new LotesLancamentos;
                    $LotesLancamentosObj->lotes_id = $LotesObj->id;
                    $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                    $LotesLancamentosObj->status = $status;
                    $LotesLancamentosObj->save();
                }

                if(floatval($value["desconto"]) > 0){
                    $historico = $string_hitorico_padao_desconto;
                    $historico = str_replace(":ndoc", $value["ndoc"], $historico);

                    if($status === 0){
                        $lancamentos_gerados[] = [
                            'datahora_baixa' => $value["data_hora_baixa"],
                            'valor_baixa' => $value['desconto'],
                            'contactb_debito' => '1111001',
                            'contactb_credito' => "6111001",
                            'historico' => $historico,
                            'codcad' => $value["codcad"],
                            'ncheque' => $value["ncheque"],
                            'ndoc' => $value["ndoc"],
                            'estabel' => $value["estabel"],
                            'codbco' => intval($value["codbco"]),
                        ];
                        $count_success++;
                    }else{
                        $count_error++;
                    }
                    $temp[] = [
                        "lotes_id" => $LotesObj->id,
                        "lancamentos_id" => "new",
                        "status" => $status
                    ];
                    
                    $LancamentosObj = new Lancamentos;
                    $LancamentosObj->datahora_baixa = $value["data_hora_baixa"];
                    $LancamentosObj->valor_baixa = number_format(floatval($value["desconto"]),2,",",".");
                    $LancamentosObj->contactb_debito = "1111001";
                    $LancamentosObj->contactb_credito = "6111001";
                    $LancamentosObj->historico = $historico;
                    $LancamentosObj->status = $status;
                    $LancamentosObj->codcad = $value["codcad"];
                    $LancamentosObj->ncheque = $value["ncheque"];
                    $LancamentosObj->ndoc = $value["ndoc"];
                    $LancamentosObj->estabel = $value["estabel"];
                    $LancamentosObj->codbco = $value["codbco"];
                    $LancamentosObj->nparc = $value["nparc"];
                    $LancamentosObj->juros = $value["juros"];
                    $LancamentosObj->desconto = $value["desconto"];
                    $LancamentosObj->save();

                    $LotesLancamentosObj = new LotesLancamentos;
                    $LotesLancamentosObj->lotes_id = $LotesObj->id;
                    $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                    $LotesLancamentosObj->status = $status;
                    $LotesLancamentosObj->save();
                }

            }
            //dd($temp);
            $this->createFileExport($lancamentos_gerados, $pathFile);
            
            $LotesObj->qtd_gerados = $count_success;
            $LotesObj->qtd_erros = $count_error;
            $LotesObj->status = ($count_error > 0) ? 1 : 0;
            $LotesObj->save();
        }

        /*
        $pathFile = "lotes/".date("Y")."/".date("m")."/".date("dmyHi").".txt";
        $LotesObj = new Lotes;
        $LotesObj->nome_gerado = $pathFile;
        $LotesObj->save();

        $count_success = 0;
        $count_error = 0;
        $temp = [];

        foreach($lancamentos as $key => $value){
            $contactb_debito = 0;
            $contactb_credito = 0;
            $historico = "";
            $status = 0;

            if(isset($fornecedor_contactb[intval($value["codcad"])])){
                $contactb_debito = $fornecedor_contactb[intval($value["codcad"])];
            } else {
                $status = 1;
            }

            $key_contactb_credito = intval($value["estabel"])."-".intval($value["codbco"]);
            if(isset($banco_contactb[$key_contactb_credito])){
                $contactb_credito = $banco_contactb[$key_contactb_credito];
            }else{
                $status = 1;
            }

            if(strpos($value["ncheque"], "D") !== false){
                $cheque = str_replace("D","",$value["ncheque"]);
                $historico = $string_hitorico_padao_cheque;
                $historico = str_replace(":ncheque", $cheque, $historico);
                $historico = str_replace(":nome_banco", $bancos[intval($value["codbco"])]['nome'], $historico);
            } else {
                $historico = $string_hitorico_padao_eletronico;
                $historico = str_replace(":ndoc", $value["ndoc"], $historico);
                $historico = str_replace(":nome_cad", $cadastros[intval($value["codcad"])]['nome'], $historico);
            }
            if($value["edit"] === 0){
                if($status === 0){
                    $lancamentos_gerados[] = [
                        'datahora_baixa' => $value["data_hora_baixa"],
                        'valor_baixa' => $value['valor_baixa'],
                        'contactb_debito' => $contactb_debito,
                        'contactb_credito' => $contactb_credito,
                        'historico' => $historico,
                        'codcad' => $value["codcad"],
                        'ncheque' => $value["ncheque"],
                        'ndoc' => $value["ndoc"],
                        'estabel' => $value["estabel"],
                        'codbco' => intval($value["codbco"]),
                    ];
                    $count_success++;
                }else{
                    $count_error++;
                }
                $temp[] = [
                    "lotes_id" => $LotesObj->id,
                    "lancamentos_id" => "new",
                    "status" => $status
                ];
                
                $LancamentosObj = new Lancamentos;
                $LancamentosObj->datahora_baixa = $value["data_hora_baixa"];
                $LancamentosObj->valor_baixa = $value['valor_baixa'];
                $LancamentosObj->contactb_debito = $contactb_debito;
                $LancamentosObj->contactb_credito = $contactb_credito;
                $LancamentosObj->historico = $historico;
                $LancamentosObj->status = $status;
                $LancamentosObj->codcad = $value["codcad"];
                $LancamentosObj->ncheque = $value["ncheque"];
                $LancamentosObj->ndoc = $value["ndoc"];
                $LancamentosObj->estabel = $value["estabel"];
                $LancamentosObj->codbco = $value["codbco"];
                $LancamentosObj->nparc = $value["nparc"];
                $LancamentosObj->save();

                $LotesLancamentosObj = new LotesLancamentos;
                $LotesLancamentosObj->lotes_id = $LotesObj->id;
                $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                $LotesLancamentosObj->status = $status;
                $LotesLancamentosObj->save();

            }else{
                if($status === 0){
                    $lancamentos_gerados[] = [
                        'datahora_baixa' => $value["data_hora_baixa"],
                        'valor_baixa' => $value['valor_baixa'],
                        'contactb_debito' => $contactb_debito,
                        'contactb_credito' => $contactb_credito,
                        'historico' => $historico,
                        'codcad' => $value["codcad"],
                        'ncheque' => $value["ncheque"],
                        'ndoc' => $value["ndoc"],
                        'estabel' => $value["estabel"],
                        'codbco' => intval($value["codbco"]),
                    ];
                    $count_success++;
                }else{
                    $count_error++;
                }
                $temp[] = [
                    "lotes_id" => $LotesObj->id,
                    "lancamentos_id" => $value["id"],
                    "status" => $status
                ];
                $LancamentosObj = Lancamentos::find($value["id"]);
                $LancamentosObj->contactb_debito = $contactb_debito;
                $LancamentosObj->contactb_credito = $contactb_credito;
                $LancamentosObj->historico = $historico;
                $LancamentosObj->status = $status;
                $LancamentosObj->save();

                $LotesLancamentosObj = new LotesLancamentos;
                $LotesLancamentosObj->lotes_id = $LotesObj->id;
                $LotesLancamentosObj->lancamentos_id = $LancamentosObj->id;
                $LotesLancamentosObj->status = $status;
                $LotesLancamentosObj->save();
            }
        }
        //dd($temp);
        $this->createFileExport($lancamentos_gerados, $pathFile);
        
        $LotesObj->qtd_gerados = $count_success;
        $LotesObj->qtd_erros = $count_error;
        $LotesObj->status = ($count_error > 0) ? 1 : 0;
        $LotesObj->save();*/
    }
    
    /**
     * Criando arquivo de lote
     *
     * @param array $lancamentos
     * @param string $name_file
     * @return void
     */
    private function createFileExport($lancamentos, $name_file){
        $lines = "";
        $numero_lancamento = 1;
        foreach ($lancamentos as $key => $value) {
            $data_hora_lancamento = $value['datahora_baixa'];
            $conta_debido = $value['contactb_debito'];
            $conta_credito = $value['contactb_credito'];
            $centrocusto = "";
            $historico = trim($value['historico']);
            $valor = number_format($value['valor_baixa'],2,",","");
            $IndLCTOFCONT = 0;
            if(strlen($historico) > 50){
                $historico = substr($historico, 0, 50);
            }
            $line = "";
            $line .= str_pad(date("dm",strtotime($data_hora_lancamento)), 4, "0", STR_PAD_LEFT);
            //$line .= str_pad($numero_lancamento, 6, "0", STR_PAD_LEFT);
            //$line .= str_pad(date("dmYHi",strtotime($data_hora_lancamento)), 12, "0", STR_PAD_LEFT);
            $line .= str_pad($conta_debido, 16, " ", STR_PAD_LEFT);
            $line .= str_pad($conta_credito, 16, " ", STR_PAD_LEFT);
            $line .= str_pad($centrocusto, 10, " ", STR_PAD_LEFT);
            $line .= str_pad($historico, 50, " ");
            $line .= str_pad($valor, 14, " ", STR_PAD_LEFT);
            $line .= $IndLCTOFCONT;

            $lines .= $line."\n\r";
            $numero_lancamento++;
        }
        Storage::put($name_file, $lines);
    }

}
