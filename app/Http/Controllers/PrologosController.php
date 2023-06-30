<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use \PDO;

class PrologosController extends Controller
{

    private $connPrologos;

    public function __construct(){
        try{
            $this->connPrologos = DB::connection('srv_prologos')->getPdo();
        } catch (\Exception $e) {
            // dd($e);
        }
    }

    /**
     * Retorna CODCAD que contiver no nome o $nome
     *
     * @param string $nome
     * @return array
     */
    public function getCodcadNome($nome){
        $dados = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT CODCAD FROM dbo.TBCAD1 WHERE NOME like :nome");
            $nome = "%".$nome."%";
            $dados->bindParam(":nome", $nome);
            $dados->execute();
        } catch (\Exception $e) {
            //dd($e);
        }
        $return_temp = $dados->fetchAll(PDO::FETCH_ASSOC);
        foreach($return_temp as $value){
            $return[] = $value["CODCAD"];
        }
        return $return;
    }

    /**
     * Retorna bancos cadastrados
     *
     * @return array
     */
    public function getBancos(){
        $dados = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT CODBCODIF, NOME, AGENCIA, NUMCTA FROM dbo.TBBCO1");
            $dados->execute();
        } catch (\Exception $e) {
            // dd($e);
        }
        $dados = $dados->fetchAll(PDO::FETCH_ASSOC);
        $return = [];
        $return[0] = [
            "nome" => "Caixa",
            "agencia" => "",
            "numero_conta" => ""
        ];
        foreach ($dados as $key => $value) {
            $return[intval($value["CODBCODIF"])] = [
                "nome" => $value["NOME"],
                "agencia" => $value["AGENCIA"],
                "numero_conta" => $value["NUMCTA"]
            ];
        }
        return $return;
    }

    /**
     * Retorna dos dados do banco da Prologos a partir do CODCAD
     *
     * @param string $codcad
     * @return array
     */
    public function getDadosCodcad($codcad){
        $dados = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT NOME FROM dbo.TBCAD1 WHERE CODCAD = :codcad");
            $dados->bindParam(":codcad", $codcad, PDO::PARAM_STR);
            $dados->execute();
        } catch (\Exception $e) {
            // dd($e);
        }
        return $dados->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna dos dados do banco da Prologos a partir do CODCAD
     *
     * @param string $codcad
     * @return array
     */
    public function getDadosCNPJCPF($cnpjcpf){
        $dados = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT NOME FROM dbo.TBCAD1 WHERE CGC_CPF = :cnpjcpf");
            $dados->bindParam(":cnpjcpf", $cnpjcpf, PDO::PARAM_STR);
            $dados->execute();
        } catch (\Exception $e) {
            // dd($e);
        }
        return $dados->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna dos dados do banco da Prologos a partir do CODVND
     *
     * @param string $codcad
     * @return array
     */
    public function getDadosCodvnd($codvnd){
        $dados = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT NOME FROM dbo.TBVND1 WHERE CODVND = :codvnd");
            $dados->bindParam(":codvnd", $codvnd, PDO::PARAM_STR);
            $dados->execute();
        } catch (\Exception $e) {
            // dd($e);
        }
        return $dados->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna Empreasas Cadastradas
     *
     * @return array
     */
    public function getEmpresas(){
        $dados = [];
        $empresas = [];
        try{
            $dados = $this->connPrologos->prepare("SELECT ESTABEL, APELIDO from dbo.TBPAE1");
            $dados->execute();
        } catch (\Exception $e) {
            // dd($e);
        }
        $dados = $dados->fetchAll(PDO::FETCH_ASSOC);
        foreach ($dados as $key => $value) {
            $empresas[intval($value["ESTABEL"])] = $value["APELIDO"];
        }
        return $empresas;
    }

    public function getDadosCODWEB($codweb){
        return DB::connection("srv_ww")->table("WWUSU")->where('CODWEB', $codweb)->first();
    }
    
}
