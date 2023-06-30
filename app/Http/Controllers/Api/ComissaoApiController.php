<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ComissaoController;

use App\Http\Requests\Api\ComissaoApiRequest;
use App\Http\Requests\ComissaoRequest;
use App\Http\Requests\ComissaoDialogRequest;

class ComissaoApiController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }
    public $successStatus = 200;
    public $errorStatus = 403;

    public function comissao(ComissaoApiRequest $request){

    	$fields = $request->only(['cod_estabelecimento', 'periodo_inicial', 'periodo_final']);

    	$request = [
    		'data_inicio' => $fields['periodo_inicial'],
    		'data_fim' => $fields['periodo_final'],
    		'estabelecimento' => $fields['cod_estabelecimento'] ?? '',
    		'api' => true
    	];

    	$comissaoController = new ComissaoController();
    	$comissaoRequest = new ComissaoRequest($request);

    	$result = json_decode($comissaoController->filter($comissaoRequest)->content(), true);

    	// dd($result);

        if (isset($result['response']['titulos'][0])){
            $response = [
                'total' => 1,
                'representantes' => [
                    [
                        "id" => $result['response']['titulos'][0]['representante_not_parse'] ?? '',
                        "representante" => $result['response']['titulos'][0]['representante'] ?? '',
                        "valor_vendido" => $result['response']['titulos'][0]['base_comissao'] ?? '',
                        "devolucao" => $result['response']['titulos'][0]['valor_devolucao'] ?? '',
                        "total" => $result['response']['titulos'][0]['valor_total'] ?? ''
                    ]
                ]
            ];
            
        }
        else{
            $response = [
                'total' => 0,
                'representantes' => []
            ];            
        }

    	$return = [
    		'error' => [
    			'error' => false,
    			'msg' => [
    				'dev' => '',
    				'user' => ''
    			]
    		],
    		'request' => [
    			'cod_estabelecimento' => $fields['cod_estabelecimento']??'',
    			'periodo_inicial' => $fields['periodo_inicial'],
    			'periodo_final' => $fields['periodo_final'],
    			'limit' => "1",
    			'offset' => "0",
    			'count' => "true"
    		],
    		'response' => $response
    	];

    	return response()->json($return, $this->successStatus);

    }

    public function comissaoDetalhes(ComissaoApiRequest $request, $id){

    	$comissaoController = new ComissaoController();
    	$fields = $request->only('periodo_inicial', 'periodo_final', 'estabelecimento');

    	$request = [
    		'representante' => $id,
    		'data_inicio' => $request->periodo_inicial,
    		'data_fim' => $request->periodo_final,
    		'estabelecimento' => $request->estabelecimento??'',
    		'api' => true,
    	];

    	$comissaoDialogRequest = new ComissaoDialogRequest($request);

    	$result = $comissaoController->dialog($comissaoDialogRequest);

    	foreach ($result['titulos'] as $value) {
	    	$representantes[] = [
		        "estabelecimento" => $value['estabelecimento'],
		        "data_emissao" => $value['data_emissao'],
		        "titulo" => $value['numero_documento'],
		        "nota" => $value['numero_nota'],
		        "cliente" => $value['cliente'],
		        "valor_vendido" => $value['base_comissao'],
		        "devolucao" => $value['valor_devolucao'],
		        "comissao" => $value['comissao'],
		        "total" => $value['valor_comissao']
	    	];
    	}

    	$return = [
    		'error' => [
    			'error' => false,
    			'msg' => [
    				'dev' => '',
    				'user' => ''
    			]
    		],
    		'request' => [
    			'id' => intval($id),
    		],
    		'response' => [
    			'total' => $result['total']['comissao'],
    			'representantes' => $representantes??[],
    		],
    	];

    	return response()->json($return, $this->successStatus);

    }
}
