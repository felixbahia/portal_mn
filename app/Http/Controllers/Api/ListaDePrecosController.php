<?php

namespace App\Http\Controllers\Api;

use Hash;
use Auth;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

use App\CepEstado;
use App\AliquotaPreco;

use App\Http\Requests\ListaDePrecosRequest;

use App\Http\Controllers\PrologosController;
use App\Http\Controllers\ListagemDePrecosController;
use App\Http\Controllers\UserCamposSalvoController;

class ListaDePrecosController extends Controller
{
    public function __construct() {
        $this->middleware(['auth:api']);
    }

	public $successStatus = 200;
	public $errorStatus = 403;

    public function popularLocalizacao(Request $request){
        header('Access-Control-Allow-Origin: *');
        $origemObj = AliquotaPreco::with('origem_detalhe')
            ->orderBy('origem')
            ->get()
            ->unique('origem');

        foreach ($origemObj as $value) {

            $aliquotaObj = AliquotaPreco::where('origem', $value->origem)->orderBy('icms_venda')->get()->unique('icms_venda');
            $aliquota_array = [];
            
            foreach ($aliquotaObj as $v) {
                $aliquota_array[] = $v->icms_venda . "%"; 
            }

            $origem_array[] = [
                'id' => $value->origem,
                'name' => $value->origem_detalhe->estado,
                'aliquotas' => $aliquota_array
            ];
        }

        $origem_array = $origem_array;
        unset($aliquota_array);

        $UserCamposSalvoControllerObj = new UserCamposSalvoController('App\ListagemDePrecos');
        $campos_salvos = $UserCamposSalvoControllerObj->returnCamposSalvos();
        if(!empty($campos_salvos['aliquota'])){
            $campos_salvos['aliquota'] = $campos_salvos['aliquota'].'%';
        }
        if(isset($campos_salvos['coluna'])){
            $campos_salvos['tabela'] = '';
            if(strpos($campos_salvos['coluna'], 'coluna_')){
                $campos_salvos['tabela'] = 1;
            }else{
                $campos_salvos['tabela'] = 2;
            }
            unset($campos_salvos['coluna']);
        }
        if(isset($campos_salvos['frete'])){
            if($campos_salvos['frete'] === 'fob'){
                $campos_salvos['frete'] = 2;
            }else{
                $campos_salvos['frete'] = 1;
            }
        }
        if(isset($campos_salvos['moeda'])){
            if($campos_salvos['moeda'] === 'real'){
                $campos_salvos['moeda'] = 1;
            }else{
                $campos_salvos['moeda'] = 2;
            }
        }
        $response = [
            'estados' => $origem_array,
            'campos_salvos' => $campos_salvos,
        ];
        $return = [
            'error' => [
                'error' => false,
                'msg' => [
                    'dev' => '',
                    'user' => ''
                ]
            ],
            'request' => [],
            'response' => $response
        ];

        return response()->json($return, $this->successStatus);
    }

    public function listaDePrecos(Request $request){
        header('Access-Control-Allow-Origin: *');
        $listaDePrecosObj = new ListagemDePrecosController();

        $fields = $request->only('origem', 'grupo', 'produto', 'descricao', 'aliquota', 'tabela', 'frete', 'moeda', 'regiao');

        if(
            !isset($fields['origem']) ||
            !isset($fields['aliquota']) ||
            !isset($fields['tabela']) ||
            !isset($fields['frete']) ||
            !isset($fields['moeda']) ||
            empty($fields['origem']) ||
            empty($fields['aliquota']) ||
            empty($fields['tabela']) ||
            empty($fields['frete']) ||
            empty($fields['moeda'])
        ){
            $erro_return = [
                'error' => [
                    'error' => true,
                    'msg' => [
                        'dev' => 'Paramentro(s) informado(s) inválido(s)',
                        'user' => 'Paramentro(s) informado(s) inválido(s)'
                    ]
                ],
                'request' => $fields,
                'response' => new \stdClass()
            ];

            return response()->json($erro_return, $this->errorStatus);
        } else {
            switch ($fields['moeda']) {
                case 1:
                    $arr['moeda'] = 'real';
                    break;
                case 2:
                    $arr['moeda'] = 'dolar';
                    break;
                default:                
                    return response()->json($erro_return, $this->errorStatus);
                    break;
            }

            switch ($fields['tabela']) {
                case 1:
                    $arr['coluna'] = 'coluna_a';
                    break;
                case 2:
                    $arr['coluna'] = 'prazo_vista';
                    break;
                default:                
                    return response()->json($erro_return, $this->errorStatus);
                    break;
            }

            switch ($fields['frete']){
                case '1':
                    $arr['frete'] = 'cif';
                    
                    if (isset($fields['regiao'])){
                        $arr['regiao'] = $fields['regiao'];
                    }
                    else{
                        $arr['regiao'] = 1;
                    }
                    break;
                case '2':
                    $arr['frete'] = 'fob';
                    break;
                default:                
                    return response()->json($erro_return, $this->errorStatus);
                    break;
            }
            
            if(CepEstado::find($fields['origem'])->get()->isNotEmpty()){
                $arr['origem'] = $fields['origem'];
            }
            else{
                return response()->json($erro_return, $this->errorStatus);
            }
            $produtos = [];

            $arr['aliquota'] = str_replace("%", '', $fields['aliquota']);

            if (AliquotaPreco::where('aliquota', $arr['aliquota'])->where('origem', $arr['origem'])->get()->isEmpty()){
                return response()->json($erro_return, $this->errorStatus);
            }

            if (isset($fields['descricao']) ||
                isset($fields['grupo']) ||
                isset($fields['produto'])
            ){

                $arr['nome'] = isset($fields['descricao'])?$fields['descricao']:'';
                $arr['grupo'] = isset($fields['grupo'])?$fields['grupo']:'';
                $arr['produto'] = isset($fields['produto'])?$fields['produto']:'';


                $filter_request = new ListaDePrecosRequest($arr);
                
                $arr_result = json_decode($listaDePrecosObj->filter($filter_request)->content(), true);

                // dd($arr_result);

                if(empty(@array_diff($arr, $arr_result))){
                    $error = [
                        'error' =>[
                            "error" => true,
                            "msg" => [
                                "dev" => "Sua pesquisa não retornou nenhum produto",
                                "user" => "Sua pesquisa não retornou nenhum produto"
                            ]
                        ],
                        'request' => $fields,
                        'response' => []
                    ];
                    return response()->json($error, $this->errorStatus);                     
                }
                // dd($arr_result);
                foreach ($arr_result as $value) {


                    if ($arr['coluna'] == "coluna_a"){
                        $valores = [
                            [
                                'id' => 1,
                                'nome' => 'A Vista',
                                'valor' => $value['prazo_vista']
                            ],
                            [
                                'id' => 2,
                                'nome' => '15 dias',
                                'valor' => $value['prazo_15']
                            ],
                            [
                                'id' => 3,
                                'nome' => '30 dias',
                                'valor' => $value['prazo_30']
                            ],
                            [
                                'id' => 4,
                                'nome' => '45 dias',
                                'valor' => $value['prazo_45']
                            ],
                            [
                                'id' => 5,
                                'nome' => '60 dias',
                                'valor' => $value['prazo_60']
                            ]
                        ];
                    }  
                    else {
                        $valores = [
                            [
                                'id' => 1,
                                'nome' => 'Grupo A',
                                'valor' => $value['coluna_a']
                            ],
                            [
                                'id' => 2,
                                'nome' => 'Grupo B',
                                'valor' => $value['coluna_b']
                            ],
                            [
                                'id' => 3,
                                'nome' => 'Grupo C',
                                'valor' => $value['coluna_c']
                            ]
                        ];
                    }

                    $produtos[] = [
                        'id' => $value['cod_produto'],
                        'valor_em' => ucwords(strtolower($arr['moeda'])),
                        'grupo' => $value['grupo'],
                        'descricao' => $value['nome'],
                        'marca' => [
                            'id' => 1,
                            'nome' => $value['marca'], 
                        ],
                        'linha' => [
                            'id' => 1,
                            'nome' => $value['linha'], 
                        ],
                        'grupos' => $valores
                    ];
                }
            }

            $return = [
                'error' => [
                    'error' => false,
                    'msg' => [
                        'dev' => '',
                        'user' => ''
                    ]
                ],
                'request' => $fields,
                'response' => [
                    'produtos' => $produtos,
                ]
            ];
            return response()->json($return, $this->successStatus);
        }
    }
}
