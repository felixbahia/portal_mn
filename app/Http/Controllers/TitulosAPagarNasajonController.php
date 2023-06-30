<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\TitulosAPagarNasajon;
use App\FornecedorNasajon;
use App\TituloAPagar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class TitulosAPagarNasajonController extends Controller
{
    private $codigos_fornecedores = ['0000304199999', '0000012009999', '60746948325500', '62232889000190', 'FINIMPBB', 'FINIMPITAU', 'BRADESCOFINIMP', '00000000504580', '97544848000202', '92693118000160'];
    private $codigos_fornecedores_itau = ['0000012009999', 'FINIMPITAU'];
    private $codigos_fornecedores_brasil = ['0000304199999', 'FINIMPBB', '00000000504580'];
    private $codigos_fornecedores_bradesco = ['60746948325500', 'BRADESCOFINIMP', '92693118000160'];
    private $codigos_fornecedores_daycoval = ['62232889000190'];
    private $estabelecimentos_particular = ['00', '20','25', '30', 'MARCELO', 'MN_NAMURA', 'NAMURA_NN','NELSON'];

    public function index(Request $request)
    {
        if (Auth::user()->hasPermissionTo("programas App\TitulosAPagarNasajon") === false) {
            return abort(403);
        }
        $request->session()->flash('model', 'App\TitulosAPagarNasajon');
        return view('programs.titulos_apagar.index');
    }

    public function filtrar(Request $request)
    {
        ini_set('memory_limit', '256M');
        set_time_limit(300);
        $fields = $request->only(['fornecedor_nome', 'data_inicio', 'data_fim']);
        try {
            $TitulosAPagarNasajon = TitulosAPagarNasajon::selectRaw('"Fornecedor", "Data do Vencimento", 
            "Estabelecimento", "Situação do Título",
             sum("Valor") as valor, sum("Valor da Baixa") as valor_baixa')
                ->whereIn('Situação do Título', ['Aberto', 'Em Débito'])
                ->where('Data do Vencimento', '!=', null)
                ->where('Estabelecimento', '!=', '25')
                ->groupBy('Fornecedor', 'Data do Vencimento', 'Estabelecimento', 'Situação do Título');
        } catch (\Exception $e) {
            $error = [
                "status" => 'error',
                'message' => 'Dados não encontrado'
            ];
            return response()->json($error, 422);
        }

        if (!empty($fields['data_inicio'])) {
            $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio']);
            $TitulosAPagarNasajon->where('Data do Vencimento', '>=', $data_inicio->format('Y-m-d'));
        }

        if (!empty($fields['data_fim'])) {
            $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim']);
            $TitulosAPagarNasajon->where('Data do Vencimento', '<=', $data_fim->format('Y-m-d'));
        }

        if (!empty($fields['fornecedor_nome'])) {
            $fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $fields['fornecedor_nome'])
                ->first();
            $fornecedorcodigo = [];
            $cnpj_cpf = (!empty($fornecedor)) ? $fornecedor->cnpj_cpf : null;
            if (!empty($cnpj_cpf)) {
                if (strlen(trim($cnpj_cpf)) == 18) {
                    $cnpj_cpf = substr($cnpj_cpf, 0, 10);
                }
                $FornecedorNasajonQuery = FornecedorNasajon::where('cnpj_cpf', 'like', $cnpj_cpf . '%');
                $fornecedores = $FornecedorNasajonQuery->get();
                foreach ($fornecedores  as $fornecedor) {
                    $fornecedorcodigo[] = [$fornecedor->codigo];
                }
                $TitulosAPagarNasajon->whereIn('Fornecedor', $fornecedorcodigo);
            } else {
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        $TitulosAPagar = $TitulosAPagarNasajon->get();
        $estabelecimentos = returnTodasEmpresasView();
        $retorno = [];

        foreach ($TitulosAPagar->chunk(100) as $chunk) {
            foreach ($chunk as $titulo) {
                $data_vencimento = Carbon::createFromFormat('Y-m-d', $titulo['Data do Vencimento']);
                $data = Carbon::now();
                if ($data_vencimento->dayOfWeekIso == 6) {
                    $data_vencimento->addDays(2);
                }
                if ($data_vencimento->dayOfWeekIso == 7) {
                    $data_vencimento->addDays(1);
                }
                if ($titulo['Situação do Título'] === 'Aberto') {
                    $valor = $titulo->valor;
                } else {
                    $valor = $titulo->valor - $titulo->valor_baixa;
                }

                $retorno[] = [
                    'estabelecimento' => $titulo['Estabelecimento'],
                    'aberto' => ($data_vencimento->gte($data)) ? $valor : 0,
                    'vencido' => ($data_vencimento->lt($data)) ? $valor : 0,
                    'total' => $valor,
                ];
            }
        }

        unset($TitulosAPagar);
        $saida = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
            'filter' => encrypt([
                'estabelecimento' => null,
                'fornecedor_nome' => $fields['fornecedor_nome'],
                'data_inicio' => $fields['data_inicio'],
                'data_fim' => $fields['data_fim'],
            ])
        ];

        foreach ($retorno as $resp) {
            if (!isset($saida[$resp['estabelecimento']])) {
                $saida[$resp['estabelecimento']] = [
                    'estabelecimento' => $estabelecimentos[$resp['estabelecimento']],
                    'aberto' => 0,
                    'vencido' => 0,
                    'total' => 0,
                    'filter' => encrypt([
                        'estabelecimento' => $resp['estabelecimento'],
                        'fornecedor_nome' => $fields['fornecedor_nome'],
                        'data_inicio' => $fields['data_inicio'],
                        'data_fim' => $fields['data_fim'],
                    ]),
                ];
            }
            $saida[$resp['estabelecimento']]['aberto'] += $resp['aberto'];
            $saida[$resp['estabelecimento']]['vencido'] += $resp['vencido'];
            $saida[$resp['estabelecimento']]['total'] += $resp['total'];
            $total['aberto'] += $resp['aberto'];
            $total['vencido'] += $resp['vencido'];
            $total['total'] += $resp['total'];
        }

        unset($retorno);
        foreach ($saida as $key => $row) {
            $saida[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $saida[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $saida[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => ['response' => $saida, 'total' => $total],
        ]);
    }

    public function modalFornecedor(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'abertura', 'total', 'aberturageral']);
        try {
            $fields = decrypt($filter['filters']);
        } catch (Exception $e) {
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $TitulosAPagarNasajon = TitulosAPagarNasajon::whereIn('Situação do Título', ['Aberto', 'Em Débito'])
            ->where('Data do Vencimento', '!=', null)
            ->where('Estabelecimento', '!=', '25');

        if (!empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->whereBetween('Data do Vencimento', [$fields['data_inicio'], $fields['data_fim']]);
        } else if (!empty($fields['data_inicio']) && empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->where('Data do Vencimento', '>=', $fields['data_inicio']);
        } else if (empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->where('Data do Vencimento', '<=', $fields['data_fim']);
        }

        if ($filter['total'] !== 'true') {
            $TitulosAPagarNasajon->where('Estabelecimento', $fields['estabelecimento']);
        }

        if (!empty($fields['fornecedor_nome'])) {
            $fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $fields['fornecedor_nome'])
                ->first();
            $fornecedorcodigo = [];
            $cnpj_cpf = (!empty($fornecedor)) ? $fornecedor->cnpj_cpf : null;
            if (!empty($cnpj_cpf)) {
                if (strlen(trim($cnpj_cpf)) == 18) {
                    $cnpj_cpf = substr($cnpj_cpf, 0, 10);
                }
                $FornecedorNasajonQuery = FornecedorNasajon::where('cnpj_cpf', 'like', $cnpj_cpf . '%');
                $fornecedores = $FornecedorNasajonQuery->get();
                foreach ($fornecedores  as $fornecedor) {
                    $fornecedorcodigo[] = [$fornecedor->codigo];
                }
                $TitulosAPagarNasajon->whereIn('Fornecedor', $fornecedorcodigo);
            } else {
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }

        $estabelecimentos = returnTodasEmpresasView();
        $TitulosAPagar = $TitulosAPagarNasajon->get();
        $retorno = [];
        $total = [
            'aberto' => 0,
            'vencido' => 0,
            'total' => 0,
        ];

        foreach ($TitulosAPagar->chunk(100) as $chunk) {
            foreach ($chunk as $titulo) {
                if ($titulo['Situação do Título'] === 'Aberto') {
                    $saldo = $titulo['Valor'];
                } else {
                    $saldo = $titulo['Valor'] - $titulo['Valor da Baixa'];;
                }
                if (!empty($saldo)) {
                    $data_vencimento = Carbon::createFromFormat('Y-m-d', $titulo['Data do Vencimento']);
                    $data = Carbon::now();
                    if ($data_vencimento->dayOfWeekIso == 6) {
                        $data_vencimento->addDays(2);
                    }
                    if ($data_vencimento->dayOfWeekIso == 7) {
                        $data_vencimento->addDays(1);
                    }
                    if (!isset($retorno[$titulo['Fornecedor']])) {
                        $retorno[$titulo['Fornecedor']] = [
                            'fornecedor' => $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['CNPJ/CPF do Fornecedor'],
                            'estabelecimento' => $estabelecimentos[$titulo['Estabelecimento']],
                            'aberto' => 0,
                            'vencido' => 0,
                            'total' => 0,
                            'filter' => encrypt([
                                'estabelecimento' => $titulo['Estabelecimento'],
                                'fornecedor_nome' => $titulo['Razão Social do Fornecedor'],
                                'cod_fornecedor' =>  $titulo['Fornecedor'],
                                'data_inicio' => $fields['data_inicio'],
                                'data_fim' => $fields['data_fim'],
                            ]),
                        ];
                    }

                    $retorno[$titulo['Fornecedor']]['aberto'] += ($data_vencimento->gte($data)) ? $saldo : 0;
                    $retorno[$titulo['Fornecedor']]['vencido'] += ($data_vencimento->lt($data)) ? $saldo : 0;
                    $retorno[$titulo['Fornecedor']]['total'] += $saldo;
                    $total['aberto'] += ($data_vencimento->gte($data)) ? $saldo : 0;
                    $total['vencido'] += ($data_vencimento->lt($data)) ? $saldo : 0;
                    $total['total'] += $saldo;
                }
            }
        }

        unset($TitulosAPagar);
        foreach ($retorno as $key => $row) {
            $retorno[$key]['aberto'] = ($row['aberto'] > 0) ? parserValor($row['aberto']) : '';
            $retorno[$key]['vencido'] = ($row['vencido'] > 0) ? parserValor($row['vencido']) : '';
            $retorno[$key]['total'] = ($row['total'] > 0) ? parserValor($row['total']) : '';
        }

        $total['aberto'] = ($total['aberto'] > 0) ? parserValor($total['aberto']) : '';
        $total['vencido'] = ($total['vencido'] > 0) ? parserValor($total['vencido']) : '';
        $total['total'] = ($total['total'] > 0) ? parserValor($total['total']) : '';

        return view("programs.titulos_apagar.modal.fornecedor")->with(["total" => $total, "retorno" => $retorno, "aberturageral" => $filter['aberturageral']]);
    }

    public function modalTitulosAbertura(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'abertura', 'total', 'ano','mes','estabelecimentos', 'banco_boolean', 'despesas', 'tipo']);

        $banco_liberado  = true;

        if(!isset($filter['banco_boolean'])){
            $banco_liberado  = true;
        }else if($filter['banco_boolean'] == "false"){
            $banco_liberado  = false;
        }else{
            $banco_liberado  = true;
        }

        $despesas  = false;

        if(!isset($filter['despesas'])){
            $despesas  = false;
        }else if($filter['despesas'] == "true"){
            $despesas  = true;
        }else{
            $despesas  = false;
        }

        if($banco_liberado){
            if (empty($filter['ano'])) {
                try {
                    $fields = decrypt($filter['filters']);
                } catch (Exception $e) {
                    $return = [
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                        'error' => '',
                        'response' => '',
                    ];
                    return response()->json($return);
                }
            }
            
            $TitulosAPagarNasajon = TitulosAPagarNasajon::whereIn('Situação do Título', ['Aberto', 'Em Débito'])
                ->where('Data do Vencimento', '!=', null)
                ->where('Estabelecimento', '!=', '25');
            
            if (empty($filter['ano'])) {
                if (!empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
                    $TitulosAPagarNasajon->whereBetween('Data do Vencimento', [$fields['data_inicio'], $fields['data_fim']]);
                } else if (!empty($fields['data_inicio']) && empty($fields['data_fim'])) {
                    $TitulosAPagarNasajon->where('Data do Vencimento', '>=', $fields['data_inicio']);
                } else if (empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
                    $TitulosAPagarNasajon->where('Data do Vencimento', '<=', $fields['data_fim']);
                };
    
                if ($filter['total'] === 'false') {
                    $TitulosAPagarNasajon->where('Estabelecimento', $fields['estabelecimento']);
                }
                $data = Carbon::now();
    
                if ($filter['abertura'] == 'aberto') {
                    $TitulosAPagarNasajon->where(DB::raw('"Data do Vencimento"::date'), '>=', $data);
                } else if ($filter['abertura'] == 'vencido') {
                    $TitulosAPagarNasajon->where(DB::raw('"Data do Vencimento"::date'), '<', $data);
                }
        
                if (isset($fields['vencimento']) && $fields['vencimento'] === 'a_vencer') {
                    $TitulosAPagarNasajon->where('Data do Vencimento', '>=', $data);
                } else if (isset($fields['vencimento']) && $fields['vencimento'] === 'vencidos') {
                    $TitulosAPagarNasajon->where('Data do Vencimento', '<', $data);
                }
        
                if (!empty($fields['fornecedor_nome'])) {
    
                    $fornecedor = FornecedorNasajon::select();
                    
                    if(isset($fields['fornecedor_id'])){
                        $fornecedor->where('id', $fields['fornecedor_id']);
                    }else{
                        $fornecedor->where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $fields['fornecedor_nome']);
                    }
    
                    $fornecedor = $fornecedor->first();
                    
                    $fornecedorcodigo = [];
                    $codigo = (!empty($fornecedor)) ? $fornecedor->codigo : null;
                    
                    if (!empty($codigo)) {
                        $FornecedorNasajonQuery = FornecedorNasajon::where('codigo', 'like', $codigo . '%');
                        $fornecedores = $FornecedorNasajonQuery->get();
                        foreach ($fornecedores  as $fornecedor) {
                            $fornecedorcodigo[] = [$fornecedor->codigo];
                        }
                        
                        $TitulosAPagarNasajon->whereIn('Fornecedor', $fornecedorcodigo);
                    } else {
                        return response()->json([
                            'status' => 'sucess',
                            'message' => '',
                            'error' => '',
                            'response' => ['response' => null, 'total' => null],
                        ]);
                    }
                }
            } else {
        
                $primeiro_dia_do_mes = Carbon::parse($filter['ano'] . "-" . $filter['mes'] . "-01")->setTime(0, 0, 0)->firstOfMonth();
                $ultimo_dia_do_mes = Carbon::parse($filter['ano'] . "-" . $filter['mes'] . "-01")->setTime(23, 59, 59)->lastOfMonth();
                if (!empty($filter['estabelecimentos'])) {
    
                    $TitulosAPagarNasajon->whereNotIn('Estabelecimento',explode(',',$filter['estabelecimentos']));
                }
                $TitulosAPagarNasajon->where('Data do Vencimento','>=',$primeiro_dia_do_mes);
                $TitulosAPagarNasajon->where('Data do Vencimento','<=',$ultimo_dia_do_mes);
                
            }
        }else{
            $primeiro_dia_do_mes = Carbon::parse($filter['ano'] . "-" . $filter['mes'] . "-01")->setTime(0, 0, 0)->firstOfMonth();
            $ultimo_dia_do_mes = Carbon::parse($filter['ano'] . "-" . $filter['mes'] . "-01")->setTime(23, 59, 59)->lastOfMonth();

            $TitulosAPagarNasajon = TituloAPagar::select()
                ->with('notaEntradaDetalhes')
                ->whereBetween('titulo_vencimento', [$primeiro_dia_do_mes, $ultimo_dia_do_mes]);
            if(!$despesas){
                if(!isset($filter['tipo'])){
                    if($filter['tipo'] == 'aberto'){
                        $TitulosAPagarNasajon->whereIn('titulo_situacao', ['Aberto', 'Em Débito']);
                    }else if($filter['tipo'] == 'fechado'){
                        $TitulosAPagarNasajon->whereIn('titulo_situacao', ['Quitado', 'Quitado (Renegociado)']);
                    }else{
                        $TitulosAPagarNasajon->whereIn('titulo_situacao', ['Aberto', 'Em Débito', 'Quitado', 'Quitado (Renegociado)']);
                    }
                }
                $TitulosAPagarNasajon->whereIn('tipo', ['compras_internacional', 'compras']);
            }else{
                $TitulosAPagarNasajon->where('tipo', 'despesas');
            }       

        }
        

        $TitulosAPagar = $TitulosAPagarNasajon->get();
       
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjFornecedor = [];
        $cnpjFornecedor[] = ['cnpj' =>  'cnpj'];
        $cnpjFornecedor[] = ['result' =>  '2'];
        $retorno = [];

        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
       
        foreach ($TitulosAPagar->chunk(100) as $chunk) {

            foreach ($chunk as $titulo) {

                if($banco_liberado){
                    if ($titulo['Situação do Título'] === 'Aberto') {
                        $valor_titulo = $titulo['Valor'];
                    } else {
                        $valor_titulo = $titulo['Valor'] - $titulo['Valor da Baixa'];
                    }
                
                    $linha = [];

                    $linha['estabelecimento'] = $estabelecimentos[$titulo['Estabelecimento']];
                    $linha['documento'] = $titulo['Número Nota'];
                    $linha['parcela'] = $titulo['Parcela'];
                    $linha['data_emissao'] = $titulo['Data de Emissão'];
                    $linha['data_vencimento'] = parserData($titulo['Data do Vencimento']);
                    $linha['valor_original'] = $titulo['Valor'];
                    $linha['valor'] = $valor_titulo;
                    $linha['multa'] = $titulo['Multa'];
                    $linha['nome_fornecedor'] = $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['CNPJ/CPF do Fornecedor'];
                    $linha['numero_boleto'] = $titulo['Número do Título'];
                    $linha['juros_diarios'] = $titulo->percentualjurosdiario;
                    $linha['data_juros'] = '';
                    $linha['desconto'] = $titulo['Desconto'];
                    $linha['POSICAO_CR'] = $titulo['Nosso número'];
                    $linha['POSICAO_CR_DESCRICAO'] = '';
                    $linha['banco'] = '';
                    $linha['documento_id'] = '';

                    $retorno[] = $linha;
                }else{
                    $linha = [];

                    if(!empty($titulo['notaEntradaDetalhes'])){
                        $linha['documento_id'] = $titulo['notaEntradaDetalhes']['Identificador Documento'];
                    }else{
                        $linha['documento_id'] = '';
                    }

                    if ($titulo['titulo_situacao'] === 'Aberto') {
                        $valor_titulo = $titulo['titulo_valor_liquido'];
                    } else {
                        $valor_titulo = $titulo['titulo_valor_liquido'] - $titulo['Valor da Baixa'];
                    }
                
                    $linha['estabelecimento'] = $estabelecimentos[$titulo['estabelecimento_codigo']];
                    $linha['documento'] = $titulo['nota_numero'];
                    $linha['parcela'] = $titulo['titulo_parcela'];
                    $linha['data_emissao'] = $titulo['titulo_emissao'];
                    $linha['data_vencimento'] = parserData($titulo['titulo_vencimento']);
                    $linha['valor_original'] = $titulo['titulo_valor_liquido'];
                    $linha['valor'] = $valor_titulo;
                    $linha['multa'] = '';
                    $linha['nome_fornecedor'] = $titulo['fornecedor_razao_social'] . ' - ' . '';
                    $linha['numero_boleto'] = $titulo['titulo_numero'];
                    $linha['juros_diarios'] = $titulo->percentualjurosdiario;
                    $linha['data_juros'] = '';
                    $linha['desconto'] = '';
                    $linha['POSICAO_CR'] = '';
                    $linha['POSICAO_CR_DESCRICAO'] = '';
                    $linha['banco'] = '';

                    $retorno[] = $linha;

                    $total['valor'] += $titulo['titulo_valor_liquido'];
                    $total['saldo'] += $valor_titulo;
                    $total['juros'] += 0;                   
                }
            }
        }

        if($banco_liberado){
            $valor = $TitulosAPagar->sum('Valor');
            $saldotitulo = $TitulosAPagar->sum('Valor da Baixa');
            $juros = $TitulosAPagar->sum('Multa');
            $saldo = $valor - $saldotitulo;

            unset($TitulosAPagar);
            $total = [
                'valor' => ($valor > 0) ? parserValor($valor) : '',
                'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
                'juros' => ($juros > 0) ? parserValor($juros) : '',
            ];
        }else{
            $total['valor'] = ($total['valor'] > 0) ? parserValor($total['valor']) : "";
            $total['saldo'] = ($total['saldo'] > 0) ? parserValor($total['saldo']) : "";
            $total['juros'] = ($total['juros'] > 0) ? parserValor($total['juros']) : "";
        }

        return view('programs.titulos_apagar.modal.titulos_faturados')->with(["dados" => $retorno, "cod_fornecedor" => $cnpjFornecedor, "totalizadores" => $total]);
    }

    public function modalTitulos(Request $request)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
        $filter = $request->only(['filters', 'abertura', 'total', 'fornecedor', 'busca', 'aberturageral']);
        try {
            $fields = decrypt($filter['filters']);
        } catch (Exception $e) {
            $return = [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!',
                'error' => '',
                'response' => '',
            ];
            return response()->json($return);
        }

        $TitulosAPagarNasajon = TitulosAPagarNasajon::whereIn('Situação do Título', ['Aberto', 'Em Débito'])
            ->where('Data do Vencimento', '!=', null)
            ->where('Estabelecimento', '!=', '25');

        if (!empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->whereBetween('Data do Vencimento', [$fields['data_inicio'], $fields['data_fim']]);
        } else if (!empty($fields['data_inicio']) && empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->where('Data do Vencimento', '>=', $fields['data_inicio']);
        } else if (empty($fields['data_inicio']) && !empty($fields['data_fim'])) {
            $TitulosAPagarNasajon->where('Data do Vencimento', '<=', $fields['data_fim']);
        }

        $data = Carbon::now();
        if ($filter['abertura'] == 'aberto') {
            $TitulosAPagarNasajon->where('Data do Vencimento', '>', $data);
        } else if ($filter['abertura'] == 'vencido') {
            $TitulosAPagarNasajon->where('Data do Vencimento', '<=', $data);
        }

        if ($filter['fornecedor'] === 'true' && $filter['aberturageral'] === 'false') {
            $TitulosAPagarNasajon->where('Estabelecimento', $fields['estabelecimento']);
        }
        if (!empty($fields['nome_fornecedor'])) {
            $fornecedor = FornecedorNasajon::where(DB::raw('CONCAT(TRIM(nome), \' - \', cnpj_cpf)'), 'ilike', $$fields['nome_fornecedor'])
                ->first();
            $fornecedorcodigo = [];
            $cnpj_cpf = (!empty($fornecedor)) ? $fornecedor->cnpj_cpf : null;
            if (!empty($cnpj_cpf)) {
                if (strlen(trim($cnpj_cpf)) == 18) {
                    $cnpj_cpf = substr($cnpj_cpf, 0, 10);
                }
                $FornecedorNasajonQuery = FornecedorNasajon::where('cnpj_cpf', 'like', $cnpj_cpf . '%');
                $fornecedores = $FornecedorNasajonQuery->get();
                foreach ($fornecedores  as $fornecedor) {
                    $fornecedorcodigo[] = [$fornecedor->codigo];
                }
                $TitulosAPagarNasajon->whereIn('Fornecedor', $fornecedorcodigo);
            } else {
                return response()->json([
                    'status' => 'sucess',
                    'message' => '',
                    'error' => '',
                    'response' => ['response' => null, 'total' => null],
                ]);
            }
        }
        if (!empty($filter['busca'])) {
            $TitulosAPagarNasajon->where(DB::raw('CONCAT(TRIM("Razão Social do Fornecedor"), \' - \', "CNPJ/CPF do Fornecedor")'), 'ilike', '%' . $filter['busca'] . '%');
        }


        $TitulosAPagar = $TitulosAPagarNasajon->get();
        $estabelecimentos = returnTodasEmpresasView();
        $cnpjFornecedor = [];
        $retorno = [];

        foreach ($TitulosAPagar->chunk(100) as $chunk) {
            foreach ($chunk as $titulo) {
                if ($filter['total'] === 'true') {
                    if (isset($titulo['CNPJ/CPF do Fornecedor'])) {
                        $cnpjFornecedor[] = [
                            'cnpj' =>  $titulo['CNPJ/CPF do Fornecedor'],
                        ];
                    }
                }

                if ($titulo['Situação do Título'] === 'Aberto') {
                    $valor_titulo = $titulo['Valor'];
                } else {
                    $valor_titulo = $titulo['Valor'] - $titulo['Valor da Baixa'];
                }

                $linha = [];

                $linha['estabelecimento'] = $estabelecimentos[$titulo['Estabelecimento']];
                $linha['documento'] = (empty($titulo['Número Nota'])) ? $titulo->notaEntrada['Número do Documento'] : $titulo['Número Nota'];
                $linha['parcela'] = ($titulo['Parcela'] > 0) ? $titulo['Parcela'] : '';
                $linha['data_emissao'] = $titulo['Data de Emissão'];
                $linha['data_vencimento'] = parserData($titulo['Data do Vencimento']);
                $linha['valor_original'] = ($titulo['Valor'] > 0) ? parserValor($titulo['Valor']) : '';
                $linha['valor'] = ($valor_titulo > 0) ? parserValor($valor_titulo) : '';
                $linha['multa'] = $titulo['Multa'];
                $linha['nome_fornecedor'] = $titulo['Razão Social do Fornecedor'] . ' - ' . $titulo['CNPJ/CPF do Fornecedor'];
                $linha['numero_boleto'] = $titulo['Número do Título'];
                $linha['juros_diarios'] = ($titulo->percentualjurosdiario > 0) ? parserValor($titulo->percentualjurosdiario) : '';
                $linha['data_juros'] = '';
                $linha['desconto'] = ($titulo['Desconto'] > 0) ? parserValor($titulo['Desconto']) : '';
                $linha['POSICAO_CR'] = (!empty($titulo['Nosso número'])) ? $titulo['Nosso número'] : '';
                $linha['banco'] = '';

                $retorno[] = $linha;
            }
        }



        $total = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
        $valor = $TitulosAPagar->sum('Valor');
        $saldotitulo = $TitulosAPagar->sum('Valor da Baixa');
        $juros = $TitulosAPagar->sum('Multa');

        $saldo = $valor - $saldotitulo;

        unset($TitulosAPagar);
        $total = [
            'valor' => ($valor > 0) ? parserValor($valor) : '',
            'saldo' => ($saldo > 0) ? parserValor($saldo) : '',
            'juros' => ($juros > 0) ? parserValor($juros) : '',
        ];

        return view('programs.titulos_apagar.modal.titulos_faturados')->with(["dados" => $retorno, "cod_fornecedor" => $cnpjFornecedor, "totalizadores" => $total]);
    }
}
