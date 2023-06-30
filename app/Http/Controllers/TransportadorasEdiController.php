<?php

namespace App\Http\Controllers;

use App\ClienteNasajon;
use App\Http\Requests\TransportadorasEdiCadastrarRequest;
use App\Http\Requests\TransportadorasEdiEditarRequest;

use App\TransportadorasEdi;
use App\TransportadorNasajon;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\EmailController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TransportadorasEdiController extends Controller
{
    public $storage = 'public/notifis/';

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\TransportadorasEdi") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\TransportadorasEdi');

        return view('programs.transportadoras_edi.index');
    }

    public function modalAdicionar() {
        return view('programs.transportadoras_edi.modal.adicionar');
    }

    public function adicionarTransportadora(TransportadorasEdiCadastrarRequest $request){
        $fields = $request->only('transportadora_edi_modal', 'email');

        $TransportadorNasajon = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ilike', trim($fields['transportadora_edi_modal']))->first();

        $TransportadorasEdObj = new TransportadorasEdi;
        $TransportadorasEdObj->transportadora_cnpj = $TransportadorNasajon->cnpj;
        $TransportadorasEdObj->email = $fields['email'];
        $TransportadorasEdObj->created_by = Auth::id();
        $TransportadorasEdObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function filtro(Request $request){
        $fields = $request->only('transportadora_edi');

        $TransportadorasEdiObj = TransportadorasEdi::with('transportadoraNome');
        if(!empty($fields['transportadora_edi'])){
            $TransportadorNasajon = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ilike', trim($fields['transportadora_edi']))->get();

            $TransportadorasEdiObj->whereIn('transportadora_cnpj', $TransportadorNasajon->pluck('cnpj'));

        }
        $result = $TransportadorasEdiObj->get();

        $dadosTransportadora = [];
        foreach($result as $value){
            $dadosTransportadora []= [
                'id' => encrypt($value->id),
                'transportadora_nome' => $value->transportadoraNome->nome,
                'transportadora_cnpj' => $value->transportadora_cnpj,
                'email' => $value->email
            ];
        }

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => [],
            'response' => [
                'transportadoras' => $dadosTransportadora
            ] 
        ];
        return response()->json($retorno, 200);
    }

    public function modalEditar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
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

        $TransportadorasEdiObj = TransportadorasEdi::find($id);
        if(is_null($TransportadorasEdiObj)){
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

        $transportadora = [
            'id' => encrypt($TransportadorasEdiObj->id),
            'transportadora_nome' => $TransportadorasEdiObj->transportadoraNome->nome,
            'transportadora_cnpj' => $TransportadorasEdiObj->transportadora_cnpj,
            'email' => $TransportadorasEdiObj->email
        ];

        return view('programs.transportadoras_edi.modal.editar')->with(['transportadora' => $transportadora]);
    }

    public function editarTransportadora(TransportadorasEdiEditarRequest $request){
        $fields = $request->only('id', 'transportadora_edi_modal_edit', 'email');
        try{
            $id = decrypt($fields['id']);
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

        $TransportadorNasajon = TransportadorNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cnpj))'), 'ilike', trim($fields['transportadora_edi_modal_edit']))->first();

        $TransportadorasEdi = TransportadorasEdi::find($id);
        $TransportadorasEdi->transportadora_cnpj = $TransportadorNasajon->cnpj;
        $TransportadorasEdi->email = $fields['email'];
        $TransportadorasEdi->updated_by = Auth::id();
        $TransportadorasEdi->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    public function modalDeletar(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
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

        $TransportadorasEdiObj = TransportadorasEdi::find($id);
        if(is_null($TransportadorasEdiObj)){
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

        $transportadora = [
            'id' => encrypt($TransportadorasEdiObj->id),
            'transportadora_nome' => $TransportadorasEdiObj->transportadoraNome->nome,
            'transportadora_cnpj' => $TransportadorasEdiObj->transportadora_cnpj,
            'email' => $TransportadorasEdiObj->email
        ];

        return view('programs.transportadoras_edi.modal.delete')->with(['transportadora' => $transportadora]);
    }

    public function deletarTransportadora(Request $request){
        $fields = $request->only('id');
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Transportadora não encontrada',
                'error' => [],
                'response' => []
            ]);
        }

        $TransportadorasEdiObj = TransportadorasEdi::find($id);
        $TransportadorasEdiObj->deleted_by = Auth::id();
        $TransportadorasEdiObj->save();
        $TransportadorasEdiObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);
    }

    private function inserirEspaco($dados,$tamanho){

        $dados = tirarAcentos($dados);
        $dados = str_replace(['º','-','.','/','(',')',','], '',$dados);
        $tamanho_dados = strlen(trim($dados));
        while ($tamanho_dados < $tamanho) {
            $dados = substr_replace($dados, $dados.' ',0);
            $tamanho_dados = strlen($dados);
        }

        if($tamanho_dados > $tamanho){
            $dados = substr($dados,0,$tamanho);
        }
        return $dados;
    }

    private function inserirZeros($valor,$tamanho,$casa_decimais){

        if($casa_decimais === 3){
            $valor = number_format($valor, 3);
        }else{
            $valor = number_format($valor, 2);
        }

        $valor = str_replace(['.',','], '',$valor);
        $tamanho_valor = strlen($valor);
        while ($tamanho_valor < $tamanho) {
            $valor = substr_replace($valor, '0'.$valor,0);
            $tamanho_valor = strlen($valor);
        }
        return $valor;
    }

    public function criarArquivoTxt(){

        $TransportadorNasajonObj = TransportadorasEdi::with(['transportadoraNotas' => function($query){
            $query->where('emissao', Carbon::now()->format("Y-m-d"))
            ->with('cliente','estabelecimento_detalhes','pedido','itens_nota', 'transportadoraRedespacho');
        }])
        ->get();

        $arquivos = [];

        foreach($TransportadorNasajonObj as $transportadora){
            try{
                $transportadoraNotas = $transportadora->transportadoraNotas->where('transportadora_documento', $transportadora->transportadora_cnpj)->count();
                if($transportadoraNotas > 0){
                    $registro_000 = [
                        'registro' => '000',
                        'identificacao_remetente' => $this->inserirEspaco('portal@tecidosmn.com.br',35),
                        'identificacao_destinatario' => $this->inserirEspaco($transportadora->email,35),
                        'data_notifis' => Carbon::now()->format("dmy"),
                        'hora_notifis' => Carbon::now()->format("Hi"),
                        'identificacao_intercambio' => 'NOT50'.Carbon::now()->format("dm").substr(uniqid(rand()), 0, 3)
                    ];
                    $linhas = [];
                    $linhas[] = implode($registro_000);
                    $nome_arquivo = 'NOTFIS_TEXTIL-MN_'.$registro_000['identificacao_intercambio'].'.txt';

                    $registro_500 = [
                        'registro' => '500',
                        'identificacao_documento' => 'NOTAS50'.Carbon::now()->format("dm").substr(uniqid(rand()), 0, 3),
                        ];
                    $linhas[] = implode($registro_500)."\n";

                    $valor_total_notas = 0;
                    $peso_total = 0;
                    $volumes_total = 0;
                    $quantidade_notas = 0;

                    foreach($transportadora->transportadoraNotas as $nota){
                        if($nota->estabelecimento_detalhes->codigo == '20'){
                            $empresa_embarcadora = $nota->estabelecimento_detalhes->descricao;
                        }else{
                            $empresa_embarcadora = $nota->estabelecimento_detalhes->nomefantasia;
                        }

                        if($nota->estabelecimento_detalhes->cidade ==  'São Paulo'){
                            $uf_embarcadora =  'SP';
                        }elseif($nota->estabelecimento_detalhes->cidade ==  'Duque de Caxias'){
                            $uf_embarcadora =  'RJ';
                        }elseif($nota->estabelecimento_detalhes->cidade ==  'Porto Velho'){
                            $uf_embarcadora =  'RO';
                        }else{
                            $uf_embarcadora =  'TO';
                        }

                        if(!isset($linhas[$nota->estabelecimento_cnpj])){
                            $registro_501 = [
                                'registro' => '501',
                                'empresa_embarcadora' => $this->inserirEspaco($empresa_embarcadora,50),
                                'estabelecimento_cnpj_embarcadora' => $nota->estabelecimento_cnpj,
                                'inscricao_estadual_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->inscricaoestadual,15),
                                'inscricao_estadual_tributario_embarcadora' => $this->inserirEspaco(' ',15),
                                'inscricao_municipal_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->inscricaomunicipal,15),
                                'endereco_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->tipologradouro.$nota->estabelecimento_detalhes->logradouro,50),
                                'bairro_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->bairro,35),
                                'cidade_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->cidade,35),
                                'cep_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->cep,9),
                                'codigo_municipio_embarcadora' => $this->inserirEspaco($nota->estabelecimento_detalhes->ibge,9),
                                'uf_embarcadora' => $this->inserirEspaco($uf_embarcadora,9),
                                'data_embarque' => Carbon::parse($nota->datasaida)->format('dmY'),
                                'area_frete' => 'DOCA',
                                'contato_emergencia' => $this->inserirEspaco(' ',25)
                            ];

                            $linhas[$nota->estabelecimento_cnpj] = implode($registro_501)."\n";
                        }

                        if(isset($nota->cliente->nome)){
                            $registro_503 = [
                                'registro' => '503',
                                'destinatario_nome' => $this->inserirEspaco($nota->cliente->nome,50),
                                'destinatario_cpf_cnpj' => $this->inserirEspaco($nota->cliente->cpf_cnpj,14),
                                'inscricao_estadual_destinatario' => $this->inserirEspaco($nota->cliente->inscricaoestadual,15),
                                'inscricao_suframa_destinatario' => $this->inserirEspaco(' ',15),
                                'endereco_destinatario' => $this->inserirEspaco($nota->cliente->tipologradouro.' '.$nota->cliente->logradouro,50),
                                'bairro_destinatario' => $this->inserirEspaco($nota->cliente->bairro,35),
                                'cidade_destinatario' => $this->inserirEspaco($nota->cliente->cidade,35),
                                'cep_destinatario' => $this->inserirEspaco($nota->cliente->cep,9),
                                'codigo_municipio_destinatario' => $this->inserirEspaco(' ',9),
                                'uf_destinatario' => $this->inserirEspaco($nota->cliente->uf,9),
                                'numero_comunicacao' => $this->inserirEspaco($nota->cliente->telefones,35),
                                'codigo_pais' => '1058',
                                'area_frete_destinatario' => 'DOCA',
                                'tipo_pessoa' => strlen($this->inserirEspaco($nota->cliente->cpf_cnpj,14)) > 11 ? '1' : '2',
                                'tipo_estabelecimento_destinatario' => 'I'
                            ];

                            $linhas[] = implode($registro_503)."\n";

                            $registro_505 = [
                                'registro' => '505',
                                'serie' => $nota->serie,
                                'numero_nota' => $nota->numero,
                                'data_emissao' => Carbon::parse($nota->emissao)->format('dmY'),
                                'tipo_mercadoria' => $this->inserirEspaco('Tecidos',15),
                                'acondicionamento' => $this->inserirEspaco('Rolo',15),
                                'codigo_rota' => $this->inserirEspaco(' ',7),
                                'meio_transporte' => '1',
                                'tipo_transporte' => '1',
                                'tipo_carga' => '2',
                                'condicao_frete' => 'C',
                                'data_saida' => Carbon::parse($nota->datasaida)->format('dmY'),
                                'desdobro' => $this->inserirEspaco(' ',10),
                                'plano_carga_rapida' => ' ',
                                'tipo_documento' => '1',
                                'indicacao_bonificao' => ' ',
                                'codigo_cfop' => $nota->itens_nota->pluck('cfop')->unique()->implode(''),
                                'sigla_estado' => $uf_embarcadora,
                                'calculo_frete' => ' ',
                                'posicao' => $this->inserirEspaco(' ',146),
                                'chave_acesso' => $nota->chavene.' ',
                                'protocolo' => $this->inserirEspaco(' ',15),
                                'acao_documento' => 'I'
                            ];

                            $linhas[] = implode($registro_505)."\n";
                        
                            $registro_506 = [
                                'registro' => '506',
                                'volumes' => $this->inserirZeros($nota->volumes,8,2),
                                'peso_bruto' => $this->inserirZeros($nota->pesoliquido,9,3),
                                'peso_liquido' => $this->inserirZeros($nota->pesoliquido,9,3),
                                'peso_densidade' => $this->inserirZeros(0,10,2),
                                'peso_cubado' => $this->inserirZeros(0,10,2),
                                'incidencia_cms' => ' ',
                                'seguro_efetuado' => ' ',
                                'valor_pago' => $this->inserirZeros(0,15,2),
                                'valor_total' => $this->inserirZeros($nota->valor,15,2),
                                'valor_seguro' => $this->inserirZeros($nota->seguro,15,2),
                                'valor_desconto' => $this->inserirZeros($nota->total_desconto,15,2),
                                'valor_outras_despesas' => $this->inserirZeros($nota->outras,15,2),
                                'valor_outras_despesas_acessorias' => $this->inserirZeros(0,15,2),
                                'base_calculo_icms' => $this->inserirZeros($nota->baseicms,15,2),
                                'valor_icms' => $this->inserirZeros($nota->valoricms,15,2),
                                'base_calculo_icms_st' => $this->inserirZeros(0,15,2),
                                'valor_icms_st' => $this->inserirZeros($nota->valoricmsst,15,2),
                                'valor_icms_retido' => $this->inserirZeros(0,15,2),
                                'imposto_importacao' => $this->inserirZeros(0,15,2),
                                'valor_ipi' => $this->inserirZeros($nota->itens_nota->sum('valoripi'),15,2),
                                'zeros' =>  $this->inserirZeros(0,84,2)
                            ];

                            $linhas[] = implode($registro_506)."\n";

                            if(isset($nota->transportadoraRedespacho->nome)){
                                $registro_514 = [
                                    'registro' => '514',
                                    'razao_social_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->nome,50),
                                    'redespacho_cnpj' => $this->inserirEspaco($nota->transportadoraRedespacho->cnpj,14),
                                    'inscricacao_estadual_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->inscricaoestadual,15),
                                    'endereco_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->endereco.' '.$nota->transportadoraRedespacho->numero,50),
                                    'bairro_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->bairro,35),
                                    'cidade_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->cidade,35),
                                    'cep_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->cep,9),
                                    'codigo_municipio' => $this->inserirEspaco(' ',9),
                                    'uf_redespacho' => $this->inserirEspaco($nota->transportadoraRedespacho->estado,9),
                                    'numero_comunicacao' => $this->inserirEspaco(' ',35),
                                    'area_frete' => 'DOCA',
                                ];
                                $linhas[] = implode($registro_514)."\n";
                            }
                        }

                        $valor_total_notas += is_numeric($nota->valor) ? $nota->valor : 0; 
                        $peso_total += is_numeric($nota->pesoliquido) ? $nota->pesoliquido : 0;
                        $volumes_total += is_numeric($nota->volumes) ? $nota->volumes : 0;
                        $quantidade_notas += 1;
                    }

                    $tamanho_valor = strlen($quantidade_notas);
                    while ($tamanho_valor < 10) {
                        $quantidade_notas = substr_replace($quantidade_notas, '0'.$quantidade_notas,0);
                        $tamanho_valor = strlen($quantidade_notas);
                    }

                    $registro_519 = [
                        'registro' => '519',
                        'valor_total_notas' => $this->inserirZeros($valor_total_notas,15,2),
                        'peso_total' => $this->inserirZeros($peso_total,15,2),
                        'volumes_total' => $this->inserirZeros($volumes_total,15,2),
                        'quantidade_notas' => $quantidade_notas
                    ];

                    $linhas[] = implode($registro_519)."\n";

                    Storage::put($this->storage.$nome_arquivo, $linhas);

                    $arquivos[] = [
                        'nome_arquivo' => $nome_arquivo,
                        'cnpj' => $transportadora->transportadora_cnpj,
                    ];
                }   
            }catch (\Exception $e) {
                Log::error($nome_arquivo.' '.$e);
            }  
        }
        return $arquivos;
    }

    public function enviarArquivoTxt(){

        $arquivos = $this->criarArquivoTxt();

        $nao_enviados = 0;
      
        foreach($arquivos as $value){
            try{
                $arquivo = Storage::path($this->storage.$value['nome_arquivo']);
                $exists = Storage::exists($this->storage.$value['nome_arquivo']);

                if($exists === false){
                    throw new \Exception($arquivo.' Arquivo txt não encontrado.');
                }

                $TransportadorasEdi = TransportadorasEdi::with('transportadoraNome')
                ->where('transportadora_cnpj', $value['cnpj'])->first();

                $transportadora = $TransportadorasEdi->transportadoraNome->nome;
                $anexo = [$this->storage.$value['nome_arquivo'] => ['as' => $value['nome_arquivo']]];
                
                $emailControllerObj = new EmailController;
                $retorno = $emailControllerObj->sendEmailToken('00', 'transportadora_edi', [$TransportadorasEdi->email], ['transportadora' => $transportadora], $anexo);
                
                try{
                    if($retorno['status'] === 'error'){
                        $nao_enviados += 1;
                        throw new \Exception($arquivo.' Não foi possivel enviar o e-mail');
                    }
                    rename($arquivo, $arquivo.'.bkp');
                }catch (\Exception $e) {
                    rename($arquivo, $arquivo.'.pro');
                    Log::error($e);
                } 
            }catch (\Exception $e) {
                Log::error($arquivo.' '.$e);
            }
        }
        if(!isset($arquivos)){
            $arquivos = [];
        }
        $arquivos_criados = count($arquivos);
        $arquivos_enviados = $arquivos_criados - $nao_enviados;
        return $arquivos_criados.' arquivos criados e '.$arquivos_enviados.' enviados!';
    }
}
