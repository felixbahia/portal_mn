<?php

namespace App\Http\Controllers;


use Carbon\Carbon;

use App\PedidoPortal;
use App\CepEstado;
use App\TransportadoraEstabelecimento;
use App\ClienteNasajon;
use App\TransportadorNasajon;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Http\Requests\TransportadoraEstabelecimentoEditarRequest;
use App\Http\Requests\TransportadoraEstabelecimentoAdicionarRequest;

class TransportadoraEstabelecimentoController extends Controller
{
    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TransportadoraEstabelecimento") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TransportadoraEstabelecimento');

        $estabelecimentos = returnEmpresasNasajonView();
 
        $request->session()->flash('model', 'App\TransportadoraEstabelecimento');
        $uf_origens = $this->estadosOrigem();
        $uf_destinos = $this->estados();
        $tipo_fretes = $this->tipoFrete();
        $uf_origens[''] ='Todas Origem';
        $uf_destinos[''] ='Todos Destino';
        $tipo_fretes['']='Todos';

        $estabelecimentos = returnEmpresasNasajonView();

        $filteredArray = Arr::where($estabelecimentos, function ($value, $key) {
            return  $key >=3 &&  $key <=20;
        });
        $filteredArray['']='Todos';

        return view('programs.transportadora_estabelecimento.index',['estabelecimentos'=>$filteredArray,'uf_origens' => $uf_origens,'uf_destinos' => $uf_destinos
        ,'tipo_fretes' => $tipo_fretes]);
    }

    public function filtro(Request $request){
        $campo = $request->only('transportadora_nome','estabelecimento','uf_origem','uf_destino','tipo_frete');

        $estabelecimentos = returnTodasEmpresasView();

        $TransportadoraEstabelecimentoObj = TransportadoraEstabelecimento::select();

        $TransportadoraEstabelecimentoObj->with(['transportadora' => function($query) use($campo){
            if(!empty($campo['transportadora_nome'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cnpj)'), 'ILIKE', '%'.($campo['transportadora_nome']).'%');
            }
        }]);
        if(!empty($campo['estabelecimento'])){
            $TransportadoraEstabelecimentoObj->where('estabelecimento',  str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT) );
        }
        if(!empty($campo['uf_origem'])){
            $TransportadoraEstabelecimentoObj->where('uf_origem',  $campo['uf_origem']);
        }
        if(!empty($campo['uf_destino'])){
            $TransportadoraEstabelecimentoObj->where('uf_destino',  $campo['uf_destino']);
        }
        if(!empty($campo['tipo_frete'])){
            $TransportadoraEstabelecimentoObj->where('tipo_frete',  $campo['tipo_frete']);
        }



        $TransportadoraEstabelecimentos = $TransportadoraEstabelecimentoObj->get();
        $saida = [];

        foreach($TransportadoraEstabelecimentos as $TransportadoraEstabelecimento){
            if(!empty($TransportadoraEstabelecimento->transportadora)){
                $saida[] = [
                    'id' => encrypt($TransportadoraEstabelecimento->id),
                    'transportadora_codigo' => $TransportadoraEstabelecimento->transportadora_codigo,
                    'transportadora_nome' => $TransportadoraEstabelecimento->transportadora->nome. ' - '.$TransportadoraEstabelecimento->transportadora->cnpj,
                    'estabelecimento' => $TransportadoraEstabelecimento->estabelecimento,
                    'tipo_frete' => $TransportadoraEstabelecimento->tipo_frete,
                    'uf_origem' => $TransportadoraEstabelecimento->uf_origem,
                    'uf_destino' => $TransportadoraEstabelecimento->uf_destino,
                    'estabelecimento_nome' =>  $estabelecimentos[$TransportadoraEstabelecimento->estabelecimento],
              
                ]; 
            }            
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $saida
        ];
        return response()->json($response, 200);
    }

    public function modalAdicionar() {
        $uf_origens = $this->estadosOrigem();
        $uf_destinos = $this->estados();
        $tipo_fretes = $this->tipoFrete();

        $estabelecimentos = returnEmpresasNasajonView();
   

         $filteredArray = Arr::where($estabelecimentos, function ($value, $key) {
                return  $key >=3 &&  $key <=20;
            });
            $filteredArray['']='Todos';
         

        return view('programs.transportadora_estabelecimento.modal.adicionar',['estabelecimentos'=>$filteredArray,'uf_origens' => $uf_origens,'uf_destinos' => $uf_destinos
        ,'tipo_fretes' => $tipo_fretes]);
    }

    public function salvar(TransportadoraEstabelecimentoAdicionarRequest $request){
        $campo = $request->only('transportadora_codigo','transportadora_nome','estabelecimento','tipo_frete','uf_origem','uf_destino');
   
        $TransportadoraEstabelecimentoObj = new TransportadoraEstabelecimento;
        $TransportadoraEstabelecimentoObj->transportadora_codigo = $campo['transportadora_codigo'];
         $TransportadoraEstabelecimentoObj->transportadora_nome =$campo['transportadora_nome'];
        if(!empty($campo['estabelecimento'])){
            $TransportadoraEstabelecimentoObj->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
        }
        $TransportadoraEstabelecimentoObj->tipo_frete = strtoupper($campo['tipo_frete']);
        $TransportadoraEstabelecimentoObj->uf_origem = strtoupper($campo['uf_origem']);
        $TransportadoraEstabelecimentoObj->uf_destino = strtoupper($campo['uf_destino']);
        $TransportadoraEstabelecimentoObj->created_by = Auth::id();
        $TransportadoraEstabelecimentoObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalEditar(Request $request){
        $campo = $request->only('id');
      
        $uf_origens = $this->estadosOrigem();
        $uf_destinos = $this->estados();
        $tipo_fretes = $this->tipoFrete();

        $estabelecimentos = returnEmpresasNasajonView();
   

         $filteredArray = Arr::where($estabelecimentos, function ($value, $key) {
                return  $key >=3 &&  $key <=20;
            });
            $filteredArray['']='Todos';
      

        try{
            $id = decrypt($campo['id']);
       
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $TransportadoraEstabelecimentoObj = TransportadoraEstabelecimento::find($id);
        if(is_null($TransportadoraEstabelecimentoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($TransportadoraEstabelecimentoObj->id),
            'transportadora_codigo' => $TransportadoraEstabelecimentoObj->transportadora_codigo,
            'transportadora_nome' => $TransportadoraEstabelecimentoObj->transportadora_nome,
            'estabelecimento' => parserValorInteiro($TransportadoraEstabelecimentoObj->estabelecimento),
            'tipo_frete' => $TransportadoraEstabelecimentoObj->tipo_frete,
            'uf_origem' => $TransportadoraEstabelecimentoObj->uf_origem,
            'uf_destino' => $TransportadoraEstabelecimentoObj->uf_destino




        ];
  
        return view('programs.transportadora_estabelecimento.modal.editar')->with(['dados' => $dados,'estabelecimentos'=>$filteredArray,'uf_origens' => $uf_origens,'uf_destinos' => $uf_destinos ,'tipo_fretes' => $tipo_fretes]);
    }

    public function editar(TransportadoraEstabelecimentoEditarRequest $request){
        $campo = $request->only('id','transportadora_codigo','transportadora_nome','estabelecimento','tipo_frete','uf_origem','uf_destino');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $TransportadoraEstabelecimentoObj = TransportadoraEstabelecimento::find($id);
        $TransportadoraEstabelecimentoObj->transportadora_codigo = ($campo['transportadora_codigo']);
        $TransportadoraEstabelecimentoObj->transportadora_nome =$campo['transportadora_nome'];
        if(!empty($campo['estabelecimento'])){
            $TransportadoraEstabelecimentoObj->estabelecimento = str_pad($campo['estabelecimento'], 2, "0", STR_PAD_LEFT);
        }

        $TransportadoraEstabelecimentoObj->tipo_frete = strtoupper($campo['tipo_frete']);
        $TransportadoraEstabelecimentoObj->uf_origem = strtoupper($campo['uf_origem']);
        $TransportadoraEstabelecimentoObj->uf_destino = strtoupper($campo['uf_destino']);
        $TransportadoraEstabelecimentoObj->updated_by = Auth::id();
        $TransportadoraEstabelecimentoObj->save(); 

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $TransportadoraEstabelecimentoObj = TransportadoraEstabelecimento::find($id);
        if(is_null($TransportadoraEstabelecimentoObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [
                    $e,
                    'id' => $id
                ],
                'response' => []
            ], 422);
        }

        $dados = [
            'id' => encrypt($TransportadoraEstabelecimentoObj->id),
            'transportadora_nome' => $TransportadoraEstabelecimentoObj->transportadora_nome
        ];
        return view('programs.transportadora_estabelecimento.modal.deletar')->with(['dados' => $dados]);
    }

    public function deletar(Request $request){
        $campo = $request->only('id');

        try{
            $id = decrypt($campo['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'id' => 'Dados não encontrados'
                ],
                'response' => []
            ], 422);
        }

        $TransportadoraEstabelecimentoObj = TransportadoraEstabelecimento::find($id);
        $TransportadoraEstabelecimentoObj->deleted_by = Auth::id();
        $TransportadoraEstabelecimentoObj->save();
        $TransportadoraEstabelecimentoObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    private function estados(){
        $estados = CepEstado::select('uf','estado')->get();
        $return =[];
        foreach($estados as $estado){
            $return[$estado->uf] = $estado->estado;
        }
        return $return;
    }

    private function estadosOrigem(){
        $estados = CepEstado::select('uf','estado')->whereIn('uf',['RO','SP','TO'])->get();
        $return =[];
        foreach($estados as $estado){
            $return[$estado->uf] = $estado->estado;
        }
        return $return;
    }

    public function tipoFrete(){

        $tipo_frete = [
            "AMBOS" => 'AMBOS',
            "CIF" => 'CIF',
            "FOB" => 'FOB'
        ];

        return $tipo_frete;
    }

    public function atualiza(){
        ini_set('memory_limit', '2048M');
        set_time_limit(300);
        $transportador = TransportadoraEstabelecimento::select();

        $transportadoraEstabelecimentos =  $transportador->get();
        $retorno = [];

        foreach( $transportadoraEstabelecimentos as $transportadoraEstabelecimento){
            $retorno[$transportadoraEstabelecimento->transportadora_codigo .$transportadoraEstabelecimento->estabelecimento . $transportadoraEstabelecimento->uf_origem .$transportadoraEstabelecimento->uf_destino] = [
                'id' => $transportadoraEstabelecimento->id,
                'transportadora_codigo' => $transportadoraEstabelecimento->transportadora_codigo,
                 'estabelecimento' => $transportadoraEstabelecimento->estabelecimento,
                'tipo_frete' => $transportadoraEstabelecimento->tipo_frete,
                'uf_origem' => $transportadoraEstabelecimento->uf_origem,
                'uf_destino' => $transportadoraEstabelecimento->uf_destino,
           
          
            ];
        }

       $inicio_periodo = Carbon::now()->subMonth(12)->format('Y-m-d');
       $fim_periodo = Carbon::now()->format('Y-m-d');
      
        $pedidoPortal = PedidoPortal::select()->with(['detalhesTransportador','cliente'])
     
       ->whereNotnull('frete_preco')
       ->whereBetween('data_pedido',[$inicio_periodo,$fim_periodo]);
        $pedido_vendas =   $pedidoPortal->get();
      
        foreach(  $pedido_vendas as $pedido){

            if(!empty($pedido->detalhesTransportador) && !empty($pedido->cliente) && !empty($pedido->detalhesTransportador->codigo)
           ){
                $chave = $pedido->detalhesTransportador->codigo . $pedido->getEstabelecimentoPadAttribute(). $pedido->getOrigemAttribute().  $pedido->cliente->uf;
              
                if(!isset($retorno[$chave])){
                    $retorno[$chave] = [
                        'id' =>'',
                        'transportadora_codigo' => $pedido->detalhesTransportador->codigo,
                         'estabelecimento' => $pedido->getEstabelecimentoPadAttribute(),
                        'tipo_frete' =>  strtoupper( $pedido->frete_preco),
                        'uf_origem' =>$pedido->getOrigemAttribute(),
                        'uf_destino' => strtoupper($pedido->cliente->uf),
                   
                  
                    ];
                    $TransportadoraEstabelecimentoObj = new TransportadoraEstabelecimento;
                    $TransportadoraEstabelecimentoObj->transportadora_codigo = $pedido->detalhesTransportador->codigo;
                     $TransportadoraEstabelecimentoObj->transportadora_nome = $pedido->detalhesTransportador->nome;
              
                        $TransportadoraEstabelecimentoObj->estabelecimento =  $pedido->getEstabelecimentoPadAttribute();
                    
                    $TransportadoraEstabelecimentoObj->tipo_frete = strtoupper( $pedido->frete_preco);
                    $TransportadoraEstabelecimentoObj->uf_origem = $pedido->getOrigemAttribute();
                    $TransportadoraEstabelecimentoObj->uf_destino = strtoupper($pedido->cliente->uf);
                    $TransportadoraEstabelecimentoObj->created_by = 1;
                    $TransportadoraEstabelecimentoObj->save();

                }

            }

        }


        
    
    }

    public function autocomplete(Request $request){
        $fields = $request->only(["term", "estabelecimento", "nome_cliente", "nome_cliente_conta_e_ordem", "redespacho"]);
	
        $return = [];
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;

        if(empty($fields["nome_cliente_conta_e_ordem"])){
            $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($fields['nome_cliente']).'%')->first();
        }else{
            $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($fields['nome_cliente_conta_e_ordem']).'%')->first();
        }    
        $cliente_uf = empty($cliente_busca)? '' : $cliente_busca->uf;

        if(!empty($estabelecimento)){
            switch ($estabelecimento) {
                case '3':
                    $origem = 'RO';
                    break;
                case '4':
                    $origem = 'TO';
                    break;            
                default:
                    $origem = 'SP';
                    break;
            }
        }else{
            $origem = '';
        }
        
        if($estabelecimento == 5 || $estabelecimento == 8){
            $query = TransportadorNasajon::select('codigo','nome','cnpj')
                ->with(['transportadoraEstabelecimento' => function($query) use ($estabelecimento, $cliente_uf){
                    $query->where('estabelecimento', str_pad($estabelecimento, 2, 0, STR_PAD_LEFT));
                    if(!empty($cliente_uf)){
                        $query->where('uf_destino', $cliente_uf);
                    }
                }])
                ->limit("15")
                ->orderBy('nome', "ASC")
                ->whereRaw("CONCAT(LOWER(TRIM(nome)), ' - ', cnpj) ilike '%".trim($fields["term"])."%'")
                ->where('bloqueado', false)
                ->distinct('nome')
                ->get()
                ->toArray();

            foreach ($query as $value){
                $value = (array) $value;
                $return[$value['codigo']] = [
                    'label' => str_replace(' - __.___.___/____-__', '', trim($value['nome']) . ' - ' . trim($value['cnpj'])),
                    'value' => $value['codigo'], 
                    'codigo' =>  trim($value['cnpj']),
                    'tipo_frete' =>  empty($value['transportadora_estabelecimento'])? 'FOB': $value['transportadora_estabelecimento']['tipo_frete'],
                ];
            }

            if(!empty($return['1002'])){
                unset($return['1002']);
            }
            if(!empty($return['1003'])){
                unset($return['1003']);
            }

            return response()->json($return);
        }else {
            $query = TransportadorNasajon::select()
                ->with(['transportadoraEstabelecimento' => function($query) use ($estabelecimento, $cliente_uf){
                    $query->where('estabelecimento', str_pad($estabelecimento, 2, 0, STR_PAD_LEFT));
                    if(!empty($cliente_uf)){
                        $query->where('uf_destino', $cliente_uf);
                    }
                }])
                ->limit("15")
                ->orderBy('nome', "ASC")
                ->whereRaw("CONCAT(LOWER(TRIM(nome)), ' - ', cnpj) ilike '%".trim($fields["term"])."%'")
                ->where('bloqueado', false)
                ->distinct('nome')
                ->get()
                ->toArray();

            foreach ($query as $value){
                $value = (array) $value;
                if(!empty($value['transportadora_estabelecimento'])){
                    $return[$value['codigo']] = [
                        'label' => str_replace(' - __.___.___/____-__', '', trim($value['nome']) . ' - ' . trim($value['cnpj'])),
                        'value' => $value['codigo'], 
                        'codigo' =>  trim($value['cnpj']),
                        'tipo_frete' => $value['transportadora_estabelecimento']['tipo_frete'],
                    ];
                }else if($value['estado'] == 'RJ'){
                    $return[$value['codigo']] = [
                        'label' => str_replace(' - __.___.___/____-__', '', trim($value['nome']) . ' - ' . trim($value['cnpj'])),
                        'value' => $value['codigo'], 
                        'codigo' =>  trim($value['cnpj']),
                        'tipo_frete' => 'FOB',
                    ];
                }			
            }
            if(!empty($return['1002'])){
                unset($return['1002']);
            }
            if(!empty($return['1003'])){
                unset($return['1003']);
            }
    
            return response()->json($return);
        }
	}

    public function autocompleteRedespacho(Request $request){
        $fields = $request->only(["term", "estabelecimento", "nome_cliente", "nome_cliente_conta_e_ordem", "redespacho"]);
	
        $return = [];
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        
		$query = TransportadorNasajon::select('codigo','nome','cnpj')
            ->with(['transportadoraEstabelecimento'])
			->limit("15")
			->orderBy('nome', "ASC")
			->whereRaw("CONCAT(LOWER(TRIM(nome)), ' - ', cnpj) ilike '%".trim($fields["term"])."%'")
			->where('bloqueado', false)
			->distinct('nome')
			->get()
			->toArray();

		foreach ($query as $value){
			$value = (array) $value;
			$return[] = [
				'label' => str_replace(' - __.___.___/____-__', '', trim($value['nome']) . ' - ' . trim($value['cnpj'])),
				'value' => $value['codigo'], 
				'codigo' =>  trim($value['cnpj']),
                'tipo_frete' =>  empty($value['transportadora_estabelecimento'])? 'FOB': $value['transportadora_estabelecimento']['tipo_frete'],
			];
		}

        return response()->json($return);

	}

    public function modalDialog(Request $request){
        $fields = $request->only(["estabelecimento", "nome_cliente", "nome_cliente_conta_e_ordem", "redespacho"]);
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;

        if(empty($fields["nome_cliente_conta_e_ordem"])){
            $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($fields['nome_cliente']).'%')->first();
        }else{
            $cliente_busca = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ILIKE', '%'.($fields['nome_cliente_conta_e_ordem']).'%')->first();
        }        
        $cliente_uf = empty($cliente_busca)? '' : $cliente_busca->uf;

        if(!empty($estabelecimento)){
            switch ($estabelecimento) {
                case '3':
                    $origem = 'RO';
                    break;
                case '4':
                    $origem = 'TO';
                    break;            
                default:
                    $origem = 'SP';
                    break;
            }
        }else{
            $origem = '';
        }

        $redespacho = $fields["redespacho"];

		return view('programs.transportadora_estabelecimento.modal.dialog')->with(['estabelecimento' => $estabelecimento, 'cliente_uf' => $cliente_uf, 'origem' => $origem, 'redespacho' => $redespacho]);
	}

    public function filterModalDialog(Request $request){
		$filtro = $request->only(['codtran', 'viatran', 'nome', 'cidade', 'cliente_uf', 'origem', 'estabelecimento', 'redespacho', 'cnpj_filtro_transportadora']);

        if($filtro['estabelecimento'] == 5 || $filtro['estabelecimento'] == 8){
            $query = TransportadorNasajon::select('codigo', 'nome', 'cnpj', 'via_transporte', 'cidade', 'estado')
            ->where('bloqueado', false)
            ->with(['transportadoraEstabelecimento' => function($query) use($filtro){
                if (!empty($filtro['cliente_uf'])){
                    $query->where('uf_destino', $filtro['cliente_uf']);
                }
                $query->where('estabelecimento', str_pad($filtro['estabelecimento'], 2, 0, STR_PAD_LEFT));
            }]);
            if($filtro['redespacho'] == "false"){
                $query->whereNotIn("codigo", ['1003', '1002']);
            }
            if (!empty($filtro['codtran'])){
                $query->where("codigo", 'ilike', "%".strtolower(trim($filtro['codtran']))."%");
            }
            if (!empty($filtro['viatran'])){
                $query->where("via_transporte", "ilike", "%".strtolower(trim($filtro['viatran']))."%");
            }
            if (!empty($filtro['nome'])){
                $query->where("nome", "ilike", "%".strtolower(trim($filtro['nome']))."%");
            }
            if (!empty($filtro['cidade'])){
                $query->where("cidade", "ilike", "%".strtolower(trim($filtro['cidade']))."%");
            }
            if (!empty($filtro['cnpj_filtro_transportadora'])){
                $query->where("cnpj", 'ilike', "%".strtolower(trim($filtro['cnpj_filtro_transportadora']))."%");
            }
            
            $result = $query->get()->toArray();
            $return = [];
            foreach ($result as $key => $transportador) {
                $return[] = [
                    'codtran' => $transportador['codigo'],
                    'nome' => trim($transportador['nome']),
                    'cgc' => $transportador['cnpj'],
                    'viatran' => $transportador['via_transporte'],
                    'cidade' => $transportador['cidade'],
                    'estado' => $transportador['estado'],
                    'tipo_frete' =>  empty($transportador['transportadora_estabelecimento'])? 'FOB': $transportador['transportadora_estabelecimento']['tipo_frete'],
                ];
            }
    
            return response()->json($return);
        }else{
            if($filtro['redespacho'] == "false"){
                $query = TransportadorNasajon::select()
                    ->with(['transportadoraEstabelecimento' => function($query) use($filtro){
                        $query->where('uf_destino', $filtro['cliente_uf']);
                        $query->where('estabelecimento', str_pad($filtro['estabelecimento'], 2, 0, STR_PAD_LEFT));
                    }]);

                if (!empty($filtro['codtran'])){
                    $query->where("codigo", 'ilike', "%".strtolower(trim($filtro['codtran']))."%");
                }
                if (!empty($filtro['viatran'])){
                    $query->where("via_transporte", "ilike", "%".strtolower(trim($filtro['viatran']))."%");
                } 
                if (!empty($filtro['nome'])){
                    $query->where("nome", "ilike", "%".strtolower(trim($filtro['nome']))."%");
                } 
                if (!empty($filtro['cnpj_filtro_transportadora'])){
                    $query->where("cnpj", 'ilike', "%".strtolower(trim($filtro['cnpj_filtro_transportadora']))."%");
                }
                $query->distinct();
                $result = $query->get()->toArray();
                $return = [];
                foreach ($result as $key => $transportador) {
                    if(!empty($transportador['transportadora_estabelecimento'])){
                        $return[] = [
                            'codtran' => $transportador['codigo'],
                            'nome' => trim($transportador['nome']),
                            'cgc' => $transportador['cnpj'],
                            'viatran' => $transportador['via_transporte'],
                            'cidade' => $transportador['cidade'],
                            'estado' => empty($transportador['transportadora_estabelecimento'])? '' : $transportador['transportadora_estabelecimento']['uf_destino'],
                            'tipo_frete' => empty($transportador['transportadora_estabelecimento'])? 'FOB' : $transportador['transportadora_estabelecimento']['tipo_frete']
                        ]; 	
                    }else if($transportador['estado'] == 'RJ'){
                        $return[] = [
                            'codtran' => $transportador['codigo'],
                            'nome' => trim($transportador['nome']),
                            'cgc' => $transportador['cnpj'],
                            'viatran' => $transportador['via_transporte'],
                            'cidade' => $transportador['cidade'],
                            'estado' => empty($transportador['transportadora_estabelecimento'])? '' : $transportador['transportadora_estabelecimento']['uf_destino'],
                            'tipo_frete' => 'FOB'
                        ]; 	
                    }                    	
                }
            }else{
                $query = TransportadorNasajon::select()
                    ->with(['transportadoraEstabelecimento' => function($query) use($filtro){
                        $query->where('uf_destino', $filtro['cliente_uf']);
                    }]);

                if (!empty($filtro['codtran'])){
                    $query->where("codigo", 'ilike', "%".strtolower(trim($filtro['codtran']))."%");
                }
                if (!empty($filtro['viatran'])){
                    $query->where("via_transporte", "ilike", "%".strtolower(trim($filtro['viatran']))."%");
                } 
                if (!empty($filtro['nome'])){
                    $query->where("nome", "ilike", "%".strtolower(trim($filtro['nome']))."%");
                } 
                if (!empty($filtro['cnpj_filtro_transportadora'])){
                    $query->where("cnpj", 'ilike', "%".strtolower(trim($filtro['cnpj_filtro_transportadora']))."%");
                }
                $query->distinct();
                $result = $query->get()->toArray();
                $return = [];
                foreach ($result as $key => $transportador) {
                    $return[] = [
                        'codtran' => $transportador['codigo'],
                        'nome' => trim($transportador['nome']),
                        'cgc' => $transportador['cnpj'],
                        'viatran' => $transportador['via_transporte'],
                        'cidade' => $transportador['cidade'],
                        'estado' => empty($transportador['transportadora_estabelecimento'])? '' : $transportador['transportadora_estabelecimento']['uf_destino'],
                        'tipo_frete' => empty($transportador['transportadora_estabelecimento'])? 'FOB' : $transportador['transportadora_estabelecimento']['tipo_frete']
                    ]; 		
                }
            }

            return response()->json($return);
        }
		
	}
}