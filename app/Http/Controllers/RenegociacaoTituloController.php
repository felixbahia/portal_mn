<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\TitulosEmAbertoNasajon;
use App\ClienteNasajon;
use App\TitulosPagosNasajon;
use App\RenegociacaoTitulo;
use App\RenegociacaoTituloTitulo;
use App\RenegociacaoTituloParcela;
use App\AprovacaoRenegociacao;
use App\ClienteBlackList;
use App\ClienteBlackListHistorico;
use App\ClienteBlackListTitulo;
use App\NasajonEstabelecimento;
use App\User;
use App\ContasNasajon;
use App\VendedorTituloNasajon;
use App\RenegociacaoTituloAvalista;
use App\PedidoFormaPagamentoNasajon;
use App\VendedorNasajon;

use Auth;
use Carbon\Carbon;
use PDF;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\RenegociacaoTituloSelecionarTituloRequest;
use App\Http\Requests\RenegociacaoTituloAdicionarRequest;
use App\Http\Requests\RenegociacaoTituloAdicionarAvalistaRequest;
use App\Http\Requests\RenegociacaoTituloAvalistaRequest;

use App\Http\Controllers\AprovacaoRenegociacaoTituloController;
use App\Http\Controllers\AssinaturaEletronicaClicksignController;
use App\Http\Requests\RenegociacaoConfirmarRequest;
use App\Http\Requests\RenegociacaoTituloAdicionarSocioRequest;
use App\Http\Requests\RenegociacaoTituloEditarRequest;
use App\LancamentoProjeto;

class RenegociacaoTituloController extends Controller
{

    protected $storage = 'public/contratos_renegociacao_titulos/avalista/';

    public function modalAdicionar(RenegociacaoTituloSelecionarTituloRequest $request) {
        $fields = $request->only('cliente_codigo', 'titulos_selecionado');
        
        $query = TitulosEmAbertoNasajon::select('titulo_id', 'numero', 'saldotitulo', 'juros', 'vencimento', 'codigo', 'cnpj','valor');
        $query->whereIn('titulo_id', $fields['titulos_selecionado']);
        $query->distinct();
        $result = $query->get();

        $saldo_total= 0;
        $titulos_tabelas = [];
        $maior_vencimento = '';
        foreach($result as $titulo){
            $saldo_total += $titulo->saldotitulo - $titulo->juros;

            $titulos_tabelas[] = [
                'titulo' => $titulo->numero,
                'valor' => parserValor($titulo->valor),
                'vencimento'=> parserData($titulo->vencimento),
            ];

            if(empty($maior_vencimento)){
                $maior_vencimento = Carbon::parse($titulo->vencimento);
            }else{
                $comparacao = Carbon::parse($titulo->vencimento);

                if($comparacao->gt($maior_vencimento)){
                    $maior_vencimento = $comparacao;
                }
            }
        }

        $data_atual = Carbon::now()->setTime(0,0,0);

        $maior_atraso = $data_atual->diffInDays($maior_vencimento);

        $data_inicial = Carbon::now()->format('d/m/Y');
        $estado_civil = $this->getEstadoCivil();

        return view('programs.negociacao_titulo.modal.adicionar')->with([
            'cliente_codigo' => $fields['cliente_codigo'],
            'saldo_total' => parserValor($saldo_total),
            'titulos' => encrypt($fields['titulos_selecionado']),
            'titulos_tabelas' => $titulos_tabelas,
            'data_inicial' => $data_inicial,
            'estado_civil' => $estado_civil,
            'maior_atraso' => $maior_atraso,
        ]);
    }

    public function modalSelecionarTitulos(Request $request){
        $fields = $request->only('cliente');

        $codigo = $fields['cliente'];
        $cliente = ClienteNasajon::select()->where('codigo', $codigo);
        $cliente = $cliente->first();

        if(empty($cliente->email)){
            return response()->json([
                'status' => 'error',
                'message' => 'Ajustar o E-mail do Cliente',
                'error' => [],
                'response' => []
            ], 422);
        }

        $cliente = $cliente->toArray();        

        $cliente_nome = $cliente['nome'];

        $cpf_cnpj = $cliente['cpf_cnpj'];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::select('*');
        $clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');

        $clientesNasajon = $clientesNasajonQuery->get();
      
        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];
        $data_atual = Carbon::now();
        $data_agora = Carbon::now()->setTime(0,0,0);
        $data_atual_menos_30 = $data_atual->subDays(30);
        
        $titulosNasajon = TitulosEmAbertoNasajon::with(['devolucoes' => function($query){
                $query->whereNotIn('devolucao_nota_status_id', [7, 8,11]);
            },'cenprot', 'cliente', 'renegociacao' => function($query){
                $query->whereHas('detalhesRenegociacao', function($query){
                    $query->whereNotIn('status_renegociacao_titulos_id', [3, 9, 10, 7]);
                });
            },'tituloNovoRenegociado'])
            ->selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota")
            ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));
        
        if(Auth::user()->hasRole('Juridico') || Auth::user()->codigo_representante == '998'){
            $titulosNasajon->where(function($query) use($data_atual_menos_30, $data_agora){
                $query->whereNotIn('codigo', [30]);
                $query->orWhereHas('vendedorTitulo', function($query){
                    $query->where('vendedor_codigo','998');
                });
                $query->orWhere(function($query) use($data_atual_menos_30, $data_agora){
                    $query->where('titulo_de_terceiro', false)
                    ->where(function($query) use($data_atual_menos_30, $data_agora){
                        $query->orWhere(function($query)use($data_atual_menos_30){
                            $query->where('vencimento', '<', $data_atual_menos_30);
                            $query->where('nota_emissao', '>=', '2022-07-01');
                        });
                        $query->orWhere(function($query)use($data_agora){
                            $query->where('vencimento', '<', $data_agora);
                            $query->where('nota_emissao', '<', '2022-07-01');
                        });
                    });
                });
            }); 
        }

        $titulosNasajon->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();

        $cnpjCliente = $clientesNasajon->pluck('codigo');

        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];
        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores){       
            if((empty($item->renegociacao) && empty($item->tituloNovoRenegociado)) || $item->tituloNovoRenegociado->renegociacao_liberada == true){
                $nota_numero = '';
                
                if(empty($item->nota_numero)){
                    $nota_numero = $item->nota;
                }else{
                    $nota_numero = $item->nota_numero;
                }

                $value['titulo_id'] = $item->titulo_id;
                $value['estabelecimento'] = $item->codigo;
                $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
                $value['nota_numero']           = $nota_numero;
                $value['parcela']               = $item->parcela;
                $value['data_emissao']          = $item->titulo_emissao;
                $value['data_vencimento_sql']   = parserData($item->vencimento);
                $value['data_vencimento']       = parserData($item->vencimento);
                $value['status']                = '';
                $value['valor_original']        = $item->valor;
                $value['valor']                 = $item->saldotitulo;
                $value['juros_cobrados']        = $item->juros;
                $value['nome_cliente']          = $item->nome_cliente .' - '. $item->cliente->cpf_cnpj;
                $value['numero']                = $item->numero;
                $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
                $value['data_juros']            = $item->datainiciomultaejuros;
                $value['desconto']              = $item->desconto;
                $value['POSICAO_CR']            = $item->nossonumero;
                $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
                $value['observacao']  = $item->observacao;
                $value['numero_titulo_renegociado'] = '';
                $value['vencimento_titulo_renegociado'] = '';

                if(!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)){
                    $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                    $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado)?parserData($item->vencimento_titulo_renegociado):'';
                }else{
                    if (strpos($item->observacao, '### Titulo ') !== false){
                        preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                        if(isset($titulo_original[0])) {
                            $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();
                            
                            if(!empty($tituloPagoObj)){
                                $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                                $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento)?parserData($tituloPagoObj->vencimento):'';
                            }
                        }
                    }
                }

                $value['banco'] = ($item->enviado_para_banco == true) ? $item->banco_codigo : 'CARTEIRA';

                // Status do título
                if($item->tem_prorrogacao === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
                }
                if($item->enviado_para_cartorio === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
                }

                $key = $item->numero . '' . $item->codigo;

                $titulos_faturados[$key] = $value;

                $totalizadores["valor"] += $item->valor;
                $totalizadores["saldo"] += $item->saldotitulo;
                $totalizadores["juros"] += $item->juros;
            }
        });

        if($totalizadores['valor'] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo']);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        }
        else{
            $totalizadores['juros'] = '';
        }

        $tipos = $this->getTipoTitulos();

        return view('programs.negociacao_titulo.modal.selecionar_titulos')->with(["dados"=>$titulos_faturados,"cod_cliente" => $cnpjCliente, "cliente_nome" => $cliente_nome, "totalizadores" => $totalizadores, "cliente_codigo" => $fields['cliente'], 'tipos' => $tipos]);
    }

    public function salvarRenegociacao(RenegociacaoTituloAvalistaRequest $request){
        $fields = $request->only('avalistas','socios','email','hash', 'tipo');

        $tipo = $fields['tipo'];
        $fields = decrypt($fields['hash']);
        $fields['tipo'] = $tipo;
        $campo = $request->only('email');
        $maior_atraso = $fields['maior_atraso'];

        $avalistas = isset($fields['avalistas'])? decrypt($fields['avalistas']) : [];
        $socios = isset($fields['socios'])? decrypt($fields['socios']) : [];
        $email_previa = !empty($campo['email'])? $campo['email'] : '';

        $titulos = decrypt($fields['titulos']);
        $juros_mes = empty($fields['juro_mes'])? 0 : floatval(parserNumber($fields['juro_mes']));
        $quantidade_parcela = intval(parserNumber($fields['quantidade_parcela'])); 
        $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_parcela'][0])->setTime(0,0,0);
        $data_final = Carbon::createFromFormat('d/m/Y', $fields['data_parcela'][intval($fields['quantidade_parcela']) - 1])->setTime(0,0,0);
        $data_inicial_renegociacao = Carbon::createFromFormat('d/m/Y', $fields['data_inicial_parcela'])->setTime(0,0,0);
        $saldo_total = 0;
        $data_atual = Carbon::Now()->setTime(0,0,0);
        $intervalo_total_dias = $data_atual->diffInDays($data_final);
        $status_renegociacao = 1;
        $cpf_cnpj = ClienteNasajon::select()->where('codigo', $fields['cliente_codigo'])->where('bloqueado', false)->first()->cpf_cnpj;
        $motivo_desconto_no_valor_do_titulo = false;
        $motivo_juros_baixo_permitido = false;
        $motivo_periodo_maior_permitido = false;
        $motivo_parcela_com_valor_fixo = false;
        $motivo_sem_fiador = false;
        $motivo_fiador_casado_sem_venia_conjugal = false;

        $query_titulos = TitulosEmAbertoNasajon::select('titulo_id', 'numero', DB::raw('sum(saldotitulo - juros) as total'));
        $query_titulos->whereIn('titulo_id', $titulos);
        $query_titulos->distinct();
        $query_titulos->groupBy('titulo_id', 'numero');
        $result_titulos = $query_titulos->get();

        $total_titulos_nasajon = 0;
        foreach($result_titulos as $titulo){
            $total_titulos_nasajon += $titulo->total;
        }

        $valor_parcela_total = 0;

        foreach($fields['valor_parcela'] as $index => $valor_parcela){
            if($index > 0){
                $aux = $valor_parcela;    
            }

            if($index > 1 ){
                if($valor_parcela != $aux){
                    $motivo_parcela_com_valor_fixo = true;
                }
            }

            $valor_parcela_total += parserNumber($valor_parcela);
        }

        try{
            if(parserValor($valor_parcela_total) != parserValor(parserNumber($fields['parcela_total']))){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => [],
                'error' => ['O valor dos títulos renegociados '.parserNumber($fields['parcela_total']).' não bate com o valor das parcelas a serem geradas '.parserValor($valor_parcela_total).'.',],
                'response' => []
            ], 422);
        }

        foreach($avalistas as $avalista){
            if($avalista['estado_civil'] === 'casado' || $avalista['estado_civil'] === 'uniao_estavel'){
                if(empty($avalista['nome_venia_conjugal'])){
                    $motivo_fiador_casado_sem_venia_conjugal = true;
                }
            }
        }

        if(empty($avalistas)){
            $motivo_sem_fiador = true;    
        }

        $motivo_juros_baixo_permitido = false;
        if($maior_atraso < 61){
            if($juros_mes > 3.57 || $juros_mes < 1.9){
                $motivo_juros_baixo_permitido = true;
            }
        }else{
            if($juros_mes > 3.57 || $juros_mes < 1){
                $motivo_juros_baixo_permitido = true;
            }
        }

        $motivo_parcelas_maior_permitido = false;
        if($quantidade_parcela > 7){
            $motivo_parcelas_maior_permitido = true;
        }

        if($fields['tipo'] === 'previa'){
            $status_renegociacao = 6;
        }else if($intervalo_total_dias > 180 || $motivo_juros_baixo_permitido || $motivo_parcela_com_valor_fixo || $motivo_fiador_casado_sem_venia_conjugal || $motivo_parcelas_maior_permitido){
            if($intervalo_total_dias >= 180){
                $motivo_periodo_maior_permitido = true;
            }
        }else{
            $status_renegociacao = 2;
        }

        $renegociacaoTituloObj = new RenegociacaoTitulo;
        $renegociacaoTituloObj->cliente_cpf_cnpj = $cpf_cnpj;
        $renegociacaoTituloObj->juros_mes = $juros_mes; 
        $renegociacaoTituloObj->parcela_quantidade = $quantidade_parcela; 
        $renegociacaoTituloObj->data_inicial = $data_atual; 
        $renegociacaoTituloObj->data_final = $data_final;
        $renegociacaoTituloObj->status_renegociacao_titulos_id = $status_renegociacao;
        $renegociacaoTituloObj->valor_total_titulos = parserNumber($fields['valor_total_atualizado']);
        $renegociacaoTituloObj->valor_total_titulos_com_juros = parserNumber($fields['parcela_total']);
        $renegociacaoTituloObj->juro_atualizacao_titulo = parserNumber($fields['juro_atualizacao']);
        $renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo = empty($fields['tarifa_bancaria_atualizacao'])? 0 : parserNumber($fields['tarifa_bancaria_atualizacao']);
        $renegociacaoTituloObj->tarifa_bancaria_renegociacao = empty($fields['tarifa_bancaria_renegociacao'])? 0 : parserNumber($fields['tarifa_bancaria_renegociacao']);
        $renegociacaoTituloObj->data_inicial_renegociacao = $data_inicial_renegociacao;
        $renegociacaoTituloObj->encargos = empty($fields['encargos'])? 0 : parserNumber($fields['encargos']);
        $renegociacaoTituloObj->maior_atraso = $maior_atraso;
        $renegociacaoTituloObj->created_by = Auth::id();
        if($fields['tipo'] === 'previa'){
            $renegociacaoTituloObj->email_previa = $email_previa;
        }
        if($fields['tipo'] == 'sem_confissao'){
            $renegociacaoTituloObj->confissao_divida = false;
        }
        $renegociacaoTituloObj->save();      

        $query_titulos = TitulosEmAbertoNasajon::select('titulo_id', 'numero', 'saldotitulo', 'juros', 'valor', 'codigo', 'cnpj', 'parcela', 'titulo_emissao', 'vencimento','nota_id', 'nota_numero');
        $query_titulos->whereIn('titulo_id', $titulos);
        $query_titulos->distinct();
        $result_titulos = $query_titulos->get();

        $titulo_empresa_estabelecimento = [];
        $valor_total_titulos = 0;
        foreach($result_titulos as $titulo){
            $renegociacaoTituloTituloObj = new RenegociacaoTituloTitulo;
            $renegociacaoTituloTituloObj->renegociacao_titulos_id = $renegociacaoTituloObj->id; 
            $renegociacaoTituloTituloObj->titulo_uuid_nasajon = $titulo->titulo_id; 
            $renegociacaoTituloTituloObj->titulo_numero = $titulo->numero; 
            $renegociacaoTituloTituloObj->titulo_valor = $titulo->saldotitulo - $titulo->juros;
            $renegociacaoTituloTituloObj->estabelecimento_codigo = $titulo->codigo;
            $renegociacaoTituloTituloObj->cpf_cnpj = $titulo->cnpj;
            $renegociacaoTituloTituloObj->parcela = $titulo->parcela;
            $renegociacaoTituloTituloObj->data_emissao = $titulo->titulo_emissao;
            $renegociacaoTituloTituloObj->data_vencimento = $titulo->vencimento;
            $renegociacaoTituloTituloObj->valor_original = $titulo->valor;
            $renegociacaoTituloTituloObj->valor_juros = $titulo->juros;
            $renegociacaoTituloTituloObj->valor_saldo = $titulo->saldotitulo;
            $renegociacaoTituloTituloObj->nota_id = $titulo->nota_id;
            $renegociacaoTituloTituloObj->nota_numero = $titulo->nota_numero;
            $renegociacaoTituloTituloObj->dias_vencido = $fields['numero_dias'][$titulo->numero];
            $renegociacaoTituloTituloObj->encargo_dia = $fields['encargo_dia'][$titulo->numero];
            $renegociacaoTituloTituloObj->encargo_periodo = $fields['encargo_periodo'][$titulo->numero];
            $renegociacaoTituloTituloObj->encargo_total = $fields['encargo_total'][$titulo->numero];
            $renegociacaoTituloTituloObj->valor_total = $fields['valor_total'][$titulo->numero];
            $renegociacaoTituloTituloObj->created_by = Auth::id();
            $renegociacaoTituloTituloObj->save();

            $saldo_total += $titulo->saldotitulo;
            $valor_total_titulos += $titulo->valor;
            
            if(empty($titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo])){
                $titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo] = [
                    'cnpj' => $titulo->cnpj,
                    'estabelecimento' => $titulo->codigo,
                    'valor' => $titulo->valor,
                    'porcetagem' => 0,
                ];
            }else{
                $titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo]['valor'] += $titulo->valor;
            }
        }

        $quantidade_separado = count($titulo_empresa_estabelecimento);
        $somatoria_da_porcetagem = 0;
        $contador = 0;
        foreach($titulo_empresa_estabelecimento as $index => $empresa_estabelecimento){
            $contador++;
            $somatoria_da_porcetagem += round($empresa_estabelecimento['valor']/$valor_total_titulos*100, 2);
            $porcetagem_faltante = 0;
            if($contador == $quantidade_separado && $somatoria_da_porcetagem < 100){
                $porcetagem_faltante = 100 - $somatoria_da_porcetagem;
            }
            $titulo_empresa_estabelecimento[$index]['porcetagem'] = round($empresa_estabelecimento['valor']/$valor_total_titulos*100+$porcetagem_faltante, 2);
            
        }

        $juros_valor_dias = $saldo_total * ($juros_mes / 30 / 100) * $intervalo_total_dias;

        foreach($fields['data_parcela'] as $index => $data_parcela){
            $data_parcela_carbon = Carbon::createFromFormat('d/m/Y', $data_parcela)->setTime(0,0,0);;

            $renegociacaoTituloParcelaObj = new RenegociacaoTituloParcela;
            $renegociacaoTituloParcelaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id;
            $renegociacaoTituloParcelaObj->data_parcela = $data_parcela_carbon; 
            $renegociacaoTituloParcelaObj->numero = $index + 1; 
            $renegociacaoTituloParcelaObj->valor = parserNumber($fields['valor_parcela'][$index]); 
            $renegociacaoTituloParcelaObj->parcela_sem_encargos = $fields['parcela_sem_encargos'][$index];
            $renegociacaoTituloParcelaObj->dias_vencimento = $fields['numero_dias'][$index];
            $renegociacaoTituloParcelaObj->encargo_dia = $fields['juros_dias'][$index];
            $renegociacaoTituloParcelaObj->encargo_periodo = $fields['encargos_juros'][$index];
            $renegociacaoTituloParcelaObj->encargo_ragazzi = $fields['encargos_sem_juros'][$index];
            $renegociacaoTituloParcelaObj->encargo_total = $fields['encargo_total'][$index];
            $renegociacaoTituloParcelaObj->encargo_dia_ragazzi = $fields['encargo_dia_ragazzi'][$index];
            $renegociacaoTituloParcelaObj->encargo_periodo_ragazzi = $fields['encargo_periodo_ragazzi'][$index];
            $renegociacaoTituloParcelaObj->parcela_sem_honorario = $fields['parcela_sem_honorario'][$index];
            $renegociacaoTituloParcelaObj->created_by = Auth::id();
            $renegociacaoTituloParcelaObj->save();
        }
        
        if($fields['tipo'] !== 'previa'){
            $aprovacaoRenegociacaoObj = new AprovacaoRenegociacao;
            $aprovacaoRenegociacaoObj->renegociacao_titulos_id = $renegociacaoTituloObj->id;
            $aprovacaoRenegociacaoObj->aprovacao_diretoria = false;
            if($status_renegociacao == 2){
                $aprovacaoRenegociacaoObj->aprovacao_automatica = true; 
                $aprovacaoRenegociacaoObj->data_aprovacao_automatica = Carbon::Now();
            }else{
                $aprovacaoRenegociacaoObj->aprovacao_automatica = false; 
            }
            $aprovacaoRenegociacaoObj->aprovacao_cliente = false;
            $aprovacaoRenegociacaoObj->created_by = Auth::id();
            $aprovacaoRenegociacaoObj->motivo_desconto_no_valor_do_titulo = $motivo_desconto_no_valor_do_titulo;
            $aprovacaoRenegociacaoObj->motivo_juros_baixo_permitido = $motivo_juros_baixo_permitido;
            $aprovacaoRenegociacaoObj->motivo_periodo_maior_permitido = $motivo_periodo_maior_permitido;
            $aprovacaoRenegociacaoObj->motivo_parcela_com_valor_fixo = $motivo_parcela_com_valor_fixo;
            $aprovacaoRenegociacaoObj->motivo_sem_fiador = $motivo_sem_fiador;
            $aprovacaoRenegociacaoObj->motivo_fiador_casado_sem_venia_conjugal = $motivo_fiador_casado_sem_venia_conjugal;
            $aprovacaoRenegociacaoObj->motivo_parcelas_maior_permitido = $motivo_parcelas_maior_permitido;
            $aprovacaoRenegociacaoObj->save();
        }

        $ClienteNasajon = ClienteNasajon::where('cpf_cnpj', '176.331.198-88')->first();

        $socios[$ClienteNasajon->cpf_cnpj]['nome'] = $ClienteNasajon->nome;
        $socios[$ClienteNasajon->cpf_cnpj]['cpf'] = $ClienteNasajon->cpf_cnpj;
        $socios[$ClienteNasajon->cpf_cnpj]['email'] = $ClienteNasajon->email;
        $socios[$ClienteNasajon->cpf_cnpj]['endereco'] = $ClienteNasajon->tipologradouro.'. '.$ClienteNasajon->logradouro.', '.$ClienteNasajon->numero.', '.$ClienteNasajon->complemento.' - '.$ClienteNasajon->cidade.' - '.$ClienteNasajon->uf.', CEP '.$ClienteNasajon->cep;

        foreach($socios as $socio){
            $renegociacaoTituloAvalistaObj = new RenegociacaoTituloAvalista;
            $renegociacaoTituloAvalistaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id; 
            $renegociacaoTituloAvalistaObj->nome = $socio['nome'];
            $renegociacaoTituloAvalistaObj->cpf = $socio['cpf'];
            $renegociacaoTituloAvalistaObj->email = $socio['email'];
            $renegociacaoTituloAvalistaObj->endereco = $socio['endereco'];
            $renegociacaoTituloAvalistaObj->tipo = 'empresa';
            $renegociacaoTituloAvalistaObj->tipo_signatario = 'representante_legal';
            $renegociacaoTituloAvalistaObj->estabelecimento_codigo = '05';
            $renegociacaoTituloAvalistaObj->cpf_cnpj = '';
            $renegociacaoTituloAvalistaObj->created_by = Auth::id();
            $renegociacaoTituloAvalistaObj->save();

            $dados_signatario[$renegociacaoTituloAvalistaObj->id] = [
                'nome' => $renegociacaoTituloAvalistaObj->nome,
                'email' => $renegociacaoTituloAvalistaObj->email,
                'cpf' => $renegociacaoTituloAvalistaObj->cpf,
                'tipo' => 'representante_legal'
            ];
        }

        if($dados_signatario[$renegociacaoTituloAvalistaObj->id]['cpf'] == $ClienteNasajon->cpf_cnpj){
            unset($dados_signatario[$renegociacaoTituloAvalistaObj->id]);
        }

        foreach($avalistas as $avalista){
            $renegociacaoTituloAvalistaObj = new RenegociacaoTituloAvalista;
            $renegociacaoTituloAvalistaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id; 
            $renegociacaoTituloAvalistaObj->nome = $avalista['nome'];
            $renegociacaoTituloAvalistaObj->cpf = $avalista['cpf'];
            $renegociacaoTituloAvalistaObj->email = $avalista['email'];
            $renegociacaoTituloAvalistaObj->endereco = $avalista['endereco'];
            $renegociacaoTituloAvalistaObj->estado_civil = $avalista['estado_civil'];
            $renegociacaoTituloAvalistaObj->nome_venia_conjugal = $avalista['nome_venia_conjugal'];
            $renegociacaoTituloAvalistaObj->cpf_venia_conjugal = $avalista['cpf_venia_conjugal'];
            $renegociacaoTituloAvalistaObj->email_venia_conjugal = $avalista['email_venia_conjugal'];
            $renegociacaoTituloAvalistaObj->tipo = 'pessoa';
            $renegociacaoTituloAvalistaObj->tipo_signatario = 'fiador';
            $renegociacaoTituloAvalistaObj->estabelecimento_codigo = '05';
            $renegociacaoTituloAvalistaObj->cpf_cnpj = '';
            $renegociacaoTituloAvalistaObj->created_by = Auth::id();
            $renegociacaoTituloAvalistaObj->save();

        
            if($avalista['estado_civil'] == 'casado' || $avalista['estado_civil'] == 'uniao_estavel'){
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['nome'] = $renegociacaoTituloAvalistaObj->nome;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['email'] = $renegociacaoTituloAvalistaObj->email;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['cpf'] = $renegociacaoTituloAvalistaObj->cpf;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['tipo'] = 'fiador';
                $dados_signatario[$avalista['cpf_venia_conjugal']]['nome'] = $renegociacaoTituloAvalistaObj->nome_venia_conjugal;
                $dados_signatario[$avalista['cpf_venia_conjugal']]['cpf'] = $renegociacaoTituloAvalistaObj->cpf_venia_conjugal;
                $dados_signatario[$avalista['cpf_venia_conjugal']]['email'] = $renegociacaoTituloAvalistaObj->email_venia_conjugal;
                $dados_signatario[$avalista['cpf_venia_conjugal']]['tipo'] = 'venia_conjugal';
            }else{
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['nome'] = $renegociacaoTituloAvalistaObj->nome;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['email'] = $renegociacaoTituloAvalistaObj->email;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['cpf'] = $renegociacaoTituloAvalistaObj->cpf;
                $dados_signatario[$renegociacaoTituloAvalistaObj->id]['tipo'] = 'fiador';
            }
        }
        
        if($status_renegociacao == 2 && $fields['tipo'] == 'com_confissao'){

            $path_pdf = $this->gerarArquivoPdf($renegociacaoTituloObj->id);
            $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
            $retorno = $AssinaturaEletronicaClicksign->solicitarAssinaturaEmail($dados_signatario, $path_pdf);

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                $request_controller = new Request([
                    'id' =>  $renegociacaoTituloObj->id
                ]);
                $this->deletarRenegociacao($request_controller);
                
                return $retorno;
            }
            $renegociacaoTituloObj->clicksign_documentos_id = decrypt($retorno->getData()->response->id);
            $renegociacaoTituloObj->save();
        }elseif($status_renegociacao == 2 && $fields['tipo'] == 'sem_confissao'){
            $this->geracaoRenegociacaoTitulo($renegociacaoTituloObj->id, '');
        }  
        
        if($fields['tipo'] === 'previa'){
            $aprovacaoRenegociacaoTituloControllerObj = new AprovacaoRenegociacaoTituloController;
            $emails = explode(";", trim($email_previa));
            foreach($emails as $email){
                if(!empty($email)){
                    $aprovacaoRenegociacaoTituloControllerObj->emailAprovacaoPreviaCliente($renegociacaoTituloObj->id, $email);
                }
            }
        }else{
            $aprovacaoRenegociacaoTituloControllerObj = new AprovacaoRenegociacaoTituloController;
            $aprovacaoRenegociacaoTituloControllerObj->emailAprovacaoDiretoria();
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }

    public function modalRenegociacaoTitulo(Request $request){
        $fields = $request->only('id', 'tipo');
        $id = $fields['id'];
        $tipo = $fields['tipo'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::with(['titulos', 'parcelas', 'parcelas.titulosEmAbertoNasajon'])->find($id);

        $motivo = '';

        if($tipo === 'avaliacao'){
            $motivo = $this->getMotivo($renegociacaoTituloObj->aprovacaoRenegociacao);
        }

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $numero_titulos = [];
        $total_titulo_antigo = 0;
        $total_titulo_antigo_atualizado = 0;
        foreach($renegociacaoTituloObj->titulos as $titulo){
            if(is_null($titulo->encargo_total)){
                $data_inicial = Carbon::parse($titulo->data_vencimento);
                $data_final = Carbon::parse($renegociacaoTituloObj->data_inicial_renegociacao);
                if($data_inicial->gte($data_final)){
                    $intervalo_total_dias = 0;
                }else{
                    $intervalo_total_dias = $data_inicial->diffInDays($data_final);
                }
                
                $juros_atualizado = $renegociacaoTituloObj->juro_atualizacao_titulo / 30 / 100;
                $juros_atualizado_valor = $titulo->titulo_valor * $juros_atualizado;

                $total_valor_atualizado = $titulo->titulo_valor + ($juros_atualizado_valor * $intervalo_total_dias) + $renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo;

                $titulos[] = [
                    'estabelecimento' =>$estabelecimentos[intval($titulo->estabelecimento_codigo)],
                    'titulo' => $titulo->titulo_numero,
                    'parcela' => empty($titulo->parcela)? '' : $titulo->parcela,
                    'data_emissao' => empty($titulo->data_emissao)? '' : parserData($titulo->data_emissao),
                    'data_vencimento' => empty($titulo->data_vencimento)? '' : parserData($titulo->data_vencimento),
                    'novo_vencimento' => empty($renegociacaoTituloObj->data_inicial_renegociacao)? '' : parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                    'valor_original' => empty($titulo->valor_original)? '' : parserValor($titulo->valor_original),
                    'intervalo_total_dias' => empty($intervalo_total_dias)? '' : $intervalo_total_dias,
                    'juros' => empty($titulo->valor_juros)? '': parserValor($titulo->valor_juros),
                    'valor_saldo' => empty($titulo->valor_saldo)? '' : parserValor($titulo->valor_saldo),
                    'nota' => empty($titulo->nota_numero)? '' : $titulo->nota_numero,
                    'taxa_encargo' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                    'valor_encargo_diario' => $intervalo_total_dias > 0 ? parserValor($juros_atualizado_valor) : '',
                    'valor_encargo' => parserValor($intervalo_total_dias * $juros_atualizado_valor),
                    'tarifa_bancaria' => parserValor($renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo),
                    'valor_atualizado' => parserValor($total_valor_atualizado),
                ];

                $total_titulo_antigo += $titulo->valor_original;
                $total_titulo_antigo_atualizado += $total_valor_atualizado;

                $datas = [];
                if(empty($dados['titulo_novos'])){
                    foreach($renegociacaoTituloObj->parcelas as $parcela){
                        $datas[] = [
                            'numero' => $parcela->numero,
                            'data' => parserData($parcela->data_parcela),
                            'valor' => parserValor($parcela->valor),
                        ];

                        $numero_titulos[] = $parcela->titulo_id_nasajon;
                    }
                }

                $titulosEmAbertoNasajonObj = TitulosEmAbertoNasajon::select()->whereIn('titulo_id', $numero_titulos)->distinct()->get();
                $titulo_novos = [];
                $quantidade_parcelas = count($numero_titulos);
                $valor_parcela = $total_titulo_antigo_atualizado / $quantidade_parcelas;
                $encargos_parcelado = ($renegociacaoTituloObj->encargos + $renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo)/$quantidade_parcelas;

                foreach($titulosEmAbertoNasajonObj as $titulo_novo){
                    $data_final_parcela = Carbon::parse($titulo_novo->vencimento);
                    $data_inicial_parcela = Carbon::parse($titulo_novo->titulo_emissao);
                    if($data_inicial_parcela->gte($data_final_parcela)){
                        $intervalo_total_dias_parcela = 0;
                    }else{
                        $intervalo_total_dias_parcela = $data_inicial_parcela->diffInDays($data_final_parcela);
                    }

                    $juros_atualizado_parcela = $valor_parcela * ($renegociacaoTituloObj->juro_atualizacao_titulo / 30 / 100);
                    $juros_atualizado_valor_parcela = $juros_atualizado_parcela * $intervalo_total_dias_parcela;
                    $total_valor_atualizado_parcela = $encargos_parcelado + $juros_atualizado_valor_parcela;
                    
                    $titulo_novos[] = [
                        'estabelecimento' => $estabelecimentos[intval($titulo_novo->codigo)],
                        'titulo' => $titulo_novo->numero,
                        'parcela' => $titulo_novo->parcela,
                        'data_renegociacao' => parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                        'data_vencimento' => parserData($titulo_novo->vencimento),
                        'encargo_ragazzi' => parserValor($encargos_parcelado),
                        'parcela_sem_encargos' => parserValor($valor_parcela),
                        'intervalo_dias' => $intervalo_total_dias_parcela,
                        'encargo_dia' => parserValor($juros_atualizado_parcela),
                        'encargo_periodo' => parserValor($juros_atualizado_valor_parcela),
                        'encargo_total' =>  parserValor($total_valor_atualizado_parcela),
                        'valor_parcela' => parserValor($titulo_novo->valor)
                    ];
                }
            }else{
                $titulos[] = [
                    'estabelecimento' =>$estabelecimentos[intval($titulo->estabelecimento_codigo)],
                    'titulo' => $titulo->titulo_numero,
                    'parcela' => empty($titulo->parcela)? '' : $titulo->parcela,
                    'data_emissao' => empty($titulo->data_emissao)? '' : parserData($titulo->data_emissao),
                    'data_vencimento' => empty($titulo->data_vencimento)? '' : parserData($titulo->data_vencimento),
                    'novo_vencimento' => empty($renegociacaoTituloObj->data_inicial_renegociacao)? '' : parserData($renegociacaoTituloObj->data_inicial_renegociacao),
                    'valor_original' => empty($titulo->valor_original)? '' : parserValor($titulo->valor_original),
                    'intervalo_total_dias' => $titulo->dias_vencido,
                    'nota' => empty($titulo->nota_numero)? '' : $titulo->nota_numero,
                    'taxa_encargo' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                    'valor_encargo_diario' => parserValor($titulo->encargo_dia),
                    'valor_encargo' => parserValor($titulo->encargo_periodo),
                    'tarifa_bancaria' => parserValor($renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo),
                    'valor_atualizado' => parserValor($titulo->valor_total),
                ];

                $total_titulo_antigo += $titulo->valor_original;
                $total_titulo_antigo_atualizado += $titulo->valor_total;

                $titulo_novos = [];

                foreach($renegociacaoTituloObj->parcelas as $titulo_novo){
                    if(is_null($titulo_novo->titulo_id_nasajon)){
                        $titulo_novos[] = [
                            'estabelecimento' => $estabelecimentos[5],
                            'parcela' => $titulo_novo->numero,
                            'data_renegociacao' => parserData($renegociacaoTituloObj->data_inicial),
                            'data_vencimento' => parserData($titulo_novo->data_parcela),
                            'encargo_ragazzi' => parserValor($titulo_novo->encargo_ragazzi),
                            'parcela_sem_encargos' => parserValor($titulo_novo->parcela_sem_encargos),
                            'intervalo_dias' => $titulo_novo->dias_vencimento,
                            'encargo_dia' => parserValor($titulo_novo->encargo_dia),
                            'encargo_periodo' => parserValor($titulo_novo->encargo_periodo),
                            'encargo_total' =>  parserValor($titulo_novo->encargo_total),
                            'valor_parcela' => parserValor($titulo_novo->valor),
                            'parcela_sem_honorario' => parserValor($titulo_novo->parcela_sem_honorario),
                            'encargo_dia_ragazzi' => parserValor($titulo_novo->encargo_dia_ragazzi),
                            'encargo_periodo_ragazzi' => parserValor($titulo_novo->encargo_periodo_ragazzi)
                        ];
                    }else{
                        $titulo_novos[] = [
                            'estabelecimento' => $estabelecimentos[5],
                            'titulo' => (!empty($titulo_novo->titulosEmAbertoNasajon->numero)) ? $titulo_novo->titulosEmAbertoNasajon->numero : '',
                            'parcela' => isset($titulo_novo->titulosEmAbertoNasajon) ? $titulo_novo->titulosEmAbertoNasajon->parcela : $titulo_novo->numero,
                            'data_renegociacao' => parserData($renegociacaoTituloObj->data_inicial),
                            'data_vencimento' => isset($titulo_novo->titulosEmAbertoNasajon) ?  parserData($titulo_novo->titulosEmAbertoNasajon->vencimento) : parserData($titulo_novo->data_parcela),
                            'parcela_sem_encargos' => parserValor($titulo_novo->parcela_sem_encargos),
                            'intervalo_dias' => $titulo_novo->dias_vencimento,
                            'encargo_dia' => parserValor($titulo_novo->encargo_dia),
                            'encargo_ragazzi' => parserValor($titulo_novo->encargo_ragazzi),
                            'encargo_periodo' => parserValor($titulo_novo->encargo_periodo),
                            'encargo_total' =>  parserValor($titulo_novo->encargo_total),
                            'valor_parcela' => isset($titulo_novo->titulosEmAbertoNasajon) ? parserValor($titulo_novo->titulosEmAbertoNasajon->valor) : parserValor($titulo_novo->parcela_sem_honorario),
                            'parcela_sem_honorario' => parserValor($titulo_novo->parcela_sem_honorario),
                            'encargo_dia_ragazzi' => parserValor($titulo_novo->encargo_dia_ragazzi),
                            'encargo_periodo_ragazzi' => parserValor($titulo_novo->encargo_periodo_ragazzi)
                        ];
                    }
                }
            }
        }

        $avalistas = [];
        foreach($renegociacaoTituloObj->avalistas as $avalista){
            if(!empty($avalista->email_venia_conjugal) && $avalista->aceito_venia_conjugal == true){
                $cpf_venia_conjugal = isset($avalista->cpf_venia_conjugal) ? $avalista->cpf_venia_conjugal.' (Assinado)' : '';
            }else{
                $cpf_venia_conjugal = isset($avalista->cpf_venia_conjugal) ? $avalista->cpf_venia_conjugal.' (Não Assinado)' : '';
            }

            if($avalista->aceito === true && empty($avalista->tipo_signatario == 'representante_legal')){
                $assinatura = 'Assinado';
            }elseif($avalista->aceito === true && empty($avalista->email_venia_conjugal)){
                $assinatura = 'Assinado';
            }else if($avalista->aceito === false){
                $assinatura = 'Recusado';
            }else{
                if($renegociacaoTituloObj->confissao_divida == false){
                    $assinatura = 'Sem Confissão de Dívida';
                }else{
                    $assinatura = 'Não Assinado';
                }
            }

           if($avalista->tipo_signatario == 'representante_legal' && $avalista->nome == 'Alessandro Nezi Ragazzi'){
                $signatario = 'Representante Legal MN';
           }
           else if($avalista->tipo_signatario == 'representante_legal'){
                $signatario = 'Representante Legal Cliente';
           }else{
            $signatario = 'Fiador';
           }

            $avalistas[] = [
                'id' => encrypt($avalista->id),
                'nome' => $avalista->nome,
                'cpf' => $avalista->cpf,
                'signatario' => $signatario,
                'email' => $avalista->email,
                'endereco' => $avalista->endereco,
                'estado_civil' => $avalista->estado_civil,
                'nome_venia_conjugal' => $avalista->nome_venia_conjugal,
                'cpf_venia_conjugal' => $cpf_venia_conjugal,
                'assinatura' => $assinatura,
                'data_assinatura' => !empty($avalista->data_assinatura) ? parserData($avalista->data_assinatura) : '',
                'documento_assinado' => empty($avalista->documento_assinado)? '' : Storage::url(substr_replace($this->storage.$avalista->renegociacao_titulos_id.'/'.$avalista->documento_assinado, '_assinado.pdf', -4))
            ];
        }

        $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
        $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
        $intervalo_total_dias = $data_inicial->diffInDays($data_final);

        $reenvio = false;
        if($renegociacaoTituloObj->status_renegociacao_titulos_id == 2){
            $reenvio = true;
        }

        if(!empty($renegociacaoTituloObj->titulos->first()->encargo_total)){
            $dados = [
                'id' => encrypt($renegociacaoTituloObj->id),
                'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
                'data' => parserData($renegociacaoTituloObj->data_inicial),
                'titulos' => $titulos,
                'valor_total' => parserValor($renegociacaoTituloObj->valor_total_titulos),
                'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
                'valor_total_juros' => parserValor($renegociacaoTituloObj->valor_total_titulos_com_juros),
                'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
                'periodo' => $intervalo_total_dias.' dias',
                'avalistas' => $avalistas,
                'titulo_novos' => $titulo_novos,
                'tarifa_bancaria_renegociacao' => parserValor($renegociacaoTituloObj->tarifa_bancaria_renegociacao),
                'encargos' => parserValor($renegociacaoTituloObj->encargos),
                'reenvio' => $reenvio,
                'total_titulo_antigo' => parserValor($total_titulo_antigo),
                'total_titulo_antigo_atualizado' => parserValor($total_titulo_antigo_atualizado),
                'juros_atualizacao' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                'link_documentacao' => Storage::url('public/contratos_renegociacao_titulos/pdf/renegociacao_titulo.pdf')
            ];
        }else{
            $dados = [
                'id' => encrypt($renegociacaoTituloObj->id),
                'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
                'data' => parserData($renegociacaoTituloObj->data_inicial),
                'titulos' => $titulos,
                'valor_total' => parserValor($renegociacaoTituloObj->valor_total_titulos),
                'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
                'valor_total_juros' => parserValor($renegociacaoTituloObj->valor_total_titulos_com_juros),
                'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
                'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
                'periodo' => $intervalo_total_dias.' dias',
                'avalistas' => $avalistas,
                'titulo_novos' => !empty($titulo_novos) ? $titulo_novos : [],
                'motivo' => $motivo,
                'datas' => !empty($datas) ? $datas : [],
                'tarifa_bancaria_renegociacao' => parserValor($renegociacaoTituloObj->tarifa_bancaria_renegociacao),
                'encargos' => parserValor($renegociacaoTituloObj->encargos),
                'reenvio' => $reenvio,
                'total_titulo_antigo' => parserValor($total_titulo_antigo),
                'total_titulo_antigo_atualizado' => parserValor($total_titulo_antigo_atualizado),
                'juros_atualizacao' => parserValor($renegociacaoTituloObj->juro_atualizacao_titulo).'%',
                'link_documentacao' => Storage::url('public/contratos_renegociacao_titulos/pdf/renegociacao_titulo.pdf')
            ];
        }
        
        return view('programs.negociacao_titulo.modal.dialog')->with(['dados' => $dados]);
    }

    public function geracaoRenegociacaoTitulo($id, $id_avalista){
        $codigo_vendedor_ragazzi = '177342f3-e5a7-46e7-af9d-66c2f151a879';

        $estabelecimentos = returnEmpresasNasajonView();
 
        $conta_bancaria = ContasNasajon::select()->where('codigo', '130055369')->first();

        $usuario_cadastro_uuid = User::where('id', 1)->first()->codigo_nasajon;

        $forma_pagamento_nasajon = PedidoFormaPagamentoNasajon::select()->where('formapagamento_codigo', '000')->first()->formapagamento;

        $renegociacaoTituloObj = RenegociacaoTitulo::with(['titulos', 'parcelas'])->find($id);
        if(!empty($id_avalista)){
            $renegociacaoTituloAvalistaObj = RenegociacaoTituloAvalista::find($id_avalista);
        }        
        $estabelecimento = NasajonEstabelecimento::select()->where('codigo', '05')->first()->estabelecimento;

        $data_atual = Carbon::now();

        $layout_uuid = "NULL";

        $valor_total_renegociacao = $renegociacaoTituloObj->valor_total_titulos_com_juros;
        $total_titulo_antigo = 0;
        foreach($renegociacaoTituloObj->titulos as $titulo){
            $total_titulo_antigo += $titulo->valor_original;
        }
        $acrescimo = $valor_total_renegociacao - $total_titulo_antigo;

        $cliente_uuid = $renegociacaoTituloObj->cliente->id;

        $renegociacao_uuid = $this->iniciarRenegociacaoTitulo($renegociacaoTituloObj->titulos[0]->titulo_numero, $renegociacaoTituloObj->data_inicial, $usuario_cadastro_uuid, $estabelecimento, $acrescimo, $cliente_uuid);

        $estabelecimentos = returnEmpresasNasajonView();
        $data_atual = Carbon::now();
        $titulos_antigos = "";
        $titulos_novos = "";
        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulos_antigos = $titulos_antigos."Estabelecimento:".$estabelecimentos[intval($titulo->estabelecimento_codigo)]."<br>Titulo : ".$titulo->titulo_numero."<br>Emissão:".parserData($titulo->detalhesTituloAberto->titulo_emissao)."<br>Vencimento:".parserData($titulo->detalhesTituloAberto->vencimento)."<br><br>";
        }
       
        if(!empty($renegociacao_uuid)){
            foreach($renegociacaoTituloObj->titulos as $titulo){
                $this->tituloRenegociacaoTitulo($renegociacao_uuid, $titulo->titulo_uuid_nasajon);
            }

            foreach($renegociacaoTituloObj->parcelas as $parcela){
                $retorno_titulo_novo = $this->tituloNovoRenegociacaoTitulo($renegociacao_uuid, $conta_bancaria->conta, $forma_pagamento_nasajon, $layout_uuid, $parcela->data_parcela, $parcela->valor);
            }

            $vendedor_ragazzi = VendedorNasajon::where('codigo', '998')->first();
            $VendedorTituloNasajonObj = VendedorTituloNasajon::where('tituloreceber', $renegociacaoTituloObj->titulos[0]->titulo_uuid_nasajon)->whereNotIn('vendedor', [$vendedor_ragazzi->id])->get();

            $vendedores = $renegociacaoTituloObj->titulos[0]->dias_vencido <= 30 ? "".$vendedor_ragazzi->id."" : '';

            try{
                if($VendedorTituloNasajonObj->count() == 0){
                    throw new \Exception;
                }
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Título '.$renegociacaoTituloObj->titulos[0]->titulo_numero.'sem vendedor',
                    'error' => [],
                    'response' => []
                ], 422);
            }
        
            $VendedorTituloNasajonObj->each(function($vendedor) use($vendedores){
                $vendedores .= ",".$vendedor->vendedor;
            });
            
            $this->processarRenegociacaoTitulo($renegociacao_uuid, $vendedores);
        }

        foreach($renegociacaoTituloObj->parcelas as $parcela){
            $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajon::select();
            $titulosEmAbertoNasajonPortalObj->where('numero', 'ilike', $renegociacaoTituloObj->titulos[0]->titulo_numero.'.'.$parcela->numero);
            $titulosEmAbertoNasajonPortalObj = $titulosEmAbertoNasajonPortalObj->first();

            if(!empty($titulosEmAbertoNasajonPortalObj)){
                $parcela->retorno_nasajon = "Api nova Renegociação";
                $parcela->titulo_id_nasajon = $titulosEmAbertoNasajonPortalObj->titulo_id;
                $parcela->save();

                $titulos_novos = $titulos_novos."Estabelecimento:".$estabelecimentos[5]."<br>Titulo : ".$renegociacaoTituloObj->titulos[0]->titulo_numero.'.'.$parcela->numero."<br>Emissão:".$data_atual->format('d/m/Y')."<br>Vencimento:".parserData($parcela->data_parcela)."<br><br>";
            }
        }

        $aprovacaoRenegociacaoObj = AprovacaoRenegociacao::select()->where('renegociacao_titulos_id', $renegociacaoTituloObj->id)->first();
        $aprovacaoRenegociacaoObj->aprovacao_cliente = true;
        $aprovacaoRenegociacaoObj->data_aprovacao_cliente = Carbon::now();
        $aprovacaoRenegociacaoObj->save();

        $renegociacaoTituloObj->status_renegociacao_titulos_id = 3;
        $renegociacaoTituloObj->save();

        try{
            $this->emailNovosTítulos($renegociacaoTituloObj->id, $titulos_antigos, $titulos_novos);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        
    }

    public function filtroSelecionarTitulo(Request $request){
        $fields = $request->only('cliente_codigo', 'titulo', 'data_inicial', 'data_final', 'tipo');

        $codigo = $fields['cliente_codigo'];
        $cliente = ClienteNasajon::select()->where('codigo', $codigo);
        $cliente = $cliente->first();
        $cliente = $cliente->toArray();

        $cpf_cnpj = $cliente['cpf_cnpj'];

        if(strlen(trim($cpf_cnpj)) == 18){
            $cpf_cnpj = substr($cpf_cnpj, 0, 10);
        }

        $clientesNasajonQuery = ClienteNasajon::select('*');
        $clientesNasajonQuery->where('cpf_cnpj', $cliente['cpf_cnpj']);
        
        $clientesNasajon = $clientesNasajonQuery->get();
      
        $empresa = returnEmpresasNasajonView();
        $titulos_faturados = [];
        $data_atual = Carbon::now();
        $data_atual_menos_30 = $data_atual->subDays(30);

        $titulosNasajon = TitulosEmAbertoNasajon::with(['devolucoes' => function($query){
                $query->whereNotIn('devolucao_nota_status_id', [7, 8]);
            },'cenprot', 'cliente', 'renegociacao' => function($query){
                $query->whereHas('detalhesRenegociacao', function($query){
                    $query->whereNotIn('status_renegociacao_titulos_id', [3, 9, 10, 7]);
                });
            },'tituloNovoRenegociado'])
            ->selectRaw("*, regexp_replace(numero, '.[0-9]{5}.[0-9]{2}.', '') as nota")
            ->whereNotIn('codigo', [30])
            ->whereIn('cod_cliente', $clientesNasajon->pluck('codigo'));
            
        if($fields['tipo'] == 'nao_vencidos'){
            $titulosNasajon->where('vencimento', '>=', Carbon::now());
        }
        if($fields['tipo'] == 'vencidos'){
            $titulosNasajon->where('vencimento', '<', $data_atual_menos_30);
        }
        if(!empty($fields['titulo'])){
            $titulosNasajon->where('numero', 'ilike', '%'.$fields['titulo'].'%');
        }

        if(!empty($fields['data_inicial'])){
            $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_inicial'])->setTime(0,0,0);
            $titulosNasajon->where('vencimento', '>=', $data_inicial);
        }
        if(!empty($fields['data_final'])){
            $data_final = Carbon::createFromFormat('d/m/Y', $fields['data_final'])->setTime(0,0,0);
            $titulosNasajon->where('vencimento', '<=', $data_final);
        }

        $titulosNasajon->orderBy('nota_numero')
            ->orderBy('parcela')
            ->get();
        
        $cnpjCliente = $clientesNasajon->pluck('codigo');
       
        $cliente     = false;

        $totalizadores = [
            'valor' => 0,
            'saldo' => 0,
            'juros' => 0,
        ];

        $titulosNasajon->each(function ($item) use (&$titulos_faturados, &$empresa, &$totalizadores){       
            if(empty($item->renegociacao) && empty($item->tituloNovoRenegociado) || $item->tituloNovoRenegociado->renegociacao_liberada == true){
                $nota_numero = '';
            
                if(empty($item->nota_numero)){
                    $nota_numero = $item->nota;
                }else{
                    $nota_numero = $item->nota_numero;
                }
    
                $value['titulo_id'] = $item->titulo_id;
                $value['estabelecimento'] = $item->codigo;
                $value['estabelecimento_nome']  = $empresa[(int) $item->codigo];
                $value['nota_numero']           = $nota_numero;
                $value['parcela']               = $item->parcela;
                $value['data_emissao']          = parserData($item->titulo_emissao);
                $value['data_vencimento_sql']   = $item->vencimento;
                $value['data_vencimento']       = parserData($item->vencimento);
                $value['status']                = '';
                $value['valor_original']        = $item->valor;
                $value['valor']                 = $item->saldotitulo;
                $value['juros_cobrados']        = $item->juros;
                $value['nome_cliente']          = $item->nome_cliente .' - '. $item->cliente->cpf_cnpj;
                $value['numero']                = $item->numero;
                $value['percentual_juros_diarios']         = $item->percentualjurosdiario;
                $value['data_juros']            = parserData($item->datainiciomultaejuros);
                $value['desconto']              = $item->desconto;
                $value['POSICAO_CR']            = $item->nossonumero;
                $value['POSICAO_CR_DESCRICAO']  = $item->nossonumero;
                $value['observacao']  = $item->observacao;
                $value['numero_titulo_renegociado'] = '';
                $value['vencimento_titulo_renegociado'] = '';
    
                if(!empty($item->numero_titulo_renegociado) || !empty($item->vencimento_titulo_renegociado)){
                    $value['numero_titulo_renegociado'] = $item->numero_titulo_renegociado;
                    $value['vencimento_titulo_renegociado'] = !empty($item->vencimento_titulo_renegociado)?parserData($item->vencimento_titulo_renegociado):'';
                }else{
                    if (strpos($item->observacao, '### Titulo ') !== false){
                        preg_match('/\d+\.\d+(\.\d)*/', $item->observacao, $titulo_original);
                        if(isset($titulo_original[0])) {
                            $tituloPagoObj = TitulosPagosNasajon::where('numero', $titulo_original[0])->first();
                            
                            if(!empty($tituloPagoObj)){
                                $value['numero_titulo_renegociado'] = $tituloPagoObj->numero;
                                $value['vencimento_titulo_renegociado'] = !empty($tituloPagoObj->vencimento)?parserData($tituloPagoObj->vencimento):'';
                            }
                        }
                    }
                }
    
                $value['banco'] = ($item->enviado_para_banco == true) ? $item->banco_codigo : 'CARTEIRA';
    
                // Status do título
                if($item->tem_prorrogacao === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-verde' data-toggle='popover' data-html='true' title='Prorrogado' data-content='Vencimento original " . parserData($item->vencimento_original) . "'><span data-toggle='tooltip' data-html='true' title='Prorrogado'>P</span></a>";
                }
                if($item->enviado_para_cartorio === true){
                    $value['status'] .= "<a href='#' class='status-titulo status-vermelho' data-toggle='popover' data-html='true' title='Enviado para cartório' data-content='Enviado para cartório: " . parserData($item->enviado_para_cartorio_data) . "'><span data-toggle='tooltip' data-html='true' title='Enviado para cartório'>C</span></a>";
                }
    
                $key = $item->numero . '' . $item->codigo;
    
                $titulos_faturados[$key] = $value;
    
                $totalizadores["valor"] += $item->valor;
                $totalizadores["saldo"] += $item->saldotitulo;
                $totalizadores["juros"] += $item->juros;
            }
        });

        if($totalizadores['valor'] > 0){
            $totalizadores['valor'] = parserValor($totalizadores['valor']);
        }
        else{
            $totalizadores['valor'] = '';
        }
        
        if($totalizadores['saldo'] > 0){
            $totalizadores['saldo'] = parserValor($totalizadores['saldo']);
        }
        else{
            $totalizadores['saldo'] = '';
        }

        if($totalizadores['juros'] > 0){
            $totalizadores['juros'] = parserValor($totalizadores['juros']);
        }
        else{
            $totalizadores['juros'] = '';
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $this->ajusteArrayParaValores($titulos_faturados),
        ];
        return response()->json($response);
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(substr_count($key, "codigo") === 0){
                            if(substr_count($key, "porcetagem") === 0){
                                $array[$key] = empty($value)? '': parserValor($value);
                            }else{
                                $array[$key] = empty($value)? '': parserValor($value).'%';
                            }                            
                        }
                    }else{
                        $array[$key] = $value;                    
                    }
                }
            }
        }

        return $array;
    }

    public function filtro(Request $request){
        $fields = $request->only('data_inicio_negociacao_titulos', 'data_fim_negociacao_titulos', 'codigo');

        $cliente = ClienteNasajon::select()->where('codigo', $fields['codigo']);
        $cliente = $cliente->first();

        $query = RenegociacaoTitulo::select();
        $query->with(['motivoRecusa', 'motivoRecusaCliente']);
        $query->where('cliente_cpf_cnpj', $cliente->cpf_cnpj);
        $result = $query->get();

        $retorno = [];

        foreach($result as $renegociacao){
            $data_inicial = Carbon::parse($renegociacao->data_inicial);
            $data_final = Carbon::parse($renegociacao->data_final);
            $intervalo_total_dias = $data_inicial->diffInDays($data_final);

            if(empty($renegociacao->motivoRecusa)){
                if($renegociacao->status_renegociacao_titulos_id === 5){
                    $motivo = $renegociacao->motivoRecusaCliente->descricao.' por '.$renegociacao->motivoRecusaCliente->avalista->nome;
                }else if($renegociacao->status_renegociacao_titulos_id === 7){
                    $motivo = $renegociacao->motivoRecusaCliente->descricao;
                }else{
                    $motivo = '';
                }
            }else{
                $motivo = $renegociacao->motivoRecusa->descricao;
            }

            $reenvio = false;
            if($renegociacao->status_renegociacao_titulos_id == 2){
                $reenvio = true;
            }

            $retorno [] = [
                'id' => encrypt($renegociacao->id),
                'cliente' => $renegociacao->cliente->nome.' - '.$renegociacao->cliente_cpf_cnpj, 	
                'data' => parserData($renegociacao->data_inicial),
                'titulos' => $renegociacao->id,
                'valor_titulos' => parserValor($renegociacao->valor_total_titulos), 
                'juros_por_mes' => parserValor($renegociacao->juros_mes)."%",
                'juros_atualizado' => parserValor($renegociacao->juro_atualizacao_titulo).'%',
                'valor_atualizado' => parserValor($renegociacao->valor_total_titulos/($renegociacao->juro_atualizacao_titulo/100+1)),
                'valor_renegociacao' => parserValor($renegociacao->valor_total_titulos_com_juros),
                'parcelas' => $renegociacao->parcela_quantidade,
                'periodo_dias' => $intervalo_total_dias,
                'status' => $renegociacao->statusDetalhes->descricao,
                'motivo' => $motivo,
                'status_id' => $renegociacao->status_renegociacao_titulos_id,
                'reenvio' => $reenvio,
            ];
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => $retorno
        ];

        return response()->json($response);
    }

    private function getTipoTitulos(){
        $tipos = [
            'todos' => 'Todos Títulos',
            'vencidos' => 'Vencidos',
            'nao_vencidos' => 'Não Vencidos',
        ];

        return $tipos;
    }

    public function modalEditar(Request $request){
        $fields = $request->only('cliente_codigo', 'cliente_nome', 'id');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacao_titulo = RenegociacaoTitulo::find($id);

        $saldo_total= 0;
        $titulos_tabelas = [];
        $titulos = [];
        foreach($renegociacao_titulo->titulos as $titulo){
            $saldo_total += $titulo->titulo_valor;

            $titulos_tabelas[] = [
                'titulo' => $titulo->titulo_numero,
                'valor' => parserValor($titulo->titulo_valor),
                'vencimento'=> parserData($titulo->data_vencimento),
            ];

            $titulos[] = $titulo->titulo_uuid_nasajon;
            $separacao[$titulo->cpf_cnpj.$titulo->estabelecimento_codigo] = $titulo->cpf_cnpj.$titulo->estabelecimento_codigo;
        }

        $parcelas = [];
        $total_parcelas = 0;
        foreach($renegociacao_titulo->parcelas as $parcela){
            $parcelas[] = [
                'numero' => $parcela->numero,
                'valor' => parserValor($parcela->valor),
                'data_parcela' => parserData($parcela->data_parcela),
                'dias_vencimento' => $parcela->dias_vencimento,
                'parcela_sem_encargos' => $parcela->parcela_sem_encargos,
                'encargo_dia' => $parcela->encargo_dia,
                'encargo_periodo'=> $parcela->encargo_periodo,
                'encargo_ragazzi' => $parcela->encargo_ragazzi,
                'encargo_total' => $parcela->encargo_total,
                'parcela_sem_honorario' => $parcela->parcela_sem_honorario,
                'encargo_dia_ragazzi' => $parcela->encargo_dia_ragazzi,
                'encargo_periodo_ragazzi' => $parcela->encargo_periodo_ragazzi
            ];

            $total_parcelas += $parcela->valor;
        }

        $total_parcelas = parserValor($total_parcelas);

        $intervalo = 0;

        $data_inicial = Carbon::parse($renegociacao_titulo->data_inicial_renegociacao);
        $intervalo = $data_inicial->diffInDays(Carbon::parse($renegociacao_titulo->data_final));

        $socios = [];
        $avalistas = [];
        foreach($renegociacao_titulo->avalistas as $signatario){
            if($signatario->tipo_signatario == 'representante_legal' && $signatario->cpf != '176.331.198-88'){
                $socios[] = [
                    'id' => encrypt($signatario->id),
                    'nome' => $signatario->nome,
                    'cpf' => $signatario->cpf,
                    'email' => $signatario->email
                ];
            }

            if($signatario->tipo_signatario == 'fiador'){
                $avalistas[] = [
                    'id' => encrypt($signatario->id),
                    'nome' => $signatario->nome,
                    'cpf' => $signatario->cpf,
                    'email' => $signatario->email
                ];
            }
        }

        $dados = [
            'id' => encrypt($id),
            'juros_mes' => parserValor($renegociacao_titulo->juros_mes),
            'quantidade_parcela' => $renegociacao_titulo->parcela_quantidade,
            'intervalo' => ($renegociacao_titulo->parcela_quantidade - 1) > 0 ? intval($intervalo / ($renegociacao_titulo->parcela_quantidade - 1)) : 0,
            'status' => $renegociacao_titulo->status_renegociacao_titulos_id,
            'data_inicial' => $renegociacao_titulo->data_inicial,
            'juro_atualizacao_titulo' => parserValor($renegociacao_titulo->juro_atualizacao_titulo),
            'tarifa_bancaria_atualizacao_titulo' => parserValor($renegociacao_titulo->tarifa_bancaria_atualizacao_titulo),
            'tarifa_bancaria_renegociacao' => parserValor($renegociacao_titulo->tarifa_bancaria_renegociacao),
            'data_inicial_renegociacao' => parserData($renegociacao_titulo->data_inicial_renegociacao),
            'valor_total_titulos' => parserValor($renegociacao_titulo->valor_total_titulos),
            'encargos' => parserValor($renegociacao_titulo->encargos),
        ];

        $data_inicial = Carbon::now()->format('d/m/Y');
        $estado_civil = $this->getEstadoCivil();

        return view('programs.negociacao_titulo.modal.editar')->with([
            'cliente_codigo' => $fields['cliente_codigo'],
            'cliente_nome' => $fields['cliente_nome'],
            'saldo_total' => parserValor($saldo_total),
            'titulos' => encrypt($titulos),
            'titulos_tabelas' => $titulos_tabelas,
            'dados' => $dados,
            'socios' => $socios,
            'avalistas' => $avalistas,
            'parcelas' => $parcelas,
            'total_parcelas' => $total_parcelas,
            'data_inicial' => $data_inicial,
            'estado_civil' => $estado_civil
        ]);
    }

    public function editarRenegociacao(RenegociacaoTituloEditarRequest $request){
        $fields = $request->only('id','email','hash', 'tipo');

        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
    
        $tipo = $fields['tipo'];
        $fields = decrypt($fields['hash']);
        $fields['tipo'] = $tipo;
        $campo = $request->only('email');

        $email_previa = isset($campo['email'])? $campo['email'] : '';
        $titulos = decrypt($fields['titulos']);
        $juros_mes = floatval(parserNumber($fields['juro_mes']));
        $quantidade_parcela = intval(parserNumber($fields['quantidade_parcela'])); 
        $data_inicial = Carbon::createFromFormat('d/m/Y', $fields['data_parcela'][0])->setTime(0,0,0);
        $data_final = Carbon::createFromFormat('d/m/Y', $fields['data_parcela'][intval($fields['quantidade_parcela']) - 1])->setTime(0,0,0);
        $saldo_total = 0;
        $data_atual = Carbon::Now()->setTime(0,0,0);
        $intervalo_total_dias = $data_atual->diffInDays($data_final);
        $status_renegociacao = 1;

        if($fields['tipo'] === 'previa'){
            $status_renegociacao = 6;
        }else if($intervalo_total_dias <= 180 && $juros_mes >= 2){
            $status_renegociacao = 2;
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
        
        $ClienteNasajon = ClienteNasajon::where('cpf_cnpj', '176.331.198-88')->first();

        if($status_renegociacao == 2 && $fields['tipo'] == 'com_confissao'){
            foreach($renegociacaoTituloObj->avalistas as $avalista){
                if($avalista->tipo_signatario == 'representante_legal'){
                    $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                    $dados_signatario[$avalista->id]['email'] = $avalista->email;
                    $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                    $dados_signatario[$avalista->id]['tipo'] = 'representante_legal';

                }else{
                    $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                    $dados_signatario[$avalista->id]['email'] = $avalista->email;
                    $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                    $dados_signatario[$avalista->id]['tipo'] = 'fiador';
                }

                if($avalista->estado_civil == 'casado' || $avalista->estado_civil == 'uniao_estavel'){
                    $dados_signatario[$avalista->cpf_venia_conjugal]['nome'] = $avalista->nome_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['cpf'] = $avalista->cpf_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['email'] = $avalista->email_venia_conjugal;
                    $dados_signatario[$avalista->cpf_venia_conjugal]['tipo'] = 'venia_conjugal';
                }

                if($dados_signatario[$avalista->id]['cpf'] == $ClienteNasajon->cpf_cnpj){
                    unset($dados_signatario[$avalista->id]);
                }
            }
            
            
            $path_pdf = $this->gerarArquivoPdf($renegociacaoTituloObj->id);
            $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
            $retorno = $AssinaturaEletronicaClicksign->solicitarAssinaturaEmail($dados_signatario, $path_pdf);

            try{
                if($retorno->getData()->status === 'error'){
                    throw new \Exception;
                }
            } catch (\Exception $e) {
                return $retorno;
            }
            $renegociacaoTituloObj->clicksign_documentos_id = decrypt($retorno->getData()->response->id);
            $renegociacaoTituloObj->save();
        }

        $renegociacaoTituloObj->juros_mes = $juros_mes; 
        $renegociacaoTituloObj->parcela_quantidade = $quantidade_parcela;  
        $renegociacaoTituloObj->data_final = $data_final;
        $renegociacaoTituloObj->status_renegociacao_titulos_id = $status_renegociacao;
        
        $renegociacaoTituloObj->valor_total_titulos = parserNumber($fields['valor_total']);
        $renegociacaoTituloObj->valor_total_titulos_com_juros = parserNumber($fields['parcela_total']);
        $renegociacaoTituloObj->valor_total_titulos_com_juros = parserNumber($fields['parcela_total']);
        $renegociacaoTituloObj->juro_atualizacao_titulo = parserNumber($fields['juro_atualizacao']);
        $renegociacaoTituloObj->tarifa_bancaria_atualizacao_titulo = empty($fields['tarifa_bancaria_atualizacao'])? 0 : parserNumber($fields['tarifa_bancaria_atualizacao']);
        $renegociacaoTituloObj->tarifa_bancaria_renegociacao = empty($fields['tarifa_bancaria_renegociacao'])? 0 : parserNumber($fields['tarifa_bancaria_renegociacao']);
        $renegociacaoTituloObj->data_inicial_renegociacao = $data_inicial;
        $renegociacaoTituloObj->encargos = empty($fields['encargos'])? 0 : parserNumber($fields['encargos']);
        $renegociacaoTituloObj->updated_by = Auth::id();
        if($fields['tipo'] == 'sem_confissao'){
            $renegociacaoTituloObj->confissao_divida = false;
        }
        $renegociacaoTituloObj->save();

        $query_titulos = TitulosEmAbertoNasajon::select('titulo_id', 'numero', 'saldotitulo', 'valor', 'codigo', 'cnpj');
        $query_titulos->whereIn('titulo_id', $titulos);
        $query_titulos->distinct();
        $result_titulos = $query_titulos->get();

        $titulo_empresa_estabelecimento = [];
        $valor_total_titulos = 0;
        foreach($result_titulos as $titulo){
            $saldo_total += $titulo->saldotitulo;
            $valor_total_titulos += $titulo->valor;
            
            if(empty($titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo])){
                $titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo] = [
                    'cnpj' => $titulo->cnpj,
                    'estabelecimento' => $titulo->codigo,
                    'valor' => $titulo->valor,
                    'porcetagem' => 0,
                ];
            }else{
                $titulo_empresa_estabelecimento[$titulo->cnpj.$titulo->codigo]['valor'] += $titulo->valor;
            }
        }

        if(isset($fields['encargo_periodo'])){
            foreach($renegociacaoTituloObj->titulos as $renegociacao_titulo){
                $renegociacao_titulo->dias_vencido = $fields['numero_dias'][$renegociacao_titulo->titulo_numero];
                $renegociacao_titulo->encargo_dia = $fields['encargo_dia'][$renegociacao_titulo->titulo_numero];
                $renegociacao_titulo->encargo_periodo = $fields['encargo_periodo'][$renegociacao_titulo->titulo_numero];
                $renegociacao_titulo->encargo_total = $fields['encargo_total'][$renegociacao_titulo->titulo_numero];
                $renegociacao_titulo->valor_total = $fields['valor_total'][$renegociacao_titulo->titulo_numero];
                $renegociacao_titulo->updated_by = Auth::id();
                $renegociacao_titulo->save();
            }
        }

        $juros_valor_dias = $saldo_total * ($juros_mes / 30 / 100) * $intervalo_total_dias;
        $parcela_valor = ($saldo_total + $juros_valor_dias) / $quantidade_parcela;
        
        if($fields['tipo'] === 'previa'){
            if(!empty($renegociacaoTituloObj->motivoRecusaCliente)){
                $renegociacaoTituloObj->motivoRecusaCliente->deleted_by = Auth::id();
                $renegociacaoTituloObj->motivoRecusaCliente->save();
                $renegociacaoTituloObj->motivoRecusaCliente->delete();
            }
        }else{
            if(!empty($renegociacaoTituloObj->motivoRecusa)){
                $renegociacaoTituloObj->motivoRecusa->deleted_by = Auth::id();
                $renegociacaoTituloObj->motivoRecusa->save();
                $renegociacaoTituloObj->motivoRecusa->delete();
            }
        }

        foreach($renegociacaoTituloObj->parcelas as $parcela){
            $parcela->deleted_by = Auth::id();
            $parcela->save();
            $parcela->delete();
        }

        foreach($renegociacaoTituloObj->parcelasPorCnpjEstabelecimento as $parcela){
            $parcela->deleted_by = Auth::id();
            $parcela->save();
            $parcela->delete();
        }

        foreach($fields['data_parcela'] as $index => $data_parcela){
            $data_parcela_carbon = Carbon::createFromFormat('d/m/Y', $data_parcela)->setTime(0,0,0);;

            $renegociacaoTituloParcelaObj = new RenegociacaoTituloParcela;
            $renegociacaoTituloParcelaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id;
            $renegociacaoTituloParcelaObj->data_parcela = $data_parcela_carbon; 
            $renegociacaoTituloParcelaObj->numero = $index + 1; 
            $renegociacaoTituloParcelaObj->valor = parserNumber($fields['valor_parcela'][$index]);
            $renegociacaoTituloParcelaObj->parcela_sem_encargos = $fields['parcela_sem_encargos'][$index];
            $renegociacaoTituloParcelaObj->dias_vencimento = $fields['numero_dias'][$index];
            $renegociacaoTituloParcelaObj->encargo_dia = $fields['juros_dias'][$index];
            $renegociacaoTituloParcelaObj->encargo_periodo = $fields['encargos_juros'][$index];
            $renegociacaoTituloParcelaObj->encargo_ragazzi = $fields['encargos_sem_juros'][$index];
            $renegociacaoTituloParcelaObj->encargo_total = $fields['encargo_total'][$index];
            $renegociacaoTituloParcelaObj->encargo_dia_ragazzi = $fields['encargo_dia_ragazzi'][$index];
            $renegociacaoTituloParcelaObj->encargo_periodo_ragazzi = $fields['encargo_periodo_ragazzi'][$index];
            $renegociacaoTituloParcelaObj->parcela_sem_honorario = $fields['parcela_sem_honorario'][$index]; 
            $renegociacaoTituloParcelaObj->created_by = Auth::id();
            $renegociacaoTituloParcelaObj->save();
        }
        
        if($fields['tipo'] !== 'previa'){
            $aprovacaoRenegociacaoObj = new AprovacaoRenegociacao;
            $aprovacaoRenegociacaoObj->renegociacao_titulos_id = $renegociacaoTituloObj->id;
            $aprovacaoRenegociacaoObj->aprovacao_diretoria = false;
            if($status_renegociacao == 2){
                $aprovacaoRenegociacaoObj->aprovacao_automatica = true; 
                $aprovacaoRenegociacaoObj->data_aprovacao_automatica = Carbon::Now();
            }else{
                $aprovacaoRenegociacaoObj->aprovacao_automatica = false; 
            }
            $aprovacaoRenegociacaoObj->aprovacao_cliente = false;
            $aprovacaoRenegociacaoObj->created_by = Auth::id();
            $aprovacaoRenegociacaoObj->save();
        }

        if($status_renegociacao == 2 && $fields['tipo'] == 'sem_confissao'){
            $this->geracaoRenegociacaoTitulo($renegociacaoTituloObj->id, '');
        }            

        else if($fields['tipo'] === 'previa'){
                $aprovacaoRenegociacaoTituloControllerObj = new AprovacaoRenegociacaoTituloController;
                $emails = explode(";", trim($email_previa));
                foreach($emails as $email){
                    if(!empty($email)){
                        $aprovacaoRenegociacaoTituloControllerObj->emailAprovacaoPreviaCliente($renegociacaoTituloObj->id, $email);
                    }
                }
        }else{
            $aprovacaoRenegociacaoTituloControllerObj = new AprovacaoRenegociacaoTituloController;
            $aprovacaoRenegociacaoTituloControllerObj->emailAprovacaoDiretoria();
        }

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response);
    }

    public function modalDeletar(Request $request){
        $fields = $request->only(['id']);
        try{
            $id = decrypt($fields['id']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $total = 0;
        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulos[] = [
                'titulo' => empty($titulo->detalhesTituloAberto)? $titulo->detalhesTitulosPagos->numero : $titulo->detalhesTituloAberto->numero,
                'valor_original' => empty($titulo->detalhesTituloAberto)? parserValor($titulo->detalhesTitulosPagos->valor) : parserValor($titulo->detalhesTituloAberto->valor),
            ];

            $total += empty($titulo->detalhesTituloAberto)? $titulo->detalhesTitulosPagos->valor : $titulo->detalhesTituloAberto->valor;
        }

        $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
        $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
        $intervalo_total_dias = $data_inicial->diffInDays($data_final);

        $dados = [
            'cliente' => $renegociacaoTituloObj->cliente->nome.' - '.$renegociacaoTituloObj->cliente_cpf_cnpj,
            'data' => parserData($renegociacaoTituloObj->data_inicial),
            'titulos' => $titulos,
            'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
            'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
            'valor_total_juros' => parserValor($renegociacaoTituloObj->valorTotalComJuros->total),
            'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
            'parcela_valor' => parserValor($renegociacaoTituloObj->parcelas[0]->valor),
            'periodo' => $intervalo_total_dias.' dias',
        ];

        return view('programs.negociacao_titulo.modal.deletar')->with(['id' => $fields['id'],'dados' => $dados, 'total' => $total]);
    }

    public function deletarRenegociacao(Request $request){
        $id = $request->only('id')['id'];
        try{
            $id = decrypt($id);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ], 422);
        }
        
        $renegociacaoTituloObj = RenegociacaoTitulo::with(['titulos', 'parcelas', 'avalistas'])->find($id);
        $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
        $documento_clicksign_id = isset($renegociacaoTituloObj->clicksignDocumento->documento_clicksign_id) ? $renegociacaoTituloObj->clicksignDocumento->documento_clicksign_id : '';

        if(!empty($documento_clicksign_id)){
            $AssinaturaEletronicaClicksign->cancelarDocumento($documento_clicksign_id);
        }

        if(!empty($renegociacaoTituloObj->aprovacaoRenegociacao)){
            $renegociacaoTituloObj->aprovacaoRenegociacao->deleted_by = Auth::id();
            $renegociacaoTituloObj->aprovacaoRenegociacao->delete();
            $renegociacaoTituloObj->aprovacaoRenegociacao->save();
        }

        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulo->deleted_by = Auth::id();
            $titulo->save();
            $titulo->delete();
        }
        foreach($renegociacaoTituloObj->parcelas as $parcela){
            $parcela->deleted_by = Auth::id();
            $parcela->save();
            $parcela->delete();
        }
        foreach($renegociacaoTituloObj->avalistas as $avalista){
            $avalista->deleted_by = Auth::id();
            $avalista->save();
            $avalista->delete();
        }
        $renegociacaoTituloObj->deleted_by = Auth::id();
        $renegociacaoTituloObj->save();
        $renegociacaoTituloObj->delete();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function adicionarAvalista(RenegociacaoTituloAdicionarAvalistaRequest $request){
        $fields = $request->only('id', 'avalistas', 'nome_avalista', 'cpf_avalista', 'email_avalista', 'endereco_avalista', 'estado_civil', 'nome_venia_conjugal', 'cpf_venia_conjugal', 'email_venia_conjugal');
        
        if(!empty($fields['id'])){
            try{
                $id = decrypt($fields['id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ]);
            }
        }

        if(!empty($fields['avalistas'])){
            $avalistas = $fields['avalistas'];
            $avalistas = decrypt($avalistas);
        }

        if(empty($fields['id'])){
            $avalistas[$fields['cpf_avalista']] = [
                'nome' => $fields['nome_avalista'],
                'cpf' => $fields['cpf_avalista'],
                'email' => $fields['email_avalista'],
                'endereco' => $fields ['endereco_avalista'],
                'estado_civil' => $fields ['estado_civil'],
                'nome_venia_conjugal' => $fields ['nome_venia_conjugal'],
                'cpf_venia_conjugal' => $fields ['cpf_venia_conjugal'],
                'email_venia_conjugal' => $fields ['email_venia_conjugal'],
            ];
    
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $avalistas,
                    'avalistas' => encrypt($avalistas),
                ]
            ];
            return response()->json($response);
        }else{

            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
            
            $avalista = [
                'nome' => $fields['nome_avalista'],
                'cpf' => $fields['cpf_avalista'],
                'email' => $fields['email_avalista'],
                'endereco' => $fields ['endereco_avalista'],
                'estado_civil' => $fields ['estado_civil'],
                'nome_venia_conjugal' => $fields ['nome_venia_conjugal'],
                'cpf_venia_conjugal' => $fields ['cpf_venia_conjugal'],
                'email_venia_conjugal' => $fields ['email_venia_conjugal'],
            ];
           
            $renegociacaoTituloAvalistaObj = new RenegociacaoTituloAvalista;
            $renegociacaoTituloAvalistaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id; 
            $renegociacaoTituloAvalistaObj->nome = $avalista['nome'];
            $renegociacaoTituloAvalistaObj->cpf = $avalista['cpf'];
            $renegociacaoTituloAvalistaObj->email = $avalista['email'];
            $renegociacaoTituloAvalistaObj->endereco = $avalista['endereco'];
            $renegociacaoTituloAvalistaObj->tipo = 'empresa';
            $renegociacaoTituloAvalistaObj->tipo_signatario = 'fiador';
            $renegociacaoTituloAvalistaObj->estabelecimento_codigo = '05';
            $renegociacaoTituloAvalistaObj->cpf_cnpj = '';
            $renegociacaoTituloAvalistaObj->created_by = Auth::id();
            $renegociacaoTituloAvalistaObj->save();

            $tabela = [];
            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);

            foreach($renegociacaoTituloObj->avalistas as $avalista){
    
                if($avalista->cpf != '176.331.198-88' && $avalista->tipo_signatario == 'fiador'){
                    $tabela[] = [
                        'nome' => $avalista->nome,
                        'cpf' => $avalista->cpf,
                        'email' => $avalista->email,
                        'endereco' => $avalista->endereco
                    ];
                }
            } 

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $tabela,
                    'socios' => ''
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function modalEnviarPrevia(RenegociacaoTituloAdicionarRequest $request){
        $fields = $request->only(
            'titulos',
            'cliente_codigo',
            'nome_cliente',
            'cpf_cliente',
            'juro_atualizacao',
            'tarifa_bancaria_atualizacao',
            'valor_total',
            'juro_mes',
            'quantidade_parcela',
            'tarifa_bancaria_renegociacao',
            'intervalo_dias',
            'data_parcela',
            'valor_parcela',
            'parcela_total',
            'data_inicial_parcela',
            'encargos',
            'socios',
            'avalistas',
            'id',
            'maior_atraso',
            'juros_dias',
            'parcela_sem_encargos',
            'encargos_juros',
            'encargos_sem_juros',
            'numero_dias',
            'encargo_dia',
            'encargo_periodo',
            'encargo_total',
            'valor_total',
            'valor_total_atualizado',
            'parcela_sem_honorario',
            'encargo_periodo_ragazzi',
            'encargo_dia_ragazzi'
        );

        $hash = encrypt($fields);

        return view('programs.negociacao_titulo.modal.email_previa')->with(['hash' => $hash, 'id' => !empty($fields['id']) ? $fields['id'] : '']);
    }
    
    public function gerarArquivoPdf($id){

        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);

        $titulos = [];
        $estabelecimentos = returnEmpresasNasajonView();
        $titulo_total = 0;

        foreach($renegociacaoTituloObj->titulos as $titulo){
            $titulos[] = [
                'estabelecimento' =>$estabelecimentos[intval($titulo->estabelecimento_codigo)],
                'titulo' => $titulo->titulo_numero,
                'parcela' => empty($titulo->parcela)? '' : $titulo->parcela,
                'data_emissao' => empty($titulo->data_emissao)? '' : parserData($titulo->data_emissao),
                'data_vencimento' => empty($titulo->data_vencimento)? '' : parserData($titulo->data_vencimento),
                'valor_original' => empty($titulo->valor_original)? '' : parserValor($titulo->valor_original),
                'juros' => empty($titulo->valor_juros)? '': parserValor($titulo->valor_juros),
                'valor_saldo' => empty($titulo->valor_saldo)? '' : parserValor($titulo->valor_saldo),
                'nota' => empty($titulo->nota_numero)? '' : $titulo->nota_numero,
            ];

            $titulo_total += empty($titulo->valor_original)? 0 : $titulo->valor_original;
        }

        $datas = [];
        $total_parcelas = 0;
        foreach($renegociacaoTituloObj->parcelas as $parcela){
            $datas[] = [
                'numero' => $parcela->numero,
                'data' => parserData($parcela->data_parcela),
                'valor' => parserValor($parcela->valor),
            ];
            $total_parcelas += $parcela->valor;
        }

        $data_inicial = Carbon::parse($renegociacaoTituloObj->data_inicial);
        $data_final = Carbon::parse($renegociacaoTituloObj->data_final);
        $intervalo_total_dias = $data_inicial->diffInDays($data_final);

        $data_atual = Carbon::now();

        foreach($renegociacaoTituloObj->avalistas as $avalista){
            $dados[] = [
                'cliente_nome' => $renegociacaoTituloObj->cliente->nome,
                'cliente_cpf_cnpj' => $renegociacaoTituloObj->cliente_cpf_cnpj,
                'cliente_endereco' => $renegociacaoTituloObj->cliente->tipologradouro." ".$renegociacaoTituloObj->cliente->logradouro.", ".$renegociacaoTituloObj->cliente->numero.", ".$renegociacaoTituloObj->cliente->bairro.", ".$renegociacaoTituloObj->cliente->cidade.", ".$renegociacaoTituloObj->cliente->uf.", CEP ".$renegociacaoTituloObj->cliente->cep,
                'avalista_nome' => $avalista->nome,
                'avalista_cpf' => $avalista->cpf,
                'avalista_endereco' => $avalista->endereco,
                'avalista_estado_civil' => $avalista->estado_civil,
                'nome_venia_conjugal' => empty($avalista->nome_venia_conjugal)? '' : $avalista->nome_venia_conjugal,
                'cpf_venia_conjugal' => empty($avalista->cpf_venia_conjugal)? '' : $avalista->cpf_venia_conjugal,
                'data' => parserData($renegociacaoTituloObj->data_inicial),
                'titulos' => $titulos,
                'valor_total' => parserValor($renegociacaoTituloObj->valorTotalTitulo->total),
                'juros_mes' => parserValor($renegociacaoTituloObj->juros_mes).'%',
                'valor_total_juros' => parserValor($total_parcelas),
                'quantidade_parcela' => $renegociacaoTituloObj->parcela_quantidade,
                'periodo' => $intervalo_total_dias.' dias',
                'datas' => $datas,
                'data_atual' => $data_atual->day.' de '.parserNomeMesInteiro($data_atual->month).' de '.$data_atual->year,
                'titulo_total' => parserValor($titulo_total),
                'tipo_signatario' => $avalista->tipo_signatario,
                'venia_conjugal' => isset($hash['venia_conjugal'])? 1 : 0, 
            ];
        }

		$pdfFilePath = 'reg_tit_'.$dados[0]['cliente_nome'].'.pdf';
		$pdf = PDF::loadView(
			'pdf.contrato_cliente_renegociacao_titulo', 
			[
				'dados' => $dados,
                'titulos' => $titulos,
			], 
			[], 
			['title' => 'Renegociação de Título', 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
        Storage::put($this->storage.$renegociacaoTituloObj->id.'/'.$pdfFilePath, $pdf->output());
        return $this->storage.$renegociacaoTituloObj->id.'/'.$pdfFilePath;
	}

    private function getEstadoCivil(){
        $estado_civil = [];

        $estado_civil = [
            'casado' => 'Casado(a)', 
            'divorciado' => 'Divorciado(a)', 
            'separado' => 'Separado(a)', 
            'solteiro' => 'Solteiro(a)', 
            'uniao_estavel' => 'União Estável', 
            'viuvo' => 'Viúvo(a)'
        ];

        return $estado_civil;
    }

    private function getMotivo(AprovacaoRenegociacao $aprovacao_renegociacao){
        $motivo = "";

        if (!empty($aprovacao_renegociacao->motivo_desconto_no_valor_do_titulo)){
            $motivo = empty($motivo)? 'Há desconto no valor do título' : $motivo.', Há desconto no valor do título';
        }
        if (!empty($aprovacao_renegociacao->motivo_juros_baixo_permitido)){
            $motivo = empty($motivo)? 'O juro está baixo do permitido' : $motivo.', O juro está baixo do permitido';
        }
        if (!empty($aprovacao_renegociacao->motivo_periodo_maior_permitido)){
            $motivo = empty($motivo)? 'Período está maior que o permitido' : $motivo.', Período está maior que o permitido';
        }
        if (!empty($aprovacao_renegociacao->motivo_parcela_com_valor_fixo)){
            $motivo = empty($motivo)? 'Mudança no valor da parcela' : $motivo.', Mudança no valor da parcela';
        }
        if (!empty($aprovacao_renegociacao->motivo_sem_fiador)){
            $motivo = empty($motivo)? 'Sem fiador' : $motivo.', Sem fiador';
        }
        if (!empty($aprovacao_renegociacao->motivo_fiador_casado_sem_venia_conjugal)){
            $motivo = empty($motivo)? 'Não informado vênia conjugal' : $motivo.', Não informado vênia conjugal';
        }
        if (!empty($aprovacao_renegociacao->motivo_parcelas_maior_permitido)){
            $motivo = empty($motivo)? 'Parcela maior que permitido' : $motivo.', Parcela maior que permitido';
        }

        $motivo = $motivo.'.';

        return $motivo;
    }

    public function cancelamentoPorInatividade(){
		return $renegociacoes = DB::transaction(function(){

			$data_atual = Carbon::Now();

			$renegociacoes_vencidas = LancamentoProjeto::where('data_inicial_renegociacao', "<", $data_atual)
				->whereIn('status', [1, 2, 5, 4, 6, 7, 8])
				->get();

			$renegociacoes = [];

			foreach ($renegociacoes_vencidas as $value) {
                $value->status = 10;
				$value->save();
				$renegociacoes[] = $value->id;
			}

			return $renegociacoes;
		});
    }

    public function emailNovosTítulos($id, $titulos_antigos, $titulos_novos){
        $RenegociacaoTitulo = RenegociacaoTitulo::with('avalistas', 'cliente')
        ->find($id);

        $EmailObj = new EmailController();
            
        $variaveis = [
            'cliente' => $RenegociacaoTitulo->cliente->nome.' - '.$RenegociacaoTitulo->cliente_cpf_cnpj,
            'titulos_antigos' => $titulos_antigos,
            'titulos_novos' => $titulos_novos,
        ];

        $documento_assinado = substr_replace($this->storage.$RenegociacaoTitulo->id.'/'.$RenegociacaoTitulo->avalistas->pluck('documento_assinado')->unique()->implode(''), '_assinado.pdf', -4);
        $anexo = [$documento_assinado  => ['as' => basename($documento_assinado)]];
        
        $retorno = $EmailObj->sendEmailToken('00', 'email_aprovacao_do_cliente', [], $variaveis, $anexo);
        
        if($retorno['status'] == 'error'){
            throw new \Exception($retorno['error']);
        }
    }

    public function gerarRenegociacaoTitulo(){
        $RenegociacaoTitulo = RenegociacaoTitulo::with([
            'clicksignDocumento.clicksignSignatarioDocumento',
            'clicksignDocumento','clicksignDocumento.clicksignSignatarioDocumento.clicksignSignatario',
            'avalistas',
            'titulos'
        ])
        ->whereNotNull('clicksign_documentos_id')
        ->where('status_renegociacao_titulos_id', 2)
        ->get();

        foreach($RenegociacaoTitulo as $titulo){
            if($titulo->clicksignDocumento->status == 'canceled'){
                $RenegociacaoTituloObj = RenegociacaoTitulo::find($titulo->id);
                $RenegociacaoTituloObj->status_renegociacao_titulos_id = 10;
                $RenegociacaoTituloObj->save();
            }
                
            foreach($titulo->clicksignDocumento->clicksignSignatarioDocumento as $signatario){
                $RenegociacaoTituloAvalista = RenegociacaoTituloAvalista::whereNull('aceito')
                ->where('cpf', $signatario->clicksignSignatario->cpf)
                ->where('tipo_signatario','representante_legal')
                ->where('renegociacao_titulos_id', $titulo->id);

                $avalista = $RenegociacaoTituloAvalista->first();
                
                if(!empty($avalista) && $signatario->assinou_como == 'legal_representative'){
                    $avalista->aceito = true;
                    $avalista->ip = $signatario->ip;
                    $avalista->data_assinatura  = $signatario->data_assinatura;
                    $avalista->documento_assinado = $titulo->clicksignDocumento->status == 'closed' ? basename($titulo->clicksignDocumento->caminho_arquivo) : null;
                    $avalista->updated_by = 1;
                    $avalista->save();
                }

                $RenegociacaoTituloAvalista = RenegociacaoTituloAvalista::whereNull('aceito')
                ->where('cpf', $signatario->clicksignSignatario->cpf)
                ->where('tipo_signatario','fiador')
                ->where('renegociacao_titulos_id', $titulo->id);

                $avalista = $RenegociacaoTituloAvalista->first();
                
                if(!empty($avalista) && $signatario->assinou_como == 'surety'){
                    $avalista->aceito = true;
                    $avalista->ip = $signatario->ip;
                    $avalista->data_assinatura  = $signatario->data_assinatura;
                    $avalista->documento_assinado = $titulo->clicksignDocumento->status == 'closed' ? basename($titulo->clicksignDocumento->caminho_arquivo) : null;
                    $avalista->updated_by = 1;
                    $avalista->save();
                }

                $RenegociacaoTituloAvalista = RenegociacaoTituloAvalista::whereNull('aceito_venia_conjugal')
                ->where('cpf_venia_conjugal', $signatario->clicksignSignatario->cpf)
                ->where('tipo_signatario','fiador')
                ->where('renegociacao_titulos_id', $titulo->id);

                $conjunge = $RenegociacaoTituloAvalista->first();
                    
                if(!empty($conjunge) && $signatario->assinou_como == 'intervening'){
                    $RenegociacaoTituloAvalista = RenegociacaoTituloAvalista::whereNull('aceito')
                    ->where('cpf', $signatario->clicksignSignatario->cpf)
                    ->where('tipo_signatario','representante_legal')
                    ->where('renegociacao_titulos_id', $titulo->id);

                    $representante_legal = $RenegociacaoTituloAvalista->first();

                    $conjunge->aceito_venia_conjugal = true;
                    $conjunge->ip_venia_conjugal = $signatario->ip;
                    $conjunge->data_assinatura_venia_conjugal  = $signatario->data_assinatura;
                    $conjunge->documento_assinado_conjuge = $titulo->clicksignDocumento->status == 'closed' ? basename($titulo->clicksignDocumento->caminho_arquivo) : null;
                    $conjunge->documento_assinado = $titulo->clicksignDocumento->status == 'closed' ? basename($titulo->clicksignDocumento->caminho_arquivo) : null;
                    $conjunge->updated_by = 1;
                    $conjunge->save();

                    $representante_legal->documento_assinado = $titulo->clicksignDocumento->status == 'closed' ? basename($titulo->clicksignDocumento->caminho_arquivo) : null;
                    $representante_legal->updated_by = 1;
                    $representante_legal->save();
                }
            }

            $num_assinaturas = RenegociacaoTituloAvalista::where(function($query){
                $query->whereNull('aceito')
                ->orWhereNotNull('cpf_venia_conjugal')
                ->whereNull('aceito_venia_conjugal');
            })
            ->where('renegociacao_titulos_id',$titulo->id)
            ->count();

            if($num_assinaturas == 0 && $titulo->clicksignDocumento->status == 'closed'){
                $this->geracaoRenegociacaoTitulo($titulo->id, '');
                
                $clienteBlackListObj = new ClienteBlackList;
                $clienteBlackListObj->cpf_cnpj = $titulo->cliente_cpf_cnpj;
                $clienteBlackListObj->created_by = 1;
                $clienteBlackListObj->status_cliente_black_lists_id = 3;
                $clienteBlackListObj->save();

                $clienteBlackListTituloObj = new ClienteBlackListTitulo;
                $clienteBlackListTituloObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListTituloObj->titulo_uuid_nasajon = $titulo->titulos->pluck('titulo_uuid_nasajon')->first();
                $clienteBlackListTituloObj->titulo_numero = $titulo->titulos->pluck('titulo_numero')->first();
                $clienteBlackListTituloObj->created_by = 1;
                $clienteBlackListTituloObj->save();

                $clienteBlackListHistoricoObj = new ClienteBlackListHistorico;
                $clienteBlackListHistoricoObj->cpf_cnpj = $titulo->cliente_cpf_cnpj;
                $clienteBlackListHistoricoObj->titulo = $titulo->titulos->pluck('titulo_numero')->first();
                $clienteBlackListHistoricoObj->motivo = 'RENEGOCIAÇÃO DE TÍTULO';
                $clienteBlackListHistoricoObj->cliente_black_lists_id = $clienteBlackListObj->id;
                $clienteBlackListHistoricoObj->created_by = 1;
                $clienteBlackListHistoricoObj->save();
            }elseif($num_assinaturas > 0 && $titulo->clicksignDocumento->status == 'closed'){
                $RenegociacaoTituloObj = RenegociacaoTitulo::find($titulo->id);
                $RenegociacaoTituloObj->status_renegociacao_titulos_id = 10;
                $RenegociacaoTituloObj->updated_by = 1;
                $RenegociacaoTituloObj->save();

                foreach($titulo->avalistas as $documento){
                    $documento->documento_assinado = basename($titulo->clicksignDocumento->caminho_arquivo);
                    $documento->updated_by = 1;
                    $documento->save();
                }
            }
        }
    }

    public function reenvioEmailAvalista(Request $request){
        $fields = $request->only('id_renegocicao');

        try{
            $id = decrypt($fields['id_renegocicao']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => [],
                'response' => []
            ]);
        }

        $RenegociacaoTitulo = RenegociacaoTitulo::find($id);

        foreach($RenegociacaoTitulo->avalistas as $avalista){
            if($avalista->tipo_signatario == 'representante_legal'){
                $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                $dados_signatario[$avalista->id]['email'] = $avalista->email;
                $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                $dados_signatario[$avalista->id]['tipo'] = 'representante_legal';
                $avalista->ip = null;
                $avalista->data_assinatura = null;
                $avalista->aceito = null;
                $avalista->save();
            }
            else{
                $dados_signatario[$avalista->id]['nome'] = $avalista->nome;
                $dados_signatario[$avalista->id]['email'] = $avalista->email;
                $dados_signatario[$avalista->id]['cpf'] = $avalista->cpf;
                $dados_signatario[$avalista->id]['tipo'] = 'fiador';
                $avalista->ip = null;
                $avalista->data_assinatura = null;
                $avalista->aceito = null;
                $avalista->save();
            }

            if($avalista->estado_civil == 'casado' || $avalista->estado_civil == 'uniao_estavel'){
                $dados_signatario[$avalista->cpf_venia_conjugal]['nome'] = $avalista->nome_venia_conjugal;
                $dados_signatario[$avalista->cpf_venia_conjugal]['cpf'] = $avalista->cpf_venia_conjugal;
                $dados_signatario[$avalista->cpf_venia_conjugal]['email'] = $avalista->email_venia_conjugal;
                $dados_signatario[$avalista->cpf_venia_conjugal]['tipo'] = 'venia_conjugal';
                $avalista->data_assinatura_venia_conjugal = null;
                $avalista->aceito_venia_conjugal = null;
                $avalista->save();
            }

            if($dados_signatario[$avalista->id]['cpf'] == '176.331.198-88'){
                unset($dados_signatario[$avalista->id]);
            }
        }

        $AssinaturaEletronicaClicksign = new AssinaturaEletronicaClicksignController;
        $documento_clicksign_id = $RenegociacaoTitulo->clicksignDocumento->documento_clicksign_id;
        $AssinaturaEletronicaClicksign->cancelarDocumento($documento_clicksign_id);
        
        $path_pdf = $this->gerarArquivoPdf($RenegociacaoTitulo->id);
        $retorno = $AssinaturaEletronicaClicksign->solicitarAssinaturaEmail($dados_signatario, $path_pdf);

        try{
            if($retorno->getData()->status === 'error'){
                throw new \Exception;
            }
        } catch (\Exception $e) {
            return $retorno;
        }

        $RenegociacaoTitulo->clicksign_documentos_id = decrypt($retorno->getData()->response->id);
        $RenegociacaoTitulo->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];

        return response()->json($response, 200);
    }

    public function adicionarSocio(RenegociacaoTituloAdicionarSocioRequest $request){
        $fields = $request->only('id', 'socios', 'nome_socio', 'cpf_socio', 'email_socio', 'endereco_socio');

        if(!empty($fields['id'])){
            try{
                $id = decrypt($fields['id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ]);
            }
        }
       
        if(!empty($fields['socios'])){
            $socios  = decrypt($fields['socios']);
        }
            
        if(empty($fields['id'])){
            $socios[$fields['cpf_socio']] = [
                'nome' => $fields['nome_socio'],
                'cpf' => $fields['cpf_socio'],
                'email' => $fields['email_socio'],
                'endereco' => $fields ['endereco_socio']
            ];

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $socios,
                    'socios' => encrypt($socios)
                ]
            ];
            return response()->json($response, 200);
        }else{

            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
            $representante_MN = $renegociacaoTituloObj->avalistas->where('cpf', '176.331.198-88')->pluck('cpf')->count();

            $socios[$fields['cpf_socio']] = [
                'nome' => $fields['nome_socio'],
                'cpf' => $fields['cpf_socio'],
                'email' => $fields['email_socio'],
                'endereco' => $fields ['endereco_socio']
            ];
            
            if($representante_MN == 0){
                $ClienteNasajon = ClienteNasajon::where('cpf_cnpj', '176.331.198-88')->first();

                $socios[$ClienteNasajon->cpf_cnpj]['nome'] = $ClienteNasajon->nome;
                $socios[$ClienteNasajon->cpf_cnpj]['cpf'] = $ClienteNasajon->cpf_cnpj;
                $socios[$ClienteNasajon->cpf_cnpj]['email'] = $ClienteNasajon->email;
                $socios[$ClienteNasajon->cpf_cnpj]['endereco'] = $ClienteNasajon->tipologradouro.'. '.$ClienteNasajon->logradouro.', '.$ClienteNasajon->numero.', '.$ClienteNasajon->complemento.' - '.$ClienteNasajon->cidade.' - '.$ClienteNasajon->uf.', CEP'.$ClienteNasajon->cep;
            }

            foreach($socios as $socio){
                $renegociacaoTituloAvalistaObj = new RenegociacaoTituloAvalista;
                $renegociacaoTituloAvalistaObj->renegociacao_titulos_id = $renegociacaoTituloObj->id; 
                $renegociacaoTituloAvalistaObj->nome = $socio['nome'];
                $renegociacaoTituloAvalistaObj->cpf = $socio['cpf'];
                $renegociacaoTituloAvalistaObj->email = $socio['email'];
                $renegociacaoTituloAvalistaObj->endereco = $socio['endereco'];
                $renegociacaoTituloAvalistaObj->tipo = 'empresa';
                $renegociacaoTituloAvalistaObj->tipo_signatario = 'representante_legal';
                $renegociacaoTituloAvalistaObj->estabelecimento_codigo = '05';
                $renegociacaoTituloAvalistaObj->cpf_cnpj = '';
                $renegociacaoTituloAvalistaObj->created_by = Auth::id();
                $renegociacaoTituloAvalistaObj->save();
            }

            $tabela = [];
            $renegociacaoTituloObj = RenegociacaoTitulo::find($id);

            foreach($renegociacaoTituloObj->avalistas as $avalista){
    
                if($avalista->cpf != '176.331.198-88' && $avalista->tipo_signatario == 'representante_legal'){
                    $tabela[] = [
                        'nome' => $avalista->nome,
                        'cpf' => $avalista->cpf,
                        'email' => $avalista->email,
                        'endereco' => $avalista->endereco
                    ];
                }
            } 

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $tabela,
                    'socios' => ''
                ]
            ];
            return response()->json($response, 200);

        }
    }

    public function deletarSocio(Request $request){
        $campo = $request->only('id', 'socios', 'cpf');

        if(isset($campo['id'])){
            try{
                $id = decrypt($campo['id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ]);
            }
        }
        
        if(!empty($campo['socios'])){
            $socios = $campo['socios'];
            $socios  = decrypt($socios);
        }

        if(empty($campo['id'])){
            unset($socios[$campo['cpf']]);
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $socios,
                    'socios' => empty($socios) ? '' : encrypt($socios)
                ]
            ];
            return response()->json($response, 200);  
        }else{
            $renegociacaoTituloAvalista = RenegociacaoTituloAvalista::find($id);
            $id_renegociacao = $renegociacaoTituloAvalista->renegociacao_titulos_id;
            $renegociacaoTituloAvalista->deleted_by = Auth::id();
            $renegociacaoTituloAvalista->save();
            $renegociacaoTituloAvalista->delete();

            $tabela = [];

            $renegociacaoTituloObj = RenegociacaoTitulo::with('avalistas')->find($id_renegociacao);

            foreach($renegociacaoTituloObj->avalistas as $avalista){
    
                if($avalista->cpf != '176.331.198-88' && $avalista->tipo_signatario == 'representante_legal'){
                    $tabela[$avalista->cpf] = [
                        'nome' => $avalista->nome,
                        'cpf' => $avalista->cpf,
                        'email' => $avalista->email,
                        'endereco' => $avalista->endereco
                    ];
                }
            } 

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $tabela,
                    'socios' => ''
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function deletarAvalista(Request $request){
        $campo = $request->only('id', 'avalistas', 'cpf');

        if(isset($campo['id'])){
            try{
                $id = decrypt($campo['id']);
            }catch(\Exception $e){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Dados não encontrados',
                    'error' => [],
                    'response' => []
                ]);
            }
        }

        if(!empty($campo['avalistas'])){
            $avalistas = $campo['avalistas'];
            $avalistas  = decrypt($avalistas);
        }
        
        if(empty($campo['id'])){
            unset($avalistas[$campo['cpf']]);
             
            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $avalistas,
                    'avalistas' => empty($avalistas) ? '' : encrypt($avalistas)
                ]
            ];
            return response()->json($response, 200);
        }else{
            $renegociacaoTituloAvalista = RenegociacaoTituloAvalista::find($id);
            $id_renegociacao = $renegociacaoTituloAvalista->renegociacao_titulos_id;
            $renegociacaoTituloAvalista->deleted_by = Auth::id();
            $renegociacaoTituloAvalista->save();
            $renegociacaoTituloAvalista->delete();

            $tabela = [];

            $renegociacaoTituloObj = RenegociacaoTitulo::with('avalistas')->find($id_renegociacao);

            foreach($renegociacaoTituloObj->avalistas as $avalista){
    
                if($avalista->cpf != '176.331.198-88' && $avalista->tipo_signatario == 'fiador'){
                    $tabela[$avalista->cpf] = [
                        'nome' => $avalista->nome,
                        'cpf' => $avalista->cpf,
                        'email' => $avalista->email,
                        'endereco' => $avalista->endereco
                    ];
                }
            } 

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => [
                    'tabela' => $tabela,
                    'socios' => ''
                ]
            ];
            return response()->json($response, 200);
        }
    }

    public function reenviarAprovacaoRenegociacaoCliente($id){
        $renegociacaoTituloObj = RenegociacaoTitulo::find($id);
       
        $titulos_antigos = "";
        $titulos_novos = "";
        $estabelecimentos = returnEmpresasNasajonView();
        $data_atual = Carbon::Now();

        foreach($renegociacaoTituloObj->titulos as $titulo){
            if(empty($observacao)){
                $observacao = 'Renegociação, Titulos Antigos: '.$titulo->titulo_numero;
            }else{
                $observacao = $observacao.', '.$titulo->titulo_numero;
            }
            $titulos_antigos = $titulos_antigos."Estabelecimento:".$estabelecimentos[intval($titulo->estabelecimento_codigo)]."<br>Titulo : ".$titulo->titulo_numero."<br>Emissão:".parserData($titulo->data_emissao)."<br>Vencimento:".parserData($titulo->data_vencimento)."<br><br>";
        }

        foreach($renegociacaoTituloObj->parcelas as $parcela){
            $titulos_novos = $titulos_novos."Estabelecimento:".$estabelecimentos[5]."<br>Titulo : ".$renegociacaoTituloObj->titulos[0]->titulo_numero.'.'.$parcela->numero."<br>Emissão:".$data_atual->format('d/m/Y')."<br>Vencimento:".parserData($parcela->data_parcela)."<br><br>";
        }

        try{
            $this->emailNovosTítulos($renegociacaoTituloObj->id, $titulos_antigos, $titulos_novos);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function iniciarRenegociacaoTitulo($numero_inicial_titulo, $data_inicial_renegociacao, $usuario_uuid, $estabelecimento_uuid, $acrescimo, $cliente_uuid){
        try{
            $sql_renegociacao_iniciar = "select integracoes.renegociacao_iniciar(
                '".$numero_inicial_titulo."',
                '".$data_inicial_renegociacao."',
                '".$estabelecimento_uuid."', 
                '".$cliente_uuid."', 
                ".$acrescimo.",
                'Renegociação pelo portal', 
                '".$usuario_uuid."');";
            
            $retorno_api_renegociar_iniciar = DB::connection('nasajon')->select($sql_renegociacao_iniciar);

            return $retorno_api_renegociar_iniciar[0]->renegociacao_iniciar;
        }catch(\Exception $e){
            Log::error($e->getMessage());
            Log::error($sql_renegociacao_iniciar);
        }

        return false;
    }

    public function tituloRenegociacaoTitulo($renegociacao_uuid, $titulo_uuid){
        try{
            $sql_renegociacao_titulo_renegociar = "select integracoes.renegociacao_titulo_renegociar(
                '".$renegociacao_uuid."',
                '".$titulo_uuid."');";
            $retorno_api_renegociacao_titulo_renegociar = DB::connection('nasajon')->select($sql_renegociacao_titulo_renegociar);
        }catch(\Exception $e){
            Log::error($e->getMessage());
            Log::error($sql_renegociacao_titulo_renegociar);
        }
    }
    
    public function tituloNovoRenegociacaoTitulo($renegociacao_uuid, $conta_uuid, $forma_pagamento_uuid, $layout_uuid, $vencimento, $valor){
        try{
            $sql_renegociacao_titulo_gerar = "select integracoes.renegociacao_titulo_gerar(
                '".$renegociacao_uuid."',
                '".$conta_uuid."',
                '".$forma_pagamento_uuid."',
                ".$layout_uuid.",
                '".$vencimento."',
                ".$valor.");";

            $retorno_api_renegociacao_titulo_gerar = DB::connection('nasajon')->select($sql_renegociacao_titulo_gerar);

            return $retorno_api_renegociacao_titulo_gerar;
        }catch(\Exception $e){
            Log::error($e->getMessage());
            Log::error($sql_renegociacao_titulo_gerar);
        }
    }

    public function processarRenegociacaoTitulo($renegociacao_uuid, $vendedores_string){
        try{
            $sql_renegociacao_processar = "select integracoes.renegociacao_processar(
                '".$renegociacao_uuid."',
                '{".$vendedores_string."}');";

            $retorno_api_sql_renegociacao_processar = DB::connection('nasajon')->select($sql_renegociacao_processar);
        }catch(\Exception $e){
            Log::error($e->getMessage());
            Log::error($sql_renegociacao_processar);
        }
    }

    public function modalConfirmarRenegociacao(RenegociacaoConfirmarRequest $request){
        $campo = $request->only( 
            'id',
            'avalistas',
            'socios',
            'titulos',
            'cliente_codigo',
            'juro_atualizacao',
            'tarifa_bancaria_atualizacao',
            'valor_total',
            'juro_mes',
            'quantidade_parcela',
            'tarifa_bancaria_renegociacao',
            'intervalo_dias',
            'data_parcela',
            'valor_parcela',
            'parcela_total',
            'data_inicial_parcela',
            'encargos',
            'maior_atraso',
            'juros_dias',
            'parcela_sem_encargos',
            'encargos_juros',
            'encargos_sem_juros',
            'numero_dias',
            'encargo_dia',
            'encargo_periodo',
            'encargo_total',
            'valor_total',
            'valor_total_atualizado',
            'parcela_sem_honorario',
            'encargo_periodo_ragazzi',
            'encargo_dia_ragazzi'
        );

        $hash = encrypt($campo);

        return view('programs.negociacao_titulo.modal.confirmar')->with([
            'hash' => $hash,
            'avalistas' => $campo['avalistas'],
            'socios' => $campo['socios'],
            'id' => $campo['id']
        ]);
    }
}
