<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Xml;
use Exception;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\ClienteNasajon;
use App\FornecedorNasajon;
use App\TransportadorNasajon;
use App\NotasNasajon;
use App\NotasImportadasEntradaDevolucaoReferencia;
use App\NotasImportadasEntrada;
use App\NotasEntradasNasajon;
use App\NotasImportadasEntradasIten;
use App\NasajonEstabelecimento;
use App\NotasImportadasEntradaRelacaoNota;
use App\ConhecimentoTransporteNasajon;
use App\NotasImportadasEntradasTitulosDuplicata;
use App\NotasImportadasEntradasTitulo;

class ImportarNotasEntradasController extends Controller
{   
    private $token = null;
    private $refresh_token = null;
    private $url = 'https://multinotas.nasajon.com.br/api/tecidosmnv2/';
    private $usuario = 'integracoes.tecidos@tecidosmn.com.br';
    private $senha = '061221@Mn';
    private $uuidestabelecimentos = null;

    public function __construct(){
        $this->autenticacaoAPI();
        $this->uuidestabelecimentos = $this->pegaEstabelecimentos();
    }

    private function autenticacaoAPI()
    {
        set_time_limit(12000);
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $response = $client->request('POST', 'https://auth.nasajon.com.br/auth/realms/master/protocol/openid-connect/token', [
            'form_params' => [
                'username' => $this->usuario,
                'password' => $this->senha,
                'grant_type' => 'password',
                'client_id' => 'multinotas_api',
                'scope' => 'offline_access',
            ]
        ]);
        if($response->getStatusCode() != 200){
            throw new Exception($response->getStatusCode());
            
        }
        $response = json_decode($response->getBody()->getContents());
        
        if(empty($response)){
            throw new Exception('Erro na Autenticação.');
        }

        $this->refresh_token = $response->refresh_token;

        return $this->token = $response->access_token;
    }

    private function refreshToken($token){
        set_time_limit(12000);
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $response = $client->request('POST', 'https://auth.nasajon.com.br/auth/realms/master/protocol/openid-connect/token', [
            'form_params' => [
                'client_id' => 'multinotas_api',
                'grant_type' => 'refresh_token',
                'scope' => 'offline_access',
                'refresh_token' => $token,
            ]
        ]);

        $response = json_decode($response->getBody()->getContents());

        $this->refresh_token = $response->refresh_token;

        return $this->token = $response->access_token;
    }

    private function pegaUuidEmpresa()
    {
        set_time_limit(12000);
        $tokenapi = $this->token;
        $uuid = [];
        if(empty($tokenapi)){
            throw new Exception('Token inválido ao pegar uuid das empresas.');
        }
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $request = $client->request('GET', $this->url.'empresas/', [
            'headers' => [
                'authorization' => $tokenapi,
            ],
            'timeout' => 900
        ]);

        if($request->getStatusCode() == 200){
            $response = json_decode($request->getBody()->getContents());
        }else if($request->getStatusCode() == 401){
            $this->autenticacaoAPI();
            $tokenapi = $this->token;

            $request = $client->request('GET', $this->url.'empresas/', [
                'headers' => [
                    'authorization' => $tokenapi,
                ],
                'timeout' => 900
            ]);

            $response = json_decode($request->getBody()->getContents());
        }else{
            throw new Exception($request->getStatusCode());
        }

        if(!empty($response)){
            foreach($response as $responses){
                $uuid[] = [
                    'uuid' => $responses->empresa
                ];
            }
        }else{
            throw new Exception('Erro ao pegar uuid de empresas.');
        }

        return $uuid;
    }

    private function pegaEstabelecimentos()
    {
        set_time_limit(12000);
        $uuid = $this->pegaUuidEmpresa();
        $tokenapi = $this->token;
        $uuidestabelecimentos = [];

        if(empty($tokenapi) || !is_array($uuid)){
            throw new Exception('Erro ao pegar uuid de estabelecimentos.');
        }
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        foreach($uuid as $uuids){
            $this->refreshToken($this->refresh_token);
            $tokenapi = $this->token;
            $request = $client->request('GET', $this->url.'empresas/'.$uuids['uuid'].'/estabelecimentos', [
                'headers' => [
                    'authorization' => $tokenapi,
                ],
                'timeout' => 900
            ]);
            if($request->getStatusCode() == 200){
                $response = json_decode($request->getBody()->getContents());
                foreach($response as $responses){
                    $uuidestabelecimentos[] = [
                        'uuid' => $responses->estabelecimento
                    ];
                }
            }else if($request->getStatusCode() == 401){
                $this->autenticacaoAPI();
                $tokenapi = $this->token;

                $request = $client->request('GET', $this->url.'empresas/'.$uuids['uuid'].'/estabelecimentos', [
                    'headers' => [
                        'authorization' => $tokenapi,
                    ],
                    'timeout' => 900
                ]);

                $response = json_decode($request->getBody()->getContents());
                foreach($response as $responses){
                    $uuidestabelecimentos[] = [
                        'uuid' => $responses->estabelecimento
                    ];
                }

            }else{
                throw new Exception($request->getStatusCode());
            }
        }

        return $uuidestabelecimentos;
    }

    private function buscaCTE($inicio_periodo,$fim_periodo)
    {
        set_time_limit(12000);
        $tokenapi = $this->token;
        $uuidestabelecimentos = $this->uuidestabelecimentos;

        if(empty($tokenapi) || !is_array($uuidestabelecimentos)){
            throw new Exception('Token ou uuid do estabelecimento vazio.');
        }

        $cte = [];
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        
        foreach($uuidestabelecimentos as $estabelecimento){
            $contador = 0;
            $emissaooffset = null;
            $cteoffset = null;
            $cont = 0;
            do{
                $this->refreshToken($this->refresh_token);
                $tokenapi = $this->token;
                if(empty($emissaooffset) && empty($cteoffset)){
                    $request = $client->request('GET', $this->url.'ctes/', [
                        'headers' => [
                            'authorization' => $tokenapi,
                        ],
                        'query' => [
                            'estabelecimento' => $estabelecimento['uuid'],
                            'emissao_data_inicio' => $inicio_periodo->format('Y-m-d'),
                            'emissao_data_fim' => $fim_periodo->format('Y-m-d'),
                        ],
                        'timeout' => 900
                    ]);
                }else{
                    $request = $client->request('GET', $this->url.'ctes/', [
                        'headers' => [
                            'authorization' => $tokenapi,
                        ],
                        'query' => [
                            'estabelecimento' => $estabelecimento['uuid'],
                            'emissao_data_inicio' => $inicio_periodo->format('Y-m-d'),
                            'emissao_data_fim' => $fim_periodo->format('Y-m-d'),
                            'offset[cte]' => $cteoffset,
                            'offset[emissao_data]' => $emissaooffset,
                        ],
                        'timeout' => 900
                    ]);
                }
                
                $cont ++;
                if($request->getStatusCode() == 200){
                    $response = json_decode($request->getBody()->getContents());
                    $ultimoregistro = end($response);
                    foreach($response as $responses){

                        $xmldownload = $client->request('GET', $responses->download, [
                            'headers' => [
                                'authorization' => $tokenapi,
                            ],
                            'timeout' => 900
                        ]);
                        $xml = $xmldownload->getBody()->getContents();
                        if(empty($xml)){
                            $contador = 0;
                        }
                        $cte[] = [
                            'cte' => $xml,
                            'id' => $responses->cte,
                            'cnpjestabelecimento' => $responses->estabelecimento->cnpj
                        ]; 
                    }
                    if(!empty($response)){
                        $dataemissao = Carbon::createFromFormat('Y-m-d H:i:s', substr($ultimoregistro->emissao_data,0,-3))->setTime(00,00,00);
                        
                        if($dataemissao->equalTo(Carbon::now()->setTime(00,00,00))){
                            $contador = 0;
                        }
                        $cteoffset = $ultimoregistro->cte;
                        $emissaooffset = substr($ultimoregistro->emissao_data,0,-3);
                        $contador = 1;
                    }else{
                        $contador = 0;
                    }
                    
                }else if($request->getStatusCode() == 401){
                    $this->autenticacaoAPI();
                    $tokenapi = $this->token;
                }else if($request->getStatusCode() == 500){
                    $mailBody = '<table border="1">'.
                    '<thead>'.
                        '<th> ESTABELECIMENTO </th>'.
                        '<th> DATA INICIO </th>'.
                        '<th> DATA FIM </th>'.
                        '<th> offset[cte] </th>'.
                        '<th> offset[emissao_data] </th>'.
                    '</thead>'.    
                    '<tbody>';
                    $mailBody .= '<tr><td>'.$estabelecimento['uuid'].'</td><td>'.$inicio_periodo->format('Y-m-d').'</td><td>'.$fim_periodo->format('Y-m-d').'</td><td>'.$cteoffset.'</td><td>'.$emissaooffset.'</td></tr>';
                    $mailBody .= '</tbody>'.
                    '</table>';
                    
                    $emailControllerObj = new EmailController;
                    $emailControllerObj->sendEmailToken('00', 'erro_500_importacao_notas_entrada', [], ['corpo' => $mailBody,'tipo' => 'CTE']);
                    
                    continue;
                }else if($request->getStatusCode() == 400){
                    continue;
                }else{
                    $contador = 0;
                }
            }while($contador > 0);
        }
        return $cte;
    }

    private function buscaNFE($inicio_periodo,$fim_periodo)
    {   
        set_time_limit(12000);
        $tokenapi = $this->token;
        $uuidestabelecimentos = $this->uuidestabelecimentos;
        $nfe = [];

        if(empty($tokenapi) || !is_array($uuidestabelecimentos)){
            throw new Exception('Token ou uuid do estabelecimento vazio.');
        }

        $client = new \GuzzleHttp\Client(['http_errors' => false]);

        foreach($uuidestabelecimentos as $estabelecimento){
            $contador = 0;
            $emissaooffset = null;
            $nfeoffset = null;
            do{
                $this->refreshToken($this->refresh_token);
                $tokenapi = $this->token;
                if(empty($emissaooffset) && empty($cteoffset)){
                    $request = $client->request('GET', $this->url.'nfes/', [
                        'headers' => [
                            'authorization' => $tokenapi,
                        ],
                        'query' => [
                            'estabelecimento' => $estabelecimento['uuid'],
                            'emissao_data_inicio' => $inicio_periodo->format('Y-m-d'),
                            'emissao_data_fim' => $fim_periodo->format('Y-m-d'),
                        ],
                        'timeout' => 900
                    ]);
                }else{
                    $request = $client->request('GET', $this->url.'nfes/', [
                        'headers' => [
                            'authorization' => $tokenapi,
                        ],
                        'query' => [
                            'estabelecimento' => $estabelecimento['uuid'],
                            'emissao_data_inicio' => $inicio_periodo->format('Y-m-d'),
                            'emissao_data_fim' => $fim_periodo->format('Y-m-d'),
                            'offset[nfe]' => $nfeoffset,
                            'offset[emissaodata]' => $emissaooffset,
                        ],
                        'timeout' => 900
                    ]);
                }

                if($request->getStatusCode() == 200){
                    $response = json_decode($request->getBody()->getContents());
                    $ultimoregistro = end($response);
                    foreach($response as $responses){
                        $xmldownload = $client->request('GET', $responses->download, [
                            'headers' => [
                                'authorization' => $tokenapi,
                            ]
                        ]);
                        $xml = $xmldownload->getBody()->getContents();
                        if(empty($xml)){
                            $contador = 0;
                        }
                        $nfe[] = [
                            'nfe' => $xml,
                            'chave' => $responses->chave,
                            'cnpjestabelecimento' => $responses->estabelecimento->cnpj
                        ]; 
                    }
                    if(!empty($response)){
                        $dataemissao = Carbon::createFromFormat('Y-m-d H:i:s', $ultimoregistro->emissaodata)->setTime(00,00,00);
                        if($dataemissao->equalTo(Carbon::now()->setTime(00,00,00))){
                            $contador = 0;
                        }
                        $nfeoffset = $ultimoregistro->nfe;
                        $emissaooffset = substr($ultimoregistro->emissaodata,0,-3);
                        $contador = 1;
                    }else{
                        $contador = 0;
                    }
                }else if($request->getStatusCode() == 401){
                    $this->autenticacaoAPI();
                    $tokenapi = $this->token;
                }else if($request->getStatusCode() == 500){
                    $mailBody = '<table border="1">'.
                    '<thead>'.
                        '<th> ESTABELECIMENTO </th>'.
                        '<th> DATA INICIO </th>'.
                        '<th> DATA FIM </th>'.
                        '<th> offset[cte] </th>'.
                        '<th> offset[emissao_data] </th>'.
                    '</thead>'.    
                    '<tbody>';
                    $mailBody .= '<tr><td>'.$estabelecimento['uuid'].'</td><td>'.$inicio_periodo->format('Y-m-d').'</td><td>'.$fim_periodo->format('Y-m-d').'</td><td>'.$nfeoffset.'</td><td>'.$emissaooffset.'</td></tr>';
                    $mailBody .= '</tbody>'.
                    '</table>';
                    
                    $emailControllerObj = new EmailController;
                    $emailControllerObj->sendEmailToken('00', 'erro_500_importacao_notas_entrada', [], ['corpo' => $mailBody,'tipo' => 'NFE']);

                    continue;
                }else if($request->getStatusCode() == 400){
                    continue;
                }else{
                    $contador = 0;
                }
            }while($contador > 0);
        }

        return $nfe;
    }

    public function importarNotas($inicio_periodo,$fim_periodo){
        set_time_limit(12000);

        $nfe = $this->buscaNFE($inicio_periodo,$fim_periodo);
        
        foreach ($nfe as $dado) {
            if(!Xml::is_valid($dado['nfe'])){
                continue;
            };

            $xml = $dado['nfe'];
            $dados = Xml::decode($dado['nfe']);
            $chave = $dado['chave'];
            $tipo = 'nfe';
            $data = '';
            $tipopagamento = '';
            $cnpjestabelecimento = $dado['cnpjestabelecimento'];
            $valorpagamento = 0;
            $notasnasajon = NotasEntradasNasajon::where('Chave NE', $chave)->first();
            $itens = $dados['NFe']['infNFe']['det'];
            $estabelecimento = NasajonEstabelecimento::where(DB::raw('CONCAT(raizcnpj,ordemcnpj)'), 'ilike', $cnpjestabelecimento)->select(DB::raw('CONCAT(raizcnpj,ordemcnpj) as cnpj, codigo'))->first();
            $duplicata = [];
            $chaves = [];

            if(isset($dados['NFe']['infNFe']['ide']['NFref']['refNFe'])){
                $chaves[] = [
                    'chave' => $dados['NFe']['infNFe']['ide']['NFref']['refNFe']
                ];
            }else if(isset($dados['NFe']['infNFe']['ide']['NFref']['refNFP']['nNF'])){
                $chaves[] = [
                    'chave' => str_pad($dados['NFe']['infNFe']['ide']['NFref']['refNFP']['nNF'],9,'0', STR_PAD_LEFT)
                ];
            }else if(isset($dados['NFe']['infNFe']['ide']['NFref']['refNF']['nNF'])){
                $chaves[] = [
                    'chave' => str_pad($dados['NFe']['infNFe']['ide']['NFref']['refNF']['nNF'],9,'0', STR_PAD_LEFT)
                ];
            }else if(isset($dados['NFe']['infNFe']['ide']['NFref'][0])){
                foreach($dados['NFe']['infNFe']['ide']['NFref'][0] as $chave_xml){
                    $chaves[] = [
                        'chave' => $chave_xml
                    ];
                    break;
                }
            }

            if (!isset($dados['NFe']['infNFe']['dest']['CNPJ'])) {
                throw new Exception('NFe sem cnpoj do destinatário.');
            }
            if (isset($dados['NFe']['infNFe']['ide']['dhEmi'])) {
                $data = date('Y-m-d H:i:s', strtotime($dados['NFe']['infNFe']['ide']['dhEmi']));
            } else if (isset($dados['NFe']['infNFe']['ide']['dEmi'])) {
                $data = date('Y-m-d', strtotime($dados['NFe']['infNFe']['ide']['dEmi']));
            }
            if (empty($data)) {
                throw new Exception('Não foi possível capturar a data de emissão da NFe.');
            }

            $cnpj_nfe = '-------';

            if(isset($dados['NFe']['infNFe']['emit']['CNPJ'])){
                $cnpj_nfe = mask($dados['NFe']['infNFe']['emit']['CNPJ'], '##.###.###/####-##');
            }else if(isset($dados['NFe']['infNFe']['emit']['CPF'])){
                $cnpj_nfe = mask($dados['NFe']['infNFe']['emit']['CPF'], '###.###.###-##');
            }

            $verificaduplicado = NotasImportadasEntrada::where('documento_numero', $dados['NFe']['infNFe']['ide']['nNF'])
                ->where('fornecedor_cnpj', $cnpj_nfe)
                ->where('tipo', 'nfe')
                ->where('data_emissao', $data)
                ->first();

            $cpnjfornecedor = '';

            if(isset($dados['NFe']['infNFe']['emit']['CNPJ'])){
                $cpnjfornecedor = mask($dados['NFe']['infNFe']['emit']['CNPJ'], '##.###.###/####-##');
            }else if($dados['NFe']['infNFe']['emit']['CPF']){
                $cpnjfornecedor = mask($dados['NFe']['infNFe']['emit']['CPF'], '##.###.###/####-##');
            }

            if (!empty($verificaduplicado)) {

                if(!empty($duplicata)){
                    $verificar_chave_duplicada = NotasImportadasEntradasTitulo::where('chave_nfe',$chave)->first();

                    if(!empty($verificar_chave_duplicada)){
                        continue;
                    }

                    $query_titulos = new NotasImportadasEntradasTitulo;
                    $query_titulos->estabelecimento = $estabelecimento->codigo;
                    $query_titulos->chave_nfe = $chave;
                    $query_titulos->emissao = $data;
                    $query_titulos->fatura = $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['fatura'];
                    $query_titulos->nota = (!empty($notasnasajon)) ? $notasnasajon["Identificador Documento"] : null;
                    $query_titulos->cfop = (isset($dados['NFe']['infNFe']['ide']['natOp'])) ? $dados['NFe']['infNFe']['ide']['natOp'] : null;
                    $query_titulos->fornecedor_documento = $cpnjfornecedor;
                    $query_titulos->fornecedor_nome = $dados['NFe']['infNFe']['emit']['xNome'];
                    $query_titulos->valor = (float)$duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['valor_liquido'];
                    $query_titulos->notas_importadas_entrada_id = $verificaduplicado->id;
                    $query_titulos->save();
    
                    foreach($duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['duplicatas'] as $duplicata_item){
                        $vencimento_titulo = (!empty($duplicata_item['vencimento'])) ? Carbon::parse($duplicata_item['vencimento']) : null;
    
                        $titulo_duplicata = new NotasImportadasEntradasTitulosDuplicata;
                        $titulo_duplicata->duplicata = $duplicata_item['duplicata_numero'];
                        $titulo_duplicata->vencimento = $vencimento_titulo;
                        $titulo_duplicata->valor = (float)$duplicata_item['valor'];
                        $titulo_duplicata->notas_importadas_entradas_titulo_id = $query_titulos->id;
                        $titulo_duplicata->save();
                    }
                }

                continue;
            }
            if (isset($dados['NFe']['infNFe']['pag'][0]['detPag']['tPag'])) {
                $tipopagamento = $dados['NFe']['infNFe']['pag'][0]['detPag']['tPag'];
                $valorpagamento = $dados['NFe']['infNFe']['pag'][0]['detPag']['vPag'];
            } else if (isset($dados['NFe']['infNFe']['pag']['detPag']['tPag'])) {
                $tipopagamento = $dados['NFe']['infNFe']['pag']['detPag']['tPag'];
                $valorpagamento = $dados['NFe']['infNFe']['pag']['detPag']['vPag'];
            }
            if ($tipopagamento != '90') {
                $cobranca = true;
            } else {
                $cobranca = false;
            }
            if (empty($estabelecimento)) {
                throw new Exception('Estabelecimento da NFe não localizado.');
            }

            $transportadora = TransportadorNasajon::where('cnpj', $cpnjfornecedor)->first();
            $transportadora = (empty($transportadora)) ? FornecedorNasajon::where('cnpj_cpf', $cpnjfornecedor)->first() : $transportadora;

            $notas = new NotasImportadasEntrada;
            $notas->nota_id = (!empty($notasnasajon)) ? $notasnasajon["Identificador Documento"] : null;
            $notas->xml = $xml;
            $notas->natureza_operacao = (isset($dados['NFe']['infNFe']['ide']['natOp'])) ? $dados['NFe']['infNFe']['ide']['natOp'] : null;
            $notas->documento_chave = $chave;
            $notas->documento_numero = (isset($dados['NFe']['infNFe']['ide']['nNF'])) ? $dados['NFe']['infNFe']['ide']['nNF'] : null;
            $notas->documento_serie = (isset($dados['NFe']['infNFe']['ide']['serie'])) ? $dados['NFe']['infNFe']['ide']['serie'] : null;
            $notas->fornecedor_id = (!empty($transportadora)) ? $transportadora->id : null;
            $notas->fornecedor_cnpj = $cpnjfornecedor;
            $notas->fornecedor_nome = $dados['NFe']['infNFe']['emit']['xNome'];
            $notas->destinatario_id = (!empty($notasnasajon)) ? $notasnasajon["Identificador da Transportadora"] : null;
            $notas->destinatario_cpf_cnpj = (!empty($dados['NFe']['infNFe']['dest']['CNPJ'])) ? mask($dados['NFe']['infNFe']['dest']['CNPJ'], '##.###.###/####-##') : null;
            $notas->destinatario_nome = (!empty($dados['NFe']['infNFe']['dest']['xNome'])) ? $dados['NFe']['infNFe']['dest']['xNome'] : null;
            $notas->data_emissao = $data;
            $notas->peso_bruto = (isset($dados['NFe']['infNFe']['transp']['vol']['pesoB'])) ? $dados['NFe']['infNFe']['transp']['vol']['pesoB'] : '0';
            $notas->peso_liquido = (isset($dados['NFe']['infNFe']['transp']['vol']['pesoL'])) ? $dados['NFe']['infNFe']['transp']['vol']['pesoL'] : '0';
            $notas->peso_base_calculo = 0;
            $notas->valor_total_servico = (isset($dados['NFe']['infNFe']['total']['ICMSTot']['vTotTrib'])) ? $dados['NFe']['infNFe']['total']['ICMSTot']['vTotTrib'] : null;
            $notas->valor_a_receber = 0;
            $notas->valor_frete = $dados['NFe']['infNFe']['total']['ICMSTot']['vFrete'];
            $notas->valor_despacho = 0;
            $notas->valor_pedagio = 0;
            $notas->valor_gris = 0;
            $notas->valor_tas = 0;
            $notas->valor_total_carga = $dados['NFe']['infNFe']['total']['ICMSTot']['vNF'];
            $notas->valor_imposto_importacao = $dados['NFe']['infNFe']['total']['ICMSTot']['vII'];
            $notas->icms_cst = 0;
            $notas->icms_base_calculo = $dados['NFe']['infNFe']['total']['ICMSTot']['vBC'];
            $notas->icms_aliquota = 0;
            $notas->icms_valor = $dados['NFe']['infNFe']['total']['ICMSTot']['vICMS'];
            $notas->quantidade = (isset($dados['NFe']['infNFe']['transp']['vol']['qVol'])) ? $dados['NFe']['infNFe']['transp']['vol']['qVol'] : '0';
            $notas->valor_seguro = $dados['NFe']['infNFe']['total']['ICMSTot']['vSeg'];
            $notas->valor_ipi = $dados['NFe']['infNFe']['total']['ICMSTot']['vIPI'];
            $notas->valor_ipi_devolucao = $dados['NFe']['infNFe']['total']['ICMSTot']['vIPIDevol'];
            $notas->valor_outros = $dados['NFe']['infNFe']['total']['ICMSTot']['vOutro'];
            $notas->valor_desconto = $dados['NFe']['infNFe']['total']['ICMSTot']['vDesc'];
            $notas->pagamento_tipo = $tipopagamento;
            $notas->pagamento_valor = $valorpagamento;
            $notas->tipo = $tipo;
            $notas->cobranca = $cobranca;
            $notas->estabelecimento = $estabelecimento->codigo;
            $notas->informacao_complementar = (isset($dados['NFe']['infNFe']['infAdic']['infCpl'])) ? $dados['NFe']['infNFe']['infAdic']['infCpl'] : null;

            if(!$notas->save()){
                throw new Exception($notas->save());
            }

            if(!empty($chaves)){
                foreach($chaves as $chave){
                    $referencia_devolucao = new NotasImportadasEntradaDevolucaoReferencia;
                    $referencia_devolucao->chave_numero_nota = $chave['chave'];
                    $referencia_devolucao->notas_importadas_entradas_id = $notas->id;

                    if(!$referencia_devolucao->save()){
                        throw new Exception($referencia_devolucao->save());
                    }
                }
            }

            if (isset($dados['NFe']['infNFe']['det'][0])) {
                foreach ($itens as $key => $item) {
                    $item = new NotasImportadasEntradasIten();
                    $item->codigo_produto = $dados['NFe']['infNFe']['det'][$key]['prod']['cProd'];
                    $item->codigo_ean = $dados['NFe']['infNFe']['det'][$key]['prod']['cEAN'];
                    $item->codigo_ncm = $dados['NFe']['infNFe']['det'][$key]['prod']['NCM'];
                    $item->codigo_cest = (isset($dados['NFe']['infNFe']['det'][$key]['prod']['CEST'])) ? $dados['NFe']['infNFe']['det'][$key]['prod']['CEST'] : null;
                    $item->codigo_cfop = $dados['NFe']['infNFe']['det'][$key]['prod']['CFOP'];
                    $item->comercial_unidade = $dados['NFe']['infNFe']['det'][$key]['prod']['uCom'];
                    $item->comercial_quantidade = $dados['NFe']['infNFe']['det'][$key]['prod']['qCom'];
                    $item->comercial_valor_unitario = $dados['NFe']['infNFe']['det'][$key]['prod']['vUnCom'];
                    $item->valor_total = $dados['NFe']['infNFe']['det'][$key]['prod']['vProd'];
                    $item->produto_nome = $dados['NFe']['infNFe']['det'][$key]['prod']['xProd'];
                    $item->tributavel_codigo_ean = $dados['NFe']['infNFe']['det'][$key]['prod']['cEANTrib'];
                    $item->tributavel_unidade = $dados['NFe']['infNFe']['det'][$key]['prod']['uTrib'];
                    $item->tributavel_quantidade = $dados['NFe']['infNFe']['det'][$key]['prod']['qTrib'];

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['orig'])) {
                        $item->icms_mercadoria_origem = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['orig'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['orig'])) {
                        $item->icms_mercadoria_origem = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['orig'];
                    } else {
                        $item->icms_mercadoria_origem = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['CST'])) {
                        $item->icms_tributacao_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['CST'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['CST'])) {
                        $item->icms_tributacao_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['CST'];
                    } else {
                        $item->icms_tributacao_cts = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['modBC'])) {
                        $item->icms_modalidade_bc = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['modBC'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['modBC'])) {
                        $item->icms_modalidade_bc = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['modBC'];
                    } else {
                        $item->icms_modalidade_bc = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['pICMS'])) {
                        $item->icms_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['pICMS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['pICMS'])) {
                        $item->icms_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['pICMS'];
                    } else {
                        $item->icms_aliquota = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['vICMS'])) {
                        $item->icms_valor = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMS00']['vICMS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['vICMS'])) {
                        $item->icms_valor = $dados['NFe']['infNFe']['det'][$key]['imposto']['ICMS']['ICMSSN102']['vICMS'];
                    } else {
                        $item->icms_valor = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['CST'])) {
                        $item->pis_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['CST'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['CST'])) {
                        $item->pis_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['CST'];
                    } else {
                        $item->pis_cts = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['vBC'])) {
                        $item->pis_base_calculo = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['vBC'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['vBC'])) {
                        $item->pis_base_calculo = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['vBC'];
                    } else {
                        $item->pis_base_calculo = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['pPIS'])) {
                        $item->pis_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['pPIS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['pPIS'])) {
                        $item->pis_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['pPIS'];
                    } else {
                        $item->pis_aliquota = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['vPIS'])) {
                        $item->pis_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['PISAliq']['vPIS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['vPIS'])) {
                        $item->pis_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['PIS']['IPINT']['vPIS'];
                    } else {
                        $item->pis_aliquota = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['CST'])) {
                        $item->cofins_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['CST'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['CST'])) {
                        $item->cofins_cts = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['CST'];
                    } else {
                        $item->cofins_cts = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['vBC'])) {
                        $item->cofins_base_calculo = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['vBC'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['vBC'])) {
                        $item->cofins_base_calculo = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['vBC'];
                    } else {
                        $item->cofins_base_calculo = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['pCOFINS'])) {
                        $item->cofins_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['pCOFINS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['pCOFINS'])) {
                        $item->cofins_aliquota = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['pCOFINS'];
                    } else {
                        $item->cofins_aliquota = null;
                    }

                    if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['vCOFINS'])) {
                        $item->cofins_valor = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSAliq']['vCOFINS'];
                    } else if (isset($dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['vCOFINS'])) {
                        $item->cofins_valor = $dados['NFe']['infNFe']['det'][$key]['imposto']['COFINS']['COFINSNT']['vCOFINS'];
                    } else {
                        $item->cofins_valor = null;
                    }

                    $item->descricao = (isset($dados['NFe']['infNFe']['det'][$key]['infAdProd'])) ? $dados['NFe']['infNFe']['det'][$key]['infAdProd'] : null;
                    $item->notas_importadas_entradas_id = $notas->id;
                    $insert = $item->save();
                    
                    if(!$insert){
                        throw new Exception($insert);
                    }

                }
            } else {
                $item = new NotasImportadasEntradasIten();
                $item->codigo_produto = $dados['NFe']['infNFe']['det']['prod']['cProd'];
                $item->codigo_ean = $dados['NFe']['infNFe']['det']['prod']['cEAN'];
                $item->codigo_ncm = $dados['NFe']['infNFe']['det']['prod']['NCM'];
                $item->codigo_cest = (isset($dados['NFe']['infNFe']['det']['prod']['CEST'])) ? $dados['NFe']['infNFe']['det']['prod']['CEST'] : null;
                $item->codigo_cfop = $dados['NFe']['infNFe']['det']['prod']['CFOP'];
                $item->comercial_unidade = $dados['NFe']['infNFe']['det']['prod']['uCom'];
                $item->comercial_quantidade = $dados['NFe']['infNFe']['det']['prod']['qCom'];
                $item->comercial_valor_unitario = $dados['NFe']['infNFe']['det']['prod']['vUnCom'];
                $item->valor_total = $dados['NFe']['infNFe']['det']['prod']['vProd'];
                $item->produto_nome = $dados['NFe']['infNFe']['det']['prod']['xProd'];
                $item->tributavel_codigo_ean = $dados['NFe']['infNFe']['det']['prod']['cEANTrib'];
                $item->tributavel_unidade = $dados['NFe']['infNFe']['det']['prod']['uTrib'];
                $item->tributavel_quantidade = $dados['NFe']['infNFe']['det']['prod']['qTrib'];

                if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['orig'])) {
                    $item->icms_mercadoria_origem = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['orig'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['orig'])) {
                    $item->icms_mercadoria_origem = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['orig'];
                } else {
                    $item->icms_mercadoria_origem = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['CST'])) {
                    $item->icms_tributacao_cts = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['CST'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['CST'])) {
                    $item->icms_tributacao_cts = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['CST'];
                } else {
                    $item->icms_tributacao_cts = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['modBC'])) {
                    $item->icms_modalidade_bc = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['modBC'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['modBC'])) {
                    $item->icms_modalidade_bc = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['modBC'];
                } else {
                    $item->icms_modalidade_bc = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['pICMS'])) {
                    $item->icms_aliquota = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['pICMS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['pICMS'])) {
                    $item->icms_aliquota = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['pICMS'];
                } else {
                    $item->icms_aliquota = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['vICMS'])) {
                    $item->icms_valor = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMS00']['vICMS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['vICMS'])) {
                    $item->icms_valor = $dados['NFe']['infNFe']['det']['imposto']['ICMS']['ICMSSN102']['vICMS'];
                } else {
                    $item->icms_valor = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['CST'])) {
                    $item->pis_cts = $dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['CST'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['CST'])) {
                    $item->pis_cts = $dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['CST'];
                } else {
                    $item->pis_cts = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['vBC'])) {
                    $item->pis_base_calculo = $dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['vBC'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['vBC'])) {
                    $item->pis_base_calculo = $dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['vBC'];
                } else {
                    $item->pis_base_calculo = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['pPIS'])) {
                    $item->pis_aliquota = $dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['pPIS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['pPIS'])) {
                    $item->pis_aliquota = $dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['pPIS'];
                } else {
                    $item->pis_aliquota = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['vPIS'])) {
                    $item->pis_aliquota = $dados['NFe']['infNFe']['det']['imposto']['PIS']['PISAliq']['vPIS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['vPIS'])) {
                    $item->pis_aliquota = $dados['NFe']['infNFe']['det']['imposto']['PIS']['IPINT']['vPIS'];
                } else {
                    $item->pis_aliquota = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['CST'])) {
                    $item->cofins_cts = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['CST'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['CST'])) {
                    $item->cofins_cts = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['CST'];
                } else {
                    $item->cofins_cts = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['vBC'])) {
                    $item->cofins_base_calculo = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['vBC'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['vBC'])) {
                    $item->cofins_base_calculo = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['vBC'];
                } else {
                    $item->cofins_base_calculo = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['pCOFINS'])) {
                    $item->cofins_aliquota = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['pCOFINS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['pCOFINS'])) {
                    $item->cofins_aliquota = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['pCOFINS'];
                } else {
                    $item->cofins_aliquota = null;
                }

                if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['vCOFINS'])) {
                    $item->cofins_valor = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSAliq']['vCOFINS'];
                } else if (isset($dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['vCOFINS'])) {
                    $item->cofins_valor = $dados['NFe']['infNFe']['det']['imposto']['COFINS']['COFINSNT']['vCOFINS'];
                } else {
                    $item->cofins_valor = null;
                }

                $item->descricao = (isset($dados['NFe']['infNFe']['det']['infAdProd'])) ? $dados['NFe']['infNFe']['det']['infAdProd'] : null;
                $item->descricao = (isset($dados['NFe']['infNFe']['det']['infAdProd'])) ? $dados['NFe']['infNFe']['det']['infAdProd'] : null;
                $item->notas_importadas_entradas_id = $notas->id;
                $insert = $item->save();

                if(!$insert){
                    throw new Exception($insert);
                }
                
            }

        }


    }

    public function importarVolumesCTE($inicio_periodo,$fim_periodo){
        ini_set('memory_limit', '1024M');
        set_time_limit(12000);

        $query_cte = NotasImportadasEntrada::where('tipo','cte')
        ->whereNull('volume')
        ->whereNotNull('xml')
        ->whereBetween('created_at',[$inicio_periodo,$fim_periodo])
        ->get();

        $query_cte->each(function($query){
            $xml = Xml::decode($query->xml);

            $volume = 0;
            
            if(isset($xml['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'][0])) {
                foreach ($xml['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'] as $infq) {
                    if (strcasecmp($infq['cUnid'], '03') == 0){
                        $volume = (float)$infq['qCarga'];
                    }
                }
            }else if (isset($xml['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'])) {
                if (strcasecmp($xml['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'], '03') == 0){
                    $volume = (float)$xml['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
            }

            $query->volume = $volume;
            $query->save();
        });
    }

    public function verificaNotasLancadas(){
        $inicio_periodo = Carbon::now()->subDays(30);
        $fim_periodo = Carbon::now();

        $faturas_notas = NotasImportadasEntradasTitulo::with('notasEntradas')->whereBetween('created_at',[$inicio_periodo,$fim_periodo])->get();

        $faturas_notas->each(function($query){
            if(!empty($query->notasEntradas)){
                $query->lancado = true;
                $query->save();
            }
        });
    }

    public function importarCte($inicio_periodo,$fim_periodo){
        set_time_limit(12000);

        $cte = $this->buscaCTE($inicio_periodo,$fim_periodo);

        foreach ($cte as $dado) {
            if(!Xml::is_valid($dado['cte'])){
                continue;
            };

            $xml = $dado['cte'];
            $dados = Xml::decode($dado['cte']);
            $idcte = $dado['id'];
            $cnpjestabelecimento = $dado['cnpjestabelecimento'];
            $chave = (isset($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']['chave'])) ? $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']['chave'] : null;
            $estabelecimento = NasajonEstabelecimento::where(DB::raw('CONCAT(raizcnpj,ordemcnpj)'), 'ilike', $cnpjestabelecimento)->select('codigo')->first();
            $quantidade = 0;
            $tipo = 'cte';
            $data = '';
            $peso = 0;
            $pesobasecalculo = 0;
            $fretepeso = 0;
            $pedagio = 0;
            $gris = 0;
            $tas = 0;
            $trt = 0;
            $taxactrc = 0;
            $descarga = 0;
            $despacho = 0;
            $unidadebasecalculo = 0;
            $etcvalor = 0;
            $etcunidade = 0;
            $pesodeclarado = 0;
            $unidadepesodeclarado = 0;
            $pesoreal = 0;
            $unidadepesoreal = 0;
            $volume = 0;
            $numero = (isset($dados['CTe']['infCte']['ide']['nCT'])) ? $dados['CTe']['infCte']['ide']['nCT'] : null;
            $expedidordocumento = '';
            
            if(isset($dados['CTe']['infCte']['rem']['CNPJ'])){
                $expedidordocumento = mask($dados['CTe']['infCte']['rem']['CNPJ'], '##.###.###/####-##');
            }else if(isset($dados['CTe']['infCte']['rem']['CPF'])){
                $expedidordocumento = mask($dados['CTe']['infCte']['rem']['CPF'], '###.###.###-##');
            }
            $expedidor = (!empty($expedidordocumento)) ? FornecedorNasajon::where('cnpj_cpf', $expedidordocumento)->first() : null;
            $documentodestinatario = '';

            if(isset($dados['CTe']['infCte']['dest']['CNPJ'])){
                $documentodestinatario = mask($dados['CTe']['infCte']['dest']['CNPJ'], '##.###.###/####-##');
            }else if(isset($dados['CTe']['infCte']['dest']['CPF'])){
                $documentodestinatario = mask($dados['CTe']['infCte']['dest']['CPF'], '###.###.###-##');
            }
            if (isset($dados['CTe']['infCte']['ide']['dhEmi'])) {
                $data = date('Y-m-d H:i:s', strtotime($dados['CTe']['infCte']['ide']['dhEmi']));
            } else if (isset($dados['CTe']['infCte']['ide']['dEmi'])) {
                $data = date('Y-m-d', strtotime($dados['CTe']['infCte']['ide']['dEmi']));
            }
            if (empty($data)) {
                throw new Exception('Não foi possível pegar a data de emissão da CTe.');
            }
            if (isset($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'][0])) {
                foreach ($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'] as $infq) {
                    if (strcasecmp($infq['tpMed'], 'PESO BRUTO') == 0 || strcasecmp($infq['tpMed'], 'PESO') == 0) {
                        $quantidade = $infq['cUnid'];
                        $peso = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'PESO BASE DE CALCULO') == 0) {
                        $unidadebasecalculo = $infq['cUnid'];
                        $pesobasecalculo = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'CAIXAS E ETC') == 0) {
                        $etcunidade = $infq['cUnid'];
                        $etcvalor = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'PESO DECLARADO') == 0) {
                        $unidadepesodeclarado = $infq['cUnid'];
                        $pesodeclarado = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'PESO REAL') == 0) {
                        $unidadepesoreal = $infq['cUnid'];
                        $pesoreal = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'PESO') == 0) {
                        $fretepeso = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['tpMed'], 'Peso Cubado (kg)') == 0) {
                        $fretepeso = $infq['qCarga'];
                    }
                    if (strcasecmp($infq['cUnid'], '03') == 0){
                        $volume = (float)$infq['qCarga'];
                    }
                }
            } else if (isset($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ'])) {
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'PESO BRUTO') == 0) {
                    $quantidade = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'];
                    $peso = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'PESO BASE DE CALCULO') == 0) {
                    $unidadebasecalculo = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'];
                    $pesobasecalculo = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'CAIXAS E ETC') == 0) {
                    $etcunidade = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'];
                    $etcvalor = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'PESO DECLARADO') == 0) {
                    $unidadepesodeclarado = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'];
                    $pesodeclarado = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'PESO REAL') == 0) {
                    $unidadepesoreal = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'];
                    $pesoreal = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'PESO') == 0) {
                    $fretepeso = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['tpMed'], 'Peso Cubado (kg)') == 0) {
                    $fretepeso = $dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
                
                if (strcasecmp($dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['cUnid'], '03') == 0){
                    $volume = (float)$dados['CTe']['infCte']['infCTeNorm']['infCarga']['infQ']['qCarga'];
                }
            }

            if (isset($dados['CTe']['infCte']['vPrest']['Comp'][0])) {
                foreach ($dados['CTe']['infCte']['vPrest']['Comp'] as $vprest) {
                    if (strcasecmp($vprest['xNome'], 'FRETE PESO') == 0) {
                        $fretepeso = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'PEDAGIO') == 0) {
                        $pedagio = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'GRIS') == 0) {
                        $gris = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'TAS') == 0) {
                        $tas = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'TRT') == 0) {
                        $trt = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'TAXA_EMI_CTRC') == 0) {
                        $taxactrc = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'DESCARGA') == 0) {
                        $descarga = $vprest['vComp'];
                    }
                    if (strcasecmp($vprest['xNome'], 'DESPACHO') == 0) {
                        $despacho = $vprest['vComp'];
                    }
                }
            } else if (isset($dados['CTe']['infCte']['vPrest']['Comp'])) {
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'FRETE PESO') == 0) {
                    $fretepeso = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'PEDAGIO') == 0) {
                    $pedagio = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'GRIS') == 0) {
                    $gris = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'TAS') == 0) {
                    $tas = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'TRT') == 0) {
                    $trt = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'TAXA_EMI_CTRC') == 0) {
                    $taxactrc = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'DESCARGA') == 0) {
                    $descarga = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
                if (strcasecmp($dados['CTe']['infCte']['vPrest']['Comp']['xNome'], 'DESPACHO') == 0) {
                    $despacho = $dados['CTe']['infCte']['vPrest']['Comp']['vComp'];
                }
            }

            if (empty($fretepeso)) {
                $chavenfe = [];
                if (isset($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'][0])) {
                    foreach ($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'] as $value) {
                        $chavenfe[] = $value['chave'];
                    }
                } else if (isset($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'])) {
                    $chavenfe[] = $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']['chave'];
                }
                $query = NotasEntradasNasajon::whereIn("Chave NE", $chavenfe)->selectRaw('sum("Peso Líquido") as peso')->first();
                $fretepeso = (!empty($query)) ? $query["Peso Líquido"] : 0;
            }

            $verificaduplicado = NotasImportadasEntrada::where('documento_numero', $numero)
                ->where('fornecedor_cnpj', mask($dados['CTe']['infCte']['emit']['CNPJ'], '##.###.###/####-##'))
                ->where('tipo', 'cte')
                ->where('data_emissao', $data)
                ->first();
            
            $transportadora = '';
            $fornecedordocumento = '';

            if(isset($dados['CTe']['infCte']['emit']['CNPJ'])){
                $fornecedordocumento = mask($dados['CTe']['infCte']['emit']['CNPJ'], '##.###.###/####-##');
            }else if(isset($dados['CTe']['infCte']['emit']['CPF'])){
                $fornecedordocumento = mask($dados['CTe']['infCte']['emit']['CPF'], '###.###.###-##');
            }

            $transportadora = TransportadorNasajon::where('cnpj', $fornecedordocumento)->first();
            $transportadora = (empty($transportadora)) ? FornecedorNasajon::where('cnpj_cpf', $fornecedordocumento)->first() : $transportadora;
            $destinatario = ClienteNasajon::where('cpf_cnpj', $documentodestinatario)->first();
            $remetente = '';
            $remetentedocumento = '';
            $cobranca = false;

            $conhecimento_transporte_nasajon = ConhecimentoTransporteNasajon::where(DB::raw('cast(cast("numero" as int) as varchar)'),$numero)->where('cnpjtransportador',$fornecedordocumento)->first();
            $notas_entradas_nasajon = NotasEntradasNasajon::where('Chave NE',$chave)->where('Estabelecimento',$estabelecimento->codigo)->first();

            if(!empty($conhecimento_transporte_nasajon) || !empty($notas_entradas_nasajon)){
                $cobranca = true;
            }

            if (!empty($verificaduplicado)) {
                $verificaduplicado->cobranca = $cobranca;
                $verificaduplicado->save();
                continue;
            }

            if(isset($dados['CTe']['infCte']['rem']['CNPJ'])){
                $remetentedocumento = mask($dados['CTe']['infCte']['rem']['CNPJ'], '##.###.###/####-##');
            }else if(isset($dados['CTe']['infCte']['rem']['CPF'])){
                $remetentedocumento = mask($dados['CTe']['infCte']['rem']['CPF'], '###.###.###-##');
            }

            if(isset($dados['CTe']['infCte']['rem'])){
                $remetente = TransportadorNasajon::where('cnpj', $remetentedocumento)->first();
                $remetente = (empty($remetente)) ? FornecedorNasajon::where('cnpj_cpf', $remetentedocumento)->first() : $remetente;
            }

            $insert = new NotasImportadasEntrada;
            $insert->nota_id = $idcte;
            $insert->documento_chave = $dados['CTe']['infCte']['@attributes']['Id'];
            $insert->documento_numero = $numero;
            $insert->xml = $xml;
            $insert->documento_serie = (isset($dados['CTe']['infCte']['ide']['serie'])) ? $dados['CTe']['infCte']['ide']['serie'] : null;
            $insert->documento_cfop = (isset($dados['CTe']['infCte']['ide']['CFOP'])) ? $dados['CTe']['infCte']['ide']['CFOP'] : null;
            $insert->fornecedor_id = (!empty($transportadora)) ? $transportadora->id : null;
            $insert->fornecedor_cnpj = $fornecedordocumento;
            $insert->fornecedor_nome = (!empty($transportadora)) ? $transportadora->nome : $dados['CTe']['infCte']['emit']['xNome'];
            $insert->remetente_id = (!empty($remetente)) ? $remetente->id : null;
            $insert->remetente_cnpj = $remetentedocumento;
            $insert->remetente_nome = (isset($dados['CTe']['infCte']['rem']['xNome'])) ? $dados['CTe']['infCte']['rem']['xNome'] : null;
            $insert->expedidor_id = (!empty($expedidor)) ? $expedidor->id : null;
            $insert->expedidor_cnpj = $expedidordocumento;
            $insert->expedidor_nome = (isset($dados['CTe']['infCte']['exped']['xNome'])) ? $dados['CTe']['infCte']['exped']['xNome'] : null;
            $insert->destinatario_id = (!empty($destinatario)) ? $destinatario->id : null;
            $insert->destinatario_nome = (isset($dados['CTe']['infCte']['dest']['xNome'])) ? $dados['CTe']['infCte']['dest']['xNome'] : null;
            $insert->destinatario_cpf_cnpj = $documentodestinatario;
            $insert->produto_predominante = (isset($dados['CTe']['infCte']['infCTeNorm']['infCarga']['proPred'])) ? $dados['CTe']['infCte']['infCTeNorm']['infCarga']['proPred'] : null;
            $insert->caracteristica_carga = (isset($dados['CTe']['infCte']['infCTeNorm']['infCarga']['xOutCat'])) ? $dados['CTe']['infCte']['infCTeNorm']['infCarga']['xOutCat'] : null;
            $insert->data_emissao = $data;
            $insert->peso_bruto = $peso;
            $insert->peso_declarado = $pesodeclarado;
            $insert->unidade_peso_declarado = $unidadepesodeclarado;
            $insert->peso_real = $pesoreal;
            $insert->unidade_peso_real = $unidadepesoreal;
            $insert->peso_base_calculo = $pesobasecalculo;
            $insert->valor_total_servico = $dados['CTe']['infCte']['vPrest']['vTPrest'];
            $insert->valor_a_receber = $dados['CTe']['infCte']['vPrest']['vRec'];
            $insert->valor_frete = $dados['CTe']['infCte']['vPrest']['vTPrest'];
            $insert->frete_peso = $fretepeso;
            $insert->valor_despacho = $despacho;
            $insert->valor_pedagio = $pedagio;
            $insert->valor_gris = $gris;
            $insert->valor_tas = $tas;
            $insert->valor_trt = $trt;
            $insert->valor_etc = $etcvalor;
            $insert->unidade_etc = $etcunidade;
            $insert->unidade_base_calculo = $unidadebasecalculo;
            $insert->valor_descarga = $descarga;
            $insert->valor_Taxa_emi_ctrc = $taxactrc;
            $insert->valor_total_carga = (isset($dados['CTe']['infCte']['infCTeNorm'])) ? $dados['CTe']['infCte']['infCTeNorm']['infCarga']['vCarga'] : null;
            $insert->icms_cst = (isset($dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['CST'])) ? $dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['CST'] : null;
            $insert->icms_base_calculo = (isset($dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['vBC'])) ? $dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['vBC'] : null;
            $insert->icms_aliquota = (isset($dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['pICMS'])) ? $dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['pICMS'] : null;
            $insert->icms_valor = (isset($dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['vICMS'])) ? $dados['CTe']['infCte']['imp']['ICMS']['ICMS00']['vICMS'] : null;
            $insert->pagamento_tipo = (isset($dados['CTe']['infCte']['pag']['detPag']['tPag'])) ? $dados['CTe']['infCte']['pag']['detPag']['tPag'] : null;
            $insert->pagamento_valor = (isset($dados['CTe']['infCte']['pag']['detPag']['vPag'])) ? $dados['CTe']['infCte']['pag']['detPag']['vPag'] : null;
            $insert->quantidade = $quantidade;
            $insert->tipo = $tipo;
            $insert->cobranca = $cobranca;
            $insert->volume = $volume;
            $insert->estabelecimento = (!empty($estabelecimento)) ? $estabelecimento->codigo : null;
            
            if(!$insert->save()){
                throw new Exception($insert->save());
            }
            $id = $insert->id;
            if (empty($chave) && isset($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'])) {
                foreach (array_keys($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']) as $key => $value) {
                    $buscanota = NotasNasajon::where('chavene', $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'][$key]['chave'])
                    ->first();

                    $relacaonota = new NotasImportadasEntradaRelacaoNota();
                    $relacaonota->notas_importadas_entradas_id_cte = $id;
                    $relacaonota->notas_importadas_entradas_id_nfe = (!empty($buscanota->id)) ? $buscanota->id : null;
                    $relacaonota->chave = $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'][$key]['chave'];

                    if(!$relacaonota->save()){
                        throw new Exception($relacaonota->save());
                    }
                }
            } else if (isset($dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe'])) {
                $buscanota = NotasNasajon::where('chavene', $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']['chave'])
                    ->first();
                $relacaonota = new NotasImportadasEntradaRelacaoNota();
                $relacaonota->notas_importadas_entradas_id_cte = $id;
                $relacaonota->notas_importadas_entradas_id_nfe = (!empty($buscanota->id)) ? $buscanota->id : null;
                $relacaonota->chave = $dados['CTe']['infCte']['infCTeNorm']['infDoc']['infNFe']['chave'];

                if(!$relacaonota->save()){
                    throw new Exception($relacaonota->save());
                }
            }
        }
    }

    public function verificarDuplicatas(){
        $inicio_periodo = Carbon::now()->subDays(20);
        $fim_periodo = Carbon::now();

        $nfe = NotasImportadasEntrada::where('tipo','nfe')
        ->whereBetween('data_emissao',[$inicio_periodo,$fim_periodo])
        ->get();

        $nfe->each(function($query){
            $dados = Xml::decode($query->xml);
            $chave = $query->documento_chave;
            $duplicata = [];

            if(isset($dados['NFe']['infNFe']['cobr']['dup'][0])){
                
                $verificar_fatura_duplicada = NotasImportadasEntradasTitulo::where('chave_nfe',$chave)->first();
                
                if(empty($verificar_fatura_duplicada)){
                    $duplicatas_xml = $dados['NFe']['infNFe']['cobr']['dup'];

                    $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']] = [
                        'fatura' => $dados['NFe']['infNFe']['cobr']['fat']['nFat'],
                        'valor_liquido' => $dados['NFe']['infNFe']['cobr']['fat']['vLiq'],
                        'duplicatas' => []
                    ];

                    foreach($duplicatas_xml as $keys_duplicatas => $duplicatas_valor){
                        $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['duplicatas'][] = [
                            'duplicata_numero' => $dados['NFe']['infNFe']['cobr']['dup'][$keys_duplicatas]['nDup'],
                            'vencimento' => $dados['NFe']['infNFe']['cobr']['dup'][$keys_duplicatas]['dVenc'],
                            'valor' => $dados['NFe']['infNFe']['cobr']['dup'][$keys_duplicatas]['vDup'],
                        ];
                    }
                }
            }else if(isset($dados['NFe']['infNFe']['cobr']['dup'])){
                
                $verificar_fatura_duplicada = NotasImportadasEntradasTitulo::where('chave_nfe',$chave)->first();

                if(empty($verificar_fatura_duplicada)){
                    $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']] = [
                        'fatura' => $dados['NFe']['infNFe']['cobr']['fat']['nFat'],
                        'valor_liquido' => $dados['NFe']['infNFe']['cobr']['fat']['vLiq'],
                        'duplicatas' => []
                    ];

                    $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['duplicatas'][] = [
                        'duplicata_numero' => $dados['NFe']['infNFe']['cobr']['dup']['nDup'],
                        'vencimento' => $dados['NFe']['infNFe']['cobr']['dup']['dVenc'],
                        'valor' => $dados['NFe']['infNFe']['cobr']['dup']['vDup'],
                    ];
                }
            }

            if(!empty($duplicata)){
                $query_titulos = new NotasImportadasEntradasTitulo;
                $query_titulos->estabelecimento = $query->estabelecimento;
                $query_titulos->chave_nfe = $chave;
                $query_titulos->emissao = $query->data_emissao;
                $query_titulos->fatura = $duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['fatura'];
                $query_titulos->nota = $query->documento_numero;
                $query_titulos->fornecedor_documento = $query->fornecedor_cnpj;
                $query_titulos->fornecedor_nome = $query->fornecedor_nome;
                $query_titulos->valor = (float)$duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['valor_liquido'];
                $query_titulos->notas_importadas_entrada_id = $query->id;
                $query_titulos->save();

                foreach($duplicata[$dados['NFe']['infNFe']['cobr']['fat']['nFat']]['duplicatas'] as $duplicata_item){
                    $vencimento_titulo = (!empty($duplicata_item['vencimento'])) ? Carbon::parse($duplicata_item['vencimento']) : null;

                    $titulo_duplicata = new NotasImportadasEntradasTitulosDuplicata;
                    $titulo_duplicata->duplicata = $duplicata_item['duplicata_numero'];
                    $titulo_duplicata->vencimento = $vencimento_titulo;
                    $titulo_duplicata->valor = (float)$duplicata_item['valor'];
                    $titulo_duplicata->notas_importadas_entradas_titulo_id = $query_titulos->id;
                    $titulo_duplicata->save();
                }
            }
        });
    }

    public function verificarNotasCanceladasSemLancamento(){
        $data_inicial = Carbon::now()->subMonths(10);
        $data_final = Carbon::now();

        $faturas_notas = NotasImportadasEntradasTitulo::with(['notaImportada','duplicatas' => function($query) use ($data_inicial,$data_final){
            $query->whereBetween('vencimento',[$data_inicial,$data_final]);
        }])
        ->with('notasEntradas')
        ->whereHas('duplicatas',function($query) use ($data_inicial,$data_final){
            $query->whereBetween('vencimento',[$data_inicial,$data_final]);
        })
        ->where(function($query){
            $query->whereNull('lancado')
            ->orWhere('lancado',false);
        })
        ->get();

        $faturas_notas->each(function($query){
            if(empty($query->notasEntradas)){
                if(!empty($query->chave_nfe)){
                    if($this->consultarCte($query->chave_nfe) == 3){
                        $query->delete();
                    };
                }
            }
        });
    }

    private function consultarCte($chave){
        $tokenapi = $this->token;
        $uuidestabelecimentos = $this->uuidestabelecimentos;

        if(empty($tokenapi) || !is_array($uuidestabelecimentos)){
            throw new Exception('Token ou uuid do estabelecimento vazio.');
        }
        
        $client = new \GuzzleHttp\Client(['http_errors' => false]);
        $retorno = 0;

        foreach($uuidestabelecimentos as $estabelecimento){
            $contador = 0;
            $emissaooffset = null;
            $nfeoffset = null;
            do{
                $this->refreshToken($this->refresh_token);
                $tokenapi = $this->token;

                $request = $client->request('GET', $this->url.'nfes/', [
                    'headers' => [
                        'authorization' => $tokenapi,
                    ],
                    'query' => [
                        'estabelecimento' => $estabelecimento['uuid'],
                        'chave' => $chave
                    ],
                    'timeout' => 900
                ]);

                if($request->getStatusCode() == 200){
                    $response = json_decode($request->getBody()->getContents());
                    if(!empty($response[0]->situacao)){
                        $retorno = $response[0]->situacao;
                    }
                }else if($request->getStatusCode() == 401){
                    $this->autenticacaoAPI();
                    $tokenapi = $this->token;
                }else if($request->getStatusCode() == 500){
                    $mailBody = '<table border="1">'.
                    '<thead>'.
                        '<th> ESTABELECIMENTO </th>'.
                        '<th> offset[cte] </th>'.
                        '<th> offset[emissao_data] </th>'.
                    '</thead>'.    
                    '<tbody>';
                    $mailBody .= '<tr><td>'.$estabelecimento['uuid'].'</td><td>'.$nfeoffset.'</td><td>'.$emissaooffset.'</td></tr>';
                    $mailBody .= '</tbody>'.
                    '</table>';
                    
                    $emailControllerObj = new EmailController;
                    $emailControllerObj->sendEmailToken('00', 'erro_500_importacao_notas_entrada', [], ['corpo' => $mailBody,'tipo' => 'NFE']);

                    continue;
                }else if($request->getStatusCode() == 400){
                    var_dump('2');
                    continue;
                }else{
                    $contador = 0;
                }
            }while($contador > 0);
        }

        return $retorno;
    }

}
