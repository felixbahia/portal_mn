<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

use App\User;
use App\PagamentoPixNasajon;
use App\ClienteNasajon;
use App\PedidoPortal;
use App\PedidoFormaPagamentoNasajon;
use App\FinancasTitulosNasajon;
use App\PedidosVendaNasajon;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class PagamentoPixNasajonController extends Controller
{
   
    public function index(Request $request) {
    
        if(Auth::user()->hasPermissionTo("programas App\PagamentoPixNasajon") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PagamentoPixNasajon');

        $statusPedido = [
            'todos' => 'Todos',
			'encerrados' => 'Encerrados'

		];

               
        return view('programs.pagamento_pix.index')->with(['statusPedido' => $statusPedido]);
    }
    
   
    public function filterPagamentoPix(Request $request){
           
        $fields       = $request->only(['cliente_nome','data_inicio','data_fim','statusPedido']);  

        $cliente_nome = $fields['cliente_nome'];
        
        $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d 00:00:00');
        $data_final   = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d 24:00:00');

        $query = PagamentoPixNasajon::select('pix_id','pix_valor','pix_horario','pix_datainsercao', 'pix_idpessoa','pix_idtitulo',
        'pix_dataatualizacao','titulo_numero', 'pixpagador_cnpj','pixpagador_cpf','pixpagador_nome','pix_chavepix');
        $query->whereBetween('pix_datainsercao',[$data_inicial,$data_final]);

        if(!empty($cliente_nome)){     
            $query->with(['cliente' => function($query) use($cliente_nome){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome));
            }]);

            $query->whereHas('cliente', function ($query) use($cliente_nome){
                $query->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome)); });
        }
        
        //pix_situacao- Situação do pix, as quais podem ser 'PENDENTE DE PARTICIPANTE', 'PENDENTE DE TÍTULO' e 'SEM PENDÊNCIAS'
       

        
        if(!empty($fields['statusPedido'])){
            $statusPedido = $fields['statusPedido'];  
            
            if($statusPedido == 'encerrados'){
                $query->where('pix_situacao','SEM PENDÊNCIAS');
            }else{
                $query->whereIn('pix_situacao',['PENDENTE DE PARTICIPANTE','PENDENTE DE TÍTULO','SEM PENDÊNCIAS']);
            }
			
		}else{

            $statusPedido = 'emaberto';
            $query->whereIn('pix_situacao',['PENDENTE DE PARTICIPANTE','PENDENTE DE TÍTULO']);
        }

        
        $total = ['valor' => 0,
                 'auto' => 0,
                 'valorauto' => 0,
                 'valormanual' => 0,
                 'manual' => 0
                 ];

        $teste = 0;
    
        $result = $query->get(); 
              
        $dadosPix    = [];
               
        foreach($result as $pagamentoPix){
                        
            $pixid         = $pagamentoPix->pix_id;     
            $valor         = $pagamentoPix->pix_valor;  
            $datainsercao  = parserDataHoraSegundo($pagamentoPix->pix_datainsercao);  
            $datadeposito  = parserDataHoraSegundo($pagamentoPix->pix_horario);     

            $conteudoChave = " ";
            if(!empty($pagamentoPix->pix_chavepix)){
                $conteudoChave = $pagamentoPix->pix_chavepix;
            }else{
                $conteudoChave = "SEM CHAVE";
            }
            
            if(!empty($pagamentoPix->pixpagador_cpf)){
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
            }else{
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
                
            }

            if(!empty($pagamentoPix->pixpagador_nome)){
                $depositanteNome      = $pagamentoPix->pixpagador_nome;                
            }else{
                $depositanteNome      = ' ';
                $depositanteDocumento = ' ';

            } 
            
            if(!empty($pagamentoPix->titulo_numero)){
                $titulo                 = $pagamentoPix->titulo_numero; 
                $mostrar_botao_aprovar  = false;
                $mostrar_botao_check    = true;

                $pix_idtitulo           = $pagamentoPix->pix_idtitulo;

                $queryObs = FinancasTitulosNasajon::select('*')->where('id', $pix_idtitulo)->get();
                foreach($queryObs as $obsResult){
                    $tituloObs = $obsResult->observacao;
                } 

            }else{
                $titulo                 = ' ';
                $mostrar_botao_aprovar  = true;
                $mostrar_botao_check    = false;
                $tituloObs              = " ";

            }


            if(!empty($pagamentoPix->cliente->nome)){
                $clienteNome            = $pagamentoPix->cliente->nome;
                $cpf_cnpj               = $pagamentoPix->cliente->cpf_cnpj;
                $clienteNome            = $clienteNome .'   CNPJ/CPF: '.$cpf_cnpj;                   

            }else{
                $clienteNome            = ' ';                
            }  
            
            $depositanteNome = $depositanteNome.'-'.$depositanteDocumento;

            //$tituloObs            = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($tituloObs) .  "'>". ($tituloObs) ."</div></div>";
            $tituloObs            = $tituloObs;
            $clienteNome          = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($clienteNome) . "''>". ($clienteNome) ."</div></div>";
            $depositanteNome      = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($depositanteNome) . "''>". ($depositanteNome) ."</div></div>";
            $depositanteDocumento = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($depositanteDocumento) . "''>". ($depositanteDocumento) ."</div></div>";
            

            if(strpos($tituloObs, 'Automático') !== false) {
               $qtdeAuto    = 1;
               $qtdeManual  = 0;
               $valorAuto   = $valor;
               $valorManual = 0;
            }else{
                $qtdeAuto    = 0;
                $qtdeManual  = 1;
                $valorAuto   = 0;
                $valorManual = $valor;

            }

            if($statusPedido == 'emaberto'){

                $qtdeAuto    = 0;
                $qtdeManual  = 0;
                $valorAuto   = 0;
                $valorManual = 0;


            }
                                              
            $dadosPix[] = [
                'id' => $pixid,
                'chave' => $conteudoChave,
                'depositanteDocumento' => $depositanteDocumento,
                'depositanteNome' => $depositanteNome,
                'statusPedido' => $statusPedido,
                'cliente_nome' => $clienteNome,
                'titulo' => $titulo,
                'tituloObs' => $tituloObs,
                'valorTotal' => $valor,
                'valor' => parserValor($valor),
                'datadeposito' => $datadeposito,
                'datainsercao' => $datainsercao,
                'mostrar_botao_aprovar' => $mostrar_botao_aprovar,
                'mostrar_botao_check' => $mostrar_botao_check,
                'valorautoTotal' => $valorAuto,
                'valormanualTotal' => $valorManual,
                'autoTotal' => $qtdeAuto,
                'manualTotal' => $qtdeManual
            ];
        
        }   
        
                
        foreach($dadosPix as $value){   
            $total['valor'] += $value['valorTotal'];  
            $total['auto'] += $value['autoTotal']; 
            $total['manual'] += $value['manualTotal']; 
            $total['valorauto'] += $value['valorautoTotal'];
            $total['valormanual'] += $value['valormanualTotal'];

        }
       
        $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : '';
        $total['valorauto'] = ($total['valorauto'] > 0) ? parserValor($total['valorauto']) : '';
        $total['valormanual'] = ($total['valormanual'] > 0) ? parserValor($total['valormanual']) : '';


        
        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ['saida' => $dadosPix, 'total' => $total]
        ];

    return response()->json($retorno); 
    

    

    }


    public function modalPedidoAssociarPix(Request $request){
        $origem = 'nasajon';

        $id_pix = $request->pix_id;

        $query = PagamentoPixNasajon::select('*');
        $query->where('pix_id',$id_pix);

        $result = $query->get();

        $retorno          = [];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $pagamentoPix){
            $valorPix        = $pagamentoPix->pix_valor;
            $depositanteNome = $pagamentoPix->pixpagador_nome;

            $pixpagadorid  = '';

            if(!empty($pagamentoPix->pixpagador_cpf)){
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
            }else{
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
                
            }

            $TituloPix = 'DEPOSITANTE : '.$depositanteNome.' -> VALOR : '.parserValor($valorPix);

            if(!empty($pagamentoPix->pixpagador_id)){
                $pixpagadorid         = $pagamentoPix->pixpagador_id;
            }

            $query = PedidoPortal::with(['cliente','estabelecimentoDetalhes','usuario_detalhes']);
            $query->whereIn('status_pedido', ['9','11']);
            $query->where('condicao_pagamento','=', '4247');
            $query->where('valor_total_nota', '>=', $valorPix); 
            
            $resultPedido = $query->get();

            foreach($resultPedido as $pedidoQuery){
                $status_pedido          = $pedidoQuery->status_pedido;

                $numeroPedido           = $pedidoQuery->id;  
                $estabelecimento        = $estabelecimentos[(integer)$pedidoQuery->estabelecimento];
                $estabelecimentopad     = $pedidoQuery->estabelecimento_pad; 
                $codigooperacao         = $pedidoQuery->codigo_operacao; 
                $pedidoNasajon          = ' ';
                $codcliente             = $pedidoQuery->cod_cliente;
                $status_pedido          = $pedidoQuery->status_pedido;

                $clienteNasajon         = '';
                $cpfcnpj                = '';

                $pedidoNasajon          = $pedidoQuery->pedido_gerado;
                if(!empty($pedidoQuery->cliente->id)){
                    $clienteNasajon         = $pedidoQuery->cliente->id;
                    $cpfcnpj                = $pedidoQuery->cliente->cpf_cnpj;
                    $clienteDocumento       = $pedidoQuery->cliente->nome .' - '. $cpfcnpj;
                }else{
                    $clienteDocumento          = $pedidoQuery->nome_comprador .' - '. $cpfcnpj;

                }

                        
                if(!empty($pedidoQuery->usuario_detalhes->supervisor->name)){
                    $gerente                = $pedidoQuery->usuario_detalhes->supervisor->name;
                }else{
                    $gerente                = " ";
                }                
                
                $estabelecimentoNasajon    = $pedidoQuery->estabelecimentoDetalhes->estabelecimento;
                
                $vendedorDados             = $pedidoQuery->usuario_detalhes->codigo_representante . ' - ' . $pedidoQuery->usuario_detalhes->name;
                
                $retorno[] = [
                    'idPix' => $id_pix, 
                    'numeroPedido' => $numeroPedido,
                    'pedidoNasajon' => $pedidoNasajon,
                    'estabelecimento' => $estabelecimento,
                    'estabelecimentoNasajon' => $estabelecimentoNasajon,
                    'clienteNasajon' => $clienteNasajon,
                    'cliente' => $clienteDocumento,
                    'cpfcnpj' => $cpfcnpj,
                    'valor' => parserValor($pedidoQuery->valor_total_nota),
                    'data' => parserData($pedidoQuery->data_pedido),
                    'gerente' => $gerente,
                    'vendedor' => $vendedorDados,
                    'origem' => $origem,
                ];  
            }  

            
            $queryNasa = PedidosVendaNasajon::with(['pedido_portal.estabelecimentoDetalhes','formaPagamento']);
            $queryNasa->whereIn('situacao_descricao', ['Aberto','Em Faturamento','Em separação'])->where('grupodeoperacao_pedido','=','VENDA');
            $queryNasa->where('rascunho','=', false);
            $queryNasa->where('valor', '>=', $valorPix); 
            $queryNasa->where(function($query){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhereNull('grupodeoperacao');
            });
           
            $resultPedido = $queryNasa->get();

            foreach($resultPedido as $pedidoQuery){

                if($pedidoQuery->formaPagamento['formapagamento_descricao'] == 'Usar Crédito'){
                

                    if(!isset($pedidoQuery->pedido_portal->id)){                            
                        $erro = $pedidoQuery->numero;
                        $numeroPedido = " ";
                        $data_pedido = ' ';
                        $gerente     = " ";
                    }

                    
                                        
                    
                    if(!empty($pedidoQuery->pedido_portal->usuario)){
                        $numeroPedido = $pedidoQuery->pedido_portal->id;
                        $data_pedido = parserData($pedidoQuery->pedido_portal->data_pedido);

                        if(!empty($pedidoQuery->pedido_portal->usuario)){
                            $queryUsu = User::select('*')->where('id', $pedidoQuery->pedido_portal->usuario)->with(['supervisor'])->get();
                            foreach($queryUsu as $usuResult){
                                if(!empty($usuResult->name)){
                                    $gerente                = $usuResult->name;
                                }                
                            }
                        } 
                    }               

                    $estabelecimentoNasajon = $pedidoQuery->estabelecimento;
                    $estabelecimento        = $estabelecimentos[(integer)$pedidoQuery->estabelecimento_codigo];
                    $clienteNasajon         = $pedidoQuery->cliente;
                    $cliente                = $pedidoQuery->cliente_razaosocial;
                    $clienteDoc             = $pedidoQuery->cliente_cnpj;
                    
                    $clienteDocumento          = $cliente .' - '. $clienteDoc;
                    $vendedorDados             = $pedidoQuery->vendedor_codigo . ' - ' . $pedidoQuery->vendedor_nome;

                  
                    

                    $retorno[] = [
                        'idPix' => $id_pix, 
                        'numeroPedido' => $numeroPedido,
                        'pedidoNasajon' => $pedidoQuery->numero,
                        'estabelecimento' => $estabelecimento,
                        'estabelecimentoNasajon' => $estabelecimentoNasajon,
                        'clienteNasajon' => $clienteNasajon,
                        'cliente' => $clienteDocumento,
                        'cpfcnpj' => $clienteDoc,
                        'valor' => parserValor($pedidoQuery->valor),
                        'data' => $data_pedido,
                        'gerente' => $gerente,
                        'vendedor' => $vendedorDados,
                        'origem' => $origem,
                    ];  
                
                }         
          
            }

        }       
       
       
        return view("programs.pagamento_pix.modal.pedido")->with(['return' => $retorno,'id' => $id_pix,'titulo' => $TituloPix]);
    
    }         
    
    public function criaCredito(Request $request){

        $fields       = $request->only(['pix_id','cpfcnpj','pedidoPortal','pedidoNasajon','valor','estabelecimento','clienteNasajon']);          
        
        $pix_id                          = $fields['pix_id'];
        $cpfcnpj                         = $fields['cpfcnpj'];
        $pedidoPortal                    = $fields['pedidoPortal'];
        $pedidoNasajon                   = $fields['pedidoNasajon'];
        $credito                         = $fields['valor'];
        $estabelecimento_uuid_nasajon    = $fields['estabelecimento'];
        $cliente_uuid_nasajon            = $fields['clienteNasajon'];
        $data_emissao                    = Carbon::now(); 
        $data_vencimento                 = Carbon::now()->addYear();
        $observacao                      = '';

        if(empty($pedidoNasajon)){
            $numero_titulo = $pedidoPortal; 
        }else{
            $numero_titulo = $pedidoNasajon;
        }

        $this->incluirPixPedido($pix_id, $pedidoPortal);

        $mensagem_retorno = $this->associaPix($pix_id, $numero_titulo, $cliente_uuid_nasajon, $observacao);

        return response()->json(
            [
                'status' => 'success',
                'message' => 'Credito cadastrado com sucesso!',
                'error' => [],
                'response' => []
            ], 220
        );

        
    
    } 
    
    public function associaPix($pix_id, $numero_titulo, $cliente_uuid_nasajon, $observacao){

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;

        $usuario_nome = Auth::user()->name;

        $data_obs     = Carbon::now()->format('d/m/Y H:i');

        if(empty($observacao)){
            $observacao_credito = "Título de Crédito Gerado pelo Sistema Pix, Usuário: ".$usuario_nome." Data Hora: ".$data_obs." Pedido: ".$numero_titulo;

        }else{
            $observacao_credito = $observacao. " Usuário: ".$usuario_nome." Data Hora: ".$data_obs;
        }
              

        $numerotitulo = $numero_titulo.'1CRD';

        $sql_associa_titulo = "select * from integracoes.api_vincularparticipantepix(
            '".$pix_id."',
            '".$numerotitulo."',
            '".$cliente_uuid_nasajon."',
            '".$usuario_cadastro_uuid."',
            '".$observacao_credito."'

        );";      
              

        try{
            $sql_nasajon = DB::connection('nasajon')->select($sql_associa_titulo);
            $mensagem_nasajon = $sql_nasajon[0]->mensagem;
            $mensagem_nasajon = json_decode($mensagem_nasajon, true);

            $mensagem_retorno = $mensagem_nasajon['mensagem'];

            return $mensagem_retorno;        

        }catch(\Exception $e){
            return  response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$e],
                'response' => []
            ], 422);
        }
    }

public function criarTituloCreditoPix(){

    $queryCria = PagamentoPixNasajon::select('pix_id','pix_valor','pix_horario','pix_datainsercao', 'pix_idpessoa','pix_idtitulo',
    'pix_dataatualizacao','titulo_numero', 'pixpagador_cnpj','pixpagador_cpf','pixpagador_nome','pixpagador_id');
    $queryCria->whereIn('pix_situacao',['PENDENTE DE PARTICIPANTE','PENDENTE DE TÍTULO']);
    $queryCria->orderBy('pix_dataatualizacao', 'desc');

    $result = $queryCria->get(); 

    foreach($result as $pagamentoPix){
        
        $pix_id        = $pagamentoPix->pix_id;     
        $valorPix      = $pagamentoPix->pix_valor; 
        $valor_Pix     = parserValor($valorPix);
        $pixpagadorid  = '';

        if(!empty($pagamentoPix->pixpagador_cpf)){
            $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
        }else{
            $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
            
        }

        if(!empty($pagamentoPix->pixpagador_id)){
            $pixpagadorid         = $pagamentoPix->pixpagador_id;
                
            $query = PedidoPortal::select('*');
            $query->where('status_pedido', '=', '9');
            $query->where('condicao_pagamento','=','4247');
            //04/05/2022 retirado a pedido do Elcio
            //$query->where('valor_total_nota', '>=', $valorPix);
            $query->whereNull('deleted_at');

            $resultPedido = $query->get();

            foreach($resultPedido as $pedidoQuery){

                $codigo       = $pedidoQuery->cod_cliente;
                $valorPedido  = $pedidoQuery->valor_total_nota;
                $valor_Pedido = parserValor($valorPedido);
                $numeroPedido = $pedidoQuery->id;

                if(!empty($pedidoQuery->pedido_gerado)){
                    $numero_titulo = $pedidoQuery->pedido_gerado;
                }else{
                    $numero_titulo = $numeroPedido; 
                }

                $cliente_busca = ClienteNasajon::select('codigo','id')->where('cpf_cnpj','ilike',substr($depositanteDocumento, 0, 10).'%')->where('codigo','=',$codigo)->get();
                $clienteIgual = 'nao';
                
                foreach($cliente_busca as $clienteBusca){
                    
                    $clienteNasajon   = $clienteBusca->id;

                    //ver se tem outro pedido com o mesmo valor do Cliente.
                    $query2regra = PedidoPortal::select('*');
                    $query2regra->where('status_pedido', '=', '9');
                    $query2regra->where('condicao_pagamento','=','4247');
                    $query2regra->where('valor_total_nota', '=', $valorPedido);
                    $query2regra->where('cod_cliente','=',$codigo);
                    $query2regra->where('id','!=',$numeroPedido);
                    $query2regra->whereNull('deleted_at');

                    $resultPedido2regra = $query2regra->get();

                    $contaPedido = 1;

                    foreach($resultPedido2regra as $pedido2Query){
                        $contaPedido++;                        
                    }  
                    
                    if($contaPedido == 1){

                        $clienteIgual     = 'sim';

                        $this->incluirPixPedido($pix_id, $numeroPedido);
                    
                        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
                    
                        $data_obs     = Carbon::now()->format('d/m/Y H:i');
                            
                        $observacao_credito = "Título de Crédito Gerado Automático pelo Sistema Pix. Data Hora: ".$data_obs." Pedido: " .$numeroPedido;
                    
                        $numerotitulo = $numero_titulo.'1CRD';

                                            
                        $sql_associa_titulo = "select * from integracoes.api_vincularparticipantepix(
                                '".$pix_id."',
                                '".$numerotitulo."',
                                '".$clienteNasajon."',
                                '".$usuario_cadastro_uuid."',
                                '".$observacao_credito."'
                    
                        );";   

                            try{
                                $sql_nasajon = DB::connection('nasajon')->select($sql_associa_titulo);
                                $mensagem_nasajon = $sql_nasajon[0]->mensagem;
                                $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                    
                                $mensagem_retorno = $mensagem_nasajon['mensagem'];
                    
                                return $mensagem_retorno;        
                    
                            }catch(\Exception $e){
                                return  response()->json([
                                    'status' => 'error',
                                    'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                                    'error' => [$e],
                                    'response' => []
                                ], 422);
                            }  
                    }  
                } 
                /*if($clienteIgual =='nao'){
                    //ver se tem outro pedido com o mesmo valor.
                    $queryregra = PedidoPortal::select('*');
                    $queryregra->where('status_pedido', '=', '9');
                    $queryregra->where('condicao_pagamento','=','4247');
                    $queryregra->where('valor_total_nota', '=', $valorPix);
                    $queryregra->whereNull('deleted_at');

                    $resultPedidoregra = $queryregra->get();

                    foreach($resultPedidoregra as $pedidoQueryValor){

                        $codigoCli        = $pedidoQueryValor->cod_cliente;
                        $valorPedidoNota  = $pedidoQueryValor->valor_total_nota;
                        $numeroPedidoValor = $pedidoQueryValor->id;

                        if(!empty($pedidoQuery->pedido_gerado)){
                            $numero_titulo = $pedidoQueryValor->pedido_gerado;
                        }else{
                            $numero_titulo = $numeroPedidoValor; 
                        }

                        $query2regra = PedidoPortal::select('*');
                        $query2regra->where('status_pedido', '=', '9');
                        $query2regra->where('condicao_pagamento','=','4247');
                        $query2regra->where('valor_total_nota', '=', $valorPedidoNota);
                        $query2regra->where('id','!=',$numeroPedidoValor);
                        $query2regra->whereNull('deleted_at');

                        $resultPedido2regra = $query2regra->get();

                        $contaPedido = 1;

                        foreach($resultPedido2regra as $pedido2Query){
                            $contaPedido++;                        
                        }  
                    
                        if($contaPedido == 1){

                            $clienteNasajon    = ClienteNasajon::select('codigo','id')->where('codigo','=',$codigoCli)->first()->id;

                            $this->incluirPixPedido($pix_id, $numeroPedidoValor);
                            
                            $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;
                            
                            $data_obs     = Carbon::now()->format('d/m/Y H:i');
                                    
                            $observacao_credito = "Título de Crédito Gerado Automático pelo Sistema Pix. Data Hora: ".$data_obs." Pedido: " .$numeroPedidoValor;
                            
                            $numerotitulo = $numero_titulo.'1CRD';

                                                
                             $sql_associa_titulo = "select * from integracoes.api_vincularparticipantepix(
                                         '".$pix_id."',
                                         '".$numerotitulo."',
                                         '".$clienteNasajon."',
                                         '".$usuario_cadastro_uuid."',
                                         '".$observacao_credito."'
                            
                             );";   

                            try{
                                $sql_nasajon = DB::connection('nasajon')->select($sql_associa_titulo);
                                $mensagem_nasajon = $sql_nasajon[0]->mensagem;
                                $mensagem_nasajon = json_decode($mensagem_nasajon, true);
                            
                                $mensagem_retorno = $mensagem_nasajon['mensagem'];
                            
                                return $mensagem_retorno;        
                            
                            }catch(\Exception $e){
                                    return  response()->json([
                                            'status' => 'error',
                                            'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                                            'error' => [$e],
                                            'response' => []
                                    ], 422);
                            }  
                                
                        }
                    }
                } */

            }
        }

    }       
   
   }  

    public function incluirPixPedido($pix_id, $pedidoPortal){

        $incluiPixPedidoObj = PedidoPortal::select()->where('id', '=', $pedidoPortal)->first();
        $incluiPixPedidoObj->pix_id = $pix_id;
        $incluiPixPedidoObj->save();

       
    }
    

    public function modalTodosPedidoAssociarPix(Request $request){
        $origem = 'nasajon';

        $id_pix = $request->pix_id;

        $query = PagamentoPixNasajon::select('*');
        $query->where('pix_id',$id_pix);

        $result = $query->get();

        $retorno          = [];
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $pagamentoPix){
            $valorPix   = $pagamentoPix->pix_valor;

            $pixpagadorid  = '';

            if(!empty($pagamentoPix->pixpagador_cpf)){
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
            }else{
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
                
            }

            if(!empty($pagamentoPix->pixpagador_id)){
                $pixpagadorid         = $pagamentoPix->pixpagador_id;
            }

            $query = PedidoPortal::with(['cliente','estabelecimentoDetalhes','usuario_detalhes']);
            $query->whereIn('status_pedido', ['9','11']);
            $query->where('condicao_pagamento','=', '4247');
            $query->where('valor_total_nota', '<', $valorPix); 
            
            $resultPedido = $query->get();

            foreach($resultPedido as $pedidoQuery){
                $status_pedido          = $pedidoQuery->status_pedido;

                $numeroPedido           = $pedidoQuery->id;  
                $estabelecimento        = $estabelecimentos[(integer)$pedidoQuery->estabelecimento];
                $estabelecimentopad     = $pedidoQuery->estabelecimento_pad; 
                $codigooperacao         = $pedidoQuery->codigo_operacao; 
                $pedidoNasajon          = ' ';
                $codcliente             = $pedidoQuery->cod_cliente;
                $status_pedido          = $pedidoQuery->status_pedido;

                $clienteNasajon         = '';
                $cpfcnpj                = '';

                $pedidoNasajon          = $pedidoQuery->pedido_gerado;
                if(!empty($pedidoQuery->cliente->id)){
                    $clienteNasajon         = $pedidoQuery->cliente->id;
                    $cpfcnpj                = $pedidoQuery->cliente->cpf_cnpj;
                }

                        
                if(!empty($pedidoQuery->usuario_detalhes->supervisor->name)){
                    $gerente                = $pedidoQuery->usuario_detalhes->supervisor->name;
                }else{
                    $gerente                = " ";
                }                
                
                $estabelecimentoNasajon    = $pedidoQuery->estabelecimentoDetalhes->estabelecimento;
                $clienteDocumento          = $pedidoQuery->nome_comprador .' - '. $cpfcnpj;
                $vendedorDados             = $pedidoQuery->usuario_detalhes->codigo_representante . ' - ' . $pedidoQuery->usuario_detalhes->name;
                
                $retorno[] = [
                    'idPix' => $id_pix, 
                    'numeroPedido' => $numeroPedido,
                    'pedidoNasajon' => $pedidoNasajon,
                    'estabelecimento' => $estabelecimento,
                    'estabelecimentoNasajon' => $estabelecimentoNasajon,
                    'clienteNasajon' => $clienteNasajon,
                    'cliente' => $clienteDocumento,
                    'cpfcnpj' => $cpfcnpj,
                    'valor' => parserValor($pedidoQuery->valor_total_nota),
                    'data' => parserData($pedidoQuery->data_pedido),
                    'gerente' => $gerente,
                    'vendedor' => $vendedorDados,
                    'origem' => $origem,
                ];  
            }  

            
            $queryNasa = PedidosVendaNasajon::with(['pedido_portal.estabelecimentoDetalhes','formaPagamento']);
            $queryNasa->whereIn('situacao_descricao', ['Aberto','Em Faturamento','Em separação'])->where('grupodeoperacao_pedido','=','VENDA');
            $queryNasa->where('rascunho','=', false);
            $queryNasa->where('valor', '<', $valorPix); 
            $queryNasa->where(function($query){
                $query->orWhere('grupodeoperacao', 'VENDA');
                $query->orWhereNull('grupodeoperacao');
            });
            

            $resultPedido = $queryNasa->get();

            foreach($resultPedido as $pedidoQuery){

                if($pedidoQuery->formaPagamento['formapagamento_descricao'] == 'Usar Crédito'){
                

                    if(!isset($pedidoQuery->pedido_portal->id)){                                     
                        $erro = $pedidoQuery->numero;
                        $numeroPedido = " ";
                        $data_pedido = ' ';
                        $gerente     = " ";
                    }
                                        
                    
                    if(!empty($pedidoQuery->pedido_portal->usuario)){
                        $numeroPedido = $pedidoQuery->pedido_portal->id;
                        $data_pedido = parserData($pedidoQuery->pedido_portal->data_pedido);

                        if(!empty($pedidoQuery->pedido_portal->usuario)){
                            $queryUsu = User::select('*')->where('id', $pedidoQuery->pedido_portal->usuario)->with(['supervisor'])->get();
                            foreach($queryUsu as $usuResult){
                                if(!empty($usuResult->name)){
                                    $gerente                = $usuResult->name;
                                }                
                            }
                        } 
                    }               

                    $estabelecimentoNasajon = $pedidoQuery->estabelecimento;
                    $estabelecimento        = $estabelecimentos[(integer)$pedidoQuery->estabelecimento_codigo];
                    $clienteNasajon         = $pedidoQuery->cliente;
                    $cliente                = $pedidoQuery->cliente_razaosocial;
                    $clienteDoc             = $pedidoQuery->cliente_cnpj;
                    
                    $clienteDocumento          = $cliente .' - '. $clienteDoc;
                    $vendedorDados             = $pedidoQuery->vendedor_codigo . ' - ' . $pedidoQuery->vendedor_nome;
                    

                    $retorno[] = [
                        'idPix' => $id_pix, 
                        'numeroPedido' => $numeroPedido,
                        'pedidoNasajon' => $pedidoQuery->numero,
                        'estabelecimento' => $estabelecimento,
                        'estabelecimentoNasajon' => $estabelecimentoNasajon,
                        'clienteNasajon' => $clienteNasajon,
                        'cliente' => $clienteDocumento,
                        'cpfcnpj' => $clienteDoc,
                        'valor' => parserValor($pedidoQuery->valor),
                        'data' => $data_pedido,
                        'gerente' => $gerente,
                        'vendedor' => $vendedorDados,
                        'origem' => $origem,
                    ];  
                
                }         
          
            }

        }       
        
            
        return view("programs.pagamento_pix.modal.todos_pedido")->with(['return' => $retorno]);
    
    }    
    
  
    
    public function modalSemPedidoAssociarPix(Request $request){

        $id_pix   =  $request->pix_id;

        $query = PagamentoPixNasajon::select('*');
        $query->where('pix_id',$id_pix);

        $result = $query->get();
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $pagamentoPix){

            if(!empty($pagamentoPix->pixpagador_cpf)){
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
            }else{
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
            }
            $cliente_busca = ClienteNasajon::select('*')->where('cpf_cnpj','=',$depositanteDocumento)->get();
                    
            foreach($cliente_busca as $clienteBusca){
               
                $clienteNasajon      = $clienteBusca->id;
                $clienteDadosNasajon = $clienteBusca->nome . ' - ' . $clienteBusca->cpf_cnpj;
            }
        
        }

        if(empty($clienteNasajon)){

            $clienteNasajon = '';   
            $clienteDadosNasajon = '';  
        }

        return view('programs.pagamento_pix.modal.sem_pedido')->with(['id' => $id_pix, 'clienteNasajon' => $clienteNasajon, 'clienteDadosNasajon'=> $clienteDadosNasajon]);
    }

   

    public function criaCreditoSemPedido(Request $request){

        $fields       = $request->only(['id','cliente_nome_modal','observacao']); 
        $pix_id       = $fields['id'];
        $observacao   = $fields['observacao'];
        $observacao   = $observacao."Gerado sem Pedido, pelo Sistema Pix ";
        $cliente_nome = $fields['cliente_nome_modal'];

        $query = PagamentoPixNasajon::select('*');
        $query->where('pix_id',$pix_id);

        $result = $query->get();
        $estabelecimentos = returnEmpresasNasajonView();

        foreach($result as $pagamentoPix){

            if(!empty($pagamentoPix->pixpagador_cpf)){
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cpf,'###.###.###-##');
                $numero_titulo = $pagamentoPix->pixpagador_cpf;
            }else{
                $depositanteDocumento = mask($pagamentoPix->pixpagador_cnpj,'##.###.###/####-##');
                $numero_titulo = $pagamentoPix->pixpagador_cnpj;                
            }

            if(empty($cliente_nome)){ 

                $cliente_busca = ClienteNasajon::select('codigo','id')->where('cpf_cnpj','=',$depositanteDocumento)->get();
                    
               
            }else{

                $cliente_busca = ClienteNasajon::select('codigo','id')->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ilike', trim($cliente_nome))->get();  
                $numero_titulo = preg_replace('/[^0-9]/', '', $cliente_nome);

            }

            foreach($cliente_busca as $clienteBusca){
               
                $clienteNasajon  = $clienteBusca->id;   
       
                $mensagem_retorno = $this->associaPix($pix_id, $numero_titulo, $clienteNasajon, $observacao);
                
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'Credito cadastrado com sucesso!',
                        'error' => [],
                        'response' => []
                    ], 220
                );
    
            }
        }     

    }    


}
