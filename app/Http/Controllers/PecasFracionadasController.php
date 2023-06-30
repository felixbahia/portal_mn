<?php

namespace App\Http\Controllers;

use Auth;
use App\FracaoNasajon;
use App\FracoesDisponiveisNasajon;
use App\ProdutosEstoque;
use Illuminate\Http\Request;

use App\EstoquePoderTerceiro;
use App\NasajonEstabelecimento;
use App\FracaoDisponivelNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\LocalDeEstoqueEnderecoNasajon;
use Illuminate\Support\Facades\Storage;

class PecasFracionadasController  extends Controller
{
    private $consultaPeçasFracionadas;

    private $grupo_de_inventario = ['Mercadorias', 'Produtos Acabados ou Manufaturados', 'Produtos Intermediários'];

    public function index(Request $request)
    {
        if (Auth::user()->hasPermissionTo("programas App\PecasFracionadas") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\PecasFracionadas');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[00]);
        unset($estabelecimentos[20]);
        unset($estabelecimentos[30]);

        return view('programs.pecas_fracionadas.index', ['estabelecimentos' => $estabelecimentos]);
    }

    public function consulta(Request $request)
    {
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $fields = $request->only('estabelecimento', 'grupo', 'linha', 'marca', 'subgrupo');
        
        $consultaPeçasFracionadas = FracoesDisponiveisNasajon::select('estabelecimento_codigo','peca_veio_de_fracionamento','produto_codigo',  DB::raw("COUNT(*) as quantidade, sum(saldo) as saldo_total"));
        $consultaPeçasFracionadas->whereIn('grupodeinventario',$this->grupo_de_inventario);

        if (!empty($fields['estabelecimento'])) {
            $consultaPeçasFracionadas->where('estabelecimento_codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
        }

        $consultaPeçasFracionadas->with(['especificacao' => function ($query) use ($fields){$query->select('codigo_produto', 'unidade', 'peso');}, ]);
        
        if (
            (isset($fields['grupo']) && !empty($fields['grupo'])) ||
            (isset($fields['subgrupo']) && !empty($fields['subgrupo'])) ||
            (isset($fields['linha']) && !empty($fields['linha'])) ||
            (isset($fields['marca']) && !empty($fields['marca']))
        ) {
            $consultaPeçasFracionadas->with(['especificacao'=> function ($query) use ($fields) {
                if (!empty($fields['grupo'])) {
                    $query->whereHas('produtoGrupo', function ($query) use($fields){
                        $query->where('descricao', 'ilike', $fields['grupo']);
                    });
                }
                if (!empty($fields['subgrupo'])) {
                    $query->where('subgrupo', 'ilike', $fields['subgrupo']);
                }
                if (!empty($fields['linha'])) {
                    $query->where('linha', 'ilike', $fields['linha']);
                }
                if (!empty($fields['marca'])) {
                    $query->where('marca', 'ilike', $fields['marca']);
                }
                $query->where('ativo', true);
            }]);
        } 
        
        $consultaPeçasFracionadas->groupBy('estabelecimento_codigo','peca_veio_de_fracionamento','produto_codigo');

        $result  = $consultaPeçasFracionadas->get();
        
        $resultado = [];
        $result->each(function ($produto) use (&$resultado) {
            
        
            if (isset($produto->especificacao)) {

                    $estabelecimento = str_pad($produto->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);

                    if (!isset( $resultado[$estabelecimento]['codigo_estabel'])) {
                        $resultado[$estabelecimento]['codigo_estabel'] = $estabelecimento;
                    }
                    

                    if(($produto->peca_veio_de_fracionamento)!=true){

                        if (!isset( $resultado[$estabelecimento]['rolos'])) {
                            $resultado[$estabelecimento]['rolos'] = $produto->quantidade;
                        } else {
                            $resultado[$estabelecimento]['rolos'] = $resultado[$estabelecimento]['rolos'] + $produto->quantidade;
                        }
                               
                    }else {

                        if (!isset( $resultado[$estabelecimento]['rolos'])) {
                            $resultado[$estabelecimento]['rolos'] = $produto->quantidade;
                        } else {
                            $resultado[$estabelecimento]['rolos'] = $resultado[$estabelecimento]['rolos'] + $produto->quantidade;
                        }

                        if(isset($produto->saldo_total)){
                                                                
                            if (!isset( $resultado[$estabelecimento]['qtde_pecas_frac'])) {
                                $resultado[$estabelecimento]['qtde_pecas_frac'] = $produto->quantidade;
                            } else {
                                $resultado[$estabelecimento]['qtde_pecas_frac'] = $resultado[$estabelecimento]['qtde_pecas_frac'] + $produto->quantidade;
                            }
            
                            $unidade = 'M';
                            if (isset($produto->especificacao->unidade)) {
                                $unidade = strtoupper(trim($produto->especificacao->unidade));
                            }
                            if ($unidade == 'KG') {
                                if (!isset($resultado[$estabelecimento]['peso_frac'])) {
                                    $resultado[$estabelecimento]['peso_frac'] = $produto->saldo_total;
                                } else {
                                    $resultado[$estabelecimento]['peso_frac'] += $produto->saldo_total;
                                }
                                            
                            } else if (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) {
                                if (!isset($resultado[$estabelecimento]['metros_frac'])) {
                                    $resultado[$estabelecimento]['metros_frac'] = $produto->saldo_total;
                                } else {
                                    $resultado[$estabelecimento]['metros_frac'] += $produto->saldo_total;
                                }
                        
                                    
                            }else {
                                if (!isset($resultado[$estabelecimento]['outras_unidades_frac'])) {
                                    $resultado[$estabelecimento]['outras_unidades_frac'] = $produto->saldo_total;
                                } else {
                                    $resultado[$estabelecimento]['outras_unidades_frac'] += $produto->saldo_total;
                                }
                            }
                        }    
                    }
                        
                    $unidade = 'M';
                    if (isset($produto->especificacao->unidade)) {
                            $unidade = strtoupper(trim($produto->especificacao->unidade));
                        }
                        if ($unidade == 'KG') {
                            if (!isset($resultado[$estabelecimento]['peso'])) {
                                $resultado[$estabelecimento]['peso'] = $produto->especificacao->peso;
                            } else {
                                $resultado[$estabelecimento]['peso'] += $produto->especificacao->peso;
                            }
                                
                        } else if (in_array($unidade, ['MT', 'M', 'METRO', 'METROS', 'MTS', 'ME'])) {
                            if (!isset($resultado[$estabelecimento]['metros'])) {
                                    $resultado[$estabelecimento]['metros'] = $produto->saldo_total;
                            } else {
                                $resultado[$estabelecimento]['metros'] += $produto->saldo_total;
                            }
            
                        
                        }else {
                            if (!isset($resultado[$estabelecimento]['outras_unidades'])) {
                                $resultado[$estabelecimento]['outras_unidades'] = $produto->saldo_total;
                            } else {
                                $resultado[$estabelecimento]['outras_unidades'] += $produto->saldo_total;
                            }
                        }
                         
                     
                     
                }
            
        });

        

        $empresas = returnEmpresasNasajonView();
        $rolos_final_total = 0;
        $metros_final_total = 0;
        $peso_final_total = 0;      
        $outras_final_total = 0;
        $qtde_pecas_frac_total = 0;
        $metros_final_frac_total = 0;
        $peso_final_frac_total = 0;  
        $outras_final_frac_total = 0;
        $porcentagem_frac_final_total = 100;
        $porcentagem_final_total = 100;

        $response = [];

        foreach ($resultado as $key => $estabelecimento) {
 
           $response[$key]['empresa'] = $empresas[intval($key)];
          
           $response[$key]['codigo_estabel'] = $estabelecimento['codigo_estabel'];

           if (isset($estabelecimento['qtde_pecas_frac'])) {
                $response[$key]['qtde_pecas_frac'] = $estabelecimento['qtde_pecas_frac'];
                $qtde_pecas_frac_total += $estabelecimento['qtde_pecas_frac'];
            } else {
                $response[$key]['qtde_pecas_frac'] = 0;
            }

            
            if (isset($estabelecimento['peso_frac'])) {
                $response[$key]['peso_frac'] = $estabelecimento['peso_frac'];
                $peso_final_frac_total += $estabelecimento['peso_frac'];
            } else {
                $response[$key]['peso_frac'] = 0;
            }


            if (isset($estabelecimento['peso'])) {
                $response[$key]['peso'] = $estabelecimento['peso'];
                $peso_final_total += $estabelecimento['peso'];
            } else {
                $response[$key]['peso'] = 0;
            }

            if (isset($estabelecimento['metros'])) {
                $response[$key]['metros'] = $estabelecimento['metros'];
                $metros_final_total += $estabelecimento['metros'];
            } else {
                $response[$key]['metros'] = 0;
            }

            if (isset($estabelecimento['metros_frac'])) {
                $response[$key]['metros_frac'] = $estabelecimento['metros_frac'];
                $metros_final_frac_total += $estabelecimento['metros_frac'];
            } else {
                $response[$key]['metros_frac'] = 0;
            }

            if (isset($estabelecimento['outras_unidades'])) {
                $response[$key]['outras_unidades'] = $estabelecimento['outras_unidades'];
                $outras_final_total += $estabelecimento['outras_unidades'];
            } else {
                $response[$key]['outras_unidades'] = 0;
            }

            if (isset($estabelecimento['outras_unidades_frac'])) {
                $response[$key]['outras_unidades_frac'] = $estabelecimento['outras_unidades_frac'];
                $outras_final_frac_total += $estabelecimento['outras_unidades_frac'];
            } else {
                $response[$key]['outras_unidades_frac'] = 0;
            }

            if (isset($estabelecimento['rolos'])) {  
                $response[$key]['rolos'] = $estabelecimento['rolos'];
                $rolos_final_total += $estabelecimento['rolos'];               
            } else {
                $estabelecimento['rolos'] = 0;
            }

        }


        $resposta = [];

        foreach ($response as $key => $estabelecimento) {
 
           $resposta[$key]['empresa'] = $empresas[intval($key)];

           $resposta[$key]['codigo_estabel'] = $estabelecimento['codigo_estabel'];

            if($estabelecimento['peso_frac'] > 0){
                $resposta[$key]['peso_frac'] = parserValor($estabelecimento['peso_frac']);
            }else{
                $resposta[$key]['peso_frac'] = ' ';
            }
            
            if($estabelecimento['peso'] > 0){
                $resposta[$key]['peso']      = parserValor($estabelecimento['peso']);
            }else{
                $resposta[$key]['peso']      = ' ';
            }
            
            if($estabelecimento['metros'] > 0){
                $resposta[$key]['metros']      = parserValor($estabelecimento['metros']);
            }else{
                $resposta[$key]['metros']      = ' ';
            }

            if($estabelecimento['metros_frac'] > 0){ 
                $resposta[$key]['metros_frac'] = parserValor($estabelecimento['metros_frac']);
            }else{
                $resposta[$key]['metros_frac'] = ' ';
            }


            if($estabelecimento['outras_unidades'] > 0){
                $resposta[$key]['outras_unidades']      = parserValor($estabelecimento['outras_unidades']);
            }else{
                $resposta[$key]['outras_unidades']      = ' ';
            }

            if($estabelecimento['outras_unidades_frac'] > 0){
                $resposta[$key]['outras_unidades_frac'] = parserValor($estabelecimento['outras_unidades_frac']);
            }else{
                $resposta[$key]['outras_unidades_frac'] = ' ';
            }

            $resposta[$key]['qtde_pecas_frac']  = $estabelecimento['qtde_pecas_frac'];
            $resposta[$key]['rolos']            = $estabelecimento['rolos'];
            $resposta[$key]['porcentagem']      =  round((($resposta[$key]['rolos'] / $rolos_final_total)*100),2);
            $resposta[$key]['porcentagem_frac'] =  round((($resposta[$key]['qtde_pecas_frac'] / $qtde_pecas_frac_total)*100),2);

            if($estabelecimento['qtde_pecas_frac'] > 0){
                $resposta[$key]['qtde_pecas_frac'] = parserValorInteiro($estabelecimento['qtde_pecas_frac']);
            }else{
                $resposta[$key]['qtde_pecas_frac'] = ' ';
            }
            
            if($estabelecimento['rolos'] > 0){
                $resposta[$key]['rolos']           = parserValorInteiro($estabelecimento['rolos']);
            }else{
                $resposta[$key]['rolos']           = ' ';
            }

        }


        if($rolos_final_total > 0){
            $rolos_final_total = parserValorInteiro($rolos_final_total);
        }else{
            $rolos_final_total = ' ';
        }

        if($metros_final_total > 0){
            $metros_final_total = parserValor($metros_final_total);
        }else{
            $metros_final_total = ' ';
        }

        if($peso_final_total > 0){
            $peso_final_total = parserValor($peso_final_total);
        }else{
            $peso_final_total = ' ';
        }

        if($outras_final_total > 0){
            $outras_final_total = parserValor($outras_final_total);
        }else{
            $outras_final_total = ' ';
        }

        if($qtde_pecas_frac_total > 0){
            $qtde_pecas_frac_total = parserValorInteiro($qtde_pecas_frac_total);
        }else{
            $qtde_pecas_frac_total = ' ';
        }

        if($metros_final_frac_total > 0){
            $metros_final_frac_total = parserValor($metros_final_frac_total);
        }else{
            $metros_final_frac_total = ' ';
        }

        if($outras_final_frac_total > 0){
            $outras_final_frac_total = parserValor($outras_final_frac_total);
        }else{
            $outras_final_frac_total = ' ';
        }

        if($peso_final_frac_total > 0){
            $peso_final_frac_total = parserValor($peso_final_frac_total);
        }else{
            $peso_final_frac_total = ' ';
        }


        $return = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'porcentagem_total' => $porcentagem_final_total,
                'porcentagem_frac_total' => $porcentagem_frac_final_total,
                'rolos_total' => $rolos_final_total,
                'metros_total' => $metros_final_total,
                'peso_total' => $peso_final_total,
                'outrasunidades_total' => $outras_final_total,
                'qtde_pecas_frac_total' => $qtde_pecas_frac_total,
                'metros_frac_total' => $metros_final_frac_total,
                'peso_frac_total' => $peso_final_frac_total,
                'outrasunidades_frac_total' => $outras_final_frac_total,             
                'dados' => $resposta
            ],
        ];

        return $return;
    }
  
    public function exibirPecasGeral(Request $request){

        set_time_limit(300);
        ini_set('memory_limit', '1024M');


        $fields = $request->only(['estabelecimento','grupo','subgrupo','linha','marca']);

        $empresas = returnEmpresasNasajonView();
        $nomeEstabel = $empresas[intval($fields['estabelecimento'])];

        $consultaPeças = FracoesDisponiveisNasajon::select('*');
        $consultaPeças->whereIn('grupodeinventario',['Mercadorias', 'Produtos Acabados ou Manufaturados', 'Produtos Intermediários']);

        if (!empty($fields['estabelecimento'])) {
            $consultaPeças->where('estabelecimento_codigo','=', $fields['estabelecimento']);
        }         
        
        if (
            (isset($fields['grupo']) && !empty($fields['grupo'])) ||
            (isset($fields['subgrupo']) && !empty($fields['subgrupo'])) ||
            (isset($fields['linha']) && !empty($fields['linha'])) ||
            (isset($fields['marca']) && !empty($fields['marca']))
        ) {
            $consultaPeças->with(['especificacao'=> function ($query) use ($fields) {
                if (!empty($fields['grupo'])) {
                    $query->whereHas('produtoGrupo', function ($query) use($fields){
                        $query->where('descricao', 'ilike', $fields['grupo']);
                    });
                }
                if (!empty($fields['subgrupo'])) {
                    $query->where('subgrupo', 'ilike', $fields['subgrupo']);
                }
                if (!empty($fields['linha'])) {
                    $query->where('linha', 'ilike', $fields['linha']);
                }
                if (!empty($fields['marca'])) {
                    $query->where('marca', 'ilike', $fields['marca']);
                }
                $query->where('ativo', true);
            }]);
        } 
        
        $resultado  = $consultaPeças->get();

        $retorno = [];
        foreach($resultado as $pecageral){   

            if (isset($pecageral->especificacao)) {

                $estabelecimento = str_pad($pecageral->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
                $produtocodigo = $pecageral->produto_codigo;

                $quantidade = $pecageral->saldo;
                $peca       = $pecageral->fracao_codigo;
                $pecapai    = $pecageral->fracao_pai;   
                $endereco   = $pecageral->endereco; 
                
                $retorno[] = [
                    'produtocodigo' => $produtocodigo,
                    'quantidade' => parserValor($quantidade),
                    'estabelecimento' => $nomeEstabel,
                    'peca' => $peca,
                    'pecapai' => $pecapai,
                    'endereco' => $endereco,
                ];        
                 
                 
            }
        
            
        }    
            
        return view("programs.pecas_fracionadas.modal.pecas")->with(['return' => $retorno]);

    }

    public function exibirPecasFracionadas(Request $request){

        set_time_limit(300);
        ini_set('memory_limit', '1024M');


        $fields = $request->only(['estabelecimento','grupo','subgrupo','linha','marca']);

        $empresas = returnEmpresasNasajonView();
        $nomeEstabel = $empresas[intval($fields['estabelecimento'])];

        $consultaPeças = FracoesDisponiveisNasajon::select('*');
        $consultaPeças->whereIn('grupodeinventario',['Mercadorias', 'Produtos Acabados ou Manufaturados', 'Produtos Intermediários']);
        $consultaPeças->where('peca_veio_de_fracionamento','=','true');

        if (!empty($fields['estabelecimento'])) {
            $consultaPeças->where('estabelecimento_codigo','=', $fields['estabelecimento']);
        }
        
        
        if (
            (isset($fields['grupo']) && !empty($fields['grupo'])) ||
            (isset($fields['subgrupo']) && !empty($fields['subgrupo'])) ||
            (isset($fields['linha']) && !empty($fields['linha'])) ||
            (isset($fields['marca']) && !empty($fields['marca']))
        ) {
            $consultaPeças->with(['especificacao'=> function ($query) use ($fields) {
                if (!empty($fields['grupo'])) {
                    $query->whereHas('produtoGrupo', function ($query) use($fields){
                        $query->where('descricao', 'ilike', $fields['grupo']);
                    });
                }
                if (!empty($fields['subgrupo'])) {
                    $query->where('subgrupo', 'ilike', $fields['subgrupo']);
                }
                if (!empty($fields['linha'])) {
                    $query->where('linha', 'ilike', $fields['linha']);
                }
                if (!empty($fields['marca'])) {
                    $query->where('marca', 'ilike', $fields['marca']);
                }
                $query->where('ativo', true);
            }]);
        } 
        
        $resultado  = $consultaPeças->get();

        $retorno = [];
        foreach($resultado as $pecageral){   

            if (isset($pecageral->especificacao)) {

                $estabelecimento = str_pad($pecageral->estabelecimento_codigo, 2, '0', STR_PAD_LEFT);
                $produtocodigo = $pecageral->produto_codigo;

                $quantidade = $pecageral->saldo;
                $peca       = $pecageral->fracao_codigo;
                $pecapai    = $pecageral->fracao_pai;   
                $endereco   = $pecageral->endereco; 
                
                $retorno[] = [
                    'produtocodigo' => $produtocodigo,
                    'quantidade' => parserValor($quantidade),
                    'estabelecimento' => $nomeEstabel,
                    'peca' => $peca,
                    'pecapai' => $pecapai,
                    'endereco' => $endereco,
                ];        
                 
                 
            }
        
            
        }    
            
        return view("programs.pecas_fracionadas.modal.pecas")->with(['return' => $retorno]);


    }

   
}
