<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ListaDePrecosRequest;

use App\AliquotaPreco;
use App\CepEstado;
use App\EstabelecimentoCidadeFob;
use App\MargemPrazo;
use App\Preco;
use App\Produto;
use App\ProdutoEspecificacao;
use App\Segmento;
use App\ProdutoNasajon;
use App\ProformaProduto;
use App\Proforma;
use App\ProformaArtigo;
use App\ProdutosEstoque;

use App\Http\Controllers\UserCamposSalvoController;

use Auth;

use Carbon\Carbon;
use PDF;

class NovaListaPrecosController extends Controller
{
    private $fator_diario_especial = 0.00267;
    private $grupos_especiais = [];

    private $valor_pis_cofins = 9.25;


    public function index(Request $request){

        if(Auth::user()->hasPermissionTo("programas App\NovaListaPrecos") === false){
            return abort(403);
        }

        $estadosObj = CepEstado::all()->toArray();

        $request->session()->flash('model', 'App\NovaListaPrecos');

        $segmentos =$this->segmentos();

        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        foreach ($origemObj as $value){
            

            $origem[$value->origem] = $value->origem_detalhe->estado; 
           
        }

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\NovaListaPrecos');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();

        return view('programs.nova_lista_precos.index')->with(['origem' => $origem, 'campos_salvos' => $campos_salvos, 'segmentos' => $segmentos]);
    }

    public function selectAliquotas(Request $request, $retorno_array = false){
        $origem = $request->input('origem');

        $aliquotaEstado = AliquotaPreco::with('estado_detalhe')
        ->where("origem", $origem)
        ->where('internacional', false)
        ->orderBy('estado')->get();

        $selectOpt = [];
        
        foreach ($aliquotaEstado as $value) {
            
           
             $selectOpt[] = [
                 'value' => $value->estado,
                 'html' => $value->estado_detalhe->estado,
                 'regiao' => in_array($value->estado_detalhe->regiao, ['NO', 'N'])?1:2
             ];
        }

        if($retorno_array){
            return $selectOpt;
        }

        return response()->json($selectOpt);

    }

    static public function exportarListas(Request $request, $use_auth = true, $salvar_pesquisa = true, $retornar_array = false, $aprovacao = false, $exportacao = false, $deletados = false, $olharEstoque = true){
                       
        $fields = $request->only(['origem', 'segmentos', 'grupo', 'produto', 'nome', 'marca', 'linha', 'aliquota', 'coluna', 'moeda', 'frete', 'estado', 'estabelecimento', 'tipo_cliente', 'prazo_medio', 'cidade', 'promocao', 'cliente', 'codigo_vendedor']);
  
        Carbon::setLocale('pt_BR');

        $inicio = Carbon::now();

        ini_set('pcre.backtrack_limit', 1000000000);
       
    
        $gruposObjReal = ProdutoEspecificacao::whereHas('estoque', function($query){$query->where('estoque', '>', 0);});
        // if(isset($fields['segmentos']) && !empty($fields['segmentos'])){
        //     $gruposObjReal->where('segmentos_id', '=', $fields['segmentos'] );
        // } 
        // if (isset($fields['marca']) && !empty($fields['marca'])) {
        //     $gruposObjReal->where('marca', 'ilike', '%' . $fields['marca'] . '%');
        // }

        // if (isset($fields['linha']) && !empty($fields['linha'])) {
        //     $gruposObjReal->where('linha', 'ilike', '%' . $fields['linha'] . '%');
        // }
        
        // if (isset($fields['grupo']) && !empty($fields['grupo'])) {
        //     $gruposObjReal->where('grupo', 'ilike', '%' . $fields['grupo'] . '%');
        // }

        // if (isset($fields['nome']) && !empty($fields['nome'])) {
        //     $gruposObjReal->where('descricao', 'ilike', '%' . $fields['nome'] . '%');
        // }

        $gruposObjReal->whereHas('preco', function($query){$query->where('preco_real', '>', 0);});
        $gruposObjReal->where('ativo','true');

        $gruposObjReal->get();
        $gruposObjReal->unique(function($item){return $item->marca.$item->linha.$item->grupo.$item->subgrupo.$item->preco->preco_real;});

        
    
        $gruposObjDolar = ProdutoEspecificacao::whereHas('estoque', function($query){
                $query->where('compras', '>', 0);
            })
            
            ->whereHas('preco', function($query){
                $query->where('preco_dolar', '>', 0);
            })
            ->where('ativo','true')
            ->get()
            
             ->unique(function($item){
                 return $item->marca.$item->linha.$item->grupo.$item->subgrupo.$item->preco->preco_dolar;
             });


            if($fields['coluna'] == 'coluna_a'){
                $mostracoluna='Comissão 3%';
                $comissao = ['coluna_a' => '3%'];
            }elseif($fields['coluna']=='coluna_b'){
               $mostracoluna='Comissão 4%'; 
               $comissao = ['coluna_b' => '4%'];
            }elseif($fields['coluna']=='coluna_c'){
               $mostracoluna ='Comissão 5%'; 
               $comissao     = ['coluna_c' => '5%'];
            }   
    
            $origens  = [$fields['origem']];
            $colunas  = [$fields['coluna']];
            $fretes   = [$fields['frete']];
            $grupo    = $fields['grupo'];
            $produto = $fields['produto'];
    
            $aliquotas = AliquotaPreco::select('origem', 'icms_venda', 'frete_adicional', DB::Raw('max(estado) as estado'))
            ->groupBy('origem', 'icms_venda', 'frete_adicional')
            ->get();
    
            $listaPrecosObj = new NovaListaPrecosController();
    
            foreach ($origens as $origem){
                foreach($aliquotas as $aliquota){
                    foreach ($colunas as $coluna){
                        foreach ($fretes as $frete){
    
                            if ($aliquota->origem == $origem &&!($origem == 'RO' && $aliquota->icms_venda != 4)){
                                $request = new ListaDePrecosRequest([
                                    'origem' => $origem,
                                    'segmentos' => $fields['segmentos'],
                                    'grupo' => $grupo,
                                    'marca' => $fields['marca'],
                                    'linha' => $fields['linha'],
                                    'nome' => $fields['nome'],
                                    //'produto' => $produto,
                                    'produto' => $gruposObjReal->pluck('codigo_produto'),
                                    'estado' => $aliquota->estado,
                                    'coluna' => $coluna,
                                    'moeda' => 'real',
                                    'frete' => $frete,
                                    'tipo_cliente' => 'normal'
                                ]);
        
                                $use_auth = false;
                                $salvar_pesquisa = false; 
                                $retornar_array = true; 
                                $aprovacao = true;
                                $exportacao = true;
                                $deletados = false;
                                $olharEstoque = true;
        
                                $titulo = 'Origem: ' . $origem . ' - ICMS: ' . $aliquota->icms_venda . '% - Comissão de ' . $comissao[$coluna] . ' - ' . strtoupper($frete) . ' - Frete de ' . $aliquota->frete_adicional . '%' . ($origem == 'RO'?' - Real':'');
        
                                $listas[] = [
                                    'dados' => $listaPrecosObj->filter($request, $use_auth, $salvar_pesquisa, $retornar_array, $aprovacao, $exportacao, $deletados, $olharEstoque),
                                    'filename' => storage_path() . '/app/public/listas_preco/' . $origem . '_' . $aliquota->icms_venda . '_' . $coluna . '_' . strtoupper($frete) . '_' . $aliquota->frete_adicional . ($origem == 'RO'?'_real':''). '.pdf',
                                    'titulo' => $titulo,
                                    'gerado' => Carbon::Now()->format('d/m/Y - H:i:s'),
                                ];
                    
        
                                if($origem == 'RO'){
    
                                    $request['tipo_cliente'] = 'isento';
    
                                    $aliquota_cliente_isento = AliquotaPreco::select('icms_venda_cliente_isento')
                                    ->where('origem', 'RO')
                                    ->where('estado', $aliquota->estado)
                                    ->where('frete_adicional', $aliquota->frete_adicional)
                                    ->first();
    
                                    $titulo = 'Origem: ' . $origem . ' - ICMS: ' . $aliquota_cliente_isento->icms_venda_cliente_isento . '% - Comissão de ' . $comissao[$coluna] . ' - ' . strtoupper($frete) . ' - Frete de ' . $aliquota->frete_adicional . '%' . ($origem == 'RO'?' - Real':'');
        
                                    $listas[] = [
                                        'dados' => $listaPrecosObj->filter($request, $use_auth, $salvar_pesquisa, $retornar_array, $aprovacao, $exportacao, $deletados, $olharEstoque),
                                        'filename' => storage_path() . '/app/public/listas_preco/' . $origem . '_' . $aliquota_cliente_isento->icms_venda_cliente_isento . '_' . $coluna . '_' . strtoupper($frete) . '_' . $aliquota->frete_adicional . ($origem == 'RO'?'_real':''). '.pdf',
                                        'titulo' => $titulo,
                                        'gerado' => Carbon::Now()->format('H:i:s - d/m/Y'),
                                    ];
        
                                    
    
                                    foreach(['isento', 'normal'] as $tipo_cliente){
                                        $request['moeda'] = 'dolar';
                                        $request['tipo_cliente'] = $tipo_cliente;
        
                                        $request['produto'] = $gruposObjDolar->pluck('codigo_produto');
            
                                        $titulo = 'Origem: ' . $origem . ' - ICMS: ' . $aliquota->icms_venda . '% - Comissão de ' . $comissao[$coluna] . ' - ' . strtoupper($frete) . ' - Frete de ' . $aliquota->frete_adicional . '%' . ($origem == 'RO'?' - Dólar':'');
            
                                        if($tipo_cliente == 'isento'){
                                            $icms = $aliquota_cliente_isento->icms_venda_cliente_isento;
                                        }
                                        else{
                                            $icms = $aliquota->icms_venda;
                                        }
    
                                        $listas[] = [
                                            'dados' => $listaPrecosObj->filter($request, $use_auth, $salvar_pesquisa, $retornar_array, $aprovacao, $exportacao, $deletados, $olharEstoque),
                                            'filename' => storage_path() . '/app/public/listas_preco/' . $origem . '_' . $icms . '_' . $coluna . '_' . strtoupper($frete) . '_' . $aliquota->frete_adicional . '.pdf',
                                            'titulo' => $titulo,
                                            'gerado' => Carbon::Now()->format('H:i:s - d/m/Y'),
                                        ];
            
                                       
                                    }
                                }
                            }
                        }
                    }
                }
            }
    
           
            foreach ($listas as $lista){

                //dd('lista'.$lista['dados']);

                $pdfFilePath = storage_path() . '/app/public/listas_preco/listagem_precos.pdf';
    
                $pdf = PDF::loadView('pdf.listagem_precos', ['lista' => $lista['dados'], 'titulo' => $lista['titulo'], 'gerado' => $lista['gerado']], [], ['title' => $lista['titulo'], 'custom_font_dir' => '', 'custom_font_data' => []] )->save($pdfFilePath);
    
               
    
            }
            
                    

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);      
        
        
    }

    public function filter(ListaDePrecosRequest $request, $use_auth = true, $salvar_pesquisa = true, $retornar_array = false, $aprovacao = false, $exportacao = false, $deletados = false, $olharEstoque = true){
        ini_set('memory_limit', '99999M');

        $fields = $request->only(['origem', 'segmentos', 'grupo', 'produto', 'nome', 'marca', 'linha', 'aliquota', 'coluna', 'moeda', 'frete', 'estado', 'estabelecimento', 'tipo_cliente', 'prazo_medio', 'cidade', 'promocao', 'cliente', 'codigo_vendedor']);
              
        $nacional = [0,3,4,5];
        $internacional = [1,2,6,7];
        
        if(isset($fields['estabelecimento']) && !is_null($fields['estabelecimento'])){

            $estabelecimento_origem = $fields['estabelecimento'];
            
            if($fields['estabelecimento'] == 3){
                $fields['origem'] = 'RO';
            }
            else if($fields['estabelecimento'] == 4){
                $fields['origem'] = 'TO';
            }
            else{
                $fields['origem'] = 'SP';
            }

        }

        else{

            if ($fields['origem'] == 'RO'){
                $estabelecimento_origem = 3;
                $fields['estabelecimento'] = '03';
            }

            else if ($fields['origem'] == 'TO'){
                $estabelecimento_origem = 4;
                $fields['estabelecimento'] = '04';
            }

            else{
                $estabelecimento_origem = 1;
                $fields['estabelecimento'] = '05';
            }

        }

                
        $aliquotaObj = AliquotaPreco::
            where('origem', $fields['origem'])
            ->where('estado', $fields['estado'])
            ->get();

        $margemPrazoObj = MargemPrazo::where('estabelecimento', $estabelecimento_origem)->first();

        $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::
            where('estabelecimento', str_pad($estabelecimento_origem, '2', '0', STR_PAD_LEFT))
            ->where('uf', $fields['estado'])
            ->where('cidade', utf8_encode($fields['cidade']??null))
            ->first();



        if ($use_auth === false && $aprovacao === false){
            $fields['coluna'] = 'coluna_a';
        }

        if ($salvar_pesquisa == 'true'){
            $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\NovaListaPrecos');
            $UserCamposSalvoControllerObj->salvarCampo('origem', $fields['origem']);
            $UserCamposSalvoControllerObj->salvarCampo('coluna', $fields['coluna']);
            $UserCamposSalvoControllerObj->salvarCampo('estado', $fields['estado']);
            $UserCamposSalvoControllerObj->salvarCampo('moeda', $fields['moeda']);
            $UserCamposSalvoControllerObj->salvarCampo('frete', $fields['frete']);
        }

        
        $produtoQuery = Preco::with('especificacoes.produtoGrupo', 'especificacoes', 'especificacoes.promocoes', 'especificacoes.promocoes.vendedor', 'produtoNasajon', 'custos');

        if($olharEstoque){
            $estoque = true;
            if(isset($fields['linha']) && (trim(strtolower($fields['linha'])) !== 'mao de obra' || strtolower($fields['linha']) !== 'insumo') ){
                $estoque = false;
            }

            if(isset($fields['grupo']) && (trim(strtolower($fields['grupo'])) !== 'desenho estamparia digital') ){
                $estoque = false;
            }

            if(isset($fields['segmentos']) && !empty($fields['segmentos'])){
                $produtoEspecificacao = ProdutoEspecificacao::whereHas('estoque', function($query){
                    $query->where('estoque', '>', 0);
                })->where('segmentos_id', '=', $fields['segmentos'] )->first();

                if(trim(strtolower($produtoEspecificacao->grupo)) !== 'desenho estamparia digital' ||
                    trim(strtolower($produtoEspecificacao->linha)) !== 'mao de obra' ||
                    strtolower($produtoEspecificacao->linha) !== 'insumo'){
                    
                    $estoque = false;
                }
            }

            if(isset($fields['nome']) && !empty($fields['nome'])){
                $produtoEspecificacao = ProdutoEspecificacao::where('descricao', 'ilike', '%' . $fields['nome'] . '%')->first();

                if(trim(strtolower($produtoEspecificacao->grupo)) !== 'desenho estamparia digital' ||
                    trim(strtolower($produtoEspecificacao->linha)) !== 'mao de obra' ||
                    strtolower($produtoEspecificacao->linha) !== 'insumo'){
                    
                    $estoque = false;
                }
            }

            if(isset($fields['produto']) && !empty($fields['produto'])){
                $produtoEspecificacao = ProdutoEspecificacao::where('codigo_produto', 'ilike', '%' . $fields['produto'] . '%')->first();

                if(trim(strtolower($produtoEspecificacao->grupo)) !== 'desenho estamparia digital' ||
                    trim(strtolower($produtoEspecificacao->linha)) !== 'mao de obra' ||
                    strtolower($produtoEspecificacao->linha) !== 'insumo'){
                    
                    $estoque = false;
                }
            }
            
            if($estoque){
                $produtoQuery->has('estoque');
            }
        }

       
        $produtoQuery->whereHas('estoque', function($query){$query->where('estoque', '>', 0);})
                     ->whereHas('especificacoes', function($query) use($fields, $deletados){
                        $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
            $query->where('ativo','true');

            if (isset($fields['marca']) && !empty($fields['marca'])) {
                $query->where('marca', 'ilike', '%' . $fields['marca'] . '%');
            }
    
            if (isset($fields['linha']) && !empty($fields['linha'])) {
                $query->where('linha', 'ilike', '%' . $fields['linha'] . '%');
            }
            
            if (isset($fields['grupo']) && !empty($fields['grupo'])) {
                $query->where('produto_grupos.descricao', 'ilike', '%' . $fields['grupo'] . '%');
            }
    
            if (isset($fields['nome']) && !empty($fields['nome'])) {
                $query->where('produto_especificacaos.descricao', 'ilike', '%' . $fields['nome'] . '%');
            }

            if (isset($fields['segmentos']) && !empty($fields['segmentos'])) {
                
                $query->where('segmentos_id', '=', $fields['segmentos'] );
            }            

            if($deletados != true){
                $query->where("produto_especificacaos.descricao", "not ilike", "%desativado%");
            }

        });

       // dd('entrou Gislene');

        if (!isset($fields['moeda']) || $fields['moeda'] != 'dolar'){
            $produtoQuery->where('preco_real', '>', '0');
        }

         if($exportacao === true){
             $produtoQuery->whereIn('codigo_produto', $fields['produto']);
         }
         else{

            if (isset($fields['produto']) && !empty($fields['produto'])) {
                $produtoQuery->where('codigo_produto', 'ilike', '%' . $fields['produto'] . '%');
            }
            
        }

        $produtosObj = $produtoQuery->get();
          
       

        // if($exportacao === true){
        //     $produtosObj = $produtosObj->sortBy(function($produtos, $key){
        //         return $produtos->especificacoes->grupo.$produtos->especificacoes->linha;
        //     });
        // }

        $resultado = [];

        $tipo_cliente = $fields['tipo_cliente'] ?? '';
        

        foreach ($produtosObj as $value) {
            

            if ($deletados == false){

                $coluna_comissoes_pedido = [];

                if(
                    isset($value->especificacoes->promocoes) &&
                    !is_null($value->especificacoes->promocoes) &&
                    (
                        isset($fields['promocao']) &&
                        $fields['promocao'] === true
                    )
                ){
                    $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                        if (
                            intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                            $item->codigo_cliente == $fields['cliente'] &&
                            isset($item->grupo) &&
                            $item->codigo_produto == $fields['produto'] &&
                            strtolower($item->tipo_frete) == strtolower($fields['frete'])
                        ){
                            return true;
                        }else{
                            return false;
                        }
                    });
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                $item->codigo_cliente == $fields['cliente'] &&
                                isset($item->grupo) &&
                                is_null($item->codigo_vendedor) &&
                                is_null($item->tipo_frete) &&
                                is_null($item->codigo_produto)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                $item->codigo_cliente == $fields['cliente'] &&
                                isset($item->grupo) &&
                                $item->codigo_produto == $fields['produto'] &&
                                is_null($item->codigo_vendedor) &&
                                is_null($item->tipo_frete)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                $item->codigo_cliente == $fields['cliente'] &&
                                isset($item->grupo) &&
                                $item->codigo_produto == $fields['produto'] &&
                                is_null($item->codigo_vendedor) &&
                                strtolower($item->tipo_frete) == strtolower($fields['frete'])
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                $item->codigo_cliente == $fields['cliente'] &&
                                is_null($item->codigo_produto) &&
                                is_null($item->codigo_vendedor) &&
                                strtolower($item->tipo_frete) == strtolower($fields['frete'])
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                (isset($item->vendedor) && $item->vendedor->codigo_representante == $fields['codigo_vendedor']) &&
                                isset($item->grupo) &&
                                is_null($item->codigo_cliente) &&
                                is_null($item->codigo_produto) &&
                                is_null($item->tipo_frete)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                (isset($item->vendedor) && $item->vendedor->codigo_representante == $fields['codigo_vendedor']) &&
                                isset($item->grupo) &&
                                $item->codigo_produto == $fields['produto'] &&
                                is_null($item->codigo_cliente) &&
                                is_null($item->tipo_frete)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                (isset($item->vendedor) && $item->vendedor->codigo_representante == $fields['codigo_vendedor']) &&
                                isset($item->grupo) &&
                                $item->codigo_produto == $fields['produto'] &&
                                is_null($item->codigo_cliente) &&
                                strtolower($item->tipo_frete) == strtolower($fields['frete'])
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                isset($item->grupo) &&
                                is_null($item->codigo_vendedor) &&
                                $item->codigo_produto == $fields['produto'] &&
                                is_null($item->codigo_cliente) &&
                                is_null($item->tipo_frete)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = $value->especificacoes->promocoes->search(function($item) use ($fields){
                            if (
                                intval($item->codigo_estabelecimento) == intval($fields['estabelecimento']) &&
                                isset($item->grupo) &&
                                is_null($item->codigo_vendedor) &&
                                is_null($item->codigo_produto) &&
                                is_null($item->codigo_cliente) &&
                                is_null($item->tipo_frete)
                            ){
                                return true;
                            }else{
                                return false;
                            }
                        });
                    }
                    if($promossao === false){
                        $promossao = null;
                    }
                }
                if ($fields['moeda'] == 'dolar'){
                    if(isset($promossao) && !is_null($promossao)){
                        if(!empty($value->especificacoes->promocoes[$promossao]->preco_dolar)){
                            $valor_base = $value->especificacoes->promocoes[$promossao]->preco_dolar;
                        }
                        else if(!empty($value->especificacoes->promocoes[$promossao]->desconto_porcentagem)){
                            $valor_base = $value->preco_dolar * (1 - ($value->especificacoes->promocoes[$promossao]->desconto_porcentagem/100));
                        }
                        else{
                            $valor_base = $value->preco_dolar;
                        }
                    }
                    else{
                        $valor_base = $value->preco_dolar;
                    }
                    $icms_base = 4;
                }
                else{
                    if (in_array($value->especificacoes->procedencia, $nacional)){

                        if(isset($promossao) && !is_null($promossao)){

                            if(!empty($value->especificacoes->promocoes[$promossao]->preco_real)){
                                $valor_base = $value->especificacoes->promocoes[$promossao]->preco_real;
                            }
                            else if(!empty($value->especificacoes->promocoes[$promossao]->desconto_porcentagem)){
                                $valor_base = $value->preco_real * (1 - ($value->especificacoes->promocoes[$promossao]->desconto_porcentagem/100));
                            }
                            else{
                                $valor_base = $value->preco_real;
                            }
    
                        }
                        else{
                            $valor_base = $value->preco_real;
                        }

                        $icms_base = 12;

                    }
                    else if (in_array(($value->especificacoes->procedencia), $internacional)){
                        
                        if(isset($promossao) && !is_null($promossao)){
                            if(!empty($value->especificacoes->promocoes[$promossao]->preco_real)){
                                $valor_base = $value->especificacoes->promocoes[$promossao]->preco_real;
                            }
                            else if(!empty($value->especificacoes->promocoes[$promossao]->desconto_porcentagem)){
                                $valor_base = $value->preco_real * (1 - ($value->especificacoes->promocoes[$promossao]->desconto_porcentagem/100));
                            }
                            else{
                                $valor_base = $value->preco_real;
                            }
                        }
                        else{
                            $valor_base = $value->preco_real;
                        }

                        $icms_base = 4;

                    }
                }

                $estabelecimento_verificação = !empty($fields['estabelecimento'])? $fields['estabelecimento'] : $estabelecimento_origem;

                $custo = $value->estoque->where('estabelecimento', str_pad($estabelecimento_verificação, 2, "0", STR_PAD_LEFT))->first();
				$custo_portal = $value->custos->where('estabelecimento', str_pad($estabelecimento_verificação, 2, "0", STR_PAD_LEFT))->first();
				if(empty($custo) && empty($custo_portal)){
					$custo = $value->preco_real / 1.43;
				}else if(!empty($custo) && !empty($custo_portal)){
					if($custo->custo > $custo_portal->custo_medio_contabil){
						$custo = $custo->custo;
					}else if(empty($custo->custo) && empty($custo_portal->custo_medio_contabil)){
						$custo = $value->preco_real / 1.43;
					}else{
						$custo = $custo_portal->custo_medio_contabil;
					}
				}else if(!empty($custo) && empty($custo_portal)){
					$custo = empty($custo->custo)? $value->preco_real / 1.43 : $custo->custo;
				}else if(empty($custo) && !empty($custo_portal)){
					$custo = empty($custo_portal->custo_medio_contabil)? $value->preco_real / 1.43 : $custo_portal->custo_medio_contabil;
				}else{
					$custo = $custo->custo;
				}

                /*switch(intval($estabelecimento_verificação)){
                    case 3:
                        $custo = $custo / 0.96;
                    break;
                    case 4:
                        if(in_array($value->especificacoes->procedencia, [0, 3, 4, 5])){
                            $custo = $custo / 0.88;
                        }else{
                            $custo = $custo / 0.96;
                        }
                    break;
                    default:
                        switch($fields['estado']){
                            case 'TO':
                            case 'RO':
                                $custo = $custo / 0.93;
                            break;
                            default:
                                $custo = $custo / 0.82;
                            break;
                        }
                    break;
                }*/

                if (!empty($valor_base)){
                    $valor_base = (float) $valor_base;
                    $colunas_exibicao = [];
                    $aliquota_key = $aliquotaObj->search( function ($item) use ($nacional, $internacional, $value) {
                    
                        if(in_array($value->especificacoes->procedencia, $nacional)){
                            return $item->internacional === false;
                        }
                        else if(in_array($value->especificacoes->procedencia, $internacional)){
                            return $item->internacional === true;
                        }

                    });
                    $valor_frete = 1 + (strtolower($fields['frete']) == 'cif' ? $aliquotaObj[$aliquota_key]->frete_adicional / 100 : 0);
                    $valor_icms = ($tipo_cliente == 'isento' ? $aliquotaObj[$aliquota_key]->icms_venda_cliente_isento : $aliquotaObj[$aliquota_key]->icms_venda);

                    if($valor_icms != 4 && $valor_icms < 12){
                        $valor_icms = 12;
                    }

                    $valor_icms_base = 1 - ($icms_base / 100);


                    $valor_dif_base = $valor_base - ($valor_base * $valor_icms_base);
                    $valor_dif_pis_cofins = $valor_base - ($valor_base * (1 - ($this->valor_pis_cofins / 100)));

                    $base_nova = $valor_base - ($valor_dif_base + $valor_dif_pis_cofins);

                    $valor = 0;
                    $valor_diff = (100 - ($valor_icms + $this->valor_pis_cofins)) / 100;

                    $valor = $base_nova / $valor_diff;
                    
                    $valor_ipi = 1;

                    if($estabelecimento_origem != 3){
                        if(empty($value->produtoNasajon)){
                            continue;
                        }
                        $valor_ipi += $value->produtoNasajon->ipi / 100;
                    }

                    $valor_fatorado = ($valor * $valor_frete) * $valor_ipi;


                    if(!in_array($value->especificacoes->grupo, $this->grupos_especiais)){
                        $prazos = [
                            'prazo_vista' => $valor_fatorado,
                            'prazo_15' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 15)),
                            'prazo_30' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 30)),
                            'prazo_45' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 45)),
                            'prazo_60' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 60)),
                            'prazo_75' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 75)),
                            'prazo_90' => $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * 90)),
                        ];

                        if (isset($fields['prazo_medio']) && strlen($fields['prazo_medio']) > 0 ){
                            $prazos['prazo_' . $fields['prazo_medio']] = $valor_fatorado * (1 + ($margemPrazoObj->fator_diario * $fields['prazo_medio']));
                        }
                    }else{
                        $prazos = [
                            'prazo_vista' => $valor_fatorado,
                            'prazo_15' => $valor_fatorado * (1 + ($this->fator_diario_especial * 15)),
                            'prazo_30' => $valor_fatorado * (1 + ($this->fator_diario_especial * 30)),
                            'prazo_45' => $valor_fatorado * (1 + ($this->fator_diario_especial * 45)),
                            'prazo_60' => $valor_fatorado * (1 + ($this->fator_diario_especial * 60)),
                            'prazo_75' => $valor_fatorado * (1 + ($this->fator_diario_especial * 75)),
                            'prazo_90' => $valor_fatorado * (1 + ($this->fator_diario_especial * 90)),
                        ];

                        if (isset($fields['prazo_medio']) && strlen($fields['prazo_medio']) > 0 ){
                            $prazos['prazo_' . $fields['prazo_medio']] = $valor_fatorado * (1 + ($this->fator_diario_especial * $fields['prazo_medio']));
                        }
                    }

                    foreach ($prazos as $k => $v) {

                        $coluna_a = $v;
                        $coluna_b = $coluna_a * $margemPrazoObj->preco_b;
                        $coluna_c = $coluna_b * $margemPrazoObj->preco_c;

                        $precos[$k] = [
                            'coluna_a' => ($coluna_a),
                            'coluna_b' => ($coluna_b),
                            'coluna_c' => ($coluna_c),
                        ];

                        $coluna_comissoes_pedido[$k][0] = $precos[$k]['coluna_a'];

                        for($x = 1; $x <= 15; $x++){
                            $coluna_comissoes_pedido[$k][$x] = $coluna_comissoes_pedido[$k][$x-1] * $margemPrazoObj->preco_b;
                        }
                        
                    }

                    if (isset($fields['coluna']) && !empty($fields['coluna'])){
            
                        if (strpos($fields['coluna'], 'coluna_') !== false){
                            $colunas_exibicao = [
                                'coluna_a' => '',
                                'coluna_b' => '',
                                'coluna_c' => '',
                                'prazo_vista' => parserValor(round($precos['prazo_vista'][$fields['coluna']]*100)/100),
                                'prazo_15' => parserValor(round($precos['prazo_15'][$fields['coluna']]*100)/100),
                                'prazo_30' => parserValor(round($precos['prazo_30'][$fields['coluna']]*100)/100),
                                'prazo_45' => parserValor(round($precos['prazo_45'][$fields['coluna']]*100)/100),
                                'prazo_60' => parserValor(round($precos['prazo_60'][$fields['coluna']]*100)/100),
                                'prazo_75' => parserValor(round($precos['prazo_75'][$fields['coluna']]*100)/100),
                                'prazo_90' => parserValor(round($precos['prazo_90'][$fields['coluna']]*100)/100),
                            ];

                        }
                        else if (strpos($fields['coluna'], 'prazo_') !== false){
                            $colunas_exibicao = [
                                'coluna_a' => parserValor(round($precos[$fields['coluna']]['coluna_a']*100)/100),
                                'coluna_b' => parserValor(round($precos[$fields['coluna']]['coluna_b']*100)/100),
                                'coluna_c' => parserValor(round($precos[$fields['coluna']]['coluna_c']*100)/100),
                                'prazo_vista' => '',
                                'prazo_15' => '',
                                'prazo_30' => '',
                                'prazo_45' => '',
                                'prazo_60' => '',
                                'prazo_75' => '',
                                'prazo_90' => '',
                            ];



                        }

                    }
                    else if (isset($fields['prazo_medio']) && strlen($fields['prazo_medio']) > 0){
                        
                        $colunas_exibicao = [
                            'coluna_a' => parserValor($precos['prazo_' . $fields['prazo_medio']]['coluna_a']),
                            'coluna_b' => parserValor($precos['prazo_' . $fields['prazo_medio']]['coluna_b']),
                            'coluna_c' => parserValor($precos['prazo_' . $fields['prazo_medio']]['coluna_c']),
                        ];

                        if($retornar_array){
                            foreach($coluna_comissoes_pedido['prazo_' . $fields['prazo_medio']] as $coluna => $faixa_comissao){
                                $colunas_exibicao[$coluna] = round($faixa_comissao*100)/100;
                            }
                        }
                    }

                    if($retornar_array){
                        if (isset($fields['coluna']) && strpos($fields['coluna'], 'prazo_') !== false){

                            foreach($coluna_comissoes_pedido['prazo_' . $fields['prazo_medio']] as $coluna => $faixa_comissao){
                                $colunas_exibicao[$coluna] = round($faixa_comissao*100)/100;
                            }
                        
                        }

                    }
                    $promossao_return = false;
                    if(isset($promossao) && !is_null($promossao)){
                        $promossao_return = [ 'comissao' => $value->especificacoes->promocoes[$promossao]['comissao'], 'sem_desconto_adicional' => $value->especificacoes->promocoes[$promossao]['sem_desconto_adicional'] ];
                    }
                    $resultado[] = array_merge([
                        'marca' => ($value->especificacoes->marca),
                        'linha' => ($value->especificacoes->linha),
                        'grupo' => ($value->especificacoes->grupo), 
                        'cod_produto' => ($value->codigo_produto),
                        'nome' => ($value->especificacoes->descricao),
                        'subgrupo' => ($value->especificacoes->subgrupo),
                        'gramatura' => $value->especificacoes->produtoGrupo->gramatura_gm2??'',
                        'largura' => $value->especificacoes->produtoGrupo->largura??'',
                        'unidade' => ($value->especificacoes->unidade),
                        'composicao' => !is_null($value->produtoNasajon)?$value->produtoNasajon->composicao:'',
                        'promocional' => $promossao_return,
                        'custo' => parserValor($custo),
                    ], $colunas_exibicao);


                    unset($colunas_exibicao, $valor_base);
                }
            }
        }

        if($retornar_array === true){
            return $resultado;
        }
        else{
            return response()->json($resultado);
        }

    }

   

  

    public function segmentos(){

        $segmentos = [];
  
        $segmentos = Segmento::all()->pluck('descricao', 'id');
               
        return  $segmentos;
    }
}
