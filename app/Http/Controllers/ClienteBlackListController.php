<?php

namespace App\Http\Controllers;

use Auth;

use Carbon\Carbon;
use App\ClienteNasajon;
use App\ClienteBlackList;
use App\TitulosPagosNasajon;
use Illuminate\Http\Request;
use App\ClienteBlackListTitulo;
use App\MotivoClienteBlackList;
use App\TitulosEmAbertoNasajon;

use App\TituloPagamentoNasajon;
use App\ClienteBlackListHistorico;

use Illuminate\Support\Facades\DB;

use App\ContasReceberBaixadoNasajon;
use App\Http\Requests\ClienteBlackListAdicionarRequest;
use App\Http\Requests\ClienteBlackListAdicionarTituloRequest;

class ClienteBlackListController extends Controller
{
    public function index(Request $request)
    {

        if (Auth::user()->hasPermissionTo("programas App\ClienteBlackList") === false) {
            return abort(403);
        }

        $request->session()->flash('model', 'App\ClienteBlackList');

        return view('programs.cliente_black_list.index');
    }

    public function filtro(Request $request)
    {
        $fields = $request->only('cliente_nome');

        $query = ClienteBlackList::select();
        $query->with('cliente');

        if (isset($fields['cliente_nome']) && !empty($fields['cliente_nome'])) {
            $cliente_busca = ClienteNasajon::select('cpf_cnpj')
                ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%' . ($fields['cliente_nome']) . '%');
            $cliente_busca = $cliente_busca->get();

            $query->whereIn('cpf_cnpj', $cliente_busca->pluck('cpf_cnpj'));
        }

        $result = $query->get();

        $clientes_table = [];

        $result->each(function ($cliente) use (&$clientes_table) {
            $clientes_table[] = [
                'id' => encrypt($cliente->id),
                'nome_razao' => $cliente->cliente->nome,
                'cpf_cnpj' => $cliente->cpf_cnpj,
                'status' => $cliente->status_cliente_black_lists_id === 3 ? 'Bloqueado' : 'Liberado',
            ];
        });

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => $clientes_table
        ];

        return response()->json($response, 220);
    }

    public function modalAdicionar(Request $request)
    {
        $titulos_adicional = [];
        $motivos = $this->getMotivo();
        return view('programs.cliente_black_list.modal.adicionar')->with(['titulos_adicional' => encrypt($titulos_adicional), 'motivos' => $motivos]);
    }

    public function adicionar(ClienteBlackListAdicionarRequest $request)
    {
        $fields = $request->only('nome_cliente', 'titulos_adicional', 'motivo', 'observacao');

        $cliente_busca = ClienteNasajon::select()
            ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', $fields['nome_cliente']);
        $cliente_busca = $cliente_busca->first();

        $clienteBlackListObj = new ClienteBlackList;
        $clienteBlackListObj->cpf_cnpj = $cliente_busca->cpf_cnpj;
        $clienteBlackListObj->created_by = Auth::id();
        $clienteBlackListObj->status_cliente_black_lists_id = 3;
        $clienteBlackListObj->motivo_cliente_black_lists_id = $fields['motivo'];
        $clienteBlackListObj->observacao = $fields['observacao'];
        $clienteBlackListObj->save();

        $titulos = decrypt($fields['titulos_adicional']);

        $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
        $clienteBlackListHistoricoObj->cpf_cnpj = $cliente_busca->cpf_cnpj;
        $clienteBlackListHistoricoObj->titulo = '';
        $clienteBlackListHistoricoObj->motivo = $clienteBlackListObj->detalhesMotivo->motivo;
        $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListHistoricoObj->created_by = Auth::id();
        $clienteBlackListHistoricoObj->observacao = $fields['observacao'];
        $clienteBlackListHistoricoObj->save();

        foreach ($titulos as $titulo) {
            $clienteBlackListTituloObj = new ClienteBlackListTitulo;
            $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
            $clienteBlackListTituloObj->titulo_numero = $titulo;
            $clienteBlackListTituloObj->created_by = Auth::id();
            $clienteBlackListTituloObj->save();
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function modalEditar(Request $request)
    {
        $id = $request->only('id')['id'];

        try {
            $id = decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $clienteBlackListObj = ClienteBlackList::find($id);

        $titulos = [];
        foreach ($clienteBlackListObj->titulos as $titulo) {
            $titulos[] = $titulo->titulo_numero;
        }

        $motivos = $this->getMotivo();

        $dados = [
            'id' => encrypt($id),
            'cliente_id' => encrypt($clienteBlackListObj->cliente->id),
            'cliente' => $clienteBlackListObj->cliente->nome . " - " . $clienteBlackListObj->cpf_cnpj,
            'titulos' => $titulos,
            'status' => $clienteBlackListObj->status_cliente_black_lists_id === 3 ? 'Bloqueado' : 'Liberado',
            'titulos_adicional' => encrypt($titulos),
            'status_cliente_black_lists_id' => $clienteBlackListObj->status_cliente_black_lists_id,
            'motivo' => $clienteBlackListObj->motivo_cliente_black_lists_id,
            'motivos' => $motivos,
            'observacao' => $clienteBlackListObj->observacao,
        ];

        return view('programs.cliente_black_list.modal.editar')->with(['dados' => $dados]);
    }

    public function liberar(Request $request)
    {
        $fields = $request->only('id', 'observacao');

        try {
            $id = decrypt($fields['id']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $clienteBlackListObj = ClienteBlackList::find($id);
        $clienteBlackListObj->updated_by = Auth::id();
        $clienteBlackListObj->status_cliente_black_lists_id = 2;
        $clienteBlackListObj->observacao = $fields['observacao'];
        $clienteBlackListObj->save();

        $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
        $clienteBlackListHistoricoObj->cpf_cnpj = $clienteBlackListObj->cpf_cnpj;
        $clienteBlackListHistoricoObj->motivo = 'Cliente Liberado';
        $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListHistoricoObj->created_by = Auth::id();
        $clienteBlackListHistoricoObj->observacao = $fields['observacao'];
        $clienteBlackListHistoricoObj->save();

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function bloquear(Request $request)
    {
        $fields = $request->only('id', 'observacao');

        try {
            $id = decrypt($fields['id']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $clienteBlackListObj = ClienteBlackList::find($id);
        $clienteBlackListObj->updated_by = Auth::id();
        $clienteBlackListObj->status_cliente_black_lists_id = 3;
        $clienteBlackListObj->observacao = $fields['observacao'];
        $clienteBlackListObj->save();

        $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
        $clienteBlackListHistoricoObj->cpf_cnpj = $clienteBlackListObj->cpf_cnpj;
        $clienteBlackListHistoricoObj->motivo = 'Cliente Bloqueado';
        $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
        $clienteBlackListHistoricoObj->created_by = Auth::id();
        $clienteBlackListHistoricoObj->observacao = $fields['observacao'];
        $clienteBlackListHistoricoObj->save();

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function autoCompleteTituloPago(Request $request)
    {
        $fields = $request->only('term', 'cliente_nome');

        $descricao = $fields['term'];

        $cliente_busca = ClienteNasajon::select()
            ->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%' . ($fields['cliente_nome']) . '%');
        $cliente_busca = $cliente_busca->first();

        $return = [];

        $TitulosPagosNasajonObj = TitulosPagosNasajon::select()->distinct('numero')->where('numero', 'ilike', "%" . $descricao . "%")->where('cliente_id', $cliente_busca->id)->where('valordesconto', '>', 0)->orderBy('numero')->limit("15");

        $result_titulos = $TitulosPagosNasajonObj->get()->toArray();

        foreach ($result_titulos as $value) {
            $value = (array) $value;

            $return[] = [
                'label' => trim(utf8_decode(utf8_encode($value['numero']))),
                'value' => trim(utf8_encode($value['numero'])),
            ];
        }
        return response()->json($return);
    }

    public function modalBuscarTitulosPago(Request $request)
    {
        $fields = $request->only('cliente_nome');

        $cliente = ClienteNasajon::select()->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%' . ($fields['cliente_nome']) . '%');
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();

        $codigo = $cliente['codigo'];
        $cpf_cnpj = $cliente['cpf_cnpj'];

        if (strlen(trim($cpf_cnpj)) == 18) {
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::select('*');
        $clientesNasajonQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        $clientesNasajon = $clientesNasajonQuery->get();

        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];

        $titulosNasajon = TitulosPagosNasajon::select(DB::raw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"))
            ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('valordesconto', '>', 0)
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');

        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'desconto' => 0,
            'juros' => 0,
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores, $titulosNasajon) {
            $nota_numero = '';

            if (empty($item->nota_numero)) {
                $nota_numero = $item->nota;
            } else {
                $nota_numero = $item->nota_numero;
            }

            $value['titulo_id'] = $item->titulo_id;
            $value['estabelecimento'] = $item->codigo;
            $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
            $value['nota_numero']           = $nota_numero;
            $value['parcela']               = $item->parcela;
            $value['data_emissao']          = $item->emissao;
            $value['data_vencimento_sql']   = $item->vencimento;
            $value['data_vencimento']       = parserData($item->vencimento);
            $value['status']                = '';
            $value['valor_original']        = $item->valor;
            $value['valor']                 = $item->valor;
            $value['juros_cobrados']        = $item->valorjuros;
            $value['nome_cliente']          = $item->nome_cliente . ' - ' . $item->cliente->cpf_cnpj;
            $value['numero']                = $item->numero;
            $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
            $value['data_juros']            = $item->datainiciomultaejuros;
            $value['desconto']              = $item->valordesconto;
            $value['POSICAO_CR']            = $item->nossonumero;
            $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
            $value['observacao']  = $item->observacao;
            $value['numero_titulo_renegociado'] = '';
            $value['vencimento_titulo_renegociado'] = '';

            if (!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)) {
                $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado) ? parserData($item->vencimento_titulo_renegociado) : '';
            } else {
                if (strpos($item->observacao, '### Titulo ') !== false) {
                    preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                    if (isset($titulo_original[0])) {
                        $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();

                        if (!empty($tituloPagoObj)) {
                            $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                            $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento) ? parserData($tituloPagoObj->vencimento) : '';
                        }
                    }
                }
            }

            $value['banco'] = ($item->enviado_para_banco == true) ? $item->banco_codigo : 'CARTEIRA';

            // Status do título
            if ($item->tem_prorrogacao === true) {
                $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
            }
            if ($item->enviado_para_cartorio === true) {
                $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
            }

            $key = $item->numero . '' . $item->codigo;

            $titulos_faturados[$key] = $value;

            $totalizadores["valor"] += $item->valor;
            $totalizadores["desconto"] += $item->valordesconto;
            $totalizadores["juros"] += $item->valorjuros;
        });

        if ($totalizadores['valor'] > 0) {
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        } else {
            $totalizadores['valor'] = '';
        }

        if ($totalizadores['desconto'] > 0) {
            $totalizadores['desconto'] = parserValor($totalizadores['desconto']);
        } else {
            $totalizadores['desconto'] = '';
        }

        if ($totalizadores['juros'] > 0) {
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        } else {
            $totalizadores['juros'] = '';
        }

        return view('programs.cliente_black_list.modal.buscar_titulo_pago')->with(["dados" => $titulos_faturados, "cod_cliente" => $cnpjCliente, "totalizadores" => $totalizadores, "codigo" => $codigo, "unico" => false]);
    }

    public function filtroTitulosParaPago(Request $request)
    {
        $fields = $request->only('codigo', 'unico', 'titulo');

        $cliente = ClienteNasajon::select()->where('codigo', $fields['codigo']);
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();

        $codigo = $cliente['codigo'];
        $cpf_cnpj = $cliente['cpf_cnpj'];

        if (strlen(trim($cpf_cnpj)) == 18) {
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::select('*');
        $clientesNasajonQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        $clientesNasajon = $clientesNasajonQuery->get();

        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];

        $titulosNasajon = TitulosPagosNasajon::select(DB::raw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota"));

        if (!empty($fields['titulo'])) {
            $titulosNasajon = $titulosNasajon->where('numero', 'ilike', '%' . $fields['titulo'] . '%');
        }

        $titulosNasajon = $titulosNasajon->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'))
            ->where('valordesconto', '>', 0)
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');

        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'desconto' => 0,
            'juros' => 0,
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores, $titulosNasajon) {
            $nota_numero = '';

            if (empty($item->nota_numero)) {
                $nota_numero = $item->nota;
            } else {
                $nota_numero = $item->nota_numero;
            }

            $value['titulo_id'] = $item->titulo_id;
            $value['estabelecimento'] = $item->codigo;
            $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
            $value['nota_numero']           = $nota_numero;
            $value['parcela']               = $item->parcela;
            $value['data_emissao']          = $item->emissao;
            $value['data_vencimento_sql']   = $item->vencimento;
            $value['data_vencimento']       = parserData($item->vencimento);
            $value['status']                = '';
            $value['valor_original']        = $item->valor;
            $value['valor']                 = $item->valor;
            $value['juros_cobrados']        = $item->valorjuros;
            $value['nome_cliente']          = $item->nome_cliente . ' - ' . $item->cliente->cpf_cnpj;
            $value['numero']                = $item->numero;
            $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
            $value['data_juros']            = $item->datainiciomultaejuros;
            $value['desconto']              = $item->valordesconto;
            $value['POSICAO_CR']            = $item->nossonumero;
            $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
            $value['observacao']  = $item->observacao;
            $value['numero_titulo_renegociado'] = '';
            $value['vencimento_titulo_renegociado'] = '';

            if (!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)) {
                $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado) ? parserData($item->vencimento_titulo_renegociado) : '';
            } else {
                if (strpos($item->observacao, '### Titulo ') !== false) {
                    preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                    if (isset($titulo_original[0])) {
                        $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();

                        if (!empty($tituloPagoObj)) {
                            $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                            $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento) ? parserData($tituloPagoObj->vencimento) : '';
                        }
                    }
                }
            }

            $value['banco'] = ($item->enviado_para_banco == true) ? $item->banco_codigo : 'CARTEIRA';

            // Status do título
            if ($item->tem_prorrogacao === true) {
                $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
            }
            if ($item->enviado_para_cartorio === true) {
                $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
            }

            $key = $item->numero . '' . $item->codigo;

            $titulos_faturados[$key] = $value;

            $totalizadores["valor"] += $item->valor;
            $totalizadores["desconto"] += $item->valordesconto;
            $totalizadores["juros"] += $item->valorjuros;
        });

        if ($totalizadores['valor'] > 0) {
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        } else {
            $totalizadores['valor'] = '';
        }

        if ($totalizadores['desconto'] > 0) {
            $totalizadores['desconto'] = parserValor($totalizadores['desconto']);
        } else {
            $totalizadores['desconto'] = '';
        }

        if ($totalizadores['juros'] > 0) {
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        } else {
            $totalizadores['juros'] = '';
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => [
                'titulos_faturados' => $titulos_faturados,
                'totalizadores' => $totalizadores
            ],
        ];
        return response()->json($response);
    }

    public function adicionarTitulo(ClienteBlackListAdicionarTituloRequest $request)
    {
        $fields = $request->only('titulos_adicional', 'titulo');

        try {
            $titulos_adicional = decrypt($fields['titulos_adicional']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $titulos_adicional[] = $fields['titulo'];

        $response = [
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'tabela' => $titulos_adicional,
                'titulos_adicional' => encrypt($titulos_adicional),
            ],
        ];

        return response()->json($response);
    }

    public function editar(Request $request)
    {
        $fields = $request->only('titulos_adicional', 'id', 'motivo', 'observacao');

        try {
            $id = decrypt($fields['id']);
            $titulos_adicional = decrypt($fields['titulos_adicional']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $clienteBlackListObj = ClienteBlackList::find($id);
        $clienteBlackListObj->motivo_cliente_black_lists_id = $fields['motivo'];
        $clienteBlackListObj->observacao = $fields['observacao'];
        $clienteBlackListObj->save();

        foreach ($titulos_adicional as $titulo) {
            $clienteBlackListTituloObj = ClienteBlackListTitulo::select();
            $clienteBlackListTituloObj->where('cliente_black_lists_id', $id);
            $clienteBlackListTituloObj->where('titulo_numero', $titulo);
            $clienteBlackListTituloObj = $clienteBlackListTituloObj->first();

            if (empty($clienteBlackListTituloObj)) {
                $clienteBlackListTituloObj = new ClienteBlackListTitulo;
                $clienteBlackListTituloObj->cliente_black_lists_id = $id;
                $clienteBlackListTituloObj->titulo_numero = $titulo;
                $clienteBlackListTituloObj->save();

                $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
                $clienteBlackListHistoricoObj->cpf_cnpj = $clienteBlackListTituloObj->cpf_cnpj;
                $clienteBlackListHistoricoObj->titulo = $titulo;
                $clienteBlackListHistoricoObj->motivo = 'Baixa do Tìtulo Com Desconto';
                $clienteBlackListHistoricoObj->cliente_black_lists_id = $id;
                $clienteBlackListHistoricoObj->created_by = Auth::id();
                $clienteBlackListHistoricoObj->save();

                $clienteBlackListObj->updated_by = Auth::id();
                $clienteBlackListObj->status_cliente_black_lists_id = 3;
                $clienteBlackListObj->save();
            }
        }

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [],
        ]);
    }

    public function modalHistorico(Request $request)
    {
        $fields = $request->only('cpf_cnpj');

        try {
            $cpf_cnpj = decrypt($fields['cpf_cnpj']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $query = ClienteBlackListHistorico::select();
        $query->with('blackList');
        $query->whereIn('cpf_cnpj', $cpf_cnpj);
        $query->orderBy('cpf_cnpj', 'ASC');
        $query->orderBy('created_at', 'ASC');
        $result = $query->get();

        $dados = [];
        foreach ($result as $historico) {
            $dados[] = [
                'cliente' => $historico->cliente->nome . " - " . $historico->cpf_cnpj,
                'motivo' => $historico->motivo,
                'data' => parserData($historico->created_at),
                'hora' => $historico->created_at->format('H:i:s'),
                'usuario' => empty($historico->criadoPor) ? '' : $historico->criadoPor->name,
                'titulo' => $historico->titulo,
                'baixa_por' => empty($historico->baixaTitulo) ? '' : $historico->baixaTitulo->criadoPor->name,
                'observacao' => empty($historico->blackList) ? $historico->observacao : $historico->blackList->observacao,
            ];
        }

        return view('programs.cliente_black_list.modal.historico')->with(['dados' => $dados]);
    }

    private function getMotivo()
    {
        $query = MotivoClienteBlackList::select();
        $result = $query->get();

        $motivos = [];
        foreach ($result as $motivo) {
            $motivos[$motivo->id] = $motivo->motivo;
        }

        return $motivos;
    }

    public function verificacaoInadImplenciaSuperior30Dias()
    {
        $data_atual = Carbon::now()->setTime(23, 59, 59);
        $data_menos_31 = $data_atual->subDays(31);

        $query = TitulosEmAbertoNasajon::select(); 
        $query->whereNotIn('cod_cliente', $this->clientesExcluido());
        $query->where('vencimento', '<', $data_menos_31);
        $query->distinct();
        $result = $query->get();

        foreach ($result as $titulo) {
            if (empty($titulo->clienteBlackListTitulo)) {
                $cliente_cnpj = $titulo->cliente->cpf_cnpj;

                $clienteBlackListObj = ClienteBlackList::select();
                $clienteBlackListObj->where('cpf_cnpj', $cliente_cnpj);
                $clienteBlackListObj = $clienteBlackListObj->first();

                if (empty($clienteBlackListObj)) {
                    $clienteBlackListObj = new ClienteBlackList;
                    $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
                    $clienteBlackListObj->created_by = 1;
                    $clienteBlackListObj->status_cliente_black_lists_id = 3;
                    $clienteBlackListObj->save();
                } else {
                    $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
                    $clienteBlackListObj->created_by = 1;
                    $clienteBlackListObj->status_cliente_black_lists_id = 3;
                    $clienteBlackListObj->save();
                }

                $clienteBlackListTituloObj = new ClienteBlackListTitulo;
                $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListTituloObj->titulo_uuid_nasajon = $titulo->titulo_id;
                $clienteBlackListTituloObj->titulo_numero = $titulo->numero;
                $clienteBlackListTituloObj->created_by = 1;
                $clienteBlackListTituloObj->save();

                $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
                $clienteBlackListHistoricoObj->cpf_cnpj = $cliente_cnpj;
                $clienteBlackListHistoricoObj->titulo = $titulo->numero;
                $clienteBlackListHistoricoObj->motivo = 'INADIMPLÊNCIA SUPERIOR A 30 DIAS';
                $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListHistoricoObj->created_by = 1;
                $clienteBlackListHistoricoObj->save();
            }
        }
    }

    public function addCliente($inicio_periodo, $fim_periodo)
    {


        $tituloPagamentoNasajonn = TituloPagamentoNasajon::select()->where('conta_codigo','PERDA CONCRETIZADA');
        $tituloPagamentoNasajonn->whereBetween('data_pagamento', [$inicio_periodo, $fim_periodo]);
        $result = $tituloPagamentoNasajonn->get();
       
        foreach ($result as $titulo) {

            $cliente_cnpj = $titulo->cliente->cpf_cnpj;

            $clienteBlackListObj = ClienteBlackList::select();
            $clienteBlackListObj->where('cpf_cnpj', $cliente_cnpj);
            $clienteBlackListObj = $clienteBlackListObj->first();

            if (empty($clienteBlackListObj)) {
                $clienteBlackListObj = new ClienteBlackList;
                $clienteBlackListObj->cpf_cnpj = $cliente_cnpj;
                $clienteBlackListObj->created_by = 1;
                $clienteBlackListObj->status_cliente_black_lists_id = 3;
                $clienteBlackListObj->save();


                $clienteBlackListTituloObj = new ClienteBlackListTitulo;
                $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListTituloObj->titulo_uuid_nasajon = $titulo->id_titulo;
                $clienteBlackListTituloObj->titulo_numero = $titulo->numero;
                $clienteBlackListTituloObj->created_by = 1;
                $clienteBlackListTituloObj->save();

                $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
                $clienteBlackListHistoricoObj->cpf_cnpj = $cliente_cnpj;
                $clienteBlackListHistoricoObj->titulo = $titulo->numero;
                $clienteBlackListHistoricoObj->motivo = 'DESISTÊNCIA DA COBRANÇA JUDICIAL';
                $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListHistoricoObj->created_by = 1;
                $clienteBlackListHistoricoObj->save();
            }
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
}
