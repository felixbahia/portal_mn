<?php


function returnEmpresasPrologusView(){
    $PrologosController = new App\Http\Controllers\PrologosController();
    $empresas = $PrologosController->getEmpresas();
    foreach ($empresas as $key => $value) {
        $empresas[$key] = str_pad($key, 2, "0", STR_PAD_LEFT)." - ".$value;
    }
    return $empresas;
}

function returnEmpresasNasajonView(){
    $empresas = App\NasajonEstabelecimento::orderBy('codigo')->get();
    $empresas_view = [];
    foreach ($empresas as $key => $empresa) {
        if($empresa->codigo === '25' || $empresa->codigo === '99' || !is_numeric($empresa->codigo)){
           continue; 
        }
        $empresas_view[(int) $empresa->codigo] = str_pad($empresa->codigo, 2, "0", STR_PAD_LEFT)." - ".$empresa->nomefantasia;
    }
    return $empresas_view;
}

function returnEmpresasTodosNasajonView(){
    $empresas = App\NasajonEstabelecimento::orderBy('codigo')->get();
    $empresas_view = [];
    foreach ($empresas as $key => $empresa) {
        if($empresa->codigo === '99' || !is_numeric($empresa->codigo)){
           continue; 
        }
        $empresas_view[(int) $empresa->codigo] = str_pad($empresa->codigo, 2, "0", STR_PAD_LEFT)." - ".$empresa->nomefantasia;
    }
    return $empresas_view;
}

function returnTodasEmpresasView(){
    $empresas = App\NasajonEstabelecimento::orderBy('codigo')->get();
    $empresas_view = [];
    foreach ($empresas as $key => $empresa) {
        if($empresa->codigo === '99'){
           continue; 
        }
        $empresas_view[$empresa->codigo] = $empresa->codigo." - ".$empresa->nomefantasia;
    }
    return $empresas_view;
}
function returnTodasEmpresasVendas(){
   
    $empresas = App\NasajonEstabelecimento::whereIn('codigo',['03','04','05','07','08'])->orderBy('codigo')->get();
    $empresas_view = [];
    foreach ($empresas as $key => $empresa) {
        $empresas_view[(int) $empresa->codigo] = str_pad($empresa->codigo, 2, "0", STR_PAD_LEFT)." - ".$empresa->nomefantasia;
    }
    return $empresas_view;
}
function returnEmpresasPrologusAuth(){
    $empresas = returnEmpresasPrologusView();
    $empresa = $empresas[\Auth::user()->empresa_padrao_id];
    return $empresa;
}

class CustomView{

    public static function createSessionPrograma(){
        $model = Request::session()->get("model");
        $programa = App\Http\Controllers\ProgramaController::returnDadosPrograma($model);
        Request::session()->flash("programa", $programa);
    }

    public static function titlePage(){
        $title = "";
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        $title = ' | '.$programa["modulo"]["nome"];
        foreach ($programa["submodulo"] as $key => $submodulos) {
            $title .= ( (!empty($submodulos["nome"])) ? " | ".$submodulos["nome"] : "" );
        }
        $title .= ' | '.$programa["nome"];
        return $title;
    }

    public static function modelIcon(){
        $icon = "";
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        $icon = URL::asset($programa["modulo"]["icon"]);
        return $icon;
    }
    
    public static function modelName(){
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        return $programa["modulo"]["nome"];
    }
    
    public static function modelUrl(){
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        return $programa["modulo"]["url"];
    }

    public static function programaName(){
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        return $programa["nome"];
    }

    public static function createBreadcrumb(){
        $html = "";
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        $html = "
            <div class=\"modulo\">
                <a href=\"".$programa["modulo"]["url"]."\">
                    ".$programa["modulo"]["nome"]."
                </a>
            </div>";
        foreach ($programa["submodulo"] as $key => $submodulos) {
            if(!empty(trim($submodulos["nome"]))){
                $html .= "
                <div class=\"modulo\">
                    <a href=\"".$submodulos["url"]."\">
                        ".$submodulos["nome"]."
                    </a>
                </div>";
            }
        }
        $html .= "
            <div class=\"tela\">
                ".$programa["nome"]."
            </div>";
        return $html;
    }

    public static function createTitlePage(){
        $html = "";
        if (!Request::session()->has('programa')) {
            CustomView::createSessionPrograma();
        }
        $programa = Request::session()->get("programa");
        $html = "
            <div class=\"content-title-page\">";
        $html .= "<h2>{$programa["modulo"]["nome"]} - ";
        if(!empty($programa["submodulo"])){
            foreach ($programa["submodulo"] as $key => $submodulos) {
                if($submodulos['nome'] === 'Acompanhamento'){
                    return $html = '';
                }
                $html .= "{$submodulos["nome"]} - ";
            }
        }
        $favorito = \App\Http\Controllers\ProgramasFavoritoController::getProgramaAtivo($programa['id']);
        $html .= "{$programa["nome"]}</h2><div class='programa-favorito ".(($favorito == true) ? 'ativo' : '')."' data-programa='".encrypt($programa['id'])."'></div>";
        $html .= "</div>";
        return $html;
    }


    public static function retornaClientePadraoId(){
        $cliente_id = \Auth::user()->cliente_padrao_id;

        if(!empty($cliente_id)){
            return $cliente_id;
        }
    }
    
    public static function retornaClientePadraoNome(){
        if(empty(Auth::user()->cliente_padrao_id)){
            return '';
        }
        $clienteObj = App\ClienteNasajon::where('codigo', \Auth::user()->cliente_padrao_id)->where('bloqueado', 'false')->first();
        if (!is_null($clienteObj)){
            return trim($clienteObj->nome) . ' - '. $clienteObj->cpf_cnpj;
        }
        return '';
    }

    public static function retornaClientePadraoNomeSemCNPJ(){
        if(empty(Auth::user()->cliente_padrao_id)){
            return '';
        }
        $clienteObj = App\ClienteNasajon::where('codigo', \Auth::user()->cliente_padrao_id)->where('bloqueado', 'false')->first();
        if (!is_null($clienteObj)){
            return trim($clienteObj->nome);
        }
        return '';
    }

    public static function retornaClientePadraoCPFCNPJ(){
        $clienteObj = App\ClienteNasajon::where('codigo', \Auth::user()->cliente_padrao_id)->where('bloqueado', 'false')->first();

        if (!is_null($clienteObj)){
            return $clienteObj->cpf_cnpj;
        }

    }


    


}
