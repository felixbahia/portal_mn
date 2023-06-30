<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Xml;
use Carbon\Carbon;

use App\TitulosEmAbertoNasajon;
use App\EmpresaNasajon;
use App\ClienteNasajon;
use App\CenprotTitulo;
use App\CenprotLog;
use App\ContasReceberBaixadoNasajon;
use App\CenprotExcecao;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\EmailController;

class CenprotController extends Controller
{
    private $token = null;
    private $url = '';
    private $usuario = 'textilmn_ws';
    private $senha = 'textil@2020';
    private $usuario_homologacao = 'mntextil_ws';
    private $senha_homologacao = 'mntextil@2020';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Cenprot") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Cenprot');

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);

        $verificacao_status = CenprotTitulo::select('cenprot_status')->distinct()->get()->pluck('cenprot_status');

        $cenprot_status = [];

        foreach($verificacao_status as $value){
            $cenprot_status[$value] = $value;
        }

        return view('programs.cenprot.index')->with(['estabelecimentos' => $estabelecimentos, 'cenprot_status' => $cenprot_status]);
    }

    private function setUrlRequest(){
		if(config('app.debug') == true){
			$this->url = config('cenprot.url.sandbox');
		}else{
			$this->url = config('cenprot.url.producao');
		}
    }
    
    public function autenticacaoAPI(){
        $client = new \GuzzleHttp\Client();

        try{
            if(config('app.debug') == true){
                $xml = [
                    'Body' => [
                        'Autenticar' => [
                            '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                            'credenciais' => [
                                '_attributes' => ['xmlns' => ""],
                                'usuario' => $this->usuario_homologacao,
                                'senha' => $this->senha_homologacao,
                                'apresentante' => 'M43',
                            ]
                        ]
                    ]
                ];
            }else{
                $xml = [
                    'Body' => [
                        'Autenticar' => [
                            '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                            'credenciais' => [
                                '_attributes' => ['xmlns' => ""],
                                'usuario' => $this->usuario,
                                'senha' => $this->senha,
                                'apresentante' => 'M43',
                            ]
                        ]
                    ]
                ];
            }

            $xml = Xml::encode($xml, [
                'rootElementName' => 'Envelope',
                '_attributes' => [
                    'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                ],
            ], true, 'UTF-8');
            
            $options  = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml,
            ];
    
            $this->setUrlRequest();

            $response = $client->request(
                'POST', 
                $this->url, 
                $options
            );
            
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Login na CENPROT',
                'error' => [],
                'response' => []
            ],422);
        }

        $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
        $retorno = Xml::decode($retorno);

        $this->token = $retorno['soapBody']['ns2AutenticarResponse']['credenciais']['token'];

        return $this->token;
    }

    public function enviarAutomaticoTitulo(){
        $this->autenticacaoAPI();

        $data_atual = Carbon::now();

        $query_verificacao = CenprotExcecao::select();
        $query_verificacao->where('data_nao_executar', $data_atual);
        $result_verificacao = $query_verificacao->first();
        
        if(empty($result_verificacao)){
            $data_menos_31 = $data_atual->subDays(31);

            $query = TitulosEmAbertoNasajon::select('id_estabelecimento', 'cod_cliente', 'nota_id', 'numero','valor', 'saldotitulo', 'titulo_emissao', 'vencimento', 'percentualjurosdiario', 'multa', 'titulo_id', 'codigo', 'nome_cliente', 'cnpj'); 
            $query->with(['estabelecimento_detalhes', 'cliente', 'cenprot', 'devolucoes' => function($query){
                $query->whereNotIn('devolucao_nota_status_id', [11, 8, 5]);
            }]);
            $query->whereNotIn('cod_cliente', $this->clientesExcluido());
            $query->where('vencimento', '<', $data_menos_31);
            $query->where('enviado_para_cartorio', false);
            $query->whereNotIn('banco_codigo', ['0']);
            $query->whereNotNull('banco_codigo');
            $query->where('titulo_emissao', '>=', '2020-09-20');
            $query->where('saldotitulo', '>', 100);
            $query->whereNotNull('nota_id');
            $query->distinct();
            $result = $query->get();
    
            $dados_email = '';
            $estabelecimentos = returnEmpresasNasajonView();
            foreach($result as $titulo){
                if($titulo->devolucoes->count() === 0 && empty($titulo->cenprot)){
                    $empresa = $titulo->estabelecimento_detalhes->raizcnpj.$titulo->estabelecimento_detalhes->ordemcnpj;
                    switch($titulo->estabelecimento_detalhes->codigo){
                        case '04':
                            $uf = 'TO';
                            break;
                        case '03':
                            $uf = 'RO';
                            break;
                        default:
                            $uf = 'SP';
                    }
        
                    $cenprot_titulo = $this->formatacaoTituloCeprot($titulo->numero);
                    
                    $xml = [
                        'Body' => [
                            'EnviarTitulo' => [
                                '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                                'token' => [
                                    '_attributes' => ['xmlns' => ""],
                                    '_value' => $this->token
                                ],
                                'titulo' => [
                                    '_attributes' => ['xmlns' => ""],
                                    'alteracao' => 'N',
                                    'cedente' => [
                                        'nome' => substr($titulo->estabelecimento_detalhes->nomefantasia, 0, 100),
                                        'documentoTipo' => 2,
                                        'documento' => substr($empresa, 0, 14),
                                        'endereco' => substr($titulo->estabelecimento_detalhes->tipologradouro.' '.$titulo->estabelecimento_detalhes->logradouro, 0, 100),
                                        'numero' => $titulo->estabelecimento_detalhes->numero,
                                        'complemento' => substr($titulo->estabelecimento_detalhes->complemento, 0, 50),
                                        'cep' => substr($titulo->estabelecimento_detalhes->cep, 0, 8),
                                        'bairro' => substr($titulo->estabelecimento_detalhes->bairro, 0, 20),
                                        'municipio' => substr($titulo->estabelecimento_detalhes->cidade, 0, 50),
                                        'uf' => $uf,
                                    ],
                                    'sacador' => [
                                        'nome' => substr($titulo->estabelecimento_detalhes->nomefantasia, 0, 100),
                                        'documentoTipo' => 2,
                                        'documento' => substr($empresa, 0, 14),
                                        'endereco' => substr($titulo->estabelecimento_detalhes->tipologradouro.' '.$titulo->estabelecimento_detalhes->logradouro, 0, 100),
                                        'numero' => $titulo->estabelecimento_detalhes->numero,
                                        'complemento' => substr($titulo->estabelecimento_detalhes->complemento, 0, 50),
                                        'cep' => substr($titulo->estabelecimento_detalhes->cep, 0, 8),
                                        'bairro' => substr($titulo->estabelecimento_detalhes->bairro, 0, 20),
                                        'municipio' => substr($titulo->estabelecimento_detalhes->cidade, 0, 50),
                                        'uf' => $uf,
                                        'empresa' => '',
                                        'filial' => '',
                                    ],
                                    'devedor' => [
                                        'nome' => substr($titulo->cliente->nome, 0, 100),
                                        'documentoTipo' => 2,
                                        'documento' => str_replace("-", "", str_replace("/", "", str_replace(".", "", $titulo->cliente->cpf_cnpj))),
                                        'endereco' => substr($titulo->cliente->tipologradouro." ".$titulo->cliente->logradouro, 0, 100),
                                        'numero' => $titulo->cliente->numero,
                                        'complemento' => substr($titulo->cliente->complemento, 0, 50),
                                        'cep' => substr($titulo->cliente->cep, 0, 8),
                                        'bairro' => substr($titulo->cliente->bairro, 0, 20),
                                        'municipio' => substr($titulo->cliente->cidade, 0, 50),
                                        'uf' => $titulo->cliente->uf,
                                        'principal' => 'S',
                                    ],
                                    'divida' => [
                                        'especie' => 'DMI',
                                        'numero' => $cenprot_titulo,
                                        'nossoNumero' => substr($titulo->numero, 0, 14),
                                        'valor' => $titulo->valor,
                                        'saldo' => $titulo->valor,
                                        'tipoEndosso' => 'B',
                                        'aceite' => 'N',
                                        'finsFalimentares' => 'N',
                                        'declaracaoPortador' => 'D',
                                        'emissao' => parserData($titulo->titulo_emissao),
                                        'vencimento' => parserData($titulo->vencimento),
                                        'planilha' =>[
                                            'juros'=> 0,
                                            'multa' => 0,
                                            'mora' => 0,
                                            'calculo' => [
                                                'parcela' => 1,
                                                'vencimento' => parserData($titulo->vencimento),
                                                'valor' => $titulo->valor,
                                                'saldo' => $titulo->valor,
                                                'juros' => $titulo->percentualjurosdiario,
                                                'multa' => $titulo->multa,
                                                'mora' => 0,
                                                'observacao' => '',
                                            ]
                                        ],
                                        'pracaManual' => '',
                                        'anotacao' => '',
                                    ]
                                ]
                            ]
                        ]
                    ];
                    
                    $xml = Xml::encode($xml, [
                        'rootElementName' => 'Envelope',
                        '_attributes' => [
                            'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                        ],
                    ], true, 'UTF-8');
    
                    $array = [];
                    $array['devedor_documento'] =str_replace("-", "", str_replace("/", "", str_replace(".", "", $titulo->cliente->cpf_cnpj)));
                    $array['titulo_id'] = $titulo->titulo_id;
                    $array['titulo_numero'] = $titulo->numero;
                    $array['nosso_umero'] = substr($titulo->numero, 0, 14);
                    $array['especie'] = 'DMI';
                    $array['vencimento'] = $titulo->vencimento;
                    $array['cenprot_titulo'] = $cenprot_titulo;
                    $array['cenprot_status'] = "COLETADO";
    
                    $client = new \GuzzleHttp\Client();
        
                    $options  = [
                        'headers' => [
                            'Content-Type' => 'text/xml; charset=UTF8',
                        ],
                        'body' => $xml,
                    ];
            
                    $this->setUrlRequest();
        
                    try{
                        $response = $client->request(
                            'POST', 
                            $this->url, 
                            $options
                        );
                    }catch(\Exception $e){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Erro ao enviar o título',
                            'error' => [$e],
                            'response' => []
                        ],422);
                    }
                    
                    $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
                    $retorno = Xml::decode($retorno);
        
                    $this->gravarTitulo($array);
                    $this->gravarLog(Auth::id(), 'enviar', $titulo->titulo_id, $titulo->numero, $cenprot_titulo, "COLETADO");
                    
                    $dados_email = $dados_email."Estabelecimento:".$estabelecimentos[intval($titulo->codigo)]."<br>Titulo : ".$titulo->numero."<br>Cliente : ".$titulo->nome_cliente." - ".$titulo->cnpj."<br>Emissão:".parserData($titulo->titulo_emissao)."<br>Vencimento:".parserData($titulo->vencimento)."<br><br>";
                } 
            }
    
            $titulos_irregulares = $this->atualizarStatusDosTitulos();
    
            $dados_email = $dados_email.$titulos_irregulares;
    
            $this->emailCenprotAutomatico($dados_email);
        }
        
    }

    public function atualizarStatusDosTitulos(){
        $this->autenticacaoAPI();

        $query = CenprotTitulo::select();
        $query->with(['titulosEmAbertoNasajon', 'criadoPor', 'excluidoPor']);
        $query->whereNotIn('cenprot_status', ['CANCELADO', 'PAGO', 'RETIRADO', 'DEVOLVIDO']);
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $titulos_irregulares = '';
        foreach($result as $titulo){
            $xml = [
                'Body' => [
                    'ConsultarTitulo' => [
                        '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                        'token' => [
                            '_attributes' => ['xmlns' => ""],
                            '_value' => $this->token
                        ],
                        'completa' => [
                            '_attributes' => ['xmlns' => ""],
                            '_value' => 'S'
                        ],
                        'titulo' => [
                            '_attributes' => ['xmlns' => ""],
                            'devedor' => [
                                'documento' => $titulo->devedor_documento,
                            ],
                            'divida' => [
                                'numero' => $titulo->cenprot_titulo,
                                'nossoNumero' => $titulo->nosso_umero,
                                'vencimento' => parserData($titulo->vencimento),
                                'especie' => $titulo->especie,
                                'emissao' => '',
                                'praca' => '',
                            ],
                        ],
                    ]
                ]
            ];

            $xml = Xml::encode($xml, [
                'rootElementName' => 'Envelope',
                '_attributes' => [
                    'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                ],
            ], true, 'UTF-8');

            $client = new \GuzzleHttp\Client();

            $options  = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml,
            ];

            $this->setUrlRequest();

            try{
                $response = $client->request(
                    'POST', 
                    $this->url, 
                    $options
                );
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao consultar',
                    'error' => [$e],
                    'response' => []
                ],422);
            }

            $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
            $retorno = Xml::decode($retorno);

            $retorno_titulo = $retorno['soapBody']['ns2ConsultarTituloResponse']['titulo'];
            $cenprotTituloObj = CenprotTitulo::find($titulo->id);

            $mensagem_cenprot = empty($retorno_titulo['resposta']['mensagem'])? '' : $retorno_titulo['resposta']['mensagem'];
            if(($retorno_titulo['resposta']['codigo'] !== $titulo->cenprot_status || $mensagem_cenprot !== $titulo->cenprot_mensagem) && $retorno_titulo['resposta']['codigo'] !== "INEXISTENTE"){
                $liberado = false;
                if(!empty($retorno_titulo['ocorrencia'])){
                    foreach($retorno_titulo['ocorrencia'] as $ocorrencia){
                        if($liberado){
                            $cenprotLogObj = new CenprotLog;
                            $cenprotLogObj->users_id = Auth::id(); 
                            $cenprotLogObj->acao = 'atualizar_status'; 
                            $cenprotLogObj->titulo_id = $titulo->titulo_id;
                            $cenprotLogObj->titulo_numero = $titulo->titulo_numero;
                            $cenprotLogObj->cenprot_titulo = $titulo->cenprot_titulo;
                            $cenprotLogObj->cenprot_status = $ocorrencia['status'];
                            $cenprotLogObj->cenprot_status_data_hora = $ocorrencia['dataHora'];
                            if(!empty($mensagem_cenprot)){
                                $cenprotLogObj->cenprot_mensagem = $mensagem_cenprot;
                            }
                            $cenprotLogObj->created_by = Auth::id();
                            $cenprotLogObj->save();
                        }
    
                        if($ocorrencia['status'] === $titulo->cenprot_status){
                            $liberado = true;
                        }
                    }
                }

                if($retorno_titulo['resposta']['codigo'] == 'COLETADO' && !empty($mensagem_cenprot)){
                    $cenprotLogObj = new CenprotLog;
                    $cenprotLogObj->users_id = Auth::id(); 
                    $cenprotLogObj->acao = 'atualizar_status'; 
                    $cenprotLogObj->titulo_id = $titulo->titulo_id;
                    $cenprotLogObj->titulo_numero = $titulo->titulo_numero;
                    $cenprotLogObj->cenprot_titulo = $titulo->cenprot_titulo;
                    $cenprotLogObj->cenprot_status = 'ERRO AO COLETAR';
                    $cenprotLogObj->cenprot_status_data_hora = Carbon::now();
                    $cenprotLogObj->cenprot_mensagem = $mensagem_cenprot;
                    $cenprotLogObj->created_by = Auth::id();
                    $cenprotLogObj->save();

                    $cenprotTituloObj->cenprot_status = 'ERRO AO COLETAR';

                    if(empty($titulos_irregulares)){
                        $titulos_irregulares = "<br />Os títulos abaixo estão com irregularidade na Cenprot:<br /><br /><br />";
                    }
                    $titulos_irregulares = empty($titulo->titulosEmAbertoNasajon)? '' : $titulos_irregulares."Estabelecimento:".$estabelecimentos[intval($titulo->titulosEmAbertoNasajon->codigo)]."<br>Titulo : ".$titulo->titulosEmAbertoNasajon->numero."<br>Cliente : ".$titulo->titulosEmAbertoNasajon->nome_cliente." - ".$titulo->titulosEmAbertoNasajon->cnpj."<br>Emissão:".parserData($titulo->titulosEmAbertoNasajon->titulo_emissao)."<br>Vencimento:".parserData($titulo->titulosEmAbertoNasajon->vencimento)."<br>Motivo:".$mensagem_cenprot."<br><br>";
                }else{
                    $cenprotTituloObj->cenprot_status = $retorno_titulo['resposta']['codigo']; 
                }
                
                if(!empty($mensagem_cenprot)){
                    $cenprotTituloObj->cenprot_mensagem = $mensagem_cenprot;
                }
                
                $cenprotTituloObj->updated_by = Auth::id();
                $cenprotTituloObj->save();
            }else if($retorno_titulo['resposta']['codigo'] === "INEXISTENTE"){
                $cenprotLogObj = new CenprotLog;
                $cenprotLogObj->users_id = Auth::id(); 
                $cenprotLogObj->acao = 'atualizar_status'; 
                $cenprotLogObj->titulo_id = $titulo->titulo_id;
                $cenprotLogObj->titulo_numero = $titulo->titulo_numero;
                $cenprotLogObj->cenprot_titulo = $titulo->cenprot_titulo;
                $cenprotLogObj->cenprot_status = 'ERRO AO COLETAR';
                $cenprotLogObj->cenprot_status_data_hora = Carbon::now();
                $cenprotLogObj->cenprot_mensagem = 'INEXISTENTE';
                $cenprotLogObj->created_by = Auth::id();
                $cenprotLogObj->save();

                $cenprotTituloObj->cenprot_status = 'ERRO AO COLETAR';

                if(empty($titulos_irregulares)){
                    $titulos_irregulares = "<br />Os títulos abaixo estão com irregularidade na Cenprot:<br /><br /><br />";
                }
                $titulos_irregulares = empty($titulo->titulosEmAbertoNasajon)? '' : $titulos_irregulares."Estabelecimento:".$estabelecimentos[intval($titulo->titulosEmAbertoNasajon->codigo)]."<br>Titulo : ".$titulo->titulosEmAbertoNasajon->numero."<br>Cliente : ".$titulo->titulosEmAbertoNasajon->nome_cliente." - ".$titulo->titulosEmAbertoNasajon->cnpj."<br>Emissão:".parserData($titulo->titulosEmAbertoNasajon->titulo_emissao)."<br>Vencimento:".parserData($titulo->titulosEmAbertoNasajon->vencimento)."<br>Motivo:".'INEXISTENTE'."<br><br>";
            }
        }

        return $titulos_irregulares;
    }

    public function enviarTitulo(Request $request){
        $fields = $request->only('titulo', 'cenprot_id');

        $this->autenticacaoAPI();

        if(!empty($fields['cenprot_id'])){
            $cenprot_id = decrypt($fields['cenprot_id']);
        }else{
            $cenprot_id = '';
        }
        
        $query = TitulosEmAbertoNasajon::select();
        $query->with(['estabelecimento_detalhes', 'cliente']);
        $query->where('numero', $fields['titulo']);
        $result = $query->first();

        if(!empty($result)){
            $empresa = $result->estabelecimento_detalhes->raizcnpj.$result->estabelecimento_detalhes->ordemcnpj;
            switch($result->estabelecimento_detalhes->codigo){
                case '04':
                    $uf = 'TO';
                    break;
                case '03':
                    $uf = 'RO';
                    break;
                default:
                    $uf = 'SP';
            }

            $cenprot_titulo = $this->formatacaoTituloCeprot($result->numero);

            $xml = [
                'Body' => [
                    'EnviarTitulo' => [
                        '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                        'token' => [
                            '_attributes' => ['xmlns' => ""],
                            '_value' => $this->token
                        ],
                        'titulo' => [
                            '_attributes' => ['xmlns' => ""],
                            'alteracao' => 'N',
                            'cedente' => [
                                'nome' => substr($result->estabelecimento_detalhes->nomefantasia, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => substr($empresa, 0, 14),
                                'endereco' => substr($result->estabelecimento_detalhes->tipologradouro.' '.$result->estabelecimento_detalhes->logradouro, 0, 100),
                                'numero' => $result->estabelecimento_detalhes->numero,
                                'complemento' => substr($result->estabelecimento_detalhes->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->estabelecimento_detalhes->cep), 0, 8),
                                'bairro' => substr($result->estabelecimento_detalhes->bairro, 0, 20),
                                'municipio' => substr($result->estabelecimento_detalhes->cidade, 0, 50),
                                'uf' => $uf,
                            ],
                            'sacador' => [
                                'nome' => substr($result->estabelecimento_detalhes->nomefantasia, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => substr($empresa, 0, 14),
                                'endereco' => substr($result->estabelecimento_detalhes->tipologradouro.' '.$result->estabelecimento_detalhes->logradouro, 0, 100),
                                'numero' => $result->estabelecimento_detalhes->numero,
                                'complemento' => substr($result->estabelecimento_detalhes->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->estabelecimento_detalhes->cep), 0, 8),
                                'bairro' => substr($result->estabelecimento_detalhes->bairro, 0, 20),
                                'municipio' => substr($result->estabelecimento_detalhes->cidade, 0, 50),
                                'uf' => $uf,
                                'empresa' => '',
                                'filial' => '',
                            ],
                            'devedor' => [
                                'nome' => substr($result->cliente->nome, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj))),
                                'endereco' => substr($result->cliente->tipologradouro." ".$result->cliente->logradouro, 0, 100),
                                'numero' => $result->cliente->numero,
                                'complemento' => substr($result->cliente->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->cliente->cep), 0, 8),
                                'bairro' => substr($result->cliente->bairro, 0, 20),
                                'municipio' => substr($result->cliente->cidade, 0, 50),
                                'uf' => $result->cliente->uf,
                                'principal' => 'S',
                            ],
                            'divida' => [
                                'especie' => 'DMI',
                                'numero' => $cenprot_titulo,
                                'nossoNumero' => substr($result->numero, 0, 14),
                                'valor' => $result->valor,
                                'saldo' => $result->valor,
                                'tipoEndosso' => 'B',
                                'aceite' => 'N',
                                'finsFalimentares' => 'N',
                                'declaracaoPortador' => 'D',
                                'emissao' => parserData($result->titulo_emissao),
                                'vencimento' => parserData($result->vencimento),
                                'planilha' =>[
                                    'juros'=> 0,
                                    'multa' => 0,
                                    'mora' => 0,
                                    'calculo' => [
                                        'parcela' => 1,
                                        'vencimento' => parserData($result->vencimento),
                                        'valor' => $result->valor,
                                        'saldo' => $result->valor,
                                        'juros' => $result->percentualjurosdiario,
                                        'multa' => $result->multa,
                                        'mora' => 0,
                                        'observacao' => '',
                                    ]
                                ],
                                'pracaManual' => '',
                                'anotacao' => '',
                            ]
                        ]
                    ]
                ]
            ];
            
            $xml = Xml::encode($xml, [
                'rootElementName' => 'Envelope',
                '_attributes' => [
                    'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                ],
            ], true, 'UTF-8');

            $array = [];
            $array['devedor_documento'] =str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj)));
            $array['titulo_id'] = $result->titulo_id;
            $array['titulo_numero'] = $result->numero;
            $array['nosso_umero'] = substr($result->numero, 0, 14);
            $array['especie'] = 'DMI';
            $array['vencimento'] = $result->vencimento;
            $array['cenprot_titulo'] = $cenprot_titulo;
            $array['cenprot_status'] = "COLETADO";

            $client = new \GuzzleHttp\Client();

            $options  = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml,
            ];
            
            try{
                $this->setUrlRequest();

                $response = $client->request(
                    'POST', 
                    $this->url, 
                    $options
                );
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao enviar o título',
                    'error' => [$e],
                    'response' => []
                ],422);
            }
            
            if($response->getStatusCode() != 200){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao enviar o título',
                    'error' => [],
                    'response' => []
                ],422);
            }else{
                $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
                $retorno = Xml::decode($retorno);

                if($retorno['soapBody']['ns2EnviarTituloResponse']['titulo']['resposta']['status'] == "false"){
                    return response()->json([
                        'status' => 'error',
                        'message' => $retorno['soapBody']['ns2EnviarTituloResponse']['titulo']['resposta']['mensagem'],
                        'error' => [],
                        'response' => []
                    ],422);
                }else{
                    if(empty($cenprot_id)){
                        $this->gravarTitulo($array);
                    }else{
                        $array['cenprot_id'] = $cenprot_id;
                        $this->atualizarTitulo($array);
                    }
                    
                    $this->gravarLog(Auth::id(), 'enviar', $result->titulo_id, $result->numero, $cenprot_titulo, "COLETADO");

                    $response = [
                        "status" => 'success',
                        "message" => '',
                        "error" => [],
                        "response" => []
                    ];
                    return response()->json($response);
                }
            }            
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao enviar o título',
                'error' => [],
                'response' => []
            ],422);
        }        
    }

    public function remocaoTitulo(Request $request){
        $fields = $request->only('titulo', 'cenprot_id');

        $this->autenticacaoAPI();

        $query = CenprotTitulo::select();
        $query->with(['titulosEmAbertoNasajon','criadoPor', 'excluidoPor']);
        $query->where('titulo_numero', $fields['titulo']);
        $titulo = $query->first();

        $xml = [
            'Body' => [
                'ConsultarTitulo' => [
                    '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                    'token' => [
                        '_attributes' => ['xmlns' => ""],
                        '_value' => $this->token
                    ],
                    'completa' => [
                        '_attributes' => ['xmlns' => ""],
                        '_value' => 'S'
                    ],
                    'titulo' => [
                        '_attributes' => ['xmlns' => ""],
                        'devedor' => [
                            'documento' => $titulo->devedor_documento,
                        ],
                        'divida' => [
                            'numero' => $titulo->cenprot_titulo,
                            'nossoNumero' => $titulo->nosso_umero,
                            'vencimento' => parserData($titulo->vencimento),
                            'especie' => $titulo->especie,
                            'emissao' => '',
                            'praca' => '',
                        ],
                    ],
                ]
            ]
        ];

        $xml = Xml::encode($xml, [
            'rootElementName' => 'Envelope',
            '_attributes' => [
                'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
            ],
        ], true, 'UTF-8');

        $client = new \GuzzleHttp\Client();

        $options  = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
            ],
            'body' => $xml,
        ];

        $this->setUrlRequest();

        try{
            $response = $client->request(
                'POST', 
                $this->url, 
                $options
            );
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consultar',
                'error' => [$e],
                'response' => []
            ],422);
        }

        $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
        $retorno = Xml::decode($retorno);

        $retorno_titulo = $retorno['soapBody']['ns2ConsultarTituloResponse']['titulo'];

        if(in_array($retorno_titulo['resposta']['codigo'], ['COLETADO', 'GERADO', 'CONFIRMADO','PROTESTADO', 'INEXISTENTE'])){
            $query = TitulosEmAbertoNasajon::select();
            $query->with(['estabelecimento_detalhes', 'cliente']);
            $query->where('numero', $fields['titulo']);
            $result = $query->first();
    
            $cenprot_titulo = $this->formatacaoTituloCeprot($result->numero);

            if($retorno_titulo['resposta']['codigo'] === 'INEXISTENTE'){
                $user_id = empty(Auth::id())? 1 : Auth::id();
                $this->gravarLog($user_id, 'remover', $result->titulo_id, $result->numero, $cenprot_titulo, "REMOVIDO");
                $this->deletarTitulo($fields['cenprot_id']);

                $response = [
                    "status" => 'success',
                    "message" => '',
                    "error" => [],
                    "response" => []
                ];
                return response()->json($response);
            }

            switch($retorno_titulo['resposta']['codigo']){
                case 'GERADO':
                case 'COLETADO':
                    $operacao = "REMOCAO";
                    break;
                case 'CONFIRMADO':
                    $operacao = "DESISTENCIA";
                    break;
                case 'PROTESTADO':
                    $operacao = "CANCELAMENTO";
                    break;
            }
            
            $xml = [
                'Body' => [
                    'OperacaoTitulo' => [
                        '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                        'token' => [
                            '_attributes' => ['xmlns' => ""],
                            '_value' => $this->token
                        ],
                        'titulo' => [
                            '_attributes' => ['xmlns' => ""],
                            'autoriza' => 'S',
                            'operacao' => 'REMOCAO',
                            'devedor' => [
                                'documento' => str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj))),
                            ],
                            'divida' => [
                                'numero' => $cenprot_titulo,
                                'nossoNumero' => substr($result->numero, 0, 14),
                                'documento' => [
                                   'extensao' => '',
                                   'documentoBase64' => '',
                                ],
                                'vencimento' => parserData($result->vencimento),
                                'especie' => '',
                            ],
                        ],
                    ]
                ]
            ];
    
            $xml = Xml::encode($xml, [
                'rootElementName' => 'Envelope',
                '_attributes' => [
                    'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                ],
            ], true, 'UTF-8');
    
            $client = new \GuzzleHttp\Client();
    
            $options  = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml,
            ];
    
            try{
                $this->setUrlRequest();
    
                $response = $client->request(
                    'POST', 
                    $this->url, 
                    $options
                );
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao remover o título',
                    'error' => [$e],
                    'response' => []
                ],422);
            }
    
            if($response->getStatusCode() != 200){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao remover o título',
                    'error' => [],
                    'response' => []
                ],422);
            }else{
                $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
                $retorno = Xml::decode($retorno);
    
                if($retorno['soapBody']['ns2OperacaoTituloResponse']['titulo']['resposta']['status'] == "false"){
                    return response()->json([
                        'status' => 'error',
                        'message' => $retorno['soapBody']['ns2OperacaoTituloResponse']['titulo']['resposta']['mensagem'],
                        'error' => [],
                        'response' => []
                    ],422);
                }else{
                    $user_id = empty(Auth::id())? 1 : Auth::id();
                    $this->gravarLog($user_id, 'remover', $result->titulo_id, $result->numero, $cenprot_titulo, "REMOVIDO");
                    $this->deletarTitulo($fields['cenprot_id']);
    
                    $response = [
                        "status" => 'success',
                        "message" => '',
                        "error" => [],
                        "response" => []
                    ];
                    return response()->json($response);
                }
            }              
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Não é possivel excluir titulos com status '.$retorno_titulo['resposta']['codigo'],
                'error' => [],
                'response' => []
            ],422);
        }
       
    }

    public function filter(Request $request){
        $fields = $request->only('estabelecimento', 'cliente', 'titulo', 'data_envio_inicial', 'data_envio_final', 'cenprot_status');

        $this->autenticacaoAPI();

        $empresa = returnEmpresasNasajonView();

        $query = CenprotTitulo::select();
        $query->with(['titulosEmAbertoNasajon' => function ($query) use($fields){
            if(!empty($fields['estabelecimento'])){
                $query->where('codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            }
            if(!empty($fields['cliente'])){
                $query->with(['cliente' => function ($query) use($fields){
                    $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                }]);
                $query->whereHas('cliente', function ($query) use($fields){
                    $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
                });
            }else{
                $query->with(['cliente']);
            }
            
        },'criadoPor', 'excluidoPor']);

        if(!empty($fields['titulo'])){
            $query->where('titulo_numero', 'ilike', '%'.$fields['titulo'].'%');
        }
        if(!empty($fields['cenprot_status'])){
            $query->where('cenprot_status', 'ilike', $fields['cenprot_status']);
        }
        if(!empty($fields['data_envio_inicial'])){
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_envio_inicial'])->setTime(0,0,0);
            $query->where('created_at', '>=', $data_inicio);
        }
        if(!empty($fields['data_envio_final'])){
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_envio_final'])->setTime(23,59,59);
            $query->where('created_at', '<=', $data_fim);
        }
        $result = $query->get();

        $titulos = [];

        foreach($result as $titulo){
            if(!empty($titulo->titulosEmAbertoNasajon)){
                $xml = [
                    'Body' => [
                        'ConsultarTitulo' => [
                            '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                            'token' => [
                                '_attributes' => ['xmlns' => ""],
                                '_value' => $this->token
                            ],
                            'completa' => [
                                '_attributes' => ['xmlns' => ""],
                                '_value' => 'N'
                            ],
                            'titulo' => [
                                '_attributes' => ['xmlns' => ""],
                                'devedor' => [
                                    'documento' => $titulo->devedor_documento,
                                ],
                                'divida' => [
                                    'numero' => $titulo->cenprot_titulo,
                                    'nossoNumero' => $titulo->nosso_umero,
                                    'vencimento' => parserData($titulo->vencimento),
                                    'especie' => $titulo->especie,
                                    'emissao' => '',
                                    'praca' => '',
                                ],
                            ],
                        ]
                    ]
                ];
    
                $xml = Xml::encode($xml, [
                    'rootElementName' => 'Envelope',
                    '_attributes' => [
                        'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                    ],
                ], true, 'UTF-8');
    
                $client = new \GuzzleHttp\Client();
    
                $options  = [
                    'headers' => [
                        'Content-Type' => 'text/xml; charset=UTF8',
                    ],
                    'body' => $xml,
                ];

                $this->setUrlRequest();
    
                try{
                    $response = $client->request(
                        'POST', 
                        $this->url, 
                        $options
                    );
                }catch(\Exception $e){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Erro ao consultar',
                        'error' => [$e],
                        'response' => []
                    ],422);
                }
    
                $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
                $retorno = Xml::decode($retorno);
    
                $retorno_titulo = $retorno['soapBody']['ns2ConsultarTituloResponse']['titulo'];
                
                $atualizado_por = empty($titulo->atualizadoPor)? '' : $titulo->atualizadoPor->name;

                if($retorno_titulo['resposta']['codigo'] === "INEXISTENTE"){
                    $status = 'REMOVIDO';
                }else if($titulo->cenprot_status == 'ERRO AO COLETAR'){
                    $status = 'ERRO AO COLETAR';
                }else{
                    $status = $retorno_titulo['resposta']['codigo'];
                }
                $titulos [$titulo->id] = [
                    'estabelecimento' => $empresa[(int) $titulo->titulosEmAbertoNasajon->codigo],
                    'cliente' => $titulo->titulosEmAbertoNasajon->cliente->nome." - ".$titulo->titulosEmAbertoNasajon->cliente->cpf_cnpj,
                    'titulo' => $titulo->titulo_numero,
                    'titulo_cenprot' => $titulo->cenprot_titulo,
                    'emissao' => parserData($titulo->titulosEmAbertoNasajon->titulo_emissao),
                    'vencimento' => $retorno_titulo['divida']['vencimento'],
                    'criado_por' => empty($titulo->criadoPor)? '' : $titulo->criadoPor->name,
                    'removido_por' => $retorno_titulo['resposta']['codigo'] === "INEXISTENTE"? $atualizado_por : '',
                    'status' => $titulo->cenprot_status,
                    'cenprot_id' => encrypt($titulo->id),
                    'data_envio' => parserData($titulo->created_at),
                ];
            }
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $titulos
        ];
        return response()->json($response);
    }

    private function gravarLog($user, $acao, $titulo_id, $titulo_numero, $cenprot_titulo, $cenprot_status){
        $cenprotLogObj = new CenprotLog;
        $cenprotLogObj->users_id = $user; 
        $cenprotLogObj->acao = $acao; 
        $cenprotLogObj->titulo_id = $titulo_id;
        $cenprotLogObj->titulo_numero = $titulo_numero;
        $cenprotLogObj->cenprot_titulo = $cenprot_titulo;
        $cenprotLogObj->cenprot_status = $cenprot_status;
        $cenprotLogObj->created_by = empty(Auth::id())? 1 : Auth::id();
        $cenprotLogObj->save();
    }

    private function gravarTitulo($array){
        $cenprotTituloObj = new CenprotTitulo;
        $cenprotTituloObj->devedor_documento = $array['devedor_documento'];
        $cenprotTituloObj->titulo_id = $array['titulo_id'];
        $cenprotTituloObj->titulo_numero = $array['titulo_numero'];
        $cenprotTituloObj->nosso_umero = $array['nosso_umero'];
        $cenprotTituloObj->especie = $array['especie'];
        $cenprotTituloObj->vencimento = $array['vencimento'];
        $cenprotTituloObj->cenprot_titulo = $array['cenprot_titulo'];
        $cenprotTituloObj->cenprot_status = $array['cenprot_status'];
        $cenprotTituloObj->created_by = empty(Auth::id())? 1 : Auth::id();
        $cenprotTituloObj->save();
    }

    private function atualizarTitulo($array){
        $cenprotTituloObj = CenprotTitulo::find($array['cenprot_id']);
        $cenprotTituloObj->devedor_documento = $array['devedor_documento'];
        $cenprotTituloObj->titulo_id = $array['titulo_id'];
        $cenprotTituloObj->titulo_numero = $array['titulo_numero'];
        $cenprotTituloObj->nosso_umero = $array['nosso_umero'];
        $cenprotTituloObj->especie = $array['especie'];
        $cenprotTituloObj->vencimento = $array['vencimento'];
        $cenprotTituloObj->cenprot_titulo = $array['cenprot_titulo'];
        $cenprotTituloObj->cenprot_status = $array['cenprot_status'];
        if(!empty($array['cenprot_mensagem'])){
            $cenprotTituloObj->cenprot_mensagem = $array['cenprot_mensagem'];
        }
        $cenprotTituloObj->created_by = empty(Auth::id())? 1 : Auth::id();
        $cenprotTituloObj->save();
    }

    private function deletarTitulo($cenprot_id){
        $cenprot_id = decrypt($cenprot_id);

        $cenprotTituloObj = CenprotTitulo::find($cenprot_id);
        $cenprotTituloObj->cenprot_status = "REMOVIDO";
        $cenprotTituloObj->updated_by = empty(Auth::id())? 1 : Auth::id();
        $cenprotTituloObj->save();
    }
    
    private function removerZeroAEsquerda($value){
        $semzerosaesquerda = preg_replace("@0+@","",$value);

        return $semzerosaesquerda;
    }

    private function formatacaoTituloCeprot($value){
        $value = $this->removerZeroAEsquerda($value);

        $value = substr($value, 0, 10);

        return $value;
    }

    public function modalHistorico(Request $request){
        $fields = $request->only('cenprot_id');

        $historico = [];

        $cenprot_id = decrypt($fields['cenprot_id']);

        $titulo = CenprotTitulo::withTrashed()->find($cenprot_id);

        $this->autenticacaoAPI();

        $xml = [
            'Body' => [
                'ConsultarTitulo' => [
                    '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                    'token' => [
                        '_attributes' => ['xmlns' => ""],
                        '_value' => $this->token
                    ],
                    'completa' => [
                        '_attributes' => ['xmlns' => ""],
                        '_value' => 'S'
                    ],
                    'titulo' => [
                        '_attributes' => ['xmlns' => ""],
                        'devedor' => [
                            'documento' => $titulo->devedor_documento,
                        ],
                        'divida' => [
                            'numero' => $titulo->cenprot_titulo,
                            'nossoNumero' => $titulo->nosso_umero,
                            'vencimento' => parserData($titulo->vencimento),
                            'especie' => $titulo->especie,
                            'emissao' => '',
                            'praca' => '',
                        ],
                    ],
                ]
            ]
        ];

        $xml = Xml::encode($xml, [
            'rootElementName' => 'Envelope',
            '_attributes' => [
                'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
            ],
        ], true, 'UTF-8');

        $client = new \GuzzleHttp\Client();

        $options  = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
            ],
            'body' => $xml,
        ];

        $this->setUrlRequest();

        try{
            $response = $client->request(
                'POST', 
                $this->url, 
                $options
            );
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao consultar',
                'error' => [$e],
                'response' => []
            ],422);
        }

        $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
        $retorno = Xml::decode($retorno);

        $retorno_titulo = $retorno['soapBody']['ns2ConsultarTituloResponse']['titulo'];

        if($retorno_titulo['resposta']['codigo'] === "COLETADO" || $retorno_titulo['resposta']['codigo'] === "INEXISTENTE"){
            $query_log = CenprotLog::select();
            $query_log->where('titulo_id', $titulo->titulo_id);
            $result_log = $query_log->get();

            foreach($result_log as $log){
                $historico [] = [
                    'titulo' => $log->titulo_numero,
                    'titulo_cenprot' => $log->cenprot_titulo,
                    'data_hora' => empty($log->cenprot_status_data_hora)? parserDataEHora($log->created_at) : parserDataEHora($log->cenprot_status_data_hora),
                    'criado_por' => empty($log->criadoPor)? '' : $log->criadoPor->name,
                    'mensagem' => empty($log->cenprot_mensagem)? '' : $log->cenprot_mensagem,
                    'status' => $log->cenprot_status,
                    'protocolo' => "",
                    'protocolo_data' => "",
                ];
            }
        }else{
            foreach($retorno_titulo['ocorrencia'] as $ocorrencia){
                $historico [] = [
                    'titulo' => $titulo->titulo_numero,
                    'titulo_cenprot' => $titulo->cenprot_titulo,
                    'data_hora' => $ocorrencia['dataHora'],
                    'criado_por' =>  $ocorrencia['status'] === "COLETADO"? $titulo->criadoPor->name : '',
                    'mensagem' => empty($ocorrencia['mensagem'])? '' : $ocorrencia['mensagem'],
                    'status' => $ocorrencia['status'],
                    'protocolo' => empty($ocorrencia['protocolo'])? '' : $ocorrencia['protocolo']['protocoloCartorio'],
                    'protocolo_data' => empty($ocorrencia['protocolo'])? '' : $ocorrencia['protocolo']['dataProtocolo'],
                ];
            }
        }

        return view('programs.cenprot.modal.historico')->with(['historico' => $historico]);
    }

    public function emailCenprotAutomatico($titulos){
        try{
            $EmailObj = new EmailController();
            
            $variaveis = [
                'titulos' => $titulos,
            ];
            
            $EmailObj->sendEmailToken('00', 'email_cenprot_automatico', [], $variaveis);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [ 'mensagem' => $e],
                'response' => []
            ], 422);
		}
    }

    public function clientesExcluido(){
        $clientes_exluir = ClienteNasajon::select('codigo')
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
            ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        return $clientes_exluir;
    }

    public function verificacaoTitulosPagos(){
        ini_set('memory_limit','2048');
        $data_atual = Carbon::now()->setTime(23,59,59);
        $dia_anterior = Carbon::now()->subDay()->setTime(0,0,0);

        $query = ContasReceberBaixadoNasajon::select();
        $query->with(['cenprot' => function($query){
            $query->whereNotIn('cenprot_status', ['CANCELADO', 'PAGO', 'RETIRADO', 'DEVOLVIDO']);
        }]);
        $query->whereBetween('data_lancamento', [$dia_anterior, $data_atual]);
        $result = $query->get();
        
        foreach($result as $titulo){
            if(!empty($titulo->cenprot)){
                $arr['cenprot_id'] = encrypt($titulo->cenprot->id);
                $arr['titulo'] = $titulo->numero;
    
                $remocao_cenprot_request = new Request($arr);

                $this->remocaoTitulo($remocao_cenprot_request);

                $cenprot = CenprotTitulo::where('titulo_id',$titulo->cenprot->titulo_id)->first();
                $cenprot->deleted_by = (!empty(Auth::id())) ? Auth::id() : 1; 
                $cenprot->save();
                $cenprot->delete();
            }
        }
    }

    public function reenvioTitulo(Request $request){
        $fields = $request->only('titulo', 'cenprot_id');

        try{
            $cenprot_id = decrypt($fields['cenprot_id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $this->autenticacaoAPI();

        $query = TitulosEmAbertoNasajon::select();
        $query->with(['estabelecimento_detalhes', 'cliente']);
        $query->where('numero', $fields['titulo']);
        $result = $query->first();

        $cenprot_titulo = $this->formatacaoTituloCeprot($result->numero);

        $operacao = "REMOCAO";
        
        $xml = [
            'Body' => [
                'OperacaoTitulo' => [
                    '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                    'token' => [
                        '_attributes' => ['xmlns' => ""],
                        '_value' => $this->token
                    ],
                    'titulo' => [
                        '_attributes' => ['xmlns' => ""],
                        'autoriza' => 'S',
                        'operacao' => 'REMOCAO',
                        'devedor' => [
                            'documento' => str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj))),
                        ],
                        'divida' => [
                            'numero' => $cenprot_titulo,
                            'nossoNumero' => substr($result->numero, 0, 14),
                            'documento' => [
                                'extensao' => '',
                                'documentoBase64' => '',
                            ],
                            'vencimento' => parserData($result->vencimento),
                            'especie' => '',
                        ],
                    ],
                ]
            ]
        ];

        $xml = Xml::encode($xml, [
            'rootElementName' => 'Envelope',
            '_attributes' => [
                'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
            ],
        ], true, 'UTF-8');

        $client = new \GuzzleHttp\Client();

        $options  = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
            ],
            'body' => $xml,
        ];

        try{
            $this->setUrlRequest();

            $response = $client->request(
                'POST', 
                $this->url, 
                $options
            );
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remover o título',
                'error' => [$e],
                'response' => []
            ],422);
        }

        if($response->getStatusCode() != 200){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao remover o título',
                'error' => [],
                'response' => []
            ],422);
        }else{
            $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
            $retorno = Xml::decode($retorno);

            if($retorno['soapBody']['ns2OperacaoTituloResponse']['titulo']['resposta']['status'] == "false"){
                return response()->json([
                    'status' => 'error',
                    'message' => $retorno['soapBody']['ns2OperacaoTituloResponse']['titulo']['resposta']['mensagem'],
                    'error' => [],
                    'response' => []
                ],422);
            }
        }
        
        if(!empty($result)){
            $empresa = $result->estabelecimento_detalhes->raizcnpj.$result->estabelecimento_detalhes->ordemcnpj;
            switch($result->estabelecimento_detalhes->codigo){
                case '04':
                    $uf = 'TO';
                    break;
                case '03':
                    $uf = 'RO';
                    break;
                default:
                    $uf = 'SP';
            }

            $cenprot_titulo = $this->formatacaoTituloCeprot($result->numero);

            $xml = [
                'Body' => [
                    'EnviarTitulo' => [
                        '_attributes' => ['xmlns' => "http://grupobst.com.br/services"],
                        'token' => [
                            '_attributes' => ['xmlns' => ""],
                            '_value' => $this->token
                        ],
                        'titulo' => [
                            '_attributes' => ['xmlns' => ""],
                            'alteracao' => 'N',
                            'cedente' => [
                                'nome' => substr($result->estabelecimento_detalhes->nomefantasia, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => substr($empresa, 0, 14),
                                'endereco' => substr($result->estabelecimento_detalhes->tipologradouro.' '.$result->estabelecimento_detalhes->logradouro, 0, 100),
                                'numero' => $result->estabelecimento_detalhes->numero,
                                'complemento' => substr($result->estabelecimento_detalhes->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->estabelecimento_detalhes->cep), 0, 8),
                                'bairro' => substr($result->estabelecimento_detalhes->bairro, 0, 20),
                                'municipio' => substr($result->estabelecimento_detalhes->cidade, 0, 50),
                                'uf' => $uf,
                            ],
                            'sacador' => [
                                'nome' => substr($result->estabelecimento_detalhes->nomefantasia, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => substr($empresa, 0, 14),
                                'endereco' => substr($result->estabelecimento_detalhes->tipologradouro.' '.$result->estabelecimento_detalhes->logradouro, 0, 100),
                                'numero' => $result->estabelecimento_detalhes->numero,
                                'complemento' => substr($result->estabelecimento_detalhes->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->estabelecimento_detalhes->cep), 0, 8),
                                'bairro' => substr($result->estabelecimento_detalhes->bairro, 0, 20),
                                'municipio' => substr($result->estabelecimento_detalhes->cidade, 0, 50),
                                'uf' => $uf,
                                'empresa' => '',
                                'filial' => '',
                            ],
                            'devedor' => [
                                'nome' => substr($result->cliente->nome, 0, 100),
                                'documentoTipo' => 2,
                                'documento' => str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj))),
                                'endereco' => substr($result->cliente->tipologradouro." ".$result->cliente->logradouro, 0, 100),
                                'numero' => $result->cliente->numero,
                                'complemento' => substr($result->cliente->complemento, 0, 50),
                                'cep' => substr(str_replace("-", "", $result->cliente->cep), 0, 8),
                                'bairro' => substr($result->cliente->bairro, 0, 20),
                                'municipio' => substr($result->cliente->cidade, 0, 50),
                                'uf' => $result->cliente->uf,
                                'principal' => 'S',
                            ],
                            'divida' => [
                                'especie' => 'DMI',
                                'numero' => $cenprot_titulo,
                                'nossoNumero' => substr($result->numero, 0, 14),
                                'valor' => $result->valor,
                                'saldo' => $result->valor,
                                'tipoEndosso' => 'B',
                                'aceite' => 'N',
                                'finsFalimentares' => 'N',
                                'declaracaoPortador' => 'D',
                                'emissao' => parserData($result->titulo_emissao),
                                'vencimento' => parserData($result->vencimento),
                                'planilha' =>[
                                    'juros'=> 0,
                                    'multa' => 0,
                                    'mora' => 0,
                                    'calculo' => [
                                        'parcela' => 1,
                                        'vencimento' => parserData($result->vencimento),
                                        'valor' => $result->valor,
                                        'saldo' => $result->valor,
                                        'juros' => $result->percentualjurosdiario,
                                        'multa' => $result->multa,
                                        'mora' => 0,
                                        'observacao' => '',
                                    ]
                                ],
                                'pracaManual' => '',
                                'anotacao' => '',
                            ]
                        ]
                    ]
                ]
            ];
            
            $xml = Xml::encode($xml, [
                'rootElementName' => 'Envelope',
                '_attributes' => [
                    'xmlns' => 'http://schemas.xmlsoap.org/soap/envelope/',
                ],
            ], true, 'UTF-8');

            $array = [];
            $array['devedor_documento'] =str_replace("-", "", str_replace("/", "", str_replace(".", "", $result->cliente->cpf_cnpj)));
            $array['titulo_id'] = $result->titulo_id;
            $array['titulo_numero'] = $result->numero;
            $array['nosso_umero'] = substr($result->numero, 0, 14);
            $array['especie'] = 'DMI';
            $array['vencimento'] = $result->vencimento;
            $array['cenprot_titulo'] = $cenprot_titulo;
            $array['cenprot_status'] = "COLETADO";
            $array['cenprot_mensagem'] = '';

            $client = new \GuzzleHttp\Client();

            $options  = [
                'headers' => [
                    'Content-Type' => 'text/xml; charset=UTF8',
                ],
                'body' => $xml,
            ];
            
            try{
                $this->setUrlRequest();

                $response = $client->request(
                    'POST', 
                    $this->url, 
                    $options
                );
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao enviar o título',
                    'error' => [$e],
                    'response' => []
                ],422);
            }
            
            
            if($response->getStatusCode() != 200){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erro ao enviar o título',
                    'error' => [],
                    'response' => []
                ],422);
            }else{
                $retorno = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $response->getBody()->getContents());
                $retorno = Xml::decode($retorno);

                if($retorno['soapBody']['ns2EnviarTituloResponse']['titulo']['resposta']['status'] == "false"){
                    return response()->json([
                        'status' => 'error',
                        'message' => $retorno['soapBody']['ns2EnviarTituloResponse']['titulo']['resposta']['mensagem'],
                        'error' => [],
                        'response' => []
                    ],422);
                }else{
                    if(empty($cenprot_id)){
                        $this->gravarTitulo($array);
                    }else{
                        $array['cenprot_id'] = $cenprot_id;
                        $this->atualizarTitulo($array);
                    }
                    
                    $this->gravarLog(Auth::id(), 'reenviar', $result->titulo_id, $result->numero, $cenprot_titulo, "COLETADO");

                    $response = [
                        "status" => 'success',
                        "message" => '',
                        "error" => [],
                        "response" => []
                    ];
                    return response()->json($response);
                }
            }            
        }else{
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao enviar o título',
                'error' => [],
                'response' => []
            ],422);
        } 
    }

}
