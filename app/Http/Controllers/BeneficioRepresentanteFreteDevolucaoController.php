<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Auth;
use Carbon\Carbon;

use App\User;
use App\Movimentacao;
use App\ClienteNasajon;

class BeneficioRepresentanteFreteDevolucaoController extends Controller
{
    public $cfop_devolucao = ['1201', '1202', '2201', '2202'];

    // public $cfop_venda = ['5922', '5949', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101'];
    public $cfop_venda = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];
    
    public function indexFreteDevolucao(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\BeneficioRepresentanteFreteDevolucao") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\BeneficioRepresentanteFreteDevolucao');

        $data = Carbon::now();
        $data = $data->format('Y');

        $representantes = $this->getRepresentante();

        return view('programs.beneficio_representante.frete_devolucao.index')->with(['data' => $data, 'representantes' => $representantes]);
    }

    private function getRepresentante(){
        $representantes_busca = User::select('codigo_representante', 'name')->where('codigo_representante', '!=', '')->orderBy('codigo_representante')->get()->toArray();
		$representantes = [];
		
		foreach ($representantes_busca as $key => $value) {
			$representantes[$value['codigo_representante']] = $value['codigo_representante']." - ".strtoupper($value['name']);
        }
        
        return $representantes;
    }

    public function filtroFreteDevolucao(Request $request){
        $fields = $request->only('ano', 'vendedor');

        if(in_array(Auth::user()->tipo_usuario_id,[12,16,22])){
            $fields['vendedor'] = Auth::user()->codigo_representante;
        }

        $ano_atual = intval($fields['ano']);
        $ano_anterior = $ano_atual - 1;
        
        $query_movimentacao = Movimentacao::select('vendedor', DB::raw("sum((quantidade * preco) + case when frete is not null then frete else 0 end + case when ipi is not null then ipi else 0 end + case when seguro is not null then seguro else 0 end - case when desconto is not null then desconto else 0 end + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total"));
        $query_movimentacao->where('sinal', 'ilike', 'saida');
        $query_movimentacao->whereBetween('data_movimentacao', [$ano_anterior.'-01-01', $ano_anterior.'-12-31']);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_venda);
        $query_movimentacao->with(['detalhesVendedor']);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        $query_movimentacao->whereNotNull('vendedor');
        $query_movimentacao->groupBy('vendedor');
        $result_movimentacao = $query_movimentacao->get();

        $movimentacoes = [];
        $total_vendas_ano = 0;
        $total_valor_credito = 0;
        $total_valor_devolucao = 0;
        $total_valor_frete = 0;
        $total_saldo = 0;
        foreach($result_movimentacao as $movimentacao){
            if($movimentacao->preco_total > 2400000){
                $valor_credito = 3000;
            }else{
                $valor_credito = 0;
            }
            $movimentacoes[$movimentacao->vendedor] = [
                'ano_atual' => $ano_atual,
                'codigo_vendedor' => $movimentacao->vendedor,
                'vendedor' => empty($movimentacao->detalhesVendedor)? $movimentacao->vendedor : $movimentacao->vendedor.' - '.$movimentacao->detalhesVendedor->name,
                'vendas_ano' => empty($movimentacao->preco_total)? 0 : $movimentacao->preco_total,
                'valor_credito' => empty($valor_credito)? 0 : $valor_credito,
                'valor_devolucao' => 0,
                'valor_frete' => 0,
                'saldo' => empty($valor_credito)? 0 : $valor_credito,
            ];
            $total_vendas_ano += empty($movimentacao->preco_total)? 0 : $movimentacao->preco_total;
            $total_valor_credito += empty($valor_credito)? 0 : $valor_credito;
            $total_saldo += empty($valor_credito)? 0 : $valor_credito;
        }

        $query_movimentacao = Movimentacao::select('vendedor', 'documento', 'estabelecimento', DB::raw("sum((quantidade * preco) + case when frete is not null then frete else 0 end + case when ipi is not null then ipi else 0 end + case when seguro is not null then seguro else 0 end - case when desconto is not null then desconto else 0 end + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total"));
        $query_movimentacao->with(['detalhesNotaDevolucao']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$ano_atual.'-01-01', $ano_atual.'-12-31']);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        if(!empty($fields['vendedor'])){
            $query_movimentacao->where('vendedor', $fields['vendedor']);
        }
        $query_movimentacao->groupBy('vendedor', 'documento', 'estabelecimento');
        $result_movimentacao = $query_movimentacao->get();
        foreach($result_movimentacao as $movimentacao){
            $valor_frete = 0;

            if(!empty($movimentacao->detalhesNotaDevolucao)){
                if($movimentacao->detalhesNotaDevolucao->responsabilidade_frete === 'representante'){
                    $valor_frete = empty($movimentacao->detalhesNotaDevolucao->frete_valor)? 0 : $movimentacao->detalhesNotaDevolucao->frete_valor;
                }
            }
            
            if(!empty($movimentacoes[$movimentacao->vendedor])){
                if(empty($movimentacoes[$movimentacao->vendedor]['valor_devolucao'])){
                    $movimentacoes[$movimentacao->vendedor]['valor_devolucao'] = empty($movimentacao->preco_total)? 0 : $movimentacao->preco_total;
                    $movimentacoes[$movimentacao->vendedor]['valor_frete'] = $valor_frete;
                }else{
                    $movimentacoes[$movimentacao->vendedor]['valor_devolucao'] += empty($movimentacao->preco_total)? 0 : $movimentacao->preco_total;
                    $movimentacoes[$movimentacao->vendedor]['valor_frete'] += $valor_frete;
                }
                $movimentacoes[$movimentacao->vendedor]['saldo'] -= $valor_frete;

                $total_valor_devolucao += empty($movimentacao->preco_total)? 0 : $movimentacao->preco_total;
                $total_valor_frete += $valor_frete;
                $total_saldo -= $valor_frete;
            }
        }

        $total_vendas_ano = empty($total_vendas_ano)? '' : parserValor($total_vendas_ano);
        $total_valor_credito = empty($total_valor_credito)? '' : parserValor($total_valor_credito);
        $total_valor_devolucao = empty($total_valor_devolucao)? '' : parserValor($total_valor_devolucao);
        $total_valor_frete = empty($total_valor_frete)? '' : parserValor($total_valor_frete);
        $total_saldo = empty($total_saldo)? '' : parserValor($total_saldo);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'movimentacoes' => $this->ajusteArrayParaValores($movimentacoes),
                'total_vendas_ano' => $total_vendas_ano,
                'total_valor_credito' => $total_valor_credito,
                'total_valor_devolucao' => $total_valor_devolucao,
                'total_valor_frete' => $total_valor_frete,
                'total_saldo' => $total_saldo,
            ],
        ]);
    }

    public function clientesExcluido(){
        $clientes_exluir = ClienteNasajon::select('codigo')
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
            ->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
            ->get();
        $clientes_exluir = $clientes_exluir->pluck('codigo')->toArray();

        return $clientes_exluir;
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

    public function modalDevolucaoFreteDevolucao(Request $request){
        $fields = $request->only('ano_atual', 'codigo_vendedor');

        $ano_atual = intval($fields['ano_atual']);

        $query_movimentacao = Movimentacao::select('vendedor', 'documento', 'estabelecimento', 'cliente_codigo', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade', DB::raw('sum(quantidade) as quantidade_total'), DB::raw("sum((quantidade * preco) + case when frete is not null then frete else 0 end + case when ipi is not null then ipi else 0 end + case when seguro is not null then seguro else 0 end - case when desconto is not null then desconto else 0 end + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total"));
        $query_movimentacao->with(['cliente']);
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$ano_atual.'-01-01', $ano_atual.'-12-31']);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        $query_movimentacao->groupBy('vendedor', 'documento', 'estabelecimento', 'cliente_codigo', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade');
        $result_movimentacao = $query_movimentacao->get();

        $produtos = [];
        $clientes = [];
        $total_quantidade = 0;
        $total_valor = 0;
        foreach($result_movimentacao as $movimentacao){
            if(empty($produtos[$movimentacao->produto_codigo])){
                $produtos[$movimentacao->produto_codigo] = [
                    'codigo' => $movimentacao->produto_codigo,
                    'descricao' => $movimentacao->descricao,
                    'marca' => $movimentacao->marca,
                    'linha' => $movimentacao->linha,
                    'grupo' => $movimentacao->grupo,
                    'quantidade' => $movimentacao->quantidade_total,
                    'valor' => $movimentacao->preco_total,
                    'unidade' => $movimentacao->unidade,
                ];
            }else{
                $produtos[$movimentacao->produto_codigo]['quantidade'] += $movimentacao->quantidade_total;
                $produtos[$movimentacao->produto_codigo]['valor'] += $movimentacao->preco_total;
            }
            
            if(empty($clientes[$movimentacao->cliente_codigo])){
                $clientes[$movimentacao->cliente_codigo] = [
                    'cliente' => $movimentacao->cliente->nome.' - '.$movimentacao->cliente->cpf_cnpj,
                    'quantidade' => $movimentacao->quantidade_total,
                    'valor' => $movimentacao->preco_total,
                ];
            }else{
                $clientes[$movimentacao->cliente_codigo]['quantidade'] += $movimentacao->quantidade_total;
                $clientes[$movimentacao->cliente_codigo]['valor'] += $movimentacao->preco_total;
            }

            $total_quantidade += $movimentacao->quantidade_total;
            $total_valor += $movimentacao->preco_total;
        }

        $produtos = $this->ajusteArrayParaValores($produtos);
        $clientes = $this->ajusteArrayParaValores($clientes);
        
        $total_quantidade = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $total_valor = empty($total_valor)? '' : parserValor($total_valor);

        return view('programs.beneficio_representante.frete_devolucao.modal.devolucao')->with(['produtos' => $produtos, 'clientes' => $clientes, 'total_quantidade' => $total_quantidade, 'total_valor' => $total_valor, 'ano_atual' => $ano_atual, 'codigo_vendedor'  => $fields['codigo_vendedor']]);
    }

    public function filtroDevolucaoProdutoFreteDevolucao(Request $request){
        $fields = $request->only('ano_atual', 'codigo_vendedor', 'codigo_produto', 'descricao_produto', 'marca_produto', 'linha_produto', 'grupo_produto', 'subgrupo_produto');

        $ano_atual = intval($fields['ano_atual']);

        $query_movimentacao = Movimentacao::select('vendedor', 'documento', 'estabelecimento', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade', DB::raw('sum(quantidade) as quantidade_total'), DB::raw("sum((quantidade * preco) + case when frete is not null then frete else 0 end + case when ipi is not null then ipi else 0 end + case when seguro is not null then seguro else 0 end - case when desconto is not null then desconto else 0 end + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total"));
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$ano_atual.'-01-01', $ano_atual.'-12-31']);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);

        if(!empty('codigo_produto')){
            $query_movimentacao->where('produto_codigo', $fields['codigo_produto']);
        }
        if(!empty('descricao_produto')){
            $query_movimentacao->where('descricao', $fields['descricao_produto']);
        }
        if(!empty('marca_produto')){
            $query_movimentacao->where('marca', $fields['marca_produto']);
        } 
        if(!empty('linha_produto')){
            $query_movimentacao->where('linha', $fields['linha_produto']);
        } 
        if(!empty('grupo_produto')){
            $query_movimentacao->where('grupo', $fields['grupo_produto']);
        }
        if(!empty('subgrupo_produto')){
            $query_movimentacao->where('subgrupo', $fields['subgrupo_produto']);
        }

        $query_movimentacao->groupBy('vendedor', 'documento', 'estabelecimento', 'produto_codigo', 'descricao', 'marca', 'linha', 'grupo', 'unidade');
        $result_movimentacao = $query_movimentacao->get();

        $produtos = [];
        $clientes = [];
        $total_quantidade = 0;
        $total_valor = 0;
        foreach($result_movimentacao as $movimentacao){
            if(empty($produtos[$movimentacao->produto_codigo])){
                $produtos[$movimentacao->produto_codigo] = [
                    'codigo' => $movimentacao->produto_codigo,
                    'descricao' => $movimentacao->descricao,
                    'marca' => $movimentacao->marca,
                    'linha' => $movimentacao->linha,
                    'grupo' => $movimentacao->grupo,
                    'quantidade' => $movimentacao->quantidade_total,
                    'valor' => $movimentacao->preco_total,
                    'unidade' => $movimentacao->unidade,
                ];
            }else{
                $produtos[$movimentacao->produto_codigo]['quantidade'] += $movimentacao->quantidade_total;
                $produtos[$movimentacao->produto_codigo]['valor'] += $movimentacao->preco_total;
            }

            $total_quantidade += $movimentacao->quantidade_total;
            $total_valor += $movimentacao->preco_total;
        }

        $produtos = $this->ajusteArrayParaValores($produtos);
        
        $total_quantidade = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $total_valor = empty($total_valor)? '' : parserValor($total_valor);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'produtos' => $produtos,
                'total_quantidade' => $total_quantidade,
                'total_valor' => $total_valor,
            ],
        ]);
    }

    public function filtroDevolucaoClienteFreteDevolucao(){
        $fields = $request->only('ano_atual', 'codigo_vendedor', 'cliente');

        $ano_atual = intval($fields['ano_atual']);

        $query_movimentacao = Movimentacao::select('vendedor', 'documento', 'estabelecimento', 'cliente_codigo', DB::raw('sum(quantidade) as quantidade_total'), DB::raw("sum((quantidade * preco) + case when frete is not null then frete else 0 end + case when ipi is not null then ipi else 0 end + case when seguro is not null then seguro else 0 end - case when desconto is not null then desconto else 0 end + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total"));
        $query_movimentacao->with(['cliente' => 'cliente', function($query) use($fields){
            if(!empty($fields['cliente'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
            }
        }]);
        $query_movimentacao->whereHas('cliente', function($query) use($fields){
            if(!empty($fields['cliente'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente']).'%');
            }
        });
        $query_movimentacao->where('sinal', 'ilike', 'entrada');
        $query_movimentacao->whereBetween('data_movimentacao', [$ano_atual.'-01-01', $ano_atual.'-12-31']);
        $query_movimentacao->whereNotIn('cliente_codigo', $this->clientesExcluido());
        $query_movimentacao->whereIn('cfop', $this->cfop_devolucao);
        $query_movimentacao->where('vendedor', $fields['codigo_vendedor']);
        $query_movimentacao->groupBy('vendedor', 'documento', 'estabelecimento', 'cliente_codigo');
        $result_movimentacao = $query_movimentacao->get();

        $clientes = [];
        $total_quantidade = 0;
        $total_valor = 0;
        foreach($result_movimentacao as $movimentacao){
            if(empty($clientes[$movimentacao->cliente_codigo])){
                $clientes[$movimentacao->cliente_codigo] = [
                    'cliente' => $movimentacao->cliente->nome.' - '.$movimentacao->cliente->cpf_cnpj,
                    'quantidade' => $movimentacao->quantidade_total,
                    'valor' => $movimentacao->preco_total,
                ];
            }else{
                $clientes[$movimentacao->cliente_codigo]['quantidade'] += $movimentacao->quantidade_total;
                $clientes[$movimentacao->cliente_codigo]['valor'] += $movimentacao->preco_total;
            }

            $total_quantidade += $movimentacao->quantidade_total;
            $total_valor += $movimentacao->preco_total;
        }

        $clientes = $this->ajusteArrayParaValores($clientes);
        
        $total_quantidade = empty($total_quantidade)? '' : parserValor($total_quantidade);
        $total_valor = empty($total_valor)? '' : parserValor($total_valor);

        return response()->json([
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => [
                'clientes' => $clientes,
                'total_quantidade' => $total_quantidade,
                'total_valor' => $total_valor,
            ],
        ]);
    }
}
