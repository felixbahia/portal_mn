<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\ListaDePrecosRequest;

use App\User;
use App\Role;
use App\TipoUsuario;
use App\TitulosPagosNasajon;
use App\VendedorComissaoNota;
use App\PedidosVendaNasajon;
use App\MargemPrazo;
use App\AliquotaPreco;
use App\ParametrosAprovacao;
use App\TitulosEmAbertoNasajonPortal;
use App\TitulosAbertosNasajonVirada;
use App\PedidosPrePago;
use App\VendedorTituloNasajon;
use App\TituloPagamentoNasajon;
use App\LancamentoDebCredVendedor;
use App\Cheque;
use App\ComissaoDataFechamento;
use App\ComissaoGerentesVendedores;
use App\VendedorNasajon;
use App\ComunicadoComissoe;
use App\NotaVendaNasajon;
use App\ComissaoAlteradaCampanhaZeraEstoque;
use App\FaturamentoNotaNasajon;
use App\FaturamentoNasajon;
use App\ComissaoAjusteZerada;
use App\CampanhasComissaoCalculo;

use Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use PDF;

use App\Http\Requests\ComissaoDialogRequest;
use App\Http\Requests\ComissaoDuplicatasFiltroRequest;

use App\Http\Controllers\ComissaoController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\RevisaoComissaoController;

use Illuminate\Support\Facades\Storage;

class ComissaoDuplicatasController extends Controller
{

    private $caminho_pdf = '';

    private $cnpj_excluir = ['05075884000167', '05075884000248', '06311274000269', '06311274000340', '08', '07', '06311274000501', '06311274000420'];

    private $subordinado_supervisor_playstation_10046 = ['510', '517', '518', '519', '521']; 
    private $subordinado_supervisor_playstation_11194 = ['534', '525', '526', '527', '528', '529', '530']; 

	public function index(Request $request) {
		
        if(Auth::user()->hasPermissionTo("programas App\ComissaoDuplicatas") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ComissaoDuplicatas');
        
        $representantes = [];

        if(!in_array(Auth::user()->tipo_usuario_id,[12, 16, 22, 19, 14, 13])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
            ->where('codigo_representante', '!=', '')
            ->where('codigo_representante', '!=', '998')
            ->orderBy('codigo_representante')
            ->get()
            ->toArray();
            $representantes = [];
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value['id']] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }else if(in_array(Auth::id(),[11194, 10046])){
            if(Auth::id() == 11194){
                $codigo_representantes = $this->subordinado_supervisor_playstation_11194;
            }else if(Auth::id() == 10046){
                $codigo_representantes = $this->subordinado_supervisor_playstation_10046;
            }
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->whereIn('codigo_representante',  $codigo_representantes)
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[Auth::id()] = Auth::user()->codigo_representante . " - " . strtoupper(Auth::user()->name);

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[$representante->id] = $representante->codigo_representante . " - " . strtoupper($representante->name);
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value->id] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }else if(in_array(Auth::user()->tipo_usuario_id,[13])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->where('codigo_representante', '!=', '')
                ->where('codigo_representante', '!=', '998')
                ->where('responsavel', Auth::user()->supervisor->id)
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[Auth::id()] = Auth::user()->codigo_representante . " - " . strtoupper(Auth::user()->name);

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[$representante->id] = $representante->codigo_representante . " - " . strtoupper($representante->name);
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value->id] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }else if(in_array(Auth::user()->tipo_usuario_id,[19, 14])){
            $representantes_busca = User::select('id', 'codigo_representante', 'name')
                ->where('codigo_representante', '!=', '')
                ->where('codigo_representante', '!=', '998')
                ->where('responsavel', Auth::id())
                ->orderBy('codigo_representante')
                ->get();

            $representantes = [];
            $representantes[Auth::id()] = Auth::user()->codigo_representante . " - " . strtoupper(Auth::user()->name);

            $representantes_busca->each(function($representante) use(&$representantes){
                $representantes[$representante->id] = $representante->codigo_representante . " - " . strtoupper($representante->name);
            });
            
            foreach ($representantes_busca as $key => $value) {
                $representantes[$value->id] = $value['codigo_representante']." - ".strtoupper($value['name']);
            }
        }

		$Role = Role::where('name', 'REP.Playstation')->first();

		$tiposObj = TipoUsuario::select('id', 'nome');
		$tiposObj->where('nome', 'Representante');
		$tiposObj->orWhere('nome', 'Vendedor Interno');
		$tiposObj->orWhere('nome', 'Agente de Venda');
		$tiposObj->orWhere('nome', 'Gerente Comercial');
		$result = $tiposObj->orderBy('nome')->get(); 
        $tipos = [
            '' => 'Todos tipos',
            $Role->id => $Role->name
        ];
		
        foreach($result as $value){
            $tipos[$value->id] = $value["nome"];
		}

		$estabelecimentos = returnEmpresasNasajonView();
		unset($estabelecimentos[20]);
		
        return view('programs.comissao_duplicatas.index')->with(['representantes' => $representantes, 'tipos' => $tipos, 'estabelecimentos' => $estabelecimentos]);
    }
    
    public function filter(ComissaoDuplicatasFiltroRequest $request, $array_retorno = false){
        ini_set('memory_limit', '2024M');
        set_time_limit(300);
        
        $fields = $request->only('estabelecimento', 'data', 'api', 'representantes', 'tipo','confirmacao');
        
        $representante_gerente = '';
        if(!empty($fields['representantes'])){
            $teste_usuario = User::select()->whereIn('tipo_usuario_id', [19,14,13])->where('id', $fields['representantes'])->first();

            if(!empty($teste_usuario)){
                $representante_gerente = $fields['representantes'];
                $fields['representantes'] = '';
            }
        }

        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('periodo', $fields['data'])->first();
        $comissaoGerentesVendedoresObj = new ComissaoGerentesVendedores;
        $userObj = new User;
        $titulosAbertosNasajonViradaObj = new TitulosAbertosNasajonVirada;

        if(!empty($comissaoDataFechamentoObj)){
            $data = Carbon::createFromFormat('m/Y', $fields['data'])->setTime(0,0,0);
            if($data->gt($comissaoDataFechamentoObj->data_fim)){
                $comissao_fechamento = true;
            }else{
                $comissao_fechamento = false;
            }

            $data_inicio = $comissaoDataFechamentoObj->data_inicio->format('Y-m-d');
            $data_fim = $comissaoDataFechamentoObj->data_fim->format('Y-m-d');
        }
        else{
            $comissao_fechamento = false;
            $data = Carbon::createFromFormat('m/Y', $fields['data']);
            $data_inicio = $data->copy()->subMonthNoOverflow()->format('Y-m-26');
            $data_fim = $data->format('Y-m-25');
        }

            
        $fields['data_inicio'] = $data_inicio;
        $fields['data_fim'] = $data_fim;

        $vendedores_internos = User::where('tipo_usuario_id', 16)->get()->pluck('codigo_representante');
        $gerentes = User::whereIn('tipo_usuario_id', [14, 19, 13])->get();

        $vendedorComissaoNota = new VendedorComissaoNota;
        $titulosEmAbertoNasajonPortal = new TitulosEmAbertoNasajonPortal;
        $vendedorTituloNasajon = new VendedorTituloNasajon;
        $tituloPagamentoNasajon = new TituloPagamentoNasajon;
        $TitulosPagosNasajon = new TitulosPagosNasajon;

        $separado_comissao_vendedor = $this->separadorComissao($tituloPagamentoNasajon);
        $separado_comissao_vendedor_gerente = $this->separadorComissaoGerente($tituloPagamentoNasajon);

        $separado_comissao_vendedor_aberto = $this->separadorComissaoAberto($titulosEmAbertoNasajonPortal);
        $separado_comissao_vendedor_gerente_aberto = $this->separadorComissaoGerenteAberto($titulosEmAbertoNasajonPortal);

        $titulosBaixados = 'with titulos as(
			select
                '.$tituloPagamentoNasajon->getTable().'.valor, valordesconto,
                CASE
					WHEN ' . $tituloPagamentoNasajon->getTable() . '.vendedor_codigo is null THEN \'001\'
					ELSE ' . $tituloPagamentoNasajon->getTable() . '.vendedor_codigo 
                END as vendedor_codigo,
                CASE
                    WHEN '.$tituloPagamentoNasajon->getTable().'.banco_nome = \'Juridico Ragazzi\' THEN ROUND('.$tituloPagamentoNasajon->getTable().'.valor * 0.01,2)
                    ELSE ROUND('.$tituloPagamentoNasajon->getTable().'.valor * ('.$vendedorTituloNasajon->getTable().'.percentual_comissao/100),2)
                END as comissao,
                CASE 
                    '.$separado_comissao_vendedor_gerente.'
                END as comissao_gerente,
                CASE
                    '.$separado_comissao_vendedor.'
                END as comissao_vendedor
            from ' . $tituloPagamentoNasajon->getTable() . '
            left join '. $vendedorTituloNasajon->getTable() . ' on '. $vendedorTituloNasajon->getTable() . '.tituloreceber = '  . $tituloPagamentoNasajon->getTable() . '.id_titulo
                and '.$vendedorTituloNasajon->getTable() .'.vendedor_codigo = '.$tituloPagamentoNasajon->getTable(). '.vendedor_codigo
            where 
            (CASE 
                WHEN \''.$data_inicio.'\' >= \'2021-05-26\' AND data_pagamento >= \'2021-05-26\' AND data_lancamento_pagamento >= \'2021-05-26\' THEN data_lancamento_pagamento
                ELSE data_pagamento
            END) BETWEEN \''. $data_inicio .'\' and \'' . $data_fim . '\' 
            AND conta_codigo not in(\'PERDA CONCRETIZADA\')
            AND '.$tituloPagamentoNasajon->getTable() .'.vendedor_codigo != \'998\'
            AND ("cod_cliente" not in (\'' . implode('\', \'', $this->cnpj_excluir). '\'))
            AND ((pagamento_com_credito is true and  formapagamento_descricao = \'Usar Crédito\') or formapagamento_descricao != \'Usar Crédito\')
            AND numero not ilike \'%ND\'';
            
        $titulosEmAbertoQuery = TitulosEmAbertoNasajonPortal::whereNotIn('cod_cliente', $this->cnpj_excluir)
            ->where('numero', 'not ilike', '%ND')
            ->select(
                DB::Raw(
                'saldotitulo - juros as saldotitulo, desconto,
                case 
					when titulo_emissao < \'2022-10-01\' then saldotitulo - juros
					else 0
				end as saldo_antes_outubro,
				case 
					when titulo_emissao >= \'2022-10-01\' then saldotitulo - juros
					else 0
				end as saldo_depois_outubro,
                (CASE
                    WHEN ' . $titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo is null THEN \'001\'
                    ELSE ' . $titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo 
                END) as vendedor_codigo,
                (CASE 
                    '.$separado_comissao_vendedor_aberto.'
                    WHEN banco_nome = \'Juridico Ragazzi\' THEN ROUND((saldotitulo - juros) * 0.01,2)
                    ELSE ROUND((saldotitulo - juros) * (percentual_comissao/100),2)
                END) as comissao,
                (CASE
                    '.$separado_comissao_vendedor_gerente_aberto.'
                END) as comissao_gerente'),
                'vencimento'
            )
            ->leftjoin($vendedorComissaoNota->getTable(), $vendedorComissaoNota->getTable() . '.id_docfis', '=', $titulosEmAbertoNasajonPortal->getTable() . '.nota_id')
			->where('valor', '>', 0);

        $chequesBaixadosQuery = Cheque::
            with([
                'pedidos_prepagos' => function($query) use ($data_inicio, $data_fim){
                    $query->whereBetween('created_at', [$data_inicio.' 00:00:00', $data_fim.' 23:59:59']);
                },
                'pedidos_prepagos.pedido',
                'pedidos_prepagos.pedido.pedido',
                'pedidos_prepagos.pedido.pedidoNasajon',
                'pedidos_prepagos.pedido.pedidoNasajon.nota',
                'pedidos_prepagos.pedido.pedidoNasajon.nota.revisao_vendedor_comissao'
                
            ])
			->whereHas('pedidos_prepagos', function($query) use ($data_inicio, $data_fim){
				$query->whereBetween('created_at', [$data_inicio.' 00:00:00', $data_fim.' 23:59:59'])
                ->whereNotNull('created_at');
			})
            ->where('tipo', '!=', 'cancelamento')
            ->where('status', '!=', 'devolvido');
            
        $titulosAbertosNasajonViradaQuery = TitulosAbertosNasajonVirada::select(
                DB::Raw(
                    'CASE
                        WHEN banco_nome = \'Juridico Ragazzi\' THEN ("'. $titulosAbertosNasajonViradaObj->getTable() .'"."valor" * (1/100))
                        ELSE ("'. $titulosAbertosNasajonViradaObj->getTable() .'"."valor" * ("'. $titulosAbertosNasajonViradaObj->getTable() . '"."percentual_comissao"/100))
                    END as comissao,
                    CASE 
                        WHEN vendedor_codigo is null THEN \'001\'
                        ELSE vendedor_codigo
                    END as vendedor_codigo'))
            ->leftjoin($userObj->getTable(), function($query) use($userObj, $titulosAbertosNasajonViradaObj){
                $query->on($userObj->getTable().'.codigo_representante', $titulosAbertosNasajonViradaObj->getTable() .'.vendedor_codigo');
            })
            ->leftjoin($userObj->getTable() . ' as gerente', function($query) use($userObj){
                $query->on('gerente.id', $userObj->getTable() .'.responsavel');
            })
            ->leftjoin($comissaoGerentesVendedoresObj->getTable(), function($query) use ($userObj, $titulosAbertosNasajonViradaObj, $comissaoGerentesVendedoresObj){
                $query->on(DB::Raw('CONCAT("'. $comissaoGerentesVendedoresObj->getTable() .'"."ano", \'-\', LPAD("'. $comissaoGerentesVendedoresObj->getTable() .'"."mes"::text, 2, \'0\'))'), DB::Raw('TO_CHAR("'. $titulosAbertosNasajonViradaObj->getTable() .'"."titulo_emissao", \'YYYY-MM\')'))
                ->on($comissaoGerentesVendedoresObj->getTable() .'.codigo_representante', 'gerente.codigo_representante');
            });
        
        $codigo_representantes = [];
		
		$busca = array_search("001", $codigo_representantes);
		if($busca == 0){
			$busca++;
        }

		if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $titulosBaixados .= 'AND '.$tituloPagamentoNasajon->getTable().'.codigo = \'' . str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT) . '\' ';
            $titulosEmAbertoQuery->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            $titulosAbertosNasajonViradaQuery->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
            $chequesBaixadosQuery->whereHas('pedidos_prepagos.pedido.pedido', function($query) use($fields){
                $query->where('estabelecimento', $fields['estabelecimento']);
            });
        }
        else{
            $titulosBaixados .= 'AND '.$tituloPagamentoNasajon->getTable().'.codigo != \'20\' ';
            $titulosEmAbertoQuery->where('codigo', '!=', '20');
        }

        $lancamentosBaixadosObj = $chequesBaixadosQuery->get();
        
		if(isset($codigo_representantes) && !empty($codigo_representantes)){
            $titulosBaixados .= 'AND ' . $tituloPagamentoNasajon->getTable() . '.vendedor_codigo in (\'' . implode($codigo_representantes, '\', \'') . '\') ';
            $titulosEmAbertoQuery->whereIn($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $codigo_representantes);
            $titulosAbertosNasajonViradaQuery->whereIn('vendedor_codigo', $codigo_representantes);
            $lancamentosBaixadosObj = $lancamentosBaixadosObj->filter(function($cheque) use ($codigo_representantes){
                return $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->intersect($codigo_representantes)->isNotEmpty();
            });
        }
        
        $titulosBaixados .= ')
            select 
                sum(valor) as base_comissao,
                sum(comissao) as valor_comissao,
                sum(comissao_gerente) as comissao_gerente,
                sum(comissao_vendedor) as comissao_vendedor,
                sum(valordesconto) as valor_desconto,
                vendedor_codigo
            from titulos t
            group by vendedor_codigo';

        $query_abertos = str_replace(['?'], ['\'%s\''], $titulosEmAbertoQuery->toSql()); 
        $query_abertos = vsprintf($query_abertos, $titulosEmAbertoQuery->getBindings());
        $query_virada = str_replace(['?'], ['\'%s\''], $titulosAbertosNasajonViradaQuery->toSql()); 
        $query_virada = vsprintf($query_virada, $titulosAbertosNasajonViradaQuery->getBindings());

        $titulos_a_vencer = collect(DB::connection('nasajon')
            ->select(
                'with abertos as ('. $query_abertos .')
                select 
                    sum(saldotitulo) as total,
                    sum(desconto) as desconto,
                    sum(comissao) as comissao,
                    sum(comissao_gerente) as comissao_gerente,
                    sum(saldo_antes_outubro) as saldo_antes_outubro,
                    sum(saldo_depois_outubro) as saldo_depois_outubro,
                    vendedor_codigo
                from
                    abertos
                where
                    vencimento >= current_date and
                    vencimento BETWEEN \''. $data_inicio .'\' and \'' . $data_fim . '\' 
                group by vendedor_codigo'
            )
        );

        $titulos_vencidos = collect(DB::connection('nasajon')
            ->select(
            'with abertos as ('. $query_abertos .')
                select 
                    sum(saldotitulo) as total,
                    sum(desconto) as desconto,
                    sum(comissao) as comissao,
                    sum(comissao_gerente) as comissao_gerente,
                    sum(saldo_antes_outubro) as saldo_antes_outubro,
                    sum(saldo_depois_outubro) as saldo_depois_outubro,
                    vendedor_codigo
                from
                    abertos
                where
                    vencimento < current_date
                group by vendedor_codigo'
            )
        );

        $titulos_virada = collect(DB::select(
            'with virada as ('.$query_virada.')
            select
                sum(comissao) as comissao,
                vendedor_codigo 
            from
                virada
            group by vendedor_codigo'
        ));

        $titulosRepresentantes = collect(DB::connection('nasajon')->select($titulosBaixados));

        $lancamento = $this->totalLancamento($fields);

        $representantesObj = User::
            where(function($query) use ($titulosRepresentantes,$titulos_a_vencer,$titulos_vencidos,$titulos_virada,$lancamento,$lancamentosBaixadosObj){
                $query->whereIn('codigo_representante', 
                    $titulosRepresentantes->pluck('vendedor_codigo')
                    ->merge($titulos_a_vencer->pluck('vendedor_codigo'))
                    ->merge($titulos_vencidos->pluck('vendedor_codigo'))
                    ->merge($titulos_virada->pluck('vendedor_codigo'))
                    ->merge(array_keys($lancamento['lancamento']))
                    ->merge($lancamentosBaixadosObj->pluck('pedidos_prepagos')->flatten()->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo'))
                )
                ->orWhereHas('supervisorEquipe',function($comissao) use ($titulosRepresentantes,$titulos_a_vencer,$titulos_vencidos,$titulos_virada,$lancamento,$lancamentosBaixadosObj){
                    $comissao->whereIn('codigo_representante', 
                        $titulosRepresentantes->pluck('vendedor_codigo')
                        ->merge($titulos_a_vencer->pluck('vendedor_codigo'))
                        ->merge($titulos_vencidos->pluck('vendedor_codigo'))
                        ->merge($titulos_virada->pluck('vendedor_codigo'))
                        ->merge(array_keys($lancamento['lancamento']))
                        ->merge($lancamentosBaixadosObj->pluck('pedidos_prepagos')->flatten()->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo'))
                    );
                });
            })
            ->with(['supervisor', 'detalhesRepresentanteCliente', 'detalhesRepresentanteFornecedor','supervisorResponsavel', 'detalhesModelHasRoles.detalhesRoles',
            'supervisorEquipe' => function($query) use ($titulosRepresentantes,$titulos_a_vencer,$titulos_vencidos,$titulos_virada,$lancamento,$lancamentosBaixadosObj){
                $query->whereIn('codigo_representante', 
                    $titulosRepresentantes->pluck('vendedor_codigo')
                    ->merge($titulos_a_vencer->pluck('vendedor_codigo'))
                    ->merge($titulos_vencidos->pluck('vendedor_codigo'))
                    ->merge($titulos_virada->pluck('vendedor_codigo'))
                    ->merge(array_keys($lancamento['lancamento']))
                    ->merge($lancamentosBaixadosObj->pluck('pedidos_prepagos')->flatten()->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo'))
                );
            },
            'confirmacaoComissao']);

        if(isset($fields['confirmacao']) && $fields['confirmacao'] == 'sim'){
            $representantesObj->whereHas('confirmacaoComissao', function($query) use ($data_fim){
                $data_fim = Carbon::parse($data_fim);
                $query->where(DB::raw('to_char(created_at, \'mm/YYYY\')'),$data_fim->format('m/Y'));
                $query->where('confirmacao',true);
                $query->orWhere('confirmacao',true);
            });
        }else if(isset($fields['confirmacao']) && $fields['confirmacao'] == 'nao'){
            $representantesObj->where(function($query) use ($data_fim){
                $query->whereHas('confirmacaoComissao', function($query) use ($data_fim){
                    $data_fim = Carbon::parse($data_fim);
                    $query->where(DB::raw('to_char(created_at, \'mm/YYYY\')'),$data_fim->format('m/Y'));
                    $query->where('confirmacao',false);
                });
                $query->orWhereDoesntHave('confirmacaoComissao');
            });
        }
            
        $representantesObj = $representantesObj->get();

        $pedidosPrePagosAbertos = PedidosPrePago::
            with('pedidoNasajon', 'pedidoNasajon.nota')
            ->whereHas('pedido', function($query) use ($representantesObj){
                $query->whereIn('usuario', $representantesObj->pluck('id'));
            })
            ->whereColumn('valor', '>', 'valor_pago')
            ->get();

        $pedidosPrePagosAbertos = $pedidosPrePagosAbertos->filter(function($pedido){
            return isset($pedido->pedidoNasajon->nota);
        });

        $lancamentoDebCredVendedorObj = LancamentoDebCredVendedor::
            whereIn('codigo_vendedor', $representantesObj->pluck('id'))
            ->where('codigo_motivo', 37)
            ->get();
        
        $result_titulos = [];
        $result_titulos_gerente = [];
		$result_total = [
            'base_comissao' => 0,
            'comissao' => 0,
            'a_vencer_valor' => 0,
            'a_vencer_comissao' => 0,
            'vencido_valor' => 0,
            'vencido_comissao' => 0,
            'desconto_comissao' => 0,
            'comissao_gerente' => 0,
            'comissao_gerente_aberto' => 0,
        ]; 

		$representantesObj->each(function($user) use(&$result_total, &$result_titulos, $titulosRepresentantes, $lancamento, $titulos_a_vencer, 
            $titulos_vencidos, $titulos_virada, $pedidosPrePagosAbertos, $lancamentosBaixadosObj, $fields, $lancamentoDebCredVendedorObj, &$result_titulos_gerente,
            &$comissao_fechamento)
        {
			if(empty($user)){
				return null;
            }
            
            $lancamentosDescontos = $lancamentoDebCredVendedorObj->where('codigo_vendedor', $user->id);

            $titulo = $titulosRepresentantes->firstWhere('vendedor_codigo', $user->codigo_representante);
            $abertos = $titulos_a_vencer->firstWhere('vendedor_codigo', $user->codigo_representante);
            $vencidos = $titulos_vencidos->firstWhere('vendedor_codigo', $user->codigo_representante);
            $virada = $titulos_virada->firstWhere('vendedor_codigo', $user->codigo_representante);

            $lancamento_total = $lancamento['lancamento'][$user->codigo_representante] ?? 0;

            $prepago_aberto = $pedidosPrePagosAbertos->filter(function($pedido_aberto) use($user){
                return $pedido_aberto->pedido->usuario == $user->id;
            })->pluck('pedido_id')->implode(', ');

            $lancamentos = $lancamentosBaixadosObj->filter(function($cheque) use ($user){
                return $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->contains($user->codigo_representante);
            });

            $lancamentosBaixadosPeriodo = 0;

            $lancamentos->each(function($cheque) use (&$lancamentosBaixadosPeriodo){
                foreach($cheque->pedidos_prepagos as $pedidos){
                    $valor = $pedidos->valor_pago;
                    $percentual = $pedidos->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao;

                    $lancamentosBaixadosPeriodo += ROUND($valor * ($percentual/100), 2);
                }
            });
            
            $comissaoLancamentosBaixadosPeriodo = $lancamentos
                ->sum(function($cheque){
                    return $cheque->pedidos_prepagos
                        ->sum(function($pedido){
                            return round($pedido->valor_pago * ($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao/100),2);
                        });
                });
            
            $comissaoLancamentosBaixadosPeriodoGerente = $lancamentos->sum(function($cheque){
                return $cheque->pedidos_prepagos
                    ->sum(function($pedido){
                        return round($pedido->valor_pago * 0.0011, 2);
                    });
            });
            $perfil_acesso = '';
            try{
                $perfil_acesso = empty($user->detalhesModelHasRoles)? '' : $user->detalhesModelHasRoles->detalhesRoles->name;
            }catch(\Exception $e){
                $perfil_acesso = '';
            }
            
            if($user->tipo_usuario_id == 16 || $user->tipo_usuario_id == 13){
                $comissao = ($titulo->comissao_vendedor ?? 0) + $lancamentosBaixadosPeriodo + $lancamento_total;

                if($user->id == 63){
                    $abertos_comissao = ROUND(($abertos->total ?? 0) * ($user->comissao_a/100), 2);
                    $vencidos_comissao = ROUND(($vencidos->total ?? 0) * ($user->comissao_a/100), 2);
                }else{
                    //$abertos_comissao = ROUND(($abertos->saldo_depois_outubro ?? 0) * ($user->comissao_a/100) + ($abertos->saldo_antes_outubro ?? 0) * (0.25/100), 2);
                    $abertos_comissao = $abertos->comissao ?? 0;
                    $vencidos_comissao = ROUND(($vencidos->saldo_depois_outubro ?? 0) * ($user->comissao_a/100) + ($vencidos->saldo_antes_outubro ?? 0) * (0.25/100), 2);
                }

                if($user->responsavel == 17){
                    if(!empty($virada)){
                        $desconto = $virada->comissao;
                    }
                    else{
                        $desconto = 0;
                    }
                }
                else{
                    if(!empty($virada)){
                        $desconto = $virada->comissao + 600;
                    }
                    else{
                        $desconto = 600;
                    }
                }

                $desconto_gerente = $virada->desconto_gerente ?? 0;


            }
            else if($user->tipo_usuario_id == 19 || $user->tipo_usuario_id == 14){
                $comissao = ($titulo->comissao_gerente ?? 0) + $comissaoLancamentosBaixadosPeriodoGerente + $lancamento_total;
                $abertos_comissao = ROUND(($abertos->total ?? 0) * 0.0011, 2);
                $vencidos_comissao = ROUND(($vencidos->total ?? 0) * 0.0011, 2);
                $desconto = $virada->comissao ?? 0;
                $desconto_gerente = $virada->desconto_gerente ?? 0;

            }
            elseif($perfil_acesso == 'REP.Playstation'){
                $DescontoTituloPercentual = isset($titulo->base_comissao) && $titulo->base_comissao > 0 ? ($titulo->valor_desconto*100)/$titulo->base_comissao : 0;
                $DescontoTituloAbertoPercentual = isset($abertos->total) && $abertos->total > 0 ? ($abertos->desconto*100)/$abertos->total : 0;
                $DescontoTituloVencidosPercentual = isset($vencidos->total) && $vencidos->total > 0 ? ($vencidos->desconto*100)/$vencidos->total : 0;

                if($DescontoTituloPercentual > 9 || $DescontoTituloAbertoPercentual > 9 || $DescontoTituloVencidosPercentual > 9){
                    $comissao = ROUND(((isset($titulo->base_comissao) && $titulo->base_comissao ?? 0) + $lancamentosBaixadosPeriodo) * (($user->comissao_a -1)/100), 2) + $lancamento_total;
                    $abertos_comissao = ROUND((isset($abertos->total) && $abertos->total ?? 0) * (($user->comissao_a - 1)/100), 2);
                    $vencidos_comissao = ROUND((isset($vencidos->total) && $vencidos->total ?? 0) * (($user->comissao_a -1)/100), 2);
                    $desconto = $virada->comissao ?? 0;
                }else{
                    $comissao = ROUND((($titulo->base_comissao ?? 0) + $lancamentosBaixadosPeriodo) * ($user->comissao_a/100), 2) + $lancamento_total;
                    $abertos_comissao = ROUND(($abertos->total ?? 0) * ($user->comissao_a/100), 2);
                    $vencidos_comissao = ROUND(($vencidos->total ?? 0) * ($user->comissao_a/100), 2);
                    $desconto = $virada->comissao ?? 0;
                }
                
            }
            else{
                $comissao = ($titulo->valor_comissao ?? 0) + $comissaoLancamentosBaixadosPeriodo + $lancamento_total;
                $abertos_comissao = $abertos->comissao ?? 0;
                $vencidos_comissao = $vencidos->comissao ?? 0;
                $desconto = $virada->comissao ?? 0;
                $desconto_gerente = $virada->desconto_gerente ?? 0;
            }

            $base_comissao = ($titulo->base_comissao ?? 0) + $lancamentosBaixadosPeriodo;
            $comissao_gerente = ($titulo->comissao_gerente ?? 0);
            $comissao_gerente_aberto = ($abertos->comissao_gerente ?? 0);

            $result_total['base_comissao'] 	    += $titulo->base_comissao ?? 0;
            $result_total['a_vencer_valor']     += $abertos->total ?? 0;
            $result_total['vencido_valor']      += $vencidos->total ?? 0;
            $result_total['a_vencer_comissao']  += $abertos_comissao ?? 0;
            $result_total['vencido_comissao']   += $vencidos_comissao ?? 0;
            $result_total['desconto_comissao']  += $desconto;
            $result_total['comissao_gerente']   += $titulo->comissao_gerente ?? 0;
            $result_total['comissao_gerente_aberto']   += $abertos->comissao_gerente ?? 0;

            if(empty($prepago_aberto) && $user->tipo_usuario_id != 19 && $user->tipo_usuario_id != 14){
                $result_total['comissao'] += $comissao ?? 0;
            }

            $verifica_confirmacao_comissao = 'Não';

            if(count($user->confirmacaoComissao) > 0){
                foreach($user->confirmacaoComissao as $comissao_confirmacao){
                    if(!empty($comissao_confirmacao->data_confirmacao)){
                        $data_confirmacao = Carbon::parse($comissao_confirmacao->data_confirmacao)->format('m/Y');

                        if($data_confirmacao == $fields['data'] && $comissao_confirmacao->confirmacao == true){
                            $verifica_confirmacao_comissao = 'Sim';
                        }
                    }
                }
            }
         
            if($user->tipo_usuario_id != 19 && $user->tipo_usuario_id != 14){

                if(isset($user->detalhesRepresentanteCliente->cpf_cnpj) && !empty($user->detalhesRepresentanteCliente->cpf_cnpj)){
                    $representante = $user->name . ' - ' . $user->detalhesRepresentanteCliente->cpf_cnpj;
                }
                else if(isset($user->detalhesRepresentanteFornecedor->cpf_cnpj) && !empty($user->detalhesRepresentanteFornecedor->cnpj_cpf)){
                    $representante = $user->name . ' - ' . $user->detalhesRepresentanteFornecedor->cnpj_cpf;
                }
                else{
                    $representante = $user->name;
                }
                
                $result_titulos[$user->codigo_representante] = [
                    'representante' => $representante,
                    'representante_not_parse' => $user->id,
                    'cod_representante' => $user->codigo_representante,
                    'base_comissao' => $base_comissao > 0 ? $base_comissao : 0,
                    'valor_comissao' => (isset($comissao) && !is_null($comissao) && $comissao != '0.00' ? $comissao : 0),
                    'a_vencer_valor' => isset($abertos->total) && !is_null($abertos->total) && $abertos->total != '0.00' ? $abertos->total : 0,
                    'a_vencer_comissao' => isset($abertos_comissao) && !is_null($abertos_comissao) && $abertos_comissao != '0.00' ? $abertos_comissao : 0,
                    'vencido_valor' => isset($vencidos->total) && !is_null($vencidos->total) && $vencidos->total != '0.00' ? $vencidos->total : 0,
                    'vencido_comissao' => isset($vencidos_comissao) && !is_null($vencidos_comissao) && $vencidos_comissao != '0.00' ? $vencidos_comissao : 0,
                    'desconto_comissao' => ($lancamentosDescontos->isNotEmpty()? "<span class='campo_obrigatorio' data-toggle='tooltip' data-html='true' title='' data-original-title='Já lançado no sistema'>*</span> ":'') . parserValor($desconto),
                    'pre_pago_aberto' => $prepago_aberto,
                    'confirmacao' => $verifica_confirmacao_comissao,
                    'comissao_fechamento' =>  $comissao_fechamento,
                    'periodo' => $fields['data'],
                    'comissao_gerente' => $comissao_gerente,
                    'comissao_gerente_aberto' => $comissao_gerente_aberto,
                ];
            }else{
                $result_titulos[$user->codigo_representante] = [
                    'representante' => $user->name,
                    'representante_not_parse' => $user->id,
                    'cod_representante' => $user->codigo_representante,
                    'base_comissao' => $base_comissao,
                    'a_vencer_valor' => isset($abertos->total) && !is_null($abertos->total) && $abertos->total != '0.00' ? $abertos->total : 0,
                    'vencido_valor' => isset($vencidos->total) && !is_null($vencidos->total) && $vencidos->total != '0.00' ? $vencidos->total : 0,
                    'desconto_comissao' => $desconto,
                    'valor_comissao' => $comissao,
                    'a_vencer_comissao' => $abertos_comissao,
                    'vencido_comissao' => $vencidos_comissao,
                    'pre_pago_aberto' => '',
                    'confirmacao' => $verifica_confirmacao_comissao,
                    'comissao_fechamento' =>  $comissao_fechamento,
                    'periodo' => $fields['data'],
                    'comissao_gerente' => $comissao_gerente,
                    'comissao_gerente_aberto' => $comissao_gerente_aberto,
                ];
            }
           
            if(isset($user->supervisor) && !is_null($user->supervisor->codigo_representante)){
                if(isset($abertos->total) && !is_null($abertos->total) && $abertos->total != '0.00'){
                    $a_vencer_valor = $abertos->total;
                }else{
                    $a_vencer_valor = 0;
                }

                if(isset($vencidos->total) && !is_null($vencidos->total) && $vencidos->total != '0.00'){
                    $vencido_valor = $vencidos->total;
                }else{
                    $vencido_valor = 0;
                }

                $result_titulos_gerente[$user->supervisor->id][$user->codigo_representante] = [
                    'cod_representante' => $user->supervisor->codigo_representante,
                    'base_comissao' => $base_comissao,
                    'a_vencer_valor' =>  $a_vencer_valor,
                    'vencido_valor' => $vencido_valor,
                    'valor_comissao' => round($base_comissao * ($user->supervisor->comissao_a/100), 2),
                    'a_vencer_comissao' => round($a_vencer_valor * ($user->supervisor->comissao_a/100), 2),
                    'vencido_comissao' => round($vencido_valor * ($user->supervisor->comissao_a/100), 2),
                    'pre_pago_aberto' => '',
                    'confirmacao' => $verifica_confirmacao_comissao,
                    'comissao_fechamento' =>  $comissao_fechamento,
                    'periodo' => $fields['data'],
                    'comissao_gerente' => $comissao_gerente,
                    'comissao_gerente_aberto' => $comissao_gerente_aberto,
                ];
            }
  
            if(isset($fields['confirmacao']) && $fields['confirmacao'] == null){
                if(!is_null($user->supervisor) && ($user->supervisor->tipo_usuario_id == 14 || $user->supervisor->tipo_usuario_id == 19) && (empty($fields['representantes']) || $fields['representantes'] == $user->supervisor->codigo_representante)){
                    if(!isset($result_titulos[$user->supervisor->codigo_representante])){

                        $result_titulos[$user->supervisor->codigo_representante] = [
                            'representante' => $user->supervisor->name,
                            'representante_not_parse' => $user->supervisor->id,
                            'cod_representante' => $user->supervisor->codigo_representante,
                            'base_comissao' => $base_comissao,
                            'a_vencer_valor' => isset($abertos->total) && !is_null($abertos->total) && $abertos->total != '0.00' ? $abertos->total : 0,
                            'vencido_valor' => isset($vencidos->total) && !is_null($vencidos->total) && $vencidos->total != '0.00' ? $vencidos->total : 0,
                            'desconto_comissao' => 0,
                            'valor_comissao' => ($titulo->comissao_gerente ?? 0) + $comissaoLancamentosBaixadosPeriodoGerente,
                            'a_vencer_comissao' => isset($abertos->comissao_gerente) && !is_null($abertos->comissao_gerente) && $abertos->comissao_gerente != '0.00' ? $abertos->comissao_gerente : 0,
                            'vencido_comissao' => isset($vencidos->comissao_gerente) && !is_null($vencidos->comissao_gerente) && $vencidos->comissao_gerente != '0.00' ?$vencidos->comissao_gerente : 0,
                            'pre_pago_aberto' => '',
                            'confirmacao' => $verifica_confirmacao_comissao,
                            'comissao_fechamento' =>  $comissao_fechamento,
                            'periodo' => $fields['data'],
                            'comissao_gerente' => $comissao_gerente,
                        ];
                    }
                    else{
                        $result_titulos[$user->supervisor->codigo_representante]['valor_comissao'] += ($titulo->comissao_gerente ?? 0) + $comissaoLancamentosBaixadosPeriodoGerente;
                        $result_titulos[$user->supervisor->codigo_representante]['a_vencer_comissao'] += isset($abertos->comissao_gerente) && !is_null($abertos->comissao_gerente) && $abertos->comissao_gerente != '0.00' ? $abertos->comissao_gerente : 0;
                        $result_titulos[$user->supervisor->codigo_representante]['vencido_comissao'] += isset($vencidos->comissao_gerente) && !is_null($vencidos->comissao_gerente) && $vencidos->comissao_gerente != '0.00' ? $vencidos->comissao_gerente : 0;

                        $result_titulos[$user->supervisor->codigo_representante]['base_comissao'] += $base_comissao;
                        $result_titulos[$user->supervisor->codigo_representante]['a_vencer_valor'] += isset($abertos->total) && !is_null($abertos->total) && $abertos->total != '0.00' ? $abertos->total : 0;
                        $result_titulos[$user->supervisor->codigo_representante]['vencido_valor'] += isset($vencidos->total) && !is_null($vencidos->total) && $vencidos->total != '0.00' ? $vencidos->total : 0; 
                    }
                }
            }
        });
        
        /***
         *  Comissão supervisor
         */
        $representantesObj->each(function($query) use (&$result_titulos){
            
            if(!empty($query->detalhesModelHasRoles->detalhesRoles) && $query->detalhesModelHasRoles->detalhesRoles->name == 'Supervisor Playstation'){
                $comissao = $result_titulos[$query->codigo_representante]['base_comissao'];
                $result_titulos[$query->codigo_representante]['valor_comissao'] = ($query->comissao_a / 100) * $comissao;
                $result_titulos[$query->codigo_representante]['base_comissao'] = $comissao;
            }

            if(!empty($query->supervisorEquipe[0])){
                $comissao = 0;

                foreach($query->supervisorEquipe as $supervisor_equipe){
                    $result_titulos[$supervisor_equipe['codigo_representante']];
                    if(isset($result_titulos[$supervisor_equipe['codigo_representante']]['base_comissao'])){
                        $comissao += $result_titulos[$supervisor_equipe['codigo_representante']]['base_comissao'];
                    }
                }

                $result_titulos[$query->codigo_representante]['valor_comissao'] += ($query->comissao_c / 100) * $comissao;
                $result_titulos[$query->codigo_representante]['base_comissao'] += $comissao;
            }
        });

        foreach($result_titulos_gerente as $supervisor => $subordinados){
            foreach($result_titulos_gerente[$supervisor] as $subordinado){
                if(!empty($result_titulos[$subordinado['cod_representante']])){
                    $result_titulos[$subordinado['cod_representante']]['base_comissao'] += $subordinado['base_comissao'];
                    $result_titulos[$subordinado['cod_representante']]['a_vencer_valor'] += $subordinado['a_vencer_valor'];
                    $result_titulos[$subordinado['cod_representante']]['valor_comissao'] += $subordinado['comissao_gerente'];
                    $result_titulos[$subordinado['cod_representante']]['a_vencer_comissao'] += $subordinado['comissao_gerente_aberto'];
                    $result_titulos[$subordinado['cod_representante']]['vencido_comissao'] += $subordinado['vencido_comissao'];
                }                
            }
        }
        
		$result_total = [
			'comissao' => isset($result_total['comissao']) && !is_null($result_total['comissao']) && $result_total['comissao'] != '0.00' ? parserValor($result_total['comissao']) : '',
			'base_comissao' => isset($result_total['base_comissao']) && !is_null($result_total['base_comissao']) && $result_total['base_comissao'] != '0.00' ? parserValor($result_total['base_comissao']) : '',
			'a_vencer_valor' => isset($result_total['a_vencer_valor']) && !is_null($result_total['a_vencer_valor']) && $result_total['a_vencer_valor'] != '0.00' ? parserValor($result_total['a_vencer_valor']) : '',
			'a_vencer_comissao' => isset($result_total['a_vencer_comissao']) && !is_null($result_total['a_vencer_comissao']) && $result_total['a_vencer_comissao'] != '0.00' ? parserValor($result_total['a_vencer_comissao']) : '',
			'vencido_valor' => isset($result_total['vencido_valor']) && !is_null($result_total['vencido_valor']) && $result_total['vencido_valor'] != '0.00' ? parserValor($result_total['vencido_valor']) : '',
			'vencido_comissao' => isset($result_total['vencido_comissao']) && !is_null($result_total['vencido_comissao']) && $result_total['vencido_comissao'] != '0.00' ? parserValor($result_total['vencido_comissao']) : '',
			'desconto_comissao' => isset($result_total['desconto_comissao']) && !is_null($result_total['desconto_comissao']) && $result_total['desconto_comissao'] != '0.00' ? parserValor($result_total['desconto_comissao']) : '',
            'comissao_gerente' => isset($result_total['comissao_gerente']) && !is_null($result_total['comissao_gerente']) && $result_total['comissao_gerente'] != '0.00' ? parserValor($result_total['comissao_gerente']) : '',
        ];
        
        if(isset($fields['representantes']) && !empty($fields['representantes'])){
			$usuario = User::where('id', $fields['representantes'])->first();
            if(empty($usuario)){
                $return = [
                    'status' => 'error',
                    'message' => '',
                    'error' => [],
                    'response' => null
                ];
                return response()->json($return);
            }else if(!isset($result_titulos[$usuario->codigo_representante])){
                $return = [
                    'status' => 'error',
                    'message' => '',
                    'error' => [],
                    'response' => null
                ];
                return response()->json($return);
            }
            $result_titulos = [$result_titulos[$usuario->codigo_representante]];
        }
        else if(isset(Auth::user()->tipo_usuario_id) && in_array(Auth::user()->tipo_usuario_id, [16, 12, 22])){
            $result_titulos = [$result_titulos[Auth::user()->codigo_representante]];
        }
        foreach($result_titulos as $vendedor => $titulo){
            if(
                empty($titulo['base_comissao']) &&
                empty($titulo['valor_comissao']) &&
                empty($titulo['a_vencer_valor']) &&
                empty($titulo['a_vencer_comissao']) &&
                empty($titulo['vencido_valor']) &&
                empty($titulo['vencido_comissao']) &&
                (empty($titulo['desconto_comissao']) || $titulo['desconto_comissao'] == '0,00')
            ){
                unset($result_titulos[$vendedor]);
            }
        }

        if(!empty($representante_gerente)){
            $fields['representantes'] = $representante_gerente;
        }
        if(
            (
                isset(Auth::user()->tipo_usuario_id) &&
                !in_array(Auth::user()->tipo_usuario_id,[12,16,22,19, 14,13])
            ) && (
                !isset($fields['api']) ||
                $fields['api'] != true
            ) && (
                !in_array(Auth::id(),[11194, 10046])
            ) || (
                !isset(Auth::user()->tipo_usuario_id)
            )
            
        ) {
	        $codigo_representantes = [];
	        if(!empty($fields['representantes'])){
                $usuario = User::with('subordinados')->where('id', $fields['representantes'])->first();

                $codigo_representantes = [];

                $codigo_representantes[] = $usuario->codigo_representante;
	        }else{
				$representantes_busca = User::select('id', 'codigo_representante', 'name');
				$representantes_busca->where('codigo_representante', '!=', '');
				$representantes_busca->whereNotNull('codigo_representante');

                if(isset($fields['tipo']) && !empty($fields['tipo'])){
                    $representantes_busca->where('tipo_usuario_id', $fields['tipo']);
                }

                $result = $representantes_busca->get();

                $codigo_representantes = $result->pluck('codigo_representante');

                $codigo_representantes = $codigo_representantes->toArray();
                
                unset($representantes_busca, $result);
	        }
	    }else if(in_array(Auth::id(),[11194, 10046])){
            if(Auth::id() == 11194){
                $codigo_representantes = $this->subordinado_supervisor_playstation_11194;
            }else if(Auth::id() == 10046){
                $codigo_representantes = $this->subordinado_supervisor_playstation_10046;
            }
        }else{
            $codigo_representantes = [];
            if(!empty($fields['representantes'])){
                $usuario = User::with('subordinados')->where('id', $fields['representantes'])->first();
                if(!empty($usuario)){
                    $codigo_representantes[] = $usuario->codigo_representante;
                }
            }else{
                $usuario = User::with(['subordinados' => function($query){
                    $query->whereNotNull('codigo_representante');
                }])->find(Auth::id());
                if(!empty($usuario)){
                    if($usuario->tipo_usuario_id == 19 || $usuario->tipo_usuario_id == 14){
                        $codigo_representantes = $usuario->subordinados->pluck('codigo_representante')->toArray();
                    }else if($usuario->tipo_usuario_id == 13){
                        $codigo_representantes = $usuario->supervisor->subordinados->pluck('codigo_representante')->toArray();
                    }
                    $codigo_representantes[] = Auth::user()->codigo_representante;
                }
            }
        }

        $result_total["comissao"] = 0; 
        $result_total["base_comissao"] = 0; 
        $result_total["a_vencer_valor"] = 0; 
        $result_total["a_vencer_comissao"] = 0; 
        $result_total["vencido_valor"] = 0; 
        $result_total["vencido_comissao"] = 0; 
        $result_total["desconto_comissao"] = 0;

        foreach ($result_titulos as $index => $value){
            if(!in_array($value['cod_representante'], $codigo_representantes)){
                unset($result_titulos[$index]);
            }else{
                if(!empty($value["valor_comissao"])){
                    $result_total["comissao"] += is_numeric($value['valor_comissao'])? $value['valor_comissao'] : parserNumber($value['valor_comissao']);
                }
                if(!empty($value["base_comissao"])){
                    $result_total["base_comissao"] += is_numeric($value['base_comissao'])? $value['base_comissao'] : parserNumber($value['base_comissao']);
                }
                if(!empty($value["a_vencer_valor"])){
                    $result_total["a_vencer_valor"] += is_numeric($value['a_vencer_valor'])? $value['a_vencer_valor'] : parserNumber($value['a_vencer_valor']);
                }
                if(!empty($value["a_vencer_comissao"])){
                    $result_total["a_vencer_comissao"] += is_numeric($value['a_vencer_comissao'])? $value['a_vencer_comissao'] : parserNumber($value['a_vencer_comissao']);
                }
                if(!empty($value["vencido_valor"])){
                    $result_total["vencido_valor"] += is_numeric($value['vencido_valor'])? $value['vencido_valor'] : parserNumber($value['vencido_valor']);
                }
                if(!empty($value["vencido_comissao"])){
                    $result_total["vencido_comissao"] += is_numeric($value['vencido_comissao'])? $value['vencido_comissao'] : parserNumber($value['vencido_comissao']);
                }
                if(!empty($value["desconto_comissao"])){
                    $result_total["desconto_comissao"] += is_numeric($value['desconto_comissao'])? $value['desconto_comissao'] : parserNumber(str_replace("<span class='campo_obrigatorio' data-toggle='tooltip' data-html='true' title='' data-original-title='Já lançado no sistema'>*</span> ", "", $value['desconto_comissao']));
                }                
            }
        }
        unset($result_titulos['-1']);
        $result_titulos = $this->ajusteArrayParaValores($result_titulos);
        $result_total = $this->ajusteArrayParaValores($result_total);

		$retorno = [
			'titulos' => array_values($result_titulos),
            'total' => $result_total,
            'data_inicio' => Carbon::parse($data_inicio)->format('d/m/Y'),
            'data_fim' => Carbon::parse($data_fim)->format('d/m/Y')
		];
        if($array_retorno){
            return $retorno;
        }
		$return = [
			'status' => 'success',
			'message' => '',
            'error' => [],
            'response' => $retorno
		];
		return response()->json($return);
	}

	public function dialog(ComissaoDialogRequest $request, $array_retorno = false){
        ini_set('memory_limit', '2024M');
		$fields = $request->only(['representante', 'data_inicio', 'data_fim', 'estabelecimento', 'api']);

		$data_inicio = Carbon::createFromFormat("d/m/Y", $fields['data_inicio'])->format('Y-m-d');
		$data_fim = Carbon::createFromFormat("d/m/Y", $fields['data_fim'])->format('Y-m-d');

        $usuario_id = empty(Auth::id())? 1 : Auth::id();
        $usuario_tipo_id = empty(Auth::user())? 1 : Auth::user()->tipo_usuario_id;

        $representante = User::with('subordinados', 'detalhesModelHasRoles.detalhesRoles','supervisorEquipe')->where('id', $fields['representante'])->first();
        $vendedores_internos = User::where('tipo_usuario_id', 16)->get()->pluck('codigo_representante');
        
        $tituloPagamentoNasajon = new TituloPagamentoNasajon;
        $vendedorTituloNasajon = new VendedorTituloNasajon;
        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', '<', date('Y-m-d'))->orderBy('data_fim', 'desc')->first();

        $grupo_acesso = empty($representante->detalhesModelHasRoles)? '' : $representante->detalhesModelHasRoles->detalhesRoles->name;
		
        $queryTitulos = TituloPagamentoNasajon::
            with('comissaoVendedorTitulo', 'cliente','campanhaComissao.comissaoItens.campanha.produtosCampanha')
            ->select('*')
            ->leftjoin($vendedorTituloNasajon->getTable(), function($join) use($vendedorTituloNasajon,$tituloPagamentoNasajon)
            {
                $join->on($vendedorTituloNasajon->getTable().'.tituloreceber', '=', $tituloPagamentoNasajon->getTable() . '.id_titulo');
                $join->on($vendedorTituloNasajon->getTable().'.vendedor_codigo', '=', $tituloPagamentoNasajon->getTable() . '.vendedor_codigo');
            })
            ->whereBetween(DB::raw(
                '(CASE
                    WHEN \''.$data_inicio.'\' >= \'2021-05-26\' AND data_pagamento >= \'2021-05-26\' AND data_lancamento_pagamento >= \'2021-05-26\' THEN data_lancamento_pagamento
                    ELSE data_pagamento
                END)'), [$data_inicio, $data_fim])
            ->whereNotIn('cod_cliente', $this->cnpj_excluir)
            ->where(function($query){
                $query->where(function($credito){
                    $credito->where('pagamento_com_credito', true)
                    ->where('formapagamento_descricao','Usar Crédito');
                })
                ->orWhere('formapagamento_descricao', '!=', 'Usar Crédito');
            })
            ->whereNotIn('conta_codigo', ['PERDA CONCRETIZADA'])
            ->where($tituloPagamentoNasajon->getTable().'.vendedor_codigo','!=', '998');

        $chequesBaixadosQuery = Cheque::
            with([
                'pedidos_prepagos' =>  function($query) use ($data_inicio, $data_fim){
                    $query->whereBetween('created_at', [$data_inicio.' 00:00:00', $data_fim.' 23:59:59']);
                },
                'pedidos_prepagos.pedido',
                'pedidos_prepagos.pedido.pedido',
                'pedidos_prepagos.pedido.pedidoNasajon',
                'pedidos_prepagos.pedido.pedidoNasajon.nota',
                'pedidos_prepagos.pedido.pedidoNasajon.cliente_detalhes',
                'pedidos_prepagos.pedido.pedidoNasajon.nota.revisao_vendedor_comissao',
                'pedidos_prepagos.pedido.pedidoNasajon.nota.campanhas.comissaoItens',
                
            ])
			->whereHas('pedidos_prepagos', function($query) use ($data_inicio, $data_fim){
				$query->whereBetween('created_at', [$data_inicio.' 00:00:00', $data_fim.' 23:59:59']);
			})
            ->where('tipo', '!=', 'cancelamento')
            ->where('status', '!=', 'devolvido');

        if($grupo_acesso == 'REP.Playstation'){
            $queryTitulos->addSelect(
                DB::Raw('ROUND("valor" * '. $representante->comissao_a / 100 . ',2) AS comissao,
                ' . $representante->comissao_a .' AS comissao_porcentagem')
            );
        }else if($representante->tipo_usuario_id == 16){
            if(in_array($representante->id, [63])){
                $queryTitulos->addSelect(DB::Raw(
                    'CASE 
                        WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN 
                            0.0
                        WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 
                            ROUND("valor" * (2/100),2) 
                        WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                            ROUND("valor" * 0.75/100,2) 
                        WHEN  emissao >= \'2023-02-01\' THEN
                            percentual_comissao
                        WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                            ROUND("valor" * '. $representante->comissao_a / 100 . ',2) 
                        WHEN  emissao >= \'2022-10-01\' and percentual_comissao < 0.30 THEN
                            ROUND("valor" * (percentual_comissao/100),2) 
                        WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                            ROUND("valor" * (percentual_comissao/100),2) 
                        ELSE ROUND("valor" * 0.75/100,2) 
                    END as comissao,
                    CASE 
                        WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN \'0.0\'
                        WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 
                            2.0
                        WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                            0.75
                        WHEN  emissao >= \'2023-02-01\' THEN
                            percentual_comissao
                        WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                            '. $representante->comissao_a . ' 
                        WHEN  emissao >= \'2022-10-01\' and emissao < \'2023-10-01\' and percentual_comissao < 0.30 THEN
                            percentual_comissao
                        WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                            percentual_comissao
                        ELSE 0.75
                    END as comissao_porcentagem'));
            }else{
                $queryTitulos->addSelect(DB::Raw(
                    'CASE 
                        WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN 
                            0.0
                        WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 
                            ROUND("valor" * (2/100),2) 
                        WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                            ROUND("valor" * 0.30/100,2) 
                        WHEN  emissao >= \'2023-02-01\' THEN
                            percentual_comissao
                        WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                            ROUND("valor" * '. $representante->comissao_a / 100 . ',2) 
                        WHEN  emissao >= \'2022-10-01\' and percentual_comissao < 0.30 THEN
                            ROUND("valor" * (percentual_comissao/100),2) 
                        WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                            ROUND("valor" * (percentual_comissao/100),2) 
                        ELSE ROUND("valor" * 0.25/100,2) 
                    END as comissao,
                    CASE 
                        WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN \'0.0\'
                        WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 
                            2.0
                        WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                            0.30
                        WHEN  emissao >= \'2023-02-01\' THEN
                            percentual_comissao
                        WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                            '. $representante->comissao_a . ' 
                        WHEN  emissao >= \'2022-10-01\' and emissao < \'2023-10-01\' and percentual_comissao < 0.30 THEN
                            percentual_comissao
                        WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                            percentual_comissao
                        ELSE 0.25
                    END as comissao_porcentagem'));
            }

        }else if($representante->tipo_usuario_id == 13){
            if(in_array($representante->id, [108, 610, 9087])){
                $queryTitulos->addSelect(DB::Raw(
                'CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN 
                        0.0
                    WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN ROUND("valor" * (2/100),2) 
                    WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        ROUND("valor" * 0.30/100,2) 
                    WHEN  emissao >= \'2023-02-01\' THEN
                        percentual_comissao
                    WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        ROUND("valor" * '. $representante->comissao_a / 100 . ',2) 
                    WHEN  emissao >= \'2022-10-01\' and percentual_comissao < 0.30 THEN
                        ROUND("valor" * (percentual_comissao/100),2) 
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        ROUND("valor" * (percentual_comissao/100),2) 
                    ELSE ROUND("valor" * 0.25/100,2) 
                END as comissao,
                CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and documento_numero = \'000028381\' THEN \'0.0\'
                    WHEN documento_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 2
                    WHEN  emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        0.30
                    WHEN  emissao >= \'2023-02-01\' THEN
                        percentual_comissao
                    WHEN  (emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        '. $representante->comissao_a . ' 
                    WHEN  emissao >= \'2022-10-01\' and emissao < \'2023-10-01\' and percentual_comissao < 0.30 THEN
                        percentual_comissao
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        percentual_comissao
                    ELSE 0.25
                END as comissao_porcentagem'));
            }else{
                $queryTitulos->addSelect(DB::Raw(
                    'ROUND("valor" * percentual_comissao / 100,2) as comissao,
                    percentual_comissao as comissao_porcentagem'));
            }
        }else if($representante->tipo_usuario_id == 19 || $representante->tipo_usuario_id == 14){
            $queryTitulos->addSelect(DB::Raw(
                'CASE 
                    WHEN  '.$vendedorTituloNasajon->getTable().'.vendedor_codigo = \''.$representante->codigo_representante.'\' and percentual_comissao >= 0.11 and percentual_comissao <= 0.33 THEN
                        ROUND("valor" * percentual_comissao,2) 
                    ELSE ROUND("valor" * 0.0011,2)
                END as comissao,
                CASE 
                    WHEN  '.$vendedorTituloNasajon->getTable().'.vendedor_codigo  = \''.$representante->codigo_representante.'\' and percentual_comissao >= 0.11 and percentual_comissao <= 0.33 THEN
                        ROUND(percentual_comissao,2) 
                    ELSE 0.11
                END as comissao_porcentagem'));
        }else{
            $queryTitulos->addSelect(DB::Raw(
                'CASE 
                    WHEN '.$tituloPagamentoNasajon->getTable().'.banco_nome = \'Juridico Ragazzi\' THEN 1
                    ELSE percentual_comissao
                END as comissao_porcentagem'));
        }
        
        if(isset($fields['estabelecimento']) && !is_null($fields['estabelecimento'])){
            $queryTitulos->where('codigo', str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT));
            $chequesBaixadosQuery->whereHas('pedidos_prepagos.pedido.pedido', function($query) use($fields){
                $query->where('estabelecimento', $fields['estabelecimento']);
            });
        }
        else{
            $queryTitulos->where('codigo', '!=', '20');
        }

        $lancamentosBaixadosObj = $chequesBaixadosQuery->get();
        
        if($representante->codigo_representante == '001'){
            $usuarios[] = '001';
			$queryTitulos->where(function($query) use ($tituloPagamentoNasajon){
				$query->where($tituloPagamentoNasajon->getTable() . '.vendedor_codigo', '001')
					->orWhereNull($tituloPagamentoNasajon->getTable() . '.vendedor_codigo');
            });

            $lancamentosBaixadosObj = $lancamentosBaixadosObj->filter(function($cheque){
                return $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->contains('001') || $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->flatten()->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->contains(null);
            });
        }
        else if($representante->tipo_usuario_id == 19 || $representante->tipo_usuario_id == 14){
            $usuarios = User::where('responsavel', $representante->id)->get()->pluck('codigo_representante')->filter()->toArray();
            $usuarios[] = $representante->codigo_representante;

            //$queryTitulos->whereIn($tituloPagamentoNasajon->getTable() . '.vendedor_codigo', $usuarios);

            $lancamentosBaixadosObj = $lancamentosBaixadosObj->filter(function($cheque) use ($usuarios){
                return $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->intersect($usuarios)->isNotEmpty();
            });
        }
		else{
            /***
            *  Comissão supervisor
            */
            if(!empty($representante->supervisorEquipe[0])){
                $usuarios = [];
                $usuarios[] = $representante->codigo_representante;

                foreach($representante->supervisorEquipe as $supervisor_equipe){
                    $usuarios[] = $supervisor_equipe->codigo_representante;
                }
                
                $queryTitulos->whereIn($tituloPagamentoNasajon->getTable() . '.vendedor_codigo', $usuarios);
            }else{
                $usuarios[] = $representante->codigo_representante;
                $queryTitulos->where($tituloPagamentoNasajon->getTable() . '.vendedor_codigo', $representante->codigo_representante);
            }

            $lancamentosBaixadosObj = $lancamentosBaixadosObj->filter(function($cheque) use ($representante){
                /***
                *  Comissão supervisor
                */
                if(!empty($representante->supervisorEquipe[0])){
                    $usuarios = [];
                    $usuarios[] = $representante->codigo_representante;

                    foreach($representante->supervisorEquipe as $supervisor_equipe){
                        $usuarios[] = $supervisor_equipe->codigo_representante;
                    }

                    $cheques = $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->toArray();

                    foreach($usuarios as $usuario){
                        if(in_array($usuario,$cheques)){
                            return true;
                        } 
                    }
                    return false;
                }else{
                    return $cheque->pedidos_prepagos->pluck('pedido')->flatten()->pluck('pedidoNasajon')->pluck('nota')->pluck('revisao_vendedor_comissao')->pluck('vendedor_codigo')->contains($representante->codigo_representante);
                }
            });
        }
		
		$titulosObj = $queryTitulos
			->where('numero', 'not ilike', '%ND')
			->where('valor', '>', 0)
            ->get();

		$return = [
			'titulos' => [],
			'total' => [
				'valor_total' => 0,
                'comissao' => 0,
                'desconto' => 0,
                'valor_com_desconto' => 0
			]
		];

        $estabelecimentos = returnEmpresasNasajonView();

		$titulosObj->each(function($titulo) use (&$return, $estabelecimentos, $representante, $comissaoDataFechamentoObj, $usuario_tipo_id, $usuario_id, $grupo_acesso, $usuarios){
            if(in_array($titulo->vendedor_codigo, $usuarios)){
                $linha = [];

                $linha['estabelecimento'] = $estabelecimentos[intval($titulo->codigo)];
                $linha['emissao'] = parserData($titulo->emissao);
                $linha['vencimento'] = parserData($titulo->vencimento);
                $linha['data_emissao'] = parserData($titulo->emissao);
                $linha['data_pagamento'] = parserData($titulo->data_pagamento);
                $linha['data_lancamento'] = parserData($titulo->data_lancamento_pagamento);
                $linha['duplicata'] = $titulo->numero;
                $linha['parcela'] = $titulo->parcela;
                $linha['nota_id'] = $titulo->documento_id;
                $linha['nota_numero'] = $titulo->documento_numero;
    
                if(isset($titulo->cliente) && !empty($titulo->cliente)){
                    $linha['cliente'] = $titulo->cliente->nome . ' - ' . $titulo->cliente->cpf_cnpj;
                }
                else{
                    $linha['cliente'] = '';
                }
    
                $tituloValor = $titulo->valor;
                
                $linha['valor'] = parserValor($tituloValor);
                $linha['id'] = $titulo->documento_id;
                $linha['id_titulo'] = $titulo->id_titulo;
                $linha['numero_documento'] = $titulo->documento_numero;
                $linha['vendedor_codigo'] = $titulo->vendedor_codigo;
    
                $linha['editavel'] = true;    

                $informativo_campanha = '';
                $verifica_campanha = [];

                if(isset($titulo->campanhaComissao->comissaoItens) && !empty($titulo->campanhaComissao->comissaoItens)){
                    foreach($titulo->campanhaComissao->comissaoItens as $produtos){
                        if(!empty($produtos->campanha->nome)){
                            if(!in_array($produtos->campanha->nome,$verifica_campanha)){
                                if(empty($informativo_campanha)){
                                    $informativo_campanha .= "'".$produtos->campanha->nome."' ";
                                }else{
                                    $informativo_campanha .= ",'".$produtos->campanha->nome."' ";
                                }
                            }
                        }                        
                    }
                }

                $linha['informativo_campanha'] = $informativo_campanha;

                if(!empty($informativo_campanha)){
                    $informativo_campanha = "<i class=\"btn-informacao-sem-alinhamento\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"\" data-original-title=\"Campanha(s) participantes: ".$informativo_campanha."\" style=\"color: black;\"></i>";
                }
                
                if($grupo_acesso == 'REP.Playstation'){
                   $DescontoTituloPercentual = $titulo->valor > 0 ? ($titulo->valor_desconto*100)/$titulo->valor : 0;
    
                   if($DescontoTituloPercentual > 9){
                        $comissao = round($titulo->valor * (($titulo->comissao_porcentagem - 1)/100), 2);
                        $comissao_porcentagem = $titulo->comissao_porcentagem - 1;
                   }
                }
    
                /***
                 *  Comissão supervisor
                 */
                
                if(!empty($representante->supervisorEquipe[0]) || $representante->detalhesModelHasRoles->detalhesRoles->name == 'Supervisor Playstation'){
                    if($titulo->vendedor_codigo == $representante->codigo_representante){
                        $comissao = round($titulo->valor * ($representante->comissao_a / 100), 2);
                        $comissao_porcentagem = $representante->comissao_a;
                    }else{
                        $comissao = round($titulo->valor * ($representante->comissao_c / 100), 2);
                        $comissao_porcentagem = $representante->comissao_c;
                    }

                }else{
                    $comissao = round($titulo->valor * ($titulo->comissao_porcentagem/100), 2);
                    $comissao_porcentagem = $titulo->comissao_porcentagem;
                }

                if(!empty($titulo->documento_id) || empty($titulo->documento_id) && $titulo->banco_nome == 'Juridico Ragazzi'){
                    
                    if(in_array($representante->tipo_usuario_id, [19, 14])) {
                        if(isset($titulo->campanhaComissao) && !empty($titulo->campanhaComissao)){
                            $comissao_porcentagem = $titulo->campanhaComissao->porcetagem_comissao_gerente;
                            $comissao = round($titulo->valor * ($titulo->campanhaComissao->porcetagem_comissao_gerente/100), 2);

                            $linha['comissao'] = parserValor($comissao);
                            $linha['porcentagem'] = parserQtd($comissao_porcentagem) . '%'.$informativo_campanha;
                        }else{
                            $linha['comissao'] = parserValor($comissao);
                            $linha['porcentagem'] = parserQtd($comissao_porcentagem) . '%'.$informativo_campanha;
                        }                        
                    }else{
                        $linha['comissao'] = parserValor($comissao);
                        $linha['porcentagem'] = parserQtd($comissao_porcentagem) . '%'.$informativo_campanha;
                    }                    
                }
                else{
                    if(empty($titulo->documento_id) && in_array($representante->tipo_usuario_id, [19, 14])) {
                        $linha['comissao'] = parserValor($comissao);
                        $linha['porcentagem'] = parserQtd($comissao_porcentagem) . '%'.$informativo_campanha;
                    }else{
                        if($representante->tipo_usuario_id == 16){
                            $linha['comissao'] = parserValor($comissao);
                            $linha['porcentagem'] = parserQtd($comissao_porcentagem) . '%'.$informativo_campanha;
                        }else if(isset($titulo->comissaoVendedorTitulo->firstWhere('vendedor_codigo', $titulo->vendedor_codigo)->percentual_comissao)){
                            $tituloValor = $titulo->valor;

                            $comissao = round($titulo->valor * ($titulo->comissaoVendedorTitulo->firstWhere('vendedor_codigo', $titulo->vendedor_codigo)->percentual_comissao)/100, 2);
                            $linha['comissao'] = parserValor($comissao);
                            $linha['porcentagem'] = parserValor($titulo->comissaoVendedorTitulo->firstWhere('vendedor_codigo', $titulo->vendedor_codigo)->percentual_comissao) . '%'.$informativo_campanha;
                        }
                        else{
                            $linha['comissao'] = parserValor(0);
                            $linha['porcentagem'] = parserValor(0) . '%'.$informativo_campanha;
                        }
                    }
                }
                $return['titulos'][] = $linha;
    
                $return['total']['valor_total'] += $tituloValor;
                $return['total']['comissao'] += $comissao;
            }
        });
        
        $lancamentosBaixadosObj->each(function($lancamento) use (&$return, $estabelecimentos, $representante, $comissaoDataFechamentoObj, $usuario_tipo_id, $usuario_id, $grupo_acesso){
            $lancamento->pedidos_prepagos->each(function($pedido) use (&$return, $estabelecimentos, $representante, $lancamento, $comissaoDataFechamentoObj, $usuario_tipo_id, $usuario_id, $grupo_acesso){
                if($representante->tipo_usuario_id == 16 || $representante->tipo_usuario_id == 13){
                    $comissaoLacamento = $pedido->valor_pago * ($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao/100);
                    $porcentagemLacamento = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao;
                }
                elseif($grupo_acesso == 'REP.Playstation'){
                    $DescontoPedidoPercentual = $pedido->valor_pago > 0 ? ($pedido->pedido->pedido->valor_desconto*100)/$pedido->valor_pago : 0;

                    if($DescontoPedidoPercentual > 9){
                        $comissaoLacamento = $pedido->valor_pago * (($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao - 1)/100);
                        $porcentagemLacamento = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao - 1;
                    }else{
                        $comissaoLacamento = $pedido->valor_pago * ($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao/100);
                        $porcentagemLacamento = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao;
                    }
                }
                else if($representante->tipo_usuario_id == 19 || $representante->tipo_usuario_id == 14){
                    $comissaoLacamento = $pedido->valor_pago * 0.0011;
                    $porcentagemLacamento = 0.11;
                }
                else{
                    $comissaoLacamento = $pedido->valor_pago * ($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao/100);
                    $porcentagemLacamento = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao;
                }
                
                /***
                 *  Comissão supervisor
                 */
                if(!empty($representante->supervisorEquipe[0]) || $representante->detalhesModelHasRoles->detalhesRoles->name == 'Supervisor Playstation'){
                    if($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->vendedor_codigo == $representante->codigo_representante){
                        $porcentagemLacamento = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao;
                        $comissaoLacamento = $pedido->valor_pago * ($pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->percentual_comissao / 100);
                    }else{
                        $porcentagemLacamento = $representante->comissao_c;
                        $comissaoLacamento = $pedido->valor_pago * ($representante->comissao_c / 100);
                    }
                }

                $informativo_campanha = '';
                $verifica_campanha = [];

                if(isset($pedido->pedido->pedidoNasajon->nota->campanhas->comissaoItens) && !empty($pedido->pedido->pedidoNasajon->nota->campanhas->comissaoItens)){
                    foreach($pedido->pedido->pedidoNasajon->nota->campanhas->comissaoItens as $produtos){
                        if(!empty($produtos->campanha->nome)){
                            if(!in_array($produtos->campanha->nome,$verifica_campanha)){
                                if(empty($informativo_campanha)){
                                    $informativo_campanha .= "'".$produtos->campanha->nome."' ";
                                }else{
                                    $informativo_campanha .= ",'".$produtos->campanha->nome."' ";
                                }
                            }
                        }                        
                    }
                }

                $linha = [];
                $linha['informativo_campanha'] = $informativo_campanha;

                if(!empty($informativo_campanha)){
                    $informativo_campanha = "<i class=\"btn-informacao-sem-alinhamento\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"\" data-original-title=\"Campanha(s) participantes: ".$informativo_campanha."\" style=\"color: black;\"></i>";
                }


                $data_pagamento = !is_null($lancamento->bom_para) ? $lancamento->bom_para : $lancamento->created_at;

                $linha['estabelecimento'] = $estabelecimentos[intval($pedido->pedido->pedido->estabelecimento)];
                $linha['emissao'] = parserData($lancamento->created_at);
                $linha['vencimento'] = parserData($lancamento->created_at);
                $linha['data_emissao'] = parserData($lancamento->created_at);
                $linha['data_pagamento'] = parserData($data_pagamento);
                $linha['data_lancamento'] = parserData($pedido->created_at);
                $linha['duplicata'] = $pedido->pedido->pedidoNasajon->nota->numero;
                $linha['parcela'] = 'Pré';
                $linha['nota_id'] = $pedido->pedido->pedidoNasajon->nota->id;
                $linha['nota_numero'] = $pedido->pedido->pedidoNasajon->nota->numero;
                $linha['cliente'] = $pedido->pedido->pedidoNasajon->cliente_detalhes->nome . ' - ' . $pedido->pedido->pedidoNasajon->cliente_cnpj;
                $linha['valor'] = parserValor($pedido->valor_pago);
                $linha['id_titulo'] = '';
                $linha['id'] = $pedido->pedido->pedidoNasajon->nota->id;
                $linha['numero_documento'] = $pedido->pedido->pedidoNasajon->nota->numero;
                $linha['vendedor_codigo'] = $pedido->pedido->pedidoNasajon->nota->revisao_vendedor_comissao->vendedor_codigo;
                    $linha['editavel'] = true;    
                $linha['comissao'] = parserValor($comissaoLacamento);
                $linha['porcentagem'] = parserValor($porcentagemLacamento) . '%'.$informativo_campanha;
                $linha['campanha'] = (!empty($informativo_campanha)) ? true : false;
                
                $return['titulos'][] = $linha;

                $return['total']['valor_total'] += $pedido->valor_pago;
                $return['total']['comissao'] += $comissaoLacamento;
            });
        });

        $comissaoAlteradaCampanhaZeraEstoqueObj = ComissaoAlteradaCampanhaZeraEstoque::select('nota_uuid')->distinct()->get();
        $notas_da_campanha = [];
        foreach($comissaoAlteradaCampanhaZeraEstoqueObj as $value){
            $notas_da_campanha[$value->nota_uuid] = $value->nota_uuid;
        }

        foreach($return['titulos'] as $index => $value){
            if(empty($notas_da_campanha[$value['nota_id']])){
                $return['titulos'][$index]['campanha'] = false;
            }else{
                $return['titulos'][$index]['campanha'] = true;
            }
        }
        
		$comissaoObj = new ComissaoDuplicatasController;
		$lancamentos = $comissaoObj->lancamentoArray($fields, parserValor($return['total']['comissao']));
		$exportar = [
			'titulos' => $return,
			'lancamentos' => $lancamentos,
			'codigo' => $fields['representante']
		];
		$exportar = encrypt($exportar);
		$return['total']['valor_total'] = parserValor($return['total']['valor_total']);
		$return['total']['comissao'] = parserValor($return['total']['comissao']);

        if($array_retorno){
            return $return;
        }
        if(isset($fields['api'])){
        	return $return;
        }
        else{
        	return view('programs.comissao_duplicatas.modal.dialog')->with(['titulos' => $return, 'lancamentos' => $lancamentos, 'exportar' => $exportar, 'codigo_representante' => $fields['representante']]);
        }
	}

	public function retornarPedido(Request $request){
		$fields = $request->only('nota_id', 'vendedor_codigo', 'titulo_id');
		
        $info_pedido = [];
        $itens = [];
        $nacional = [0,3,4,5];
        $internacional = [1,2,6,7];
        $coluna_a = null;
        $coluna_b = null;
        $coluna_c = null;
        $preco_base = null;
        $result = PedidosVendaNasajon::
            with(['itens_pedido', 'itens_pedido.especificacao', 'pedido_portal', 'nota', 'nota.revisao_vendedor_comissao' => function($query) use($fields){
                $query->where('vendedor_codigo', $fields['vendedor_codigo']);
            }, 'pedido_pre_pago','campanhaComissao.comissaoItens.campanha.produtosCampanha','percentualComissaoVendedores'])
            ->where('notafiscal_id', $fields['nota_id'])
            ->where('rascunho', 'false')
            ->where(function($query){
                $query->orWhereIn('grupodeoperacao', ['VENDA', 'TRANSFERENCIA']);
                $query->orWhereNull('grupodeoperacao');
            })
            ->first();

        $usuario_detalhes = User::where('codigo_representante', $fields['vendedor_codigo'])->first();

        if(empty($result)){
            $result = FaturamentoNotaNasajon::select()->with(['cliente', 'itens_faturamento.produtoDetalhes'])->where('Identificador Documento', $fields['nota_id'])->first();
        }

        $estabelecimento = empty($result->estabelecimento_codigo)? $result['Estabelecimento'] : $result->estabelecimento_codigo;

        $listagemDePrecosController = new ListagemDePrecosController();
        $estabelecimentos = returnEmpresasNasajonView();
        $clienteObj = empty($result->cliente_detalhes)? $result->cliente : $result->cliente_detalhes;
        $margemPrazoObj = MargemPrazo::where('estabelecimento', intval($estabelecimento))->first();
        $aliquotasObj = AliquotaPreco::where('origem', $this->getOrigemAttribute($estabelecimento))->where('estado', $clienteObj->uf)->get();
        $parametrosAprovacaoObj = ParametrosAprovacao::with('tipoUsuario')->where('estabelecimento', str_pad($estabelecimento, 2, '0', STR_PAD_LEFT))->get();
        $emissao = empty($result->nota->emissao)? $result['Data de Emissão']: $result->nota->emissao;
        $emissao = empty($emissao)? date('Y-m-d') : $emissao;
        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_inicio', '<=', $emissao)->where('data_fim', '>=', $emissao)->first();

        $desconto_maximo_gerente = 1 - $parametrosAprovacaoObj[($parametrosAprovacaoObj->search(function ($item, $key){ return strtolower($item->tipoUsuario->nome) == 'gerente'; }))]->percentual_desconto / 100;
        if ($clienteObj['indicadorinscricaoestadual'] == 2){
            $aliquota_internacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === true;    
            })]->icms_venda_cliente_isento;

            $aliquota_nacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->icms_venda_cliente_isento;

            $frete = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->frete_adicional;
        }
        else {
            $aliquota_internacional = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === true;
            })]->icms_venda;

            $aliquota_nacional  =$aliquotasObj[ 
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->icms_venda;

            $frete = $aliquotasObj[
                $aliquotasObj->search( function ($item, $key) {
                return $item->internacional === false;    
            })]->frete_adicional;

        }

        $devolucoes = 0;
        $fator_comissao = $margemPrazoObj->preco_b - 1;

        $info_pedido['frete_aplicado'] = '';
        $info_pedido['frete_preco'] = '';
        if(isset($result->pedido_portal)){

            if(!is_null($result->pedido_portal->frete_preco)){
                $info_pedido['frete_preco'] = $result->pedido_portal->frete_preco;
    
                if($result->pedido_portal->frete_preco == 'cif'){
                    $info_pedido['frete_aplicado'] = $frete . "%";
                }
                else{
                    $info_pedido['frete_aplicado'] = '0%';
                }
    
                if($result->pedido_portal->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';
                }
                else{
                    $info_pedido['frete_pedido'] = 'fob';                    
                }
    
            }
            else{
                if($result->pedido_portal->tipo_frete == 'P'){
                    $info_pedido['frete_pedido'] = 'cif';
    
                    if(!is_null($result->pedido_portal->transportadora_redespacho)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        
                    }
                    else{
                        if(isset($estabelecimentoCidadeFob) && !is_null($estabelecimentoCidadeFob)){
                        $info_pedido['frete_preco'] = 'fob';
                        $info_pedido['frete_aplicado'] = '0%';
                        }
                        else{
                            $info_pedido['frete_preco'] = 'cif';
                            $info_pedido['frete_aplicado'] = $frete . '%';
                        }
                    }
                }
                else{
                    $info_pedido['frete_pedido'] =  'fob';
                    $info_pedido['frete_preco'] = 'fob';
                    $info_pedido['frete_aplicado'] = '0%';
                }                    
            }

            if(isset($result->pedido_portal->detalhesProjeto)){
                $info_pedido['id_projeto'] = $result->pedido_portal->detalhesProjeto->id;

                $nome_projeto = empty($result->pedido_portal->detalhesProjeto->nome_projeto)? '': $result->pedido_portal->detalhesProjeto->nome_projeto;
                $cliente_projeto = empty($result->pedido_portal->detalhesProjeto->cliente)? '' : $result->pedido_portal->detalhesProjeto->cliente->nome;
                
                $info_pedido['titulo_modal_projeto'] = 'Detalhes do Projeto: ' . $result->pedido_portal->detalhesProjeto->id . ' - '. $nome_projeto . ' - Estabelecimento: '. $estabelecimentos[intval($estabelecimento)] . ' - Cliente: ' . $cliente_projeto;

            }
        }

        $lista_precos_parametros = [
            'frete' => $info_pedido['frete_preco'],
            'estabelecimento' => intval($result->estabelecimento_codigo),
            'estado' => $clienteObj->uf,
            'moeda' => 'real',
            'tipo_cliente' => ($clienteObj->inscricaoestadual == 'ISENTO' || intval($clienteObj->indicadorinscricaoestadual) == 2) ? 'isento' : 'juridico',
            'prazo_medio' => intval($result->pedido_portal->condicao_pagamento_detalhes->media??null),
            'frete' => $info_pedido['frete_preco']
        ];

        $total_pedido = 0;
        $comissao_total = 0;
        if(!empty($result->itens_pedido)){
            foreach($result->itens_pedido as $value){
                $lista_precos_parametros['produto'] = $value->item_item;
    
                $result_produto = $value->especificacao;
    
                $valor_portal = 0;
                if(!isset($result->pedido_portal)){
                    $precos = $listagemDePrecosController->filter(new ListaDePrecosRequest($lista_precos_parametros), false, false, true, true, false, false, false);
                    $comissao = '0%';
                }else{
                    $produto = $result->pedido_portal->itens_pedido->where('cod_produto', $value->item_item)->first();
    
                    $precos = [
                        'coluna_a' => 0,
                        'coluna_b' => 0,
                        'coluna_c' => 0
                    ];
    
                    if(isset($produto->coluna_a)){
                        $precos['coluna_a'] = parserValor($produto->coluna_a);
                    }
                    if(isset($produto->coluna_b)){
                        $precos['coluna_b'] = parserValor($produto->coluna_b);
                    }
                    if(isset($produto->coluna_c)){
                        $precos['coluna_c'] = parserValor($produto->coluna_c);
                    }
    
                    if(isset($produto->comissao)){
                        $comissao = $produto->comissao . '%';
                    }
                    else{
                        $comissao = '';
                    }
    
                    if(isset($produto->preco_unitario)){
                        $valor_portal = $produto->preco_unitario;
                    }
                    else{
                        $valor_portal = 0;
                    }
                }
    
                if(!empty($precos) && isset($precos['coluna_a'])){
                    $coluna_a = parserNumber($precos['coluna_a']);
                    $coluna_b = parserNumber($precos['coluna_b']);
                    $coluna_c = parserNumber($precos['coluna_c']);
                    $preco_base = '';
                }
    
                if(
                    !is_null($coluna_a) &&
                    !is_null($coluna_b) &&
                    !is_null($coluna_c) &&
                    !is_null($preco_base)
                ){
                    if(isset($result['pedido_pre_pago'])){
                        $info_pedido["retorno_pre"] = true;
                        $value['valortotal'] = $value['valortotal'] * 2;
                    }
    
                    $preco_unitario = (float) $value['valortotal'] / $value['quantidadecomercial'];
                    
                    if ( $coluna_a * (1 + $fator_comissao ) <= $preco_unitario ){
                        $porcentagem_acrescida = ((($preco_unitario / $coluna_a) - $fator_comissao) - 1) * 100;
                        $acrescimo_comissao = ceil($porcentagem_acrescida / 3);
        
                        $comissao_porcentagem = $usuario_detalhes->comissao_a + $acrescimo_comissao;
    
                        if(strtotime($result->emissao) < strtotime('2019-04-25')){
                            $comissao_porcentagem = $comissao_porcentagem > 5 ? 5 : $comissao_porcentagem;
                        }
                        else{
                            $comissao_porcentagem = $comissao_porcentagem > 15 ? 15 : $comissao_porcentagem;
                        }
    
                        $coluna_preco = '0';
        
                    }
                    else if(
                        $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                        $coluna_a * $desconto_maximo_gerente < $preco_unitario
                    ){
                        $comissao_porcentagem = $usuario_detalhes->comissao_a;
                    }
                    else if($preco_unitario < ($coluna_a * $desconto_maximo_gerente)){
    
                        $porcentagem_desconto = 1 - ($preco_unitario / $coluna_a);
    
                        $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
        
                        $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
        
                        $comissao_porcentagem = $usuario_detalhes->comissao_a - $desconto_comissao;
        
                        $comissao_porcentagem = $comissao_porcentagem >= 2 ? $comissao_porcentagem : 2;
                    }

                    if($usuario_detalhes->tipo_usuario_id == 16 || in_array($usuario_detalhes->id, [108, 610, 9087])){
                        $comissao_porcentagem = $usuario_detalhes->comissao_a;
                    }
    
                    $TituloPagamentoNasajon = TituloPagamentoNasajon::where('id_titulo', $fields['titulo_id'])->first();
                    $TitulosEmAbertoNasajonPortal = TitulosEmAbertoNasajonPortal::where('titulo_id', $fields['titulo_id'])->first();
    
                    if(isset($TituloPagamentoNasajon->banco_nome)){
                        if($TituloPagamentoNasajon->banco_nome  == 'Juridico Ragazzi'){
                            $comissao_porcentagem = 1;
                        }
                    }
    
                    if(isset($TitulosEmAbertoNasajonPortal->banco_nome)){
                        if($TitulosEmAbertoNasajonPortal->banco_nome == 'Juridico Ragazzi'){
                            $comissao_porcentagem = 1;
                        }
                    }
    
                    if(in_array($result_produto->procedencia, $internacional)){
                        $aliquota_base = '12%';
                        $aliquota_aplicada = $aliquota_internacional . '%';
                    }
                    else if(in_array($result_produto->procedencia, $nacional)){
                        $aliquota_base = '4%';
                        $aliquota_aplicada = $aliquota_nacional .'%';
                    }
    
                    $dois_e_meio = $coluna_a * $desconto_maximo_gerente;
                    $dois = $coluna_a * ($desconto_maximo_gerente - 0.015);
    
                    if($result['estabelecimento_codigo'] != 3){
                        $ipi_aplicado = ($result_produto->produto_nasajon->ipi??0) . '%';
                    }
                    else{
                        $ipi_aplicado = '0%';
                    }
    
                    $quantidade = $value['quantidade_faturada']??0;
                    if(!empty($value['quantidadecomercial'])){
                        $preco = $value->valortotal / $value->quantidadecomercial;
                    }else{
                        $preco = 0;
                    }

                    $incentivo_campanha = null;
                    $subistitui_comissao_padrao = '';
                    $total_percentual = 0;

                    if(isset($result->campanhaComissao->comissaoItens) && !empty($result->campanhaComissao->comissaoItens)){
                        $verificacao = $result->campanhaComissao->comissaoItens->firstWhere('produto_codigo', $value->item_item);
                        if(!empty($verificacao)){
                            $data_inclusao_item = '';
                            $incentivo_campanha = $verificacao->incetivo_percentual_comissao;
                            $data_inclusao_item = Carbon::parse($verificacao->created_at)->format('d/m/Y');
                            if($verificacao->tipo_comissao_campanha == 1){
                                $subistitui_comissao_padrao = false;
                                $total_percentual = round($comissao_porcentagem + $incentivo_campanha,2)."%<i class=\"btn-informacao-sem-alinhamento\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"\" data-original-title=\"Campanha '".$verificacao->campanha->nome."' Comissão ".$comissao_porcentagem."% + ".$incentivo_campanha."% incentivo, Incluido na data ".$data_inclusao_item."\" style=\"color: black;\"></i>";
                            }else if($verificacao->tipo_comissao_campanha == 2){
                                $subistitui_comissao_padrao = true;
                                $total_percentual = ($incentivo_campanha)."%<i class=\"btn-informacao-sem-alinhamento\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"\" data-original-title=\"Campanha ".$verificacao->campanha->nome." Comissão ".$incentivo_campanha."%, Incluido na data ".$data_inclusao_item."\" style=\"color: black;\"></i>";
                            }
                        }
                    }
    
                    $devolucoes = 0;
                    $total = $quantidade * $preco;

                    if($subistitui_comissao_padrao === false){
                        $comissao_valor = ($total) * (round($comissao_porcentagem + $incentivo_campanha,2)/100);
                    }else if($subistitui_comissao_padrao === true){
                        $comissao_valor = ($total) * (round($incentivo_campanha,2)/100);
                    }else{
                        $comissao_valor = ($total) * ($comissao_porcentagem/100);
                        $total_percentual = ($comissao_porcentagem);
                    }
                    
                    if(!empty($value->quantidade_apurada)){
                        $valor_apurado = $value->valortotal / $value->quantidadecomercial;
                    }else{
                        $valor_apurado = 0;
                    }

                    $itens[] = [
                        'codigo' => $value->item_item,
                        'nome' => $value->produto_especificacao,
                        'quantidade' => parserValor($value->quantidadecomercial),
                        'quantidade_pedido' => parserValor($value->quantidade_faturada),
                        'devolvidos' => $devolucoes,
                        'total' => parserValor($value->valortotal),
                        'total_nota' => parserValor($value->quantidade_faturada * $valor_apurado),
                        'preco_unitario_digitado' => parserValor($valor_portal),
                        'preco_unitario_pedido' => parserValor($valor_apurado),
                        'comissao_calculada' => $comissao,
                        'comissao_recalculada' => $comissao_porcentagem . '%',
                        'preco_base' => parserValor($preco_base),
                        'aliquota_base' => $aliquota_base,
                        'aliquota_aplicada' => $aliquota_aplicada,
                        'coluna_a' => parserValor($coluna_a),
                        'coluna_b' => parserValor($coluna_b),
                        'coluna_c' => parserValor($coluna_c),
                        'dois_e_meio' => parserValor($dois_e_meio),
                        'dois' => parserValor($dois),
                        'ipi_aplicado' => $ipi_aplicado,
                        'incentivo_comissao' => (!empty($incentivo_campanha) && $incentivo_campanha > 0) ? $incentivo_campanha.'%' : '',
                        'total_percentual' => $total_percentual,
                        'comissao_valor' => parserValor($comissao_valor),
                    ];
    
                    $comissao_total += $comissao_valor;
                    $total_pedido += $value->valortotal;
                }
            }
        }else {
            foreach($result->itens_faturamento as $value){
                $lista_precos_parametros['produto'] = $value->item_item;
    
                $result_produto = $value->produtoDetalhes;
    
                $valor_portal = 0;

                $comissao = '0%';

                $itens[] = [
                    'codigo' => $value['Item - Código'],
                    'nome' => $value->produto_especificacao,
                    'quantidade' => parserValor($value['Item - Quantidade']),
                    'quantidade_pedido' => parserValor($value['Item - Quantidade']),
                    'devolvidos' => 0,
                    'total' => parserValor($value['Item - Valor Total']),
                    'total_nota' => parserValor($value['Item - Valor Total'] * $value['Item - Quantidade']),
                    'preco_unitario_digitado' => parserValor(0),
                    'preco_unitario_pedido' => parserValor($value['Item - Valor Total']),
                    'comissao_calculada' => $comissao,
                    'comissao_recalculada' => '0' . "%",
                    'preco_base' => parserValor(0),
                    'aliquota_base' => '0',
                    'aliquota_aplicada' => '0',
                    'coluna_a' => parserValor(0),
                    'coluna_b' => parserValor(0),
                    'coluna_c' => parserValor(0),
                    'dois_e_meio' => parserValor(0),
                    'dois' => parserValor(0),
                    'ipi_aplicado' => '0',
                    'incentivo_comissao' => '',
                    'total_percentual' => '',
                    'comissao_valor' => parserValor(0),
                ];

                $comissao_total += 0;
                $total_pedido += empty($value->valortotal)? $value['Item - Valor Total']:$value->valortotal;
            }
        }
        $vendedoresObj = User::select('codigo_representante', 'name')->with('vendedor_nasajon')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

        $vendedores = [];
        
        $vendedoresObj->each(function($item) use (&$vendedores){
            if(isset($item->vendedor_nasajon->id)){
                $vendedores[$item->vendedor_nasajon->id] = $item->codigo_representante . ' - ' . strtoupper($item->name);
            }
        });

        $encriptar = [
            'id' => empty($result->notafiscal_id)? $result['Identificador Documento'] : $result->notafiscal_id,
            'estabelecimento' => empty($result->estabelecimento_codigo)? $result['Estabelecimento'] : $result->estabelecimento_codigo,
            'origem' => 'nasajon'
        ];

        $info_pedido["vendedor"] = User::where('codigo_representante', $fields['vendedor_codigo'])->first()->vendedor_nasajon->id;
        $encriptar["vendedor"] = $info_pedido['vendedor'];
        
        if(!empty($result->nota)){
            if($result->nota->total_produto > 0){
                $comissao_pedido = round(($comissao_total / $result->nota->total_produto * 100), 2);
            }else{
                $comissao_pedido = 0;
            }
        }else{
            $comissao_pedido = 0;
        }

        $info_pedido["estabelecimento"] = $estabelecimentos[intval($estabelecimento)];
        $info_pedido["id"] = $result->pedido_portal->id??null;
        $info_pedido["pedido_gerado"] = $result->numero;
        $info_pedido["id_pedido"] = $result->id;
        $info_pedido["cliente"] = $clienteObj->nome;
        $info_pedido["estado"] = $clienteObj->uf;
        $info_pedido['vendedores_array'] = $vendedores;
        $info_pedido["localizacao_cliente"] = $clienteObj->cidade;
        $info_pedido["prazo_medio"] = $result->pedido_portal->condicao_pagamento_detalhes->media??null;
        $info_pedido["fator_prazo"] = $result->pedido_portal->condicao_pagamento_detalhes->media??0 * 0.04;
        $info_pedido["comissao_pedido"] = ($comissao_pedido);
        $info_pedido["valor_total_produtos_nota"] = empty($result->valor_total)? $result["Valor Documento"] : $result->valor_total->total;
        $info_pedido["valor_total_produtos_pedido"] = $result->pedido_portal->valor_total->total??0;
        $info_pedido["comissao_pedido_valor"] = parserValor($comissao_total);
        $info_pedido["origem"] = 'nasajon';
        $info_pedido["itens"] = $itens;
        $info_pedido["comissao_na_nota"] = empty($result->percentualComissaoVendedores)? '0,00' : parserValor($result->percentualComissaoVendedores->percentual_comissao);

        if(isset($result['pedido_pre_pago'])){
            $info_pedido["valor_total"] = parserValor($result->valor*2);
        }
        else{
            $info_pedido["valor_total"] = parserValor($result->valor);
        }
        
        if(!is_null($comissaoDataFechamentoObj) && $comissaoDataFechamentoObj->data_fim->gte(Carbon::now()->format('Y-m-d')) || Auth::user()->tipo_usuario_id == 1 || in_array(Auth::id(), [46, 26, 105])){
            $info_pedido['permitir_edicao'] = true;
        }
        else{
            $info_pedido['permitir_edicao'] = false;
        }

        $info_pedido["hash"] = Crypt::encrypt($encriptar);
        
        if(!empty($info_pedido)){
            return view('programs.revisao_comissao.info_pedido')->with(['info_pedido' => $info_pedido]);
        }
	}

	public function getOrigemAttribute($estabelecimento){
        switch ($estabelecimento){
        case '3':
            return 'RO';
            break;
        case '4':
            return 'TO';
            break;
        default:
            return 'SP';
            break;
        }
    }

    public function gerarArquivoPdf(Request $request){

        set_time_limit(300);
        ini_set('memory_limit','1024M');
        ini_set("pcre.backtrack_limit", "5000000");
        
		$field = $request->only('exportar');
		try{
            $exportar = decrypt($field['exportar']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => $e,
                'response' => []
            ]);
		}
		$pdfFilePath = 'rep_'.$exportar['lancamentos']['representante'].'.pdf';
		$pdf = PDF::loadView(
			'pdf.comissao_duplicata', 
			[
				'representante' => $exportar['lancamentos']['nome'],
				'cod_representante' => $exportar['lancamentos']['representante'],
				'titulos' => $exportar['titulos'],
				'data_inicio' => $exportar['lancamentos']['data_inicio'],
				'data_fim' => $exportar['lancamentos']['data_fim'],
				'lancamentos' => $exportar['lancamentos']
			], 
			[], 
			['title' => 'Comissão '.$exportar['lancamentos']['nome'], 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);

		$pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);
    }
    
    public function titulosAbertosModal(Request $request){

        ini_set('memory_limit', '1024M');

        $fields = $request->only('data_inicio', 'data_fim', 'representante', 'estabelecimento', 'vencer_vencido');
        
        $estabelecimentos = returnEmpresasNasajonView();
		unset($estabelecimentos[20]);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $fields['data_inicio'])->format('Y-m-d');
        $data_fim = Carbon::createFromFormat('d/m/Y', $fields['data_fim'])->format('Y-m-d');

        $user =  User::with('subordinados')->withTrashed()->find($fields['representante']);
        $usuario_id = Auth::id();

        $vendedorComissaoNota = new VendedorComissaoNota;
        $titulosEmAbertoNasajonPortal = new TitulosEmAbertoNasajonPortal;
        $vendedorTituloNasajon = new VendedorTituloNasajon;
        $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', '<', Carbon::now()->format('Y-m-d'))->orderBy('data_fim', 'desc')->first();

        if($user->tipo_usuario_id == 19 || $user->tipo_usuario_id == 11){
            $percentual_comissao = 0.11;
        }else{
            $percentual_comissao = $user->comissao_a;
        }

        $perfil_acesso = '';
        try{
            $perfil_acesso = empty($user->detalhesModelHasRoles)? '' : $user->detalhesModelHasRoles->detalhesRoles->name;
        }catch(\Exception $e){
            $perfil_acesso = '';
        }
        
        $titulosEmAbertoQuery = TitulosEmAbertoNasajonPortal::with('cliente',  'campanhaComissao')
            ->whereNotIn('cod_cliente', $this->cnpj_excluir)
            ->where('numero', 'not ilike', '%ND')
            ->select(
                'valor',
                'desconto',
                'codigo',
                'titulo_emissao',
                'vencimento',
                'numero',
                'parcela',
                'nota_id',
                'titulo_id',
                'nota_numero',
                'cod_cliente',
                'vencimento',
                DB::Raw('saldotitulo - juros as saldotitulo,
                    CASE
                        WHEN ' . $titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo is null THEN \'001\'
                        ELSE ' . $titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo 
                    END as vendedor_codigo'
                )
            )
            ->leftjoin($vendedorComissaoNota->getTable(), $vendedorComissaoNota->getTable() . '.id_docfis', '=', $titulosEmAbertoNasajonPortal->getTable() . '.nota_id');

        if($user->tipo_usuario_id == 19 || $user->tipo_usuario_id == 14){
            
            $subordinados = User::select('codigo_representante')->withTrashed()->where('responsavel', $user->id)->get()->pluck('codigo_representante')->push($user->codigo_representante);
            $titulosEmAbertoQuery->addSelect(DB::Raw(
                    'ROUND((saldotitulo - juros) * 0.0011, 2) as comissao,
                    0.11 as percentual_comissao'
                    )
                )
                ->whereIn($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $subordinados);
        }else if($user->tipo_usuario_id == 16){
            $titulosEmAbertoQuery->addSelect(DB::Raw(
                'CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and nota_numero = \'000028381\' THEN 
                        0.0
                    WHEN nota_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN ROUND((saldotitulo - juros) * (2/100),2) 
                    WHEN  nota_emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        ROUND((saldotitulo - juros) * 0.30/100,2) 
                    WHEN  nota_emissao >= \'2023-02-01\' THEN
                        ROUND((saldotitulo - juros) * percentual_comissao/100,2) 
                    WHEN  (nota_emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (nota_emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        ROUND((saldotitulo - juros) * '. $user->comissao_a / 100 . ',2) 
                    WHEN  nota_emissao >= \'2022-10-01\' and percentual_comissao < 0.30 THEN
                        ROUND((saldotitulo - juros) * (percentual_comissao/100),2) 
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        ROUND((saldotitulo - juros) * (percentual_comissao/100),2) 
                    ELSE ROUND((saldotitulo - juros) * 0.25/100,2) 
                END as comissao,
                CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and nota_numero = \'000028381\' THEN \'0.0\'
                    WHEN nota_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 2
                    WHEN  nota_emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        0.30
                    WHEN  nota_emissao >= \'2023-02-01\' THEN
                        percentual_comissao
                    WHEN  (nota_emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (nota_emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        '. $user->comissao_a . ' 
                    WHEN  nota_emissao >= \'2022-10-01\' and nota_emissao < \'2023-10-01\' and percentual_comissao < 0.30 THEN
                        percentual_comissao
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        percentual_comissao
                    ELSE 0.25
                END as comissao_porcentagem'))->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $user->codigo_representante);          
        }else if($user->tipo_usuario_id == 13){
            if(in_array($user->id, [108, 610, 9087])){
                $titulosEmAbertoQuery->addSelect(DB::Raw(
                'CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and nota_numero = \'000028381\' THEN 
                        0.0
                    WHEN nota_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN ROUND((saldotitulo - juros) * (2/100),2) 
                    WHEN  nota_emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        ROUND((saldotitulo - juros) * 0.30/100,2) 
                    WHEN  nota_emissao >= \'2023-02-01\' THEN
                        ROUND((saldotitulo - juros) * percentual_comissao/100,2) 
                    WHEN  (nota_emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (nota_emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        ROUND((saldotitulo - juros) * '. $user->comissao_a / 100 . ',2) 
                    WHEN  nota_emissao >= \'2022-10-01\' and percentual_comissao < 0.30 THEN
                        ROUND((saldotitulo - juros) * (percentual_comissao/100),2) 
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        ROUND((saldotitulo - juros) * (percentual_comissao/100),2) 
                    ELSE ROUND((saldotitulo - juros) * 0.25/100,2) 
                END as comissao,
                CASE 
                    WHEN  cod_cliente = \'34.060.185/0001-41\' and nota_numero = \'000028381\' THEN \'0.0\'
                    WHEN nota_id = \'f2cc51ea-f27a-42e1-a595-af6368b36c6a\' THEN 2
                    WHEN  nota_emissao >= \'2023-02-01\' and percentual_comissao = 0 THEN
                        0.30
                    WHEN  nota_emissao >= \'2023-02-01\' THEN
                        percentual_comissao
                    WHEN  (nota_emissao >= \'2022-10-01\' and percentual_comissao >= 0.30) or (nota_emissao >= \'2022-10-01\' and percentual_comissao = 0) THEN
                        '. $user->comissao_a . ' 
                    WHEN  nota_emissao >= \'2022-10-01\' and nota_emissao < \'2023-10-01\' and percentual_comissao < 0.30 THEN
                        percentual_comissao
                    WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 THEN
                        percentual_comissao
                    ELSE 0.25
                END as comissao_porcentagem'))->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $user->codigo_representante);
            }else{
                $titulosEmAbertoQuery->addSelect(DB::Raw(
                    'ROUND((saldotitulo - juros) * (' . $percentual_comissao . '/100),2) as comissao,
                    ' . $percentual_comissao .' as percentual_comissao'
                    )
                )
                ->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $user->codigo_representante);
            }
            
        }else if($user->tipo_usuario_id == 16 || $perfil_acesso == 'REP.Playstation' || $user->tipo_usuario_id == 13){
            $titulosEmAbertoQuery->addSelect(DB::Raw(
                'ROUND((saldotitulo - juros) * (' . $percentual_comissao . '/100),2) as comissao,
                ' . $percentual_comissao .' as percentual_comissao'
                )
            )
            ->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $user->codigo_representante);
        }else{
            $titulosEmAbertoQuery->addSelect(DB::Raw(
                    'CASE 
                        WHEN '.$titulosEmAbertoNasajonPortal->getTable().'.banco_nome = \'Juridico Ragazzi\' THEN 1
                        ELSE percentual_comissao
                    END as percentual_comissao'
                )
            );

            if($user->codigo_representante == '001'){
                $titulosEmAbertoQuery->where(function($query) use ($titulosEmAbertoNasajonPortal){
                    $query->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', '001')
                        ->orWhereNull($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo');
                });
            }else{
                $titulosEmAbertoQuery->where($titulosEmAbertoNasajonPortal->getTable() . '.vendedor_codigo', $user->codigo_representante);
            }
        }

        if(isset($fields['estabelecimento']) && !empty($fields['estabelecimento'])){
            $titulosEmAbertoQuery->where('codigo', str_pad($fields['estabelecimento'], 2, "0", STR_PAD_LEFT));
        }
        else{
            $titulosEmAbertoQuery->where('codigo', '!=', 20);
        }

        if($fields['vencer_vencido'] == 'vencer'){
            $titulosObj = $titulosEmAbertoQuery
                ->whereRaw('vencimento >= current_date')
                ->whereBetween('vencimento', [$data_inicio, $data_fim])
                ->get();
        }
        else if($fields['vencer_vencido'] == 'vencido'){
            $titulosObj = $titulosEmAbertoQuery->whereRaw('vencimento < current_date')->get();
        }

		$return = [
			'titulos' => [],
			'total' => [
				'valor_total' => 0,
                'comissao' => 0,
                'desconto' => 0,
                'valor_com_desconto' => 0,
                'saldotitulo' => 0
			]
		];

        $titulosObj->each(function($titulo) use (&$return, $estabelecimentos, $user, $percentual_comissao, $usuario_id, $comissaoDataFechamentoObj, $perfil_acesso){

            $linha = [];
            
            if($user->tipo_usuario_id == 16 || $user->tipo_usuario_id == 13){
                $data_verificacao = Carbon::parse('2022-10-01');
                $data_verificacao_titulo = Carbon::parse($titulo->titulo_emissao);
                $porcentagem = parserValor($titulo->comissao_porcentagem) . '%';
                $comissao = $titulo->comissao;
            }elseif(in_array($user->tipo_usuario_id, [19, 14])) {
                if(isset($titulo->campanhaComissao) && !empty($titulo->campanhaComissao)){
                    $comissao_porcentagem = $titulo->campanhaComissao->porcetagem_comissao_gerente;
                    $comissao = round($titulo->saldotitulo * ($titulo->campanhaComissao->porcetagem_comissao_gerente/100), 2);

                    $comissao = $comissao;
                    $porcentagem = parserQtd($comissao_porcentagem) . '%';
                }else{
                    $porcentagem = parserValor($titulo->percentual_comissao) . '%';
                    $comissao = round($titulo->saldotitulo  * ($titulo->percentual_comissao/100), 2);
                }                        
            }elseif($perfil_acesso == 'REP.Playstation'){
                $DescontoPercentual = $titulo->saldotitulo > 0 ? ($titulo->desconto*100)/$titulo->saldotitulo : 0;

                if($DescontoPercentual > 9){
                    $porcentagem = parserValor($user->comissao_a - 1) . '%';
                    $comissao = round($titulo->saldotitulo * (($user->comissao_a - 1)/100), 2);
                }else{
                    $porcentagem = parserValor($titulo->percentual_comissao) . '%';
                    $comissao = round($titulo->saldotitulo  * ($user->comissao_a/100), 2);
                }
            }
            else{
                $porcentagem = parserValor($titulo->percentual_comissao) . '%';
                $comissao = round($titulo->saldotitulo  * ($titulo->percentual_comissao/100), 2);
            }

            $informativo_campanha = '';
            $verifica_campanha = [];

            if(isset($titulo->campanhaComissao->comissaoItens) && !empty($titulo->campanhaComissao->comissaoItens)){
                foreach($titulo->campanhaComissao->comissaoItens as $produtos){
                    if(!empty($produtos->campanha->nome)){
                        if(!in_array($produtos->campanha->nome,$verifica_campanha)){
                            if(empty($informativo_campanha)){
                                $informativo_campanha .= "'".$produtos->campanha->nome."' ";
                            }else{
                                $informativo_campanha .= ",'".$produtos->campanha->nome."' ";
                            }

                            $verifica_campanha[] = $produtos->campanha->nome;
                        }
                    }                        
                }
            }

            $linha['informativo_campanha'] = $informativo_campanha;

            if(!empty($informativo_campanha)){
                $informativo_campanha = "<i class=\"btn-informacao-sem-alinhamento\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"\" data-original-title=\"Campanha(s) participantes: ".$informativo_campanha."\" style=\"color: black;\"></i>";
            }

			$linha['estabelecimento'] = isset($estabelecimentos[intval($titulo->codigo)])? $estabelecimentos[intval($titulo->codigo)] : $titulo->codigo;
			$linha['emissao'] = parserData($titulo->titulo_emissao);
			$linha['vencimento'] = parserData($titulo->vencimento);
			$linha['data_emissao'] = parserData($titulo->titulo_emissao);
            $linha['duplicata'] = $titulo->numero; 
            $linha['parcela'] = $titulo->parcela;
			$linha['nota_id'] = $titulo->nota_id;
			$linha['nota_numero'] = $titulo->nota_numero;
			$linha['cliente'] = !empty($titulo->cliente) ? $titulo->cliente->nome . ' - ' . $titulo->cliente->cpf_cnpj : '';
			$linha['valor_total'] = parserValor($titulo->valor);
			$linha['valor_com_desconto'] = parserValor($titulo->valor - $titulo->desconto);
			$linha['desconto'] = parserValor($titulo->desconto);
			$linha['nota_id'] = $titulo->nota_id;
            $linha['id_titulo'] = $titulo->titulo_id;
			$linha['numero_documento'] = $titulo->nota_numero;
			$linha['vendedor_codigo'] = $titulo->vendedor_codigo;
            $linha['porcentagem'] = $porcentagem.$informativo_campanha;
            $linha['saldotitulo'] = parserValor($titulo->saldotitulo);
            $linha['comissao'] = parserValor($comissao);

                $linha['editavel'] = true;    
 
			$return['titulos'][] = $linha;
            $return['total']['valor_total'] += $titulo->valor;
            $return['total']['desconto'] += $titulo->desconto;
			$return['total']['valor_com_desconto'] += $titulo->valor - $titulo->desconto;
            $return['total']['saldotitulo'] += $titulo->saldotitulo;
            $return['total']['comissao'] += $comissao;
        });
        
        $return['total']['valor_total'] = parserValor($return['total']['valor_total']);
        $return['total']['desconto'] = parserValor($return['total']['desconto']);
        $return['total']['valor_com_desconto'] = parserValor($return['total']['valor_com_desconto']);
		$return['total']['saldotitulo'] = parserValor($return['total']['saldotitulo']);
		$return['total']['comissao'] = parserValor($return['total']['comissao']);

		$exportar = [
            'titulos' => $return,
            'total' => $return['total'],
			'codigo' => $fields['representante'],
            'nome' => $user->codigo_representante . ' - ' . $user->name,
            'data_inicio' => parserData($data_inicio),
            'data_fim' => parserData($data_fim),
            'vencer_vencido' => $fields['vencer_vencido']


		];
		$exportar = encrypt($exportar);

        return view('programs.comissao_duplicatas.modal.abertos')->with(['titulos' => $return, 'exportar' => $exportar, 'codigo_representante' => $fields['representante']]);
        
    }

    public function gerarArquivoAbertosPdf(Request $request){
		$field = $request->only('exportar');
		try{
            $exportar = decrypt($field['exportar']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Dados não encontrados',
                'error' => $e,
                'response' => []
            ]);
		}
		$pdfFilePath = 'rep_'.$exportar['codigo'].'.pdf';
		$pdf = PDF::loadView(
			'pdf.comissao_abertos', 
			[
				'representante' => $exportar['nome'],
				'cod_representante' => $exportar['codigo'],
                'titulos' => $exportar['titulos'],
                'total' => $exportar['total'],
				'data_inicio' => $exportar['data_inicio'],
				'data_fim' => $exportar['data_fim'],
			], 
			[], 
			['title' => 'Comissão ' . ($exportar['vencer_vencido']=='vencer'?'a vencer ':' vencidos ') . $exportar['nome'], 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
		$pdf->save($pdfFilePath);

        return view('pdf.leitura')->with(['caminho' => '/'.$pdfFilePath]);
    }

    private function totalLancamento($fields){
		$total_representante = 0.0;
		$total = 0.0;
		$credito = 0.0;
		$debito = 0.0;
		$data_inicio = $fields['data_inicio'];
		$data_fim = $fields['data_fim'];
		$lancamento_val = [];

        $data_inicio = $data_inicio.' 00:00:00';
		$data_fim = $data_fim.' 23:59:59';
        if(
            (
                isset(Auth::user()->tipo_usuario_id) &&
                Auth::user()->tipo_usuario_id != 12
            ) && (
                !isset($fields['api']) ||
                $fields['api'] != true
            )
        ) {
	        $codigo_representantes = [];
	        if(!empty($fields['representantes'])){
				$representantes_busca = User::select('id');
				$representantes_busca->where('id', '=', $fields['representantes']);
				$result = $representantes_busca->first();
				$codigo_representantes[] = isset($result->id) ? $result->id : null;
            }
            else if(Auth::user()->tipo_usuario_id == 19){
                $codigo_representantes = User::where('responsavel', Auth::user()->id)->get()->pluck('id')->toArray();
                $codigo_representantes[] = Auth::id();
            }
            else if(Auth::user()->tipo_usuario_id == 14){
                $codigo_representantes = User::where('responsavel', Auth::user()->supervisor->id)->get()->pluck('id')->toArray();
                $codigo_representantes[] = Auth::user()->supervisor->id;
                $codigo_representantes[] = Auth::id();
            }
				
            unset($representantes_busca);
	    }else{
            if(isset(Auth::user()->codigo_representante)){
                $representantes_busca = User::select('id');
                $representantes_busca->where('codigo_representante', '=', Auth::user()->codigo_representante);
                $result = $representantes_busca->first();
                $codigo_representantes[] = $result->id;
            }else{
                $codigo_representantes = [];
            }
		}

		$queryLancamento = LancamentoDebCredVendedor::with('vendedor')->select('codigo_vendedor', 'tipo', 'valor');
		
        if(!empty($codigo_representantes)){
            $queryLancamento->whereIn('codigo_vendedor', $codigo_representantes);
        }
        
		$queryLancamento->whereBetween('data_lancamento', [$data_inicio, $data_fim]);
		$Lancamentos = $queryLancamento->get();
		$lancamento_val = [];
		$total = 0;
		foreach($Lancamentos as $lancamento){
            if(isset($lancamento->vendedor->codigo_representante)){
                if(empty($lancamento_val[$lancamento->vendedor->codigo_representante])){
                    $lancamento_val[$lancamento->vendedor->codigo_representante] = 0;
                }
                if(trim($lancamento->tipo) == 'C'){
                    $lancamento_val[$lancamento->vendedor->codigo_representante] += $lancamento->valor;
                } else{
                    $lancamento_val[$lancamento->vendedor->codigo_representante] -= $lancamento->valor;
                }
                $total += $lancamento->valor;
            }
		}
		$retorno = [
			'lancamento' => $lancamento_val,
			'total' => $total
		];
		return $retorno;
	}

    public function emailRepresentantesVirada(){

        $hoje = Carbon::Now();

        $comissaoDataFechamento = ComissaoDataFechamento::where('data_fim', $hoje->copy()->subDay()->format('Y-m-d'))->first();

        $data_inicio_premiacao = Carbon::Now()->subMonth()->firstOfMonth();
        $data_fim_premiacao = Carbon::Now()->subMonth()->lastOfMonth();

        if(is_null($comissaoDataFechamento)){
            return null;
        }
        
        $representantes = User::where('tipo_usuario_id', 12)->whereNotNull('email')->get();

        $emailControllerObj = new EmailController;

        $representantes->each(function($representante) use ($emailControllerObj, $comissaoDataFechamento, $data_inicio_premiacao, $data_fim_premiacao){
            
            $variaveis_replace = [
                'representante' => $representante->name,
                'data_inicio' => $comissaoDataFechamento->data_inicio->format('d/m/Y'),
                'data_fim' => $comissaoDataFechamento->data_fim->format('d/m/Y'),
                'data_inicio_premiacao' => $data_inicio_premiacao->format('d/m/Y'),
                'data_fim_premiacao' => $data_fim_premiacao->format('d/m/Y'),
                'periodo' => $comissaoDataFechamento->periodo,
                'link' => '<a href="' . route('comissao_duplicatas.index') . '">' . route('comissao_duplicatas.index') . '</a>'
            ];
    
            $emailControllerObj->sendEmailToken('00', 'email_representante_comissao', [$representante->email], $variaveis_replace);
        });

        echo "E-mails enviados: " . $representantes->count() . PHP_EOL . 'Demorou ' . $hoje->diffForHumans() . PHP_EOL;

    }

    public function lancamentoArray($fields, $comissao){
		$total_credito = 0.0;
		$total_debito = 0.0;
		$total_comissao = str_replace(",", ".", str_replace(".", "", $comissao));
		$queryLancamento = LancamentoDebCredVendedor::select('num_documento', 'data_lancamento', 'codigo_motivo', 'codigo_vendedor', 'tipo', 'valor', 'parcela', 'nota_uuid');
		$queryLancamento->with(['motivofinanceiro' => function($queryLancamento){
            $queryLancamento->select('id', 'motivo');
		}]);
		$queryLancamento->with(['vendedor' => function($queryLancamento){
            $queryLancamento->select('id', 'name', 'codigo_representante');
		}]);
		$queryLancamento->with(['nota']);
		$queryLancamento->where('codigo_vendedor', '=', $fields['representante']);
		$queryLancamento->whereBetween('data_lancamento', [$fields['data_inicio'], $fields['data_fim']]);
		$result = $queryLancamento->get();
		$nome ="";
		$cod_representante = "";
		$dados = [];
        foreach ($result as $key => $lancamento) {
            $data = new Carbon($lancamento->data_lancamento);
            $data = $data->format('d/m/Y');

			$motivo = $lancamento->motivofinanceiro->motivo;

			$nome = $lancamento->vendedor->name;
			$cod_representante = $lancamento->vendedor->codigo_representante;
			
			if(trim($lancamento->tipo) == 'C'){
				$credito = parserValor($lancamento->valor);
				$debito = '';
				$total_credito = $total_credito + $lancamento->valor;
				$total_comissao = $total_comissao + $lancamento->valor;
			} else{
				$credito = '';
				$debito = parserValor((-1) * $lancamento->valor);
				$total_debito = $total_debito + $lancamento->valor;
				$total_comissao = $total_comissao - $lancamento->valor;
			}

			if(!empty($lancamento->parcela)){
				$documento = $lancamento->num_documento . ' - ' . $lancamento->parcela;
			}
			else{
				$documento = $lancamento->num_documento;
			}
			$cliente = '';
			$nota = '';
			if(($lancamento->nota)){
				$cliente = $lancamento->nota->cliente_nome;
				$nota = $lancamento->nota->numero;
			}

            $dados[] = [
				'data' => $data,
				'documento' => $documento,
                'motivo' => $motivo,
				'credito' => $credito,
				'debito' => $debito,
				'cliente' => $cliente,
				'nota' => $nota
            ];
		}
		
		$total = [
			'credito' => parserValor($total_credito),
			'debito' => parserValor((-1) * $total_debito),
			'comissao' => parserValor($total_comissao)
		];

		if(empty($cod_representante)){
			$queryVendedor = User::select('name', 'codigo_representante');
			$queryVendedor->where('id', '=', $fields['representante']);
			$resultVendedor = $queryVendedor->get();
			foreach ($resultVendedor as $key => $vendedor) {
				$nome = $vendedor->name;
				$cod_representante = $vendedor->codigo_representante;
			}
		}

		$lancamentos = [
			'nome' => $nome,
			'representante' => $cod_representante,
			'data_inicio' => $fields['data_inicio'],
			'data_fim' => $fields['data_fim'],
			'dados' => $dados,
			'total' => $total
		];
		return $lancamentos;
	}

    public function alterarComissaoDuplicataModal(Request $request){

        $fields = $request->only('id');
        
        $tituloPagamentoNasajonObj = TituloPagamentoNasajon::with('comissaoVendedorTitulo', 'vendedorNasajon')->where('id_titulo', $fields['id'])->first();
        $titulosEmAbertoNasajonPortalObj = TitulosEmAbertoNasajonPortal::with('vendedorTitulo', 'vendedorNasajon')->where('titulo_id', $fields['id'])->first();
        
        $vendedoresObj = User::select('codigo_representante', 'name')->with('vendedor_nasajon')->whereNotNull('codigo_representante')->orderBy('codigo_representante')->get();

        $vendedores = [];
        
        $vendedoresObj->each(function($item) use (&$vendedores){
            if(isset($item->vendedor_nasajon->id)){
                $vendedores[$item->vendedor_nasajon->id] = $item->codigo_representante . ' - ' . strtoupper($item->name);
            }
        });

        $retorno = [];

        $retorno['vendedores_array'] = $vendedores;

        $retorno['id'] = isset($tituloPagamentoNasajonObj->id_titulo) ? $tituloPagamentoNasajonObj->id_titulo : $titulosEmAbertoNasajonPortalObj->titulo_id;
        $retorno['titulo'] = isset($tituloPagamentoNasajonObj->numero) ? $tituloPagamentoNasajonObj->numero : $titulosEmAbertoNasajonPortalObj->numero;

        if(isset($tituloPagamentoNasajonObj->vendedorNasajon)){
            $retorno['vendedor'] = $tituloPagamentoNasajonObj->vendedorNasajon->id;
        }
        elseif(isset($titulosEmAbertoNasajonPortalObj->vendedorNasajon)){
            $retorno['vendedor'] = $titulosEmAbertoNasajonPortalObj->vendedorNasajon->id;
        }else{
            $retorno['vendedor'] = '';
        }

        if(isset($tituloPagamentoNasajonObj->banco_nome) && $tituloPagamentoNasajonObj->banco_nome == 'Juridico Ragazzi' || isset($titulosEmAbertoNasajonPortalObj->banco_nome) && $titulosEmAbertoNasajonPortalObj->banco_nome == 'Juridico Ragazzi'){
            $retorno['comissao'] = '1,00';
        }
        else if(isset($tituloPagamentoNasajonObj->vendedorNasajon) && isset($tituloPagamentoNasajonObj->comissaoVendedorTitulo->firstWhere('vendedor', $tituloPagamentoNasajonObj->vendedorNasajon->id)->percentual_comissao)){
            $retorno['comissao'] = parserValor($tituloPagamentoNasajonObj->comissaoVendedorTitulo->firstWhere('vendedor', $tituloPagamentoNasajonObj->vendedorNasajon->id)->percentual_comissao);
        }
        else if(isset($titulosEmAbertoNasajonPortalObj->vendedorNasajon) && isset($titulosEmAbertoNasajonPortalObj->vendedorTitulo->firstWhere('vendedor', $titulosEmAbertoNasajonPortalObj->vendedorNasajon->id)->percentual_comissao)){
            $retorno['comissao'] = parserValor($titulosEmAbertoNasajonPortalObj->vendedorTitulo->firstWhere('vendedor', $titulosEmAbertoNasajonPortalObj->vendedorNasajon->id)->percentual_comissao);
        }
        else{
            $retorno['comissao'] = '0,00';
        }

        return view("programs.comissao_duplicatas.modal.edicao")->with($retorno);
    }

    public function salvarComissaoDuplicata(Request $request){

        $fields = $request->only('id', 'comissao', 'vendedor', 'vendedor_original');

        $tituloPagamentoNasajonObj = TituloPagamentoNasajon::with('comissaoVendedorTitulo')->where('id_titulo', $fields['id'])->first();

        $vendedor = VendedorNasajon::where('id', $fields['vendedor'])->first();
        $comissao = parserNumber($fields['comissao']);

        if(isset($tituloPagamentoNasajonObj->comissaoVendedorTitulo->firstWhere('vendedor', $fields['vendedor_original'])->percentual_comissao)){

            $retorno = DB::connection('nasajon')->select("select * from integracoes.api_tituloreceber_vendedorexcluir(
                '{$fields['id']}',
                '{$fields['vendedor_original']}'
            )");

            $retorno = reset($retorno);
            $mensagem = $retorno->mensagem;
            unset($retorno);
            $mensagem = json_decode($mensagem);

            if($mensagem->codigo == 'ERRO'){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                    'error' => [$mensagem],
                    'response' => []
                ], 422);
            }
        }

        $response = DB::connection('nasajon')->select("select * from integracoes.api_tituloreceber_vendedornovo(
            '{$fields['id']}',
            '{$vendedor->id}', 
            '100',
            '{$comissao}')");

        $resposta_tratada = $this->tratarRetornoApiNasajon($response);
        if($resposta_tratada['status'] == 'error'){
            return response()->json($resposta_tratada, 422);
        }
        else{
            return response()->json($resposta_tratada);
        }
    }

    private function tratarRetornoApiNasajon($retorno){
        $response = [];
        $retorno = reset($retorno);
        $mensagem = $retorno->mensagem;
        unset($retorno);
        $mensagem = json_decode($mensagem);
        if($mensagem->codigo == 'ERRO'){
            return [
                'status' => 'error',
                'message' => 'Ocorreu uma instabilidade, contate o setor responsavel!',
                'error' => [$mensagem],
                'response' => []
            ];
        }else{
            return [
                'status' => 'success',
                'message' => '',
                'error' => [$mensagem],
                'response' => []
            ];
        }
    }

    private function ajusteArrayParaValores($array){
        if(is_array($array)){
            foreach($array as $key => $value){
                if(is_array($value)){
                    $array[$key] = $this->ajusteArrayParaValores($value);
                }else{
                    if(is_numeric($value)){
                        if(in_array($key, ['valor_comissao', 'comissao','base_comissao','a_vencer_valor','a_vencer_comissao','vencido_valor','vencido_comissao','desconto_comissao'])){
                            $array[$key] = !empty($value) && $value > 0 ? parserValor($value) : '';                         
                        }
                    }else{
                        $array[$key] = $value;
                    }
                }
            }
        }
        return $array;
    }

    function separadorComissao($tituloPagamentoNasajon){
        $separador_comissao = [];
        $comissao_representantes = [];
        $usuarios = User::select()->whereIn('tipo_usuario_id', [13,16])->whereNotIn('codigo_representante', ['999'])->get();
        foreach($usuarios as $usuario){
            $comissao_representantes[$usuario->comissao_a][] = $usuario->codigo_representante;
        }
        $quantidade = count($comissao_representantes);
        $contador = 0;

        foreach($comissao_representantes as $comissao => $representantes){
            $contador = 0;
            if($comissao == '0.25'){
                $separador_comissao[$comissao] = "WHEN  ".$tituloPagamentoNasajon->getTable().".cod_cliente = '34.060.185/0001-41' and documento_numero = '000028381' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * ((percentual_comissao/100) * 0.0025 / 0.0025),2) ";
            }else if($comissao == '0.30'){
                $separador_comissao[$comissao] = "WHEN  ".$tituloPagamentoNasajon->getTable().".cod_cliente = '34.060.185/0001-41' and documento_numero = '000028381' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN documento_id = 'f2cc51ea-f27a-42e1-a595-af6368b36c6a' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 2/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  emissao >= '2023-02-01' and percentual_comissao < 0.30 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  emissao >= '2023-02-01' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * percentual_comissao/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  emissao > '2022-10-01' and percentual_comissao >= 0.30 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  emissao > '2022-10-01' and percentual_comissao = 0 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  emissao > '2022-10-01' and percentual_comissao < 0.30 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * percentual_comissao/100,2) ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * ((percentual_comissao/100) * 0.0025 / 0.0025),2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao <= 0.25 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.0025,2) ";
            }else if($comissao == '0.75'){
                $separador_comissao[$comissao] = "WHEN  ".$tituloPagamentoNasajon->getTable().".cod_cliente = '34.060.185/0001-41' and documento_numero = '000028381' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  emissao >= '2023-02-01' and percentual_comissao = 0.0 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.0075,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  emissao >= '2023-02-01' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * percentual_comissao/100,2) ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.0 and percentual_comissao <= 5 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * 0.0075,2) ";
            }else{
                $separador_comissao[$comissao] = " WHEN  ".$tituloPagamentoNasajon->getTable().".cod_cliente = '34.060.185/0001-41' and documento_numero = '000028381' and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }     
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }     
                }
                $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * (percentual_comissao/100),2) ";
            }
        }

        $query = '';
        foreach($separador_comissao as $separacao){
            $query .= $separacao;
        }
        $query .= 'ELSE ROUND('.$tituloPagamentoNasajon->getTable().'.valor * 0.0025,2) ';

        return $query;
    }

    public function aprovarComissao(Request $request){
        $campo = $request->only('valor_comissao', 'periodo');
        
        $vendedor = User::find(Auth::id());

        if(!in_array($vendedor->tipo_usuario_id, [13,12,16,19,14])){
            return response()->json([
                'status' => 'error', 
                'message' => 'Usuário sem permissão para aprovar!', 
                'error' => 'Usuário sem permissão para aprovar!',
                'response' => []
            ], 422);   
        }
        $mes = $campo['periodo'];

        $link = md5($vendedor->id.$mes.$vendedor->name.'comissao');
        $verificar_comunicado = ComunicadoComissoe::where('link',$link)->with('vendedor')->first();

        if(empty($verificar_comunicado->confirmacao)){
            $comunicado_comissoes = new ComunicadoComissoe;
            $comunicado_comissoes->tipo = 'comissao';
            $comunicado_comissoes->vendedor_codigo = $vendedor->codigo_representante;
            $comunicado_comissoes->valor_meta = 0;
            $comunicado_comissoes->valor_comissao = parserNumber($campo['valor_comissao']);
            $comunicado_comissoes->created_by = Auth::id();
            $comunicado_comissoes->link = $link;
            $comunicado_comissoes->confirmacao = false;
            $comunicado_comissoes->save();
        }
        elseif($verificar_comunicado->confirmacao == true){
            $response = [
                "status" => 'error',
                "message" => 'A confirmação já foi realizada!',
                "error" => 'A confirmação já foi realizada!',
                "response" => []
            ];
            return response()->json($response, 422);  
        }

        $verificar_comunicado = ComunicadoComissoe::where('link',$link)->with('vendedor')->first();
        $verificar_comunicado->updated_by = Auth::id();
        $verificar_comunicado->confirmacao = true;
        $verificar_comunicado->data_confirmacao = Carbon::now();
        $verificar_comunicado->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response, 200);

    }

    public function ajusteComissaoCampanhaPromocional(){
        $query = NotaVendaNasajon::select();
        $query->whereHas('pedido');
        $query->with('item.produto_detalhes', 'revisao_vendedor_comissao.usuario', 'pedido.pedido_portal.itens_pedido.especificacoes');
        $query->whereBetween('emissao', ['2022-05-07', '2022-05-31']);
        $result = $query->get();

        foreach($result as $value){
            $linha_campanha_zera_estoque = false; 
            $itens_nota = [];
            if(in_array($value->revisao_vendedor_comissao->usuario->tipo_usuario_id, [12,16, 19, 14])){
                foreach($value->item as $item){
                    if($value->revisao_vendedor_comissao->usuario->tipo_usuario_id === 16){
                        if($item->produto_detalhes->linha === 'CAMPANHA ZERA ESTOQUE' || $item->produto_detalhes->linha === 'CAMPANHA REATIVE A MODA'){
                            $linha_campanha_zera_estoque = true;
                            $comissao = 0.50;
                        }else{
                            $comissao = 0.25;
                        }
                    }else if($value->revisao_vendedor_comissao->usuario->tipo_usuario_id === 12){
                        $produto = $value->pedido->pedido_portal->itens_pedido->where('cod_produto', $item->cod_produto)->first();
                        if($produto->especificacoes->linha === 'CAMPANHA ZERA ESTOQUE' || $item->produto_detalhes->linha === 'CAMPANHA REATIVE A MODA'){
                            $linha_campanha_zera_estoque = true;
                            $comissao = $produto->comissao + 1.0;
                        }else{
                            $comissao = $produto->comissao;
                        }
                    }else{
                        $produto = $value->pedido->pedido_portal->itens_pedido->where('cod_produto', $item->cod_produto)->first();
                        if($produto->especificacoes->linha === 'CAMPANHA ZERA ESTOQUE' || $item->produto_detalhes->linha === 'CAMPANHA REATIVE A MODA'){
                            $linha_campanha_zera_estoque = true;
                            $comissao = 0.22;
                        }else{
                            $comissao = 0.11;
                        }
                    }
                    
                    $itens_nota[] =[
                        'valor_total' => $item->valor_total,
                        'comissao' => $comissao ,
                        'comissao_valor' => $item->valor_total * $comissao  / 100,
                    ];
    
                    if(empty($itens_nota['total'])){
                        $itens_nota['total'] = $item->valor_total * $comissao / 100;
                    }else{
                        $itens_nota['total'] += $item->valor_total * $comissao / 100;
                    }
                    
                }
    
                if($linha_campanha_zera_estoque){
                    $id_nota = $value->id_nota;
                    $id_vendedor = $value->revisao_vendedor_comissao->vendedor;
                    $comissao = round($itens_nota['total'] / $value->valor * 100, 2);
    
                    $comissaoAlteradaCampanhaZeraEstoqueObj = new ComissaoAlteradaCampanhaZeraEstoque;
                    $comissaoAlteradaCampanhaZeraEstoqueObj->nota_uuid = $value->id_nota;
                    $comissaoAlteradaCampanhaZeraEstoqueObj->nota_numero = $value->numero;
                    $comissaoAlteradaCampanhaZeraEstoqueObj->comissao_anterior = $value->revisao_vendedor_comissao->percentual_comissao;
                    $comissaoAlteradaCampanhaZeraEstoqueObj->comissao_atual = $comissao;
                    $comissaoAlteradaCampanhaZeraEstoqueObj->save();
    
                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$id_nota}', /* id_nota */
                        '{$id_vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '{$comissao}', /* comissao */
                        true /* vendedor_principal */
                    );";
                    
                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
            }
        }
    }

    function separadorComissaoGerente($tituloPagamentoNasajon){
        $separador_comissao = [];
        $comissao_representantes = [];
        $usuarios = User::select()->where('tipo_usuario_id', 19)->whereNotIn('codigo_representante', ['999'])->get();
        foreach($usuarios as $usuario){
            $comissao_representantes[$usuario->comissao_a][] = $usuario->codigo_representante;
        }
        $quantidade = count($comissao_representantes);

        $campanhasComissaoCalculoObj = CampanhasComissaoCalculo::select()->whereNotNull('porcetagem_comissao_gerente')->whereNotNull('nota_id')->get();
        $contador = 0;
        foreach($campanhasComissaoCalculoObj as $value){
            $contador = 0;
            $nota_id = empty($value->nota_id)? null : $value->nota_id;
            $separador_comissao[$value->nota_id."campanha"] = " WHEN  integracoes.vw_baixastitulos_vendedor_v2.documento_id in ('".$nota_id."')";
            $separador_comissao[$value->nota_id."campanha"] .= " THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * ".$value->porcetagem_comissao_gerente." / 100,2) ";
        }

        $contador = 0;
        foreach($comissao_representantes as $comissao => $representantes){
            $contador = 0;
            $separador_comissao[$comissao] = " WHEN  percentual_comissao >= 0.11 and percentual_comissao <= 0.33 and ".$tituloPagamentoNasajon->getTable().".vendedor_codigo in (";
            foreach($representantes as $representante){
                if($contador == 0){
                    $separador_comissao[$comissao] .= "'".$representante."'";
                    $contador++;
                }else{
                    $separador_comissao[$comissao] .= ",'".$representante."'";
                }                    
            }
            $separador_comissao[$comissao] .= ") THEN ROUND(".$tituloPagamentoNasajon->getTable().".valor * percentual_comissao / 100,2) ";
        }

        $query = '';
        foreach($separador_comissao as $separacao){
            $query .= $separacao;
        }
        $query .= 'ELSE ROUND('.$tituloPagamentoNasajon->getTable().'.valor * 0.0011,2) ';

        return $query;
    }

    public function atualizarComissaoZerada(){
        $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
        $vendedorTituloNasajonObj->where('percentual_comissao', 0);
        $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
        $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();

        foreach($vendedorTituloNasajonObj as $value){
            $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
            $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
            $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
            $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
            if(!empty($tituloPagamentoNasajonObj)){
                $faturamentoNotaNasajonObj = FaturamentoNotaNasajon::select('Vendedor - Percentual Comissão');
                $faturamentoNotaNasajonObj->where('Identificador Documento', $tituloPagamentoNasajonObj->documento_id);
                $faturamentoNotaNasajonObj->where('Vendedor - Percentual Comissão', '>', 0);
                $faturamentoNotaNasajonObj = $faturamentoNotaNasajonObj->first();
    
                if(!empty($faturamentoNotaNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = 0;
                    $comissaoAjusteZeradaObj->comissao_atual = $faturamentoNotaNasajonObj['Vendedor - Percentual Comissão'];
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '{$faturamentoNotaNasajonObj['Vendedor - Percentual Comissão']}', /* comissao */
                        true /* vendedor_principal */
                    );";
                    
                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
            }
            
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('comissao_a', 0.3)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [16])->where('codigo_representante', '<>', '415')->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
            }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '<', '2022-10-01');
                $query->where('emissao', '>', '2022-09-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.25);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.25;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.25', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('titulosAbertos', function($query){
                $query->where('titulo_emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TitulosEmAbertoNasajonPortal::select('nota_id');
                $tituloPagamentoNasajonObj->where('titulo_id', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('nota_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();

                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->nota_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->nota_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('titulosAbertos', function($query){
                $query->where('titulo_emissao', '<', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.25);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TitulosEmAbertoNasajonPortal::select('nota_id');
                $tituloPagamentoNasajonObj->where('titulo_id', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('nota_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();

                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->nota_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.25;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->nota_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.25', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }
    }

    public function testeZerado(){
        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '<', '2022-10-01');
                $query->where('emissao', '>', '2022-09-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.25);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.25;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.25', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('detalhesTituloPagamento', function($query){
                $query->where('emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TituloPagamentoNasajon::select('documento_id');
                $tituloPagamentoNasajonObj->where('id_titulo', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('documento_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();
    
                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->documento_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->documento_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('titulosAbertos', function($query){
                $query->where('titulo_emissao', '>=', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.30);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TitulosEmAbertoNasajonPortal::select('nota_id');
                $tituloPagamentoNasajonObj->where('titulo_id', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('nota_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();

                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->nota_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.30;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->nota_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.30', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }

        $userObj = User::select()->whereIn('tipo_usuario_id', [13])->where('id', 108)->get();

        foreach($userObj as $usuario){
            $vendedorTituloNasajonObj = VendedorTituloNasajon::select('tituloreceber', 'vendedor', 'percentual_comissao');
            $vendedorTituloNasajonObj->whereHas('titulosAbertos', function($query){
                $query->where('titulo_emissao', '<', '2022-10-01');
            });
            $vendedorTituloNasajonObj->whereNotIn('vendedor_codigo', ['998', '999', '001']);
            $vendedorTituloNasajonObj->where('vendedor_codigo', $usuario->codigo_representante);
            $vendedorTituloNasajonObj->where('percentual_comissao', '>', 0.25);
            $vendedorTituloNasajonObj = $vendedorTituloNasajonObj->get();
            
            foreach($vendedorTituloNasajonObj as $value){
                $tituloPagamentoNasajonObj = TitulosEmAbertoNasajonPortal::select('nota_id');
                $tituloPagamentoNasajonObj->where('titulo_id', $value->tituloreceber);
                $tituloPagamentoNasajonObj->whereNotIn('nota_id', ['95d48d3f-b2d7-42e3-a19e-35ffebed9182']);
                $tituloPagamentoNasajonObj = $tituloPagamentoNasajonObj->first();

                if(!empty($tituloPagamentoNasajonObj)){
                    $comissaoAjusteZeradaObj = new ComissaoAjusteZerada;
                    $comissaoAjusteZeradaObj->nota_id = $tituloPagamentoNasajonObj->nota_id;
                    $comissaoAjusteZeradaObj->vendedor_id = $value->vendedor;
                    $comissaoAjusteZeradaObj->comissao_anterior = $value->percentual_comissao;
                    $comissaoAjusteZeradaObj->comissao_atual = 0.25;
                    $comissaoAjusteZeradaObj->save();

                    $sql_comissao = "select * from integracoes.api_nota_vendedoralterar(
                        '{$tituloPagamentoNasajonObj->nota_id}', /* id_nota */
                        '{$value->vendedor}', /* id_vendedor */
                        '100', /* participacao */
                        '0.25', /* comissao */
                        true /* vendedor_principal */
                    );";

                    $return = DB::connection('nasajon')->select($sql_comissao);
                }
                
            }
        }
    }

    function separadorComissaoAberto($titulosEmAbertoNasajonPortal){
        $separador_comissao = [];
        $comissao_representantes = [];
        $usuarios = User::select()->whereIn('tipo_usuario_id', [13,16])->whereNotIn('codigo_representante', ['999'])->get();
        foreach($usuarios as $usuario){
            $comissao_representantes[$usuario->comissao_a][] = $usuario->codigo_representante;
        }
        $quantidade = count($comissao_representantes);
        $contador = 0;

        foreach($comissao_representantes as $comissao => $representantes){
            $contador = 0;
            if($comissao == '0.25'){
                $separador_comissao[$comissao] = "WHEN  cod_cliente = '34.060.185/0001-41' and nota_numero = '000028381' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * ((percentual_comissao/100) * 0.0025 / 0.0025),2) ";
            }else if($comissao == '0.30'){
                $separador_comissao[$comissao] = "WHEN cod_cliente = '34.060.185/0001-41' and nota_numero = '000028381' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN nota_id = 'f2cc51ea-f27a-42e1-a595-af6368b36c6a' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros)  * 2/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  nota_emissao >= '2023-02-01' and percentual_comissao < 0.30 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= "WHEN  nota_emissao >= '2023-02-01' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * percentual_comissao/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  nota_emissao > '2022-10-01' and percentual_comissao >= 0.30 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  nota_emissao > '2022-10-01' and percentual_comissao = 0 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * 0.30/100,2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  nota_emissao > '2022-10-01' and percentual_comissao < 0.30 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * percentual_comissao/100,2) ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.25 and percentual_comissao <= 0.75 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * ((percentual_comissao/100) * 0.0025 / 0.0025),2) ";

                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao <= 0.25 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * 0.0025,2) ";
            }else if($comissao == '0.75'){
                $separador_comissao[$comissao] = "WHEN  cod_cliente = '34.060.185/0001-41' and nota_numero = '000028381' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN  percentual_comissao >= 0.0 and percentual_comissao <= 5 and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }                    
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * 0.0075,2) ";
            }else{
                $separador_comissao[$comissao] = " WHEN cod_cliente = '34.060.185/0001-41' and nota_numero = '000028381' and " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }     
                }
                $separador_comissao[$comissao] .= ") THEN 0.0 ";
                
                $contador = 0;
                $separador_comissao[$comissao] .= " WHEN " . $titulosEmAbertoNasajonPortal->getTable() . ".vendedor_codigo in (";
                foreach($representantes as $representante){
                    if($contador == 0){
                        $separador_comissao[$comissao] .= "'".$representante."'";
                        $contador++;
                    }else{
                        $separador_comissao[$comissao] .= ",'".$representante."'";
                    }     
                }
                $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * (percentual_comissao/100),2) ";
            }
        }

        $query = '';
        foreach($separador_comissao as $separacao){
            $query .= $separacao;
        }
        return $query;
    }

    function separadorComissaoGerenteAberto($titulosEmAbertoNasajonPortal){
        $separador_comissao = [];
        $comissao_representantes = [];
        $usuarios = User::select()->where('tipo_usuario_id', 19)->whereNotIn('codigo_representante', ['999'])->get();
        foreach($usuarios as $usuario){
            $comissao_representantes[$usuario->comissao_a][] = $usuario->codigo_representante;
        }
        $quantidade = count($comissao_representantes);

        $campanhasComissaoCalculoObj = CampanhasComissaoCalculo::select()->whereNotNull('porcetagem_comissao_gerente')->whereNotNull('nota_id')->get();
        $contador = 0;

        foreach($campanhasComissaoCalculoObj as $value){
            $contador = 0;
            $nota_id = empty($value->nota_id)? null : $value->nota_id;
            $separador_comissao[$value->nota_id."campanha"] = " WHEN  nota_id in ('".$nota_id."')";
            $separador_comissao[$value->nota_id."campanha"] .= " THEN ROUND((saldotitulo - juros) * ".$value->porcetagem_comissao_gerente." / 100,2) ";
        }

        $contador = 0;
        foreach($comissao_representantes as $comissao => $representantes){
            $contador = 0;
            $separador_comissao[$comissao] = " WHEN  percentual_comissao >= 0.11 and percentual_comissao <= 0.33 and ".$titulosEmAbertoNasajonPortal->getTable().".vendedor_codigo in (";
            foreach($representantes as $representante){
                if($contador == 0){
                    $separador_comissao[$comissao] .= "'".$representante."'";
                    $contador++;
                }else{
                    $separador_comissao[$comissao] .= ",'".$representante."'";
                }                    
            }
            $separador_comissao[$comissao] .= ") THEN ROUND((saldotitulo - juros) * percentual_comissao / 100,2) ";
        }

        $query = '';
        foreach($separador_comissao as $separacao){
            $query .= $separacao;
        }
        $query .= 'ELSE ROUND((saldotitulo - juros) * 0.0011,2) ';

        return $query;
    }
}
