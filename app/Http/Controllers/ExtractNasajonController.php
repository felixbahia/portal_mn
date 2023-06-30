<?php

namespace App\Http\Controllers;

use App\Cliente;
use App\CepEndereco;
use App\CepBairro;
use App\CepCidade;
use App\CepEstado;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExtractNasajonController extends Controller
{
    public function extractClientes(){
        ini_set('max_execution_time', 500);
        ini_set('memory_limit', '3024M');
        $ClientesObj = Cliente::select('*');
        $ClientesObj = $ClientesObj->get();
        $file = [];
        $header = ['CODIGO', 'CNPJ', 'RAZAOSOCIAL', 'NOMEFANTASIA', 'DATACADASTRO', 'EMAIL', 'SITE', 'TIPOSIMPLES', 'INSCRICAOMUNICIPAL', 'INSCRICAOESTADUAL', 'TIPOLOGRADOURO', 'LOGRADOURO', 'NUMERO', 'COMPLEMENTO', 'BAIRRO', 'CEP', 'UF', 'PAIS', 'MUNICIPIO', 'CODIGOMUNICIPIO', 'COBRANCA_TIPOLOGRADOURO', 'COBRANCA_LOGRADOURO', 'COBRANCA_NUMERO', 'COBRANCA_COMPLEMENTO', 'COBRANCA_BAIRRO', 'COBRANCA_CEP', 'COBRANCA_UF', 'COBRANCA_PAIS', 'COBRANCA_MUNICIPIO', 'COBRANCA_CODIGOMUNICIPIO', 'REFERENCIA', 'DDI1', 'DDD1', 'telefone1', 'RAMAL1', 'TIPO1', 'DDI2', 'DDD2', 'telefone2', 'RAMAL2', 'TIPO2', 'DDI3', 'DDD3', 'telefone3', 'RAMAL3', 'TIPO3', 'NOMECONTATO1', 'SOBRENOMECONTATO1', 'EMAILCONTATO1', 'SEXO1', 'NOMECONTATO2', 'SOBRENOMECONTATO2', 'EMAILCONTATO2', 'SEXO2', 'NOMECONTATO3', 'SOBRENOMECONTATO3', 'EMAILCONTATO3', 'SEXO3', 'LIMITEDECREDITO', 'CODVND'];
        $file[] = implode($header, ";");

        $regex_numero_logradouro = '/(\d+)((?1))/m';

        foreach ($ClientesObj as $key => $Cliente) {
        	$line = ['CODIGO' => '', 'CNPJ' => '', 'RAZAOSOCIAL' => '', 'NOMEFANTASIA' => '', 'DATACADASTRO' => '', 'EMAIL' => '', 'SITE' => '', 'TIPOSIMPLES' => '', 'INSCRICAOMUNICIPAL' => '', 'INSCRICAOESTADUAL' => '', 'TIPOLOGRADOURO' => '', 'LOGRADOURO' => '', 'NUMERO' => '', 'COMPLEMENTO' => '', 'BAIRRO' => '', 'CEP' => '', 'UF' => '', 'PAIS' => '', 'MUNICIPIO' => '', 'CODIGOMUNICIPIO' => '', 'COBRANCA_TIPOLOGRADOURO' => '', 'COBRANCA_LOGRADOURO' => '', 'COBRANCA_NUMERO' => '', 'COBRANCA_COMPLEMENTO' => '', 'COBRANCA_BAIRRO' => '', 'COBRANCA_CEP' => '', 'COBRANCA_UF' => '', 'COBRANCA_PAIS' => '', 'COBRANCA_MUNICIPIO' => '', 'COBRANCA_CODIGOMUNICIPIO' => '', 'REFERENCIA' => '', 'DDI1' => '', 'DDD1' => '', 'telefone1' => '', 'RAMAL1' => '', 'TIPO1' => '', 'DDI2' => '', 'DDD2' => '', 'telefone2' => '', 'RAMAL2' => '', 'TIPO2' => '', 'DDI3' => '', 'DDD3' => '', 'telefone3' => '', 'RAMAL3' => '', 'TIPO3' => '', 'NOMECONTATO1' => '', 'SOBRENOMECONTATO1' => '', 'EMAILCONTATO1' => '', 'SEXO1' => '', 'NOMECONTATO2' => '', 'SOBRENOMECONTATO2' => '', 'EMAILCONTATO2' => '', 'SEXO2' => '', 'NOMECONTATO3' => '', 'SOBRENOMECONTATO3' => '', 'EMAILCONTATO3' => '', 'SEXO3' => '', 'LIMITEDECREDITO' => '', 'CODVND' => ''];

            $TIPOLOGRADOURO = 'Rua';
            $CEP = $Cliente->CEP;
            if($Cliente->ESTADO !== 'EX'){
                $CEP = (int) str_replace("-", "", $CEP);
                $CepBusca = CepEndereco::with(['cidadeBusca'])->find($CEP);
                if(!is_null($CepBusca)){
                    $CepBusca = $CepBusca->toArray();
                }
                if(intval($CepBusca['cidade_busca']['id_municipio_subordinado']) > 0){
                    $cidadeBusca = (int) $CepBusca['cidade_busca']['id_municipio_subordinado'];
                    $CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
                    if(intval($CepCidadeObj['id_municipio_subordinado']) > 0){
                        $cidadeBusca = $cidadeBusca['id_municipio_subordinado'];
                        $CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
                    }
                    $CepBusca['cidade_busca'] = $CepCidadeObj["cidade"];
                    $CepBusca['cidade_busca'] = $CepCidadeObj["uf"];
                    $CepBusca['cidade_busca'] = $CepCidadeObj["cod_ibge"];
                }
                if(!empty($CepBusca['tipo_logradouro'])){
                    $TIPOLOGRADOURO = $CepBusca['tipo_logradouro'];
                }

                $logradouro = str_replace(";",':',utf8_encode($Cliente->ENDERECO));
                $logradouro = str_replace(['Rua:', 'Rua.', 'Rua', 'RUA.', 'RUA:', 'RUA', 'R.', 'R', 'R:'], '', $logradouro);
                $logradouro = str_replace(['Avenida', 'AVENIDA', 'AV.', 'Av.', 'AV:', 'Av:', 'AV', 'Av'], '', $logradouro);
                $logradouro = trim(str_replace('/(^-)|(^.)|(^:)/m', '', $logradouro));
                $logradouro = trim(str_replace('/(^-)|(^.)|(^:)/m', '', $logradouro));

                preg_match($regex_numero_logradouro, $logradouro, $matches);
                if(count($matches) > 0){
                    $numero = $matches[0];
                    $logradouro = trim(str_replace($numero, '', $logradouro));
                }else{
                    $numero = 'S/N';
                }
                $line['TIPOLOGRADOURO'] = $TIPOLOGRADOURO;

                $line['LOGRADOURO'] = $logradouro;
                $line['NUMERO'] = $numero;
                $line['BAIRRO'] = utf8_encode($Cliente->BAIRRO);

                $line['CEP'] = $Cliente->CEP;

                if(!empty($CepBusca)){
                    $line['MUNICIPIO'] = $CepBusca['cidade_busca']["cidade"];
                    $line['UF'] = $CepBusca['cidade_busca']["uf"];
                    $line['CODIGOMUNICIPIO'] = $CepBusca['cidade_busca']["cod_ibge"];
                }else{
                    $CepBusca = CepCidade::whereRaw("LOWER(TRANSLATE(cidade,'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ','aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC')) ilike '".tirarAcentos(strtolower(utf8_encode($Cliente->CIDADE)))."'")->whereRaw('LOWER(uf) = \''.strtolower($Cliente->ESTADO).'\'')->first();
                    if(!empty($CepBusca)){
                        $line['MUNICIPIO'] = $CepBusca->cidade;
                        $line['UF'] = $CepBusca->uf;
                        $line['CODIGOMUNICIPIO'] = $CepBusca->cod_ibge;
                    }else{
                        $line['MUNICIPIO'] = utf8_encode($Cliente->CIDADE);
                        $line['UF'] = $Cliente->ESTADO;
                        $line['CODIGOMUNICIPIO'] = '';
                    }
                }

                $line['PAIS'] = '1058';
            }else{
                $line['TIPOLOGRADOURO'] = '';
                $line['LOGRADOURO'] = str_replace(";",':',utf8_encode($Cliente->endereco));
                $line['NUMERO'] = 'S/N';
                $line['BAIRRO'] = '';

                $line['MUNICIPIO'] = '';
                $line['UF'] = 'EX';
                $line['CODIGOMUNICIPIO'] = '';

                $line['PAIS'] = '1058';
            }
            if(!empty(str_replace("-", "", str_replace("_", "", $Cliente->CEP_COBRANCA)))){

                $TIPOLOGRADOURO = 'Rua';
                $CEP = $Cliente->CEP_COBRANCA;
                $CEP = (int) str_replace("-", "", $CEP);
                $CepBusca = CepEndereco::with(['cidadeBusca'])->find($CEP);
                if(is_null($CepBusca)){
                    $line['COBRANCA_CEP'] = $line['CEP'];
                    $line['COBRANCA_TIPOLOGRADOURO'] = $line['TIPOLOGRADOURO'];
                    $line['COBRANCA_LOGRADOURO'] = $line['LOGRADOURO'];
                    $line['COBRANCA_NUMERO'] = $line['NUMERO'];
                    $line['COBRANCA_BAIRRO'] = $line['BAIRRO'];
                    $line['COBRANCA_MUNICIPIO'] = $line['MUNICIPIO'];
                    $line['COBRANCA_UF'] = $line['UF'];
                    $line['COBRANCA_CODIGOMUNICIPIO'] = $line['CODIGOMUNICIPIO'];
                }else{
                    $CepBusca = $CepBusca->toArray();
                    if(intval($CepBusca['cidade_busca']['id_municipio_subordinado']) > 0){
                        $cidadeBusca = (int) $CepBusca['cidade_busca']['id_municipio_subordinado'];
                        $CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
                        if(intval($CepCidadeObj['id_municipio_subordinado']) > 0){
                            $cidadeBusca = $cidadeBusca['id_municipio_subordinado'];
                            $CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
                        }
                        $CepBusca['cidade_busca'] = $CepCidadeObj["cidade"];
                        $CepBusca['cidade_busca'] = $CepCidadeObj["uf"];
                        $CepBusca['cidade_busca'] = $CepCidadeObj["cod_ibge"];
                    }
                    if(!empty($CepBusca['tipo_logradouro'])){
                        $TIPOLOGRADOURO = $CepBusca['tipo_logradouro'];
                    }

                    $logradouro = str_replace(";",':',utf8_encode($Cliente->endereco_COBRANCA));
                    $logradouro = str_replace(['Rua:', 'Rua.', 'Rua', 'RUA.', 'RUA:', 'RUA', 'R.', 'R', 'R:'], '', $logradouro);
                    $logradouro = str_replace(['Avenida', 'AVENIDA', 'AV.', 'Av.', 'AV:', 'Av:', 'AV', 'Av'], '', $logradouro);
                    $logradouro = trim(str_replace('/(^-)|(^.)|(^:)/m', '', $logradouro));
                    $logradouro = trim(str_replace('/(^-)|(^.)|(^:)/m', '', $logradouro));

                    preg_match($regex_numero_logradouro, $logradouro, $matches);
                    if(count($matches) > 0){
                        $numero = $matches[0];
                        $logradouro = trim(str_replace($numero, '', $logradouro));
                    }else{
                        $numero = 'S/N';
                    }
                    $line['COBRANCA_CEP'] = $Cliente->CEP_COBRANCA;
                    $line['COBRANCA_TIPOLOGRADOURO'] = $TIPOLOGRADOURO;
                    $line['COBRANCA_LOGRADOURO'] = $logradouro;
                    $line['COBRANCA_NUMERO'] = $numero;
                    $line['COBRANCA_BAIRRO'] = utf8_encode($Cliente->BAIRRO_COBRANCA);
                    if(!empty($CepBusca)){
                        $line['COBRANCA_MUNICIPIO'] = $CepBusca['cidade_busca']["cidade"];
                        $line['COBRANCA_UF'] = $CepBusca['cidade_busca']["uf"];
                        $line['COBRANCA_CODIGOMUNICIPIO'] = $CepBusca['cidade_busca']["cod_ibge"];
                    }else{
                        $CepBusca = CepCidade::whereRaw("LOWER(TRANSLATE(cidade,'áéíóúàèìòùãõâêîôôäëïöüçÁÉÍÓÚÀÈÌÒÙÃÕÂÊÎÔÛÄËÏÖÜÇ','aeiouaeiouaoaeiooaeioucAEIOUAEIOUAOAEIOOAEIOUC')) ilike '".tirarAcentos(strtolower(utf8_encode($Cliente->CIDADE)))."'")->whereRaw('LOWER(uf) = \''.strtolower($Cliente->ESTADO).'\'')->first();
                        if(!empty($CepBusca)){
                            $line['COBRANCA_MUNICIPIO'] = $CepBusca->cidade;
                            $line['COBRANCA_UF'] = $CepBusca->uf;
                            $line['COBRANCA_CODIGOMUNICIPIO'] = $CepBusca->cod_ibge;
                        }else{
                            $line['COBRANCA_CEP'] = $line['CEP'];
                            $line['COBRANCA_TIPOLOGRADOURO'] = $line['TIPOLOGRADOURO'];
                            $line['COBRANCA_LOGRADOURO'] = $line['LOGRADOURO'];
                            $line['COBRANCA_NUMERO'] = $line['NUMERO'];
                            $line['COBRANCA_BAIRRO'] = $line['BAIRRO'];
                            $line['COBRANCA_MUNICIPIO'] = $line['MUNICIPIO'];
                            $line['COBRANCA_UF'] = $line['UF'];
                            $line['COBRANCA_CODIGOMUNICIPIO'] = $line['CODIGOMUNICIPIO'];
                        }
                    }
                }
            }else{
                $line['COBRANCA_CEP'] = $line['CEP'];
                $line['COBRANCA_TIPOLOGRADOURO'] = $line['TIPOLOGRADOURO'];
                $line['COBRANCA_LOGRADOURO'] = $line['LOGRADOURO'];
                $line['COBRANCA_NUMERO'] = $line['NUMERO'];
                $line['COBRANCA_BAIRRO'] = $line['BAIRRO'];
                $line['COBRANCA_MUNICIPIO'] = $line['MUNICIPIO'];
                $line['COBRANCA_UF'] = $line['UF'];
                $line['COBRANCA_CODIGOMUNICIPIO'] = $line['CODIGOMUNICIPIO'];
            }

            $cnpj = $Cliente->CGC_CPF;
            $cnpj = str_replace(".", "", $cnpj);
            $cnpj = str_replace("-", "", $cnpj);
            $cnpj = str_replace("/", "", $cnpj);
            $cnpj = str_replace("_", "", $cnpj);

            $line['CODIGO'] = $Cliente->CODCAD;
        	$line['CNPJ'] = $cnpj;
        	$line['RAZAOSOCIAL'] = str_replace(";",':',utf8_encode($Cliente->NOME));
        	$line['NOMEFANTASIA'] = (!empty(trim($Cliente->GUERRA))) ? str_replace(";",':',utf8_encode($Cliente->GUERRA)) : str_replace(";",':',utf8_encode($Cliente->NOME)) ;
        	$line['DATACADASTRO'] = date('Y-m-d',strtotime($Cliente->DTDESDE));
        	$line['EMAIL'] = str_replace(";",':',utf8_encode($Cliente->EMAIL));
        	$line['TIPOSIMPLES'] = '0';
        	$line['INSCRICAOMUNICIPAL'] = $Cliente->IEST;
            $line['INSCRICAOESTADUAL'] = $Cliente->IMUN;
            $line['LIMITEDECREDITO'] = $Cliente->LIMCRED;
            $line['CODVND'] = $Cliente->CODVND;
            

            $telefone = strtolower($Cliente->TELEFONE);
        	//$line['telefone_temp'] = strtolower($Cliente->TELEFONE);
        	$telefone = trim(preg_replace('/[a-z]/', '', $telefone));

        	$ddd1 = '';
        	$telefone1 = $telefone;
        	$ddd2 = '';
        	$telefone2 = '';
        	$ddd3 = '';
        	$telefone3 = '';
        	if(
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\*([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{5})\-([0-9]{4}))\*([0-9]{4})/', $telefone)){
        		$telefone = explode("*", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
        	}elseif(
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/(([0-9]{3})\ ([0-9]{4})\-([0-9]{4}))/', $telefone) ||
        		preg_match('/(([0-9]{3})\-([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\ \/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/([0-9]{7})\/([0-9]{8})/', $telefone) ||
        		preg_match('/([0-9]{8})\/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/(([0-9]{3})\-([0-9]{4}))/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/\ ([0-9]{2})\ /', $telefone)
        	){
	        	$telefone = explode("/", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
        	} elseif (
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\ ([0-9]{4}))\-([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{8}))\-([0-9]{8})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-(([0-9]{4})\.([0-9]{4}))/', $telefone)
        	) {
        		$telefone = explode("-", $telefone);
        		if(count($telefone)>0){
        			if(strlen($telefone[0]) == 2){
        				$telefone1 = $telefone[0].'-'.$telefone[1];
						if(count($telefone) > 1){
	        				$telefone2 = $telefone[2];
	        			}
        			}else{
        				$telefone1 = $telefone[0];
	        			if(count($telefone) > 1){
	        				$telefone2 = $telefone[1];
	        			}
	        			if(count($telefone) > 2){
	        				$telefone3 = $telefone[2];
	        			}
	        		}
        		}
        		unset($telefone);
        	} elseif(
                preg_match('/(([0-9]{3})\ ([0-9]{4})\ ([0-9]{4}))\ (([0-9]{4})\ ([0-9]{4}))/', $telefone)
            ){
                preg_match_all('/(([0-9]{3})\ ([0-9]{4})\ ([0-9]{4}))\ (([0-9]{4})\ ([0-9]{4}))/', $telefone, $matches);
                $dd1 = $matches[2][0];
                $telefone1 = str_replace($dd1, '', $matches[1][0]);
                $dd2 = $matches[2][0];
                $telefone2 = $matches[5][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{3}\)\ [0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{3}\)\ [0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)\ [0-9]{8})\ ([0-9]{9})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)\ [0-9]{8})\ ([0-9]{9})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\/\ ([0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\/\ ([0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{8})\ ([0-9]{8})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{8})\ ([0-9]{8})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\-([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\-([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})\ \ (\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})\ \ (\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\ [0-9]{8})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\ [0-9]{8})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{5}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{5}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/([0-9]{2}\-\ [0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/([0-9]{2}\-\ [0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\*[0-9]{3}\-[0-9]{4})\/([0-9]{3}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\*[0-9]{3}\-[0-9]{4})\/([0-9]{3}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ \-\ ([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ \-\ ([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)[0-9]{4}\-[0-9]{4})\/\ ([0-9]{2}\/\ [0-9]{5}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)[0-9]{4}\-[0-9]{4})\/\ ([0-9]{2}\/\ [0-9]{5}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/\ \ ([0-9]{5}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/\ \ ([0-9]{5}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\/\ [0-9]{4}\-[0-9]{4})\ \/([0-9]{8})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\/\ [0-9]{4}\-[0-9]{4})\ \/([0-9]{8})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2})\-([0-9]{4}\-[0-9]{4})\.([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2})\-([0-9]{4}\-[0-9]{4})\.([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $ddd1 = $matches[1][0];
                $ddd2 = $matches[1][0];
                $telefone1 = $matches[2][0];
                $telefone2 = $matches[3][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\ ([0-9]{9})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\ ([0-9]{9})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\/[0-9]{4}\-[0-9]{4})\ ([0-9]{2}\/[0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\/[0-9]{4}\-[0-9]{4})\ ([0-9]{2}\/[0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
        		preg_match('/([0-9]{4})\-([0-9]{4})\ \ \ ([0-9]{4})\-([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-(([0-9]{4})\.([0-9]{4}))/', $telefone) ||
        		preg_match('/([0-9]{4})\-([0-9]{4})\ ([0-9]{8})/', $telefone) ||
        		preg_match('/([0-9]{4})\-([0-9]{4})\ ([0-9]{2})\/([0-9]{4})/', $telefone)
        	){
        		$telefone = explode(" ", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
	        }
        	unset($CepBusca);
        	$line['DDD1'] = $ddd1;
        	$line['telefone1'] = trim($telefone1);
        	$line['DDD2'] = trim($ddd2);
        	$line['telefone2'] = trim($telefone2);
        	$line['DDD3'] = $ddd3;
        	$line['telefone3'] = $telefone3;

            if(empty($line['DDD1']) && strlen($line['telefone1']) > 8){
                if(
                    preg_match('/^(\([0-9]{2}\))(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^(\([0-9]{2}\))(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('(','',str_replace(')','',$matches[1][0]));
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^(\([0-9]{3}\))(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^(\([0-9]{3}\))(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('(','',str_replace(')','',$matches[1][0]));
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3})\-([0-9]{3}\.[0-9]{4})/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3})\-([0-9]{3}\.[0-9]{4})/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\-)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\-)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('-','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\ )(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\ )(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\*)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\*)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('*','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\*)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\*)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('*','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\ )(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\ )(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\.)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\.)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('.','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }
            }

            $line['DDD1'] = trim($line['DDD1']);
            $line['DDD2'] = trim($line['DDD2']);
            $line['DDD3'] = trim($line['DDD3']);
            $line['telefone1'] = trim($line['telefone1']);
            $line['telefone2'] = trim($line['telefone2']);
            $line['telefone3'] = trim($line['telefone3']);

            if(!empty($line['telefone1'])){
                $line['TIPO1'] = '1';
            }
            if(!empty($line['telefone2'])){
                $line['TIPO2'] = '1';
            }
            if(!empty($line['telefone3'])){
                $line['TIPO3'] = '1';
            }

            $file[] = implode(array_values($line), ";");
            echo implode(array_values($line), ";")."\n";
            unset($line);
        }
        $file = implode("\n",$file);
        $name_file = "clientes_vendedores_".date('YmdHis').".csv";
        Storage::put($name_file, $file);
        //echo implode($file, "\n");exit;


        die();

        $ClientesObj = Cliente::where("atividade", ">=", '51');
        /*$ClientesObj->where(function($query){
            $query->whereOr("ESTADO", "=", "EX");
            $query->whereOr("CEP", "=", "_____-___");
            $query->whereOr("CEP", "=", "0____-___");
            $query->whereOr("CEP", "=", "00000-000");
            $query->whereOr("CEP", "=", "00001-000");
            $query->whereOr("CGC_CPF", "=", "");
        });*/
        $ClientesObj = $ClientesObj->get();
        $file = [];
        $header = ['CODIGO', 'CNPJ', 'RAZAOSOCIAL', 'NOMEFANTASIA', 'DATACADASTRO', 'EMAIL', 'SITE', 'TIPOSIMPLES', 'INSCRICAOMUNICIPAL', 'INSCRICAOESTADUAL', 'TIPOLOGRADOURO', 'LOGRADOURO', 'NUMERO', 'COMPLEMENTO', 'BAIRRO', 'CEP', 'UF', 'PAIS', 'MUNICIPIO', 'CODIGOMUNICIPIO', 'REFERENCIA', 'DDI1', 'DDD1', 'telefone1', 'RAMAL1', 'TIPO1', 'DDI2', 'DDD2', 'telefone2', 'RAMAL2', 'TIPO2', 'DDI3', 'DDD3', 'telefone3', 'RAMAL3', 'TIPO3', 'NOMECONTATO1', 'SOBRENOMECONTATO1', 'EMAILCONTATO1', 'SEXO1', 'NOMECONTATO2', 'SOBRENOMECONTATO2', 'EMAILCONTATO2', 'SEXO2', 'NOMECONTATO3', 'SOBRENOMECONTATO3', 'EMAILCONTATO3', 'SEXO3', 'LIMITEDECREDITO', 'CODVND'];
        $file[] = implode($header, ";");

        foreach ($ClientesObj as $key => $Cliente) {
        	$line = ['CODIGO' => '', 'CNPJ' => '', 'RAZAOSOCIAL' => '', 'NOMEFANTASIA' => '', 'DATACADASTRO' => '', 'EMAIL' => '', 'SITE' => '', 'TIPOSIMPLES' => '', 'INSCRICAOMUNICIPAL' => '', 'INSCRICAOESTADUAL' => '', 'TIPOLOGRADOURO' => '', 'LOGRADOURO' => '', 'NUMERO' => '', 'COMPLEMENTO' => '', 'BAIRRO' => '', 'CEP' => '', 'UF' => '', 'PAIS' => '', 'MUNICIPIO' => '', 'CODIGOMUNICIPIO' => '', 'REFERENCIA' => '', 'DDI1' => '', 'DDD1' => '', 'telefone1' => '', 'RAMAL1' => '', 'TIPO1' => '', 'DDI2' => '', 'DDD2' => '', 'telefone2' => '', 'RAMAL2' => '', 'TIPO2' => '', 'DDI3' => '', 'DDD3' => '', 'telefone3' => '', 'RAMAL3' => '', 'TIPO3' => '', 'NOMECONTATO1' => '', 'SOBRENOMECONTATO1' => '', 'EMAILCONTATO1' => '', 'SEXO1' => '', 'NOMECONTATO2' => '', 'SOBRENOMECONTATO2' => '', 'EMAILCONTATO2' => '', 'SEXO2' => '', 'NOMECONTATO3' => '', 'SOBRENOMECONTATO3' => '', 'EMAILCONTATO3' => '', 'SEXO3' => '', 'LIMITEDECREDITO' => '', 'CODVND' => ''];

        	$CEP = $Cliente->CEP;
        	$CEP = (int) str_replace("-", "", $CEP);
            /*
        	$CepBusca = CepEndereco::with(['cidadeBusca'])->find($CEP);
        	if(is_null($CepBusca)){
        		continue;
        	}
        	$CepBusca = $CepBusca->toArray();
        	if(intval($CepBusca['cidade_busca']['id_municipio_subordinado']) > 0){
        		$cidadeBusca = (int) $CepBusca['cidade_busca']['id_municipio_subordinado'];
        		$CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
        		if(intval($CepCidadeObj['id_municipio_subordinado']) > 0){
        			$cidadeBusca = $cidadeBusca['id_municipio_subordinado'];
        			$CepCidadeObj = CepCidade::find($cidadeBusca)->toArray();
        		}
        		$CepBusca['cidade_busca'] = $CepCidadeObj["cidade"];
        		$CepBusca['cidade_busca'] = $CepCidadeObj["uf"];
        		$CepBusca['cidade_busca'] = $CepCidadeObj["cod_ibge"];
        	}*/

        	$cnpj = $Cliente->CGC_CPF;
        	$cnpj = str_replace(".", "", $cnpj);
        	$cnpj = str_replace("-", "", $cnpj);
            $cnpj = str_replace("/", "", $cnpj);
        	$cnpj = str_replace("_", "", $cnpj);

            $line['CODIGO'] = $Cliente->codcad;
        	$line['CNPJ'] = $cnpj;
        	$line['RAZAOSOCIAL'] = str_replace(";",':',utf8_encode($Cliente->NOME));
        	$line['NOMEFANTASIA'] = str_replace(";",':',utf8_encode($Cliente->GUERRA));
        	$line['DATACADASTRO'] = date('Y-m-d',strtotime($Cliente->dtdesde));
        	$line['EMAIL'] = str_replace(";",':',utf8_encode($Cliente->EMAIL));
        	$line['TIPOSIMPLES'] = '0';
        	$line['INSCRICAOMUNICIPAL'] = $Cliente->IEST;
        	$line['INSCRICAOESTADUAL'] = $Cliente->IMUN;
        	$line['LOGRADOURO'] = str_replace(";",':',utf8_encode($Cliente->endereco));
            $line['BAIRRO'] = utf8_encode($Cliente->BAIRRO);

            $line['CEP'] = $Cliente->CEP;
            $line['UF'] = $Cliente->ESTADO;
        	$line['MUNICIPIO'] = utf8_encode($Cliente->CIDADE);
        	$line['LIMITEDECREDITO'] = $Cliente->LIMCRED;
        	$line['CODVND'] = $Cliente->CODVND;
/*
        	$line['MUNICIPIO'] = $CepBusca['cidade_busca']["cidade"];
        	$line['UF'] = $CepBusca['cidade_busca']["uf"];
        	$line['CODIGOMUNICIPIO'] = $CepBusca['cidade_busca']["cod_ibge"];
*/
        	$line['PAIS'] = '1058';

            $telefone = strtolower($Cliente->TELEFONE);
        	//$line['telefone_temp'] = strtolower($Cliente->TELEFONE);
        	$telefone = trim(preg_replace('/[a-z]/', '', $telefone));

        	$ddd1 = '';
        	$telefone1 = $telefone;
        	$ddd2 = '';
        	$telefone2 = '';
        	$ddd3 = '';
        	$telefone3 = '';
        	if(
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\*([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{5})\-([0-9]{4}))\*([0-9]{4})/', $telefone)){
        		$telefone = explode("*", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
        	}elseif(
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/(([0-9]{3})\ ([0-9]{4})\-([0-9]{4}))/', $telefone) ||
        		preg_match('/(([0-9]{3})\-([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\/([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\ \/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/([0-9]{7})\/([0-9]{8})/', $telefone) ||
        		preg_match('/([0-9]{8})\/\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/(([0-9]{3})\-([0-9]{4}))/', $telefone) ||
        		preg_match('/(([0-9]{4})\-([0-9]{4}))\/\ ([0-9]{2})\ /', $telefone)
        	){
	        	$telefone = explode("/", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
        	} elseif (
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-\ ([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\ ([0-9]{4}))\-([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{8}))\-([0-9]{8})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-(([0-9]{4})\.([0-9]{4}))/', $telefone)
        	) {
        		$telefone = explode("-", $telefone);
        		if(count($telefone)>0){
        			if(strlen($telefone[0]) == 2){
        				$telefone1 = $telefone[0].'-'.$telefone[1];
						if(count($telefone) > 1){
	        				$telefone2 = $telefone[2];
	        			}
        			}else{
        				$telefone1 = $telefone[0];
	        			if(count($telefone) > 1){
	        				$telefone2 = $telefone[1];
	        			}
	        			if(count($telefone) > 2){
	        				$telefone3 = $telefone[2];
	        			}
	        		}
        		}
        		unset($telefone);
        	} elseif(
                preg_match('/(([0-9]{3})\ ([0-9]{4})\ ([0-9]{4}))\ (([0-9]{4})\ ([0-9]{4}))/', $telefone)
            ){
                preg_match_all('/(([0-9]{3})\ ([0-9]{4})\ ([0-9]{4}))\ (([0-9]{4})\ ([0-9]{4}))/', $telefone, $matches);
                $dd1 = $matches[2][0];
                $telefone1 = str_replace($dd1, '', $matches[1][0]);
                $dd2 = $matches[2][0];
                $telefone2 = $matches[5][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{3}\)\ [0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{3}\)\ [0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)\ [0-9]{8})\ ([0-9]{9})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)\ [0-9]{8})\ ([0-9]{9})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\/\ ([0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\/\ ([0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{8})\ ([0-9]{8})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{8})\ ([0-9]{8})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\-([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\-([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})\ \ (\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})\ \ (\([0-9]{2}\)\ [0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\ [0-9]{8})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\ [0-9]{8})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{5}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{5}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/([0-9]{2}\-\ [0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/([0-9]{2}\-\ [0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\*[0-9]{3}\-[0-9]{4})\/([0-9]{3}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\*[0-9]{3}\-[0-9]{4})\/([0-9]{3}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\-[0-9]{4})\ ([0-9]{4}\ [0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ \-\ ([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\-[0-9]{4}\.[0-9]{4})\ \-\ ([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/(\([0-9]{2}\)[0-9]{4}\-[0-9]{4})\/\ ([0-9]{2}\/\ [0-9]{5}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/(\([0-9]{2}\)[0-9]{4}\-[0-9]{4})\/\ ([0-9]{2}\/\ [0-9]{5}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/\ \ ([0-9]{5}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\-[0-9]{4})\/\ \ ([0-9]{5}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\/\ [0-9]{4}\-[0-9]{4})\ \/([0-9]{8})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\/\ [0-9]{4}\-[0-9]{4})\ \/([0-9]{8})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2})\-([0-9]{4}\-[0-9]{4})\.([0-9]{4}\.[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2})\-([0-9]{4}\-[0-9]{4})\.([0-9]{4}\.[0-9]{4})/', $telefone, $matches);
                $ddd1 = $matches[1][0];
                $ddd2 = $matches[1][0];
                $telefone1 = $matches[2][0];
                $telefone2 = $matches[3][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\ ([0-9]{9})/', $telefone)
            ){
                preg_match_all('/([0-9]{3}\ [0-9]{4}\ [0-9]{4})\ ([0-9]{9})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
                preg_match('/([0-9]{2}\/[0-9]{4}\-[0-9]{4})\ ([0-9]{2}\/[0-9]{4}\-[0-9]{4})/', $telefone)
            ){
                preg_match_all('/([0-9]{2}\/[0-9]{4}\-[0-9]{4})\ ([0-9]{2}\/[0-9]{4}\-[0-9]{4})/', $telefone, $matches);
                $telefone1 = $matches[1][0];
                $telefone2 = $matches[2][0];
                unset($matches);
                unset($telefone);

            } elseif(
        		preg_match('/([0-9]{4})\-([0-9]{4})\ \ \ ([0-9]{4})\-([0-9]{4})/', $telefone) ||
        		preg_match('/(([0-9]{4})\.([0-9]{4}))\-(([0-9]{4})\.([0-9]{4}))/', $telefone) ||
        		preg_match('/([0-9]{4})\-([0-9]{4})\ ([0-9]{8})/', $telefone) ||
        		preg_match('/([0-9]{4})\-([0-9]{4})\ ([0-9]{2})\/([0-9]{4})/', $telefone)
        	){
        		$telefone = explode(" ", $telefone);
        		if(count($telefone)>0){
        			$telefone1 = $telefone[0];
        			if(count($telefone) > 1){
        				$telefone2 = $telefone[1];
        			}
        			if(count($telefone) > 2){
        				$telefone3 = $telefone[2];
        			}
        		}
        		unset($telefone);
	        } else {
            }/*
	        if(empty($telefone1)){
	        	continue;
	        }*/
        	unset($CepBusca);
        	$line['DDD1'] = $ddd1;
        	$line['telefone1'] = trim($telefone1);
        	$line['DDD2'] = trim($ddd2);
        	$line['telefone2'] = trim($telefone2);
        	$line['DDD3'] = $ddd3;
        	$line['telefone3'] = $telefone3;

            if(empty($line['DDD1']) && strlen($line['telefone1']) > 8){
                if(
                    preg_match('/^(\([0-9]{2}\))(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^(\([0-9]{2}\))(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('(','',str_replace(')','',$matches[1][0]));
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^(\([0-9]{3}\))(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^(\([0-9]{3}\))(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('(','',str_replace(')','',$matches[1][0]));
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3})\-([0-9]{3}\.[0-9]{4})/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3})\-([0-9]{3}\.[0-9]{4})/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\-)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\-)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('-','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\ )(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\ )(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{2}\*)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{2}\*)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('*','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\*)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\*)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('*','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\ )(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\ )(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = $matches[1][0];
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }elseif(
                    preg_match('/^([0-9]{3}\.)(.*)/', $line['telefone1'])
                ){
                    preg_match_all('/^([0-9]{3}\.)(.*)/', $line['telefone1'], $matches);
                    $line['DDD1'] = str_replace('.','',$matches[1][0]);
                    $line['telefone1'] = trim($matches[2][0]);
                    unset($matches);

                }
            }

            $line['DDD1'] = trim($line['DDD1']);
            $line['DDD2'] = trim($line['DDD2']);
            $line['DDD3'] = trim($line['DDD3']);
            $line['telefone1'] = trim($line['telefone1']);
            $line['telefone2'] = trim($line['telefone2']);
            $line['telefone3'] = trim($line['telefone3']);

            if(!empty($line['telefone1'])){
                $line['TIPO1'] = '1';
            }
            if(!empty($line['telefone2'])){
                $line['TIPO2'] = '1';
            }
            if(!empty($line['telefone3'])){
                $line['TIPO3'] = '1';
            }

        	$file[] = implode(array_values($line), ";");
        }
        $file = implode("\n",$file);
        $name_file = "fornecedores_".date('YmdHis').".csv";
        Storage::put($name_file, $file);
        //echo implode($file, "\n");exit;
    }
}
