<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Carbon\Carbon;

use App\ClienteNasajon;
use App\NotaItensNasajon;
use App\NotasNasajon;
use App\VendedorComissaoNota;
use App\Movimentacao;

use Illuminate\Support\Facades\DB;


class ConsultaClienteGrupoController extends Controller
{
    private $cfop_vendas = ['5922', '6108', '6110', '6119', '6123', '6106', '5123', '6118', '5118', '5122', '5551', '6551', '5102', '6102', '5106', '5101', '6101', '5104', '6104'];

    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ConsultaClienteGrupo") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ConsultaClienteGrupo');

        $respresentantes = [];
        if(Auth::user()->tipo_usuario_id == 19 || Auth::user()->tipo_usuario_id == 14){
            foreach(Auth::user()->subordinados as $subordinado){
                if(!empty($subordinado->codigo_representante)){
                    $respresentantes[] = $subordinado->codigo_representante;
                }
            }

            if(!empty(Auth::user()->codigo_representante)){
                $respresentantes[] = Auth::user()->codigo_representante;
            }
        }else if(Auth::user()->tipo_usuario_id == 12 || Auth::user()->tipo_usuario_id == 16 || Auth::user()->tipo_usuario_id == 11){
            if(!empty(Auth::user()->codigo_representante)){
                $respresentantes[] = Auth::user()->codigo_representante;
            }
        }else if(Auth::user()->tipo_usuario_id == 13){
            foreach(Auth::user()->supervisorEquipe as $subordinado){
                if(!empty($subordinado->codigo_representante)){
                    $respresentantes[] = $subordinado->codigo_representante;
                }
            }

            if(!empty(Auth::user()->codigo_representante)){
                $respresentantes[] = Auth::user()->codigo_representante;
            }
        }

        return view('programs.consulta_cliente_grupo.index')->with(['respresentantes' => encrypt($respresentantes)]);
    }

    public function filtro(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('grupo', 'descricao', 'codigo', 'cliente_nome', 'subgrupo', 'marca', 'linha', 'data_inicio', 'data_fim', 'respresentantes');
        $itens = $this->itensVendaNasajon($fields);

        $retorno = [
            'status' => 'sucess',
            'message' => '',
            'error' => '',
            'response' => $itens
        ];
        return response()->json($retorno);
    }

    public function itensVendaNasajon($fields){
        $data_inicio = $fields['data_inicio'];
        $data_fim = $fields['data_fim'];

        $fields['respresentantes'] = decrypt($fields['respresentantes']);

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';

        $query = Movimentacao::select(DB::raw('*, ((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query->whereBetween('data_movimentacao', [$data_inicio, $data_fim]);
        $query->whereIn('cfop', $this->cfop_vendas);

        if(!empty($fields['cliente_nome'])){
            $query->with(['cliente' => function($query) use($fields){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($fields['cliente_nome']).'%');
            }]);
        }

        if(!empty($fields['respresentantes'])){
            $query->whereIn('vendedor', $fields['respresentantes']);
        }
        if(isset($fields['codigo'])){
            $query->where('produto_codigo', 'ilike', '%' . $fields['codigo'] . '%');
        }

        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.$fields['grupo'].'%');
        }

        if(!empty($fields['subgrupo'])){
            $query->where('subgrupo', 'ilike', '%'.$fields['subgrupo'].'%');
        }

        if(!empty($fields['descricao'])){
            $query->where('descricao', 'ilike', '%'.$fields['descricao'].'%');
        }

        if(!empty($fields['marca'])){
            $query->where('marca', 'ilike', '%'.$fields['marca'].'%');
        }

        if(!empty($fields['linha'])){
            $query->where('linha', 'ilike', '%'.$fields['linha'].'%');
        }
        $result = $query->get();

        $itens = [];
        $total = ["quantidade" => 0, "valor" => 0, "grupo" => "", "data_inicial" => $data_inicio, "data_final" => $data_fim, "filtro" => encrypt($fields)];
        foreach($result as $item){
            if(empty( $itens[$item->grupo])){
                $itens[$item->grupo] = [
                    'grupo' => $item->grupo,
                    'quantidade' => $item->quantidade,
                    'preco_total' => $item->preco_total,
                    'data_inicial' => $data_inicio,
                    'data_final' => $data_fim,
                    'filtro' => encrypt($fields),
                ];
            }else{
                $itens[$item->grupo]['quantidade'] += $item->quantidade;
                $itens[$item->grupo]['preco_total'] += ($item->preco_total);
            }
            $total["quantidade"] += $item->quantidade; 
            $total["valor"] += ($item->preco_total); 
        }
        $itens = $this->ajusteArrayParaValores($itens);
        $total = $this->ajusteArrayParaValores($total);

        $retorno = [
            'itens' => $itens,
            'total' => $total,
        ];

        return $retorno;
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
                                //$array[$key] = empty($value)? '': parserValor($value);
                                if($key == 'diferenca_meta'){
                                    $array[$key] = empty($value)? '': parserValor($value);
                                }else{
                                    $array[$key] = $value > 0 ?  parserValor($value) : '';
                                }
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

    function modalDetalhes(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('grupo', 'data_inicial', 'data_final', 'filtro');
        
        $filtro = decrypt($fields['filtro']);

        $data_inicio = $filtro['data_inicio'];
        $data_fim = $filtro['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';

        $query = Movimentacao::select(DB::raw('*, ((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query->whereBetween('data_movimentacao', [$data_inicio, $data_fim]);
        $query->whereIn('cfop', $this->cfop_vendas);

        $query->with(['notasNasajon', 'cliente' => function($query) use($filtro){
            if(!empty($filtro['cliente_nome'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($filtro['cliente_nome']).'%');
            }
        }]);        

        if(!empty($filtro['respresentantes'])){
            $query->whereIn('vendedor', $filtro['respresentantes']);
        }
        if(isset($fields['codigo'])){
            $query->where('produto_codigo', 'ilike', '%' . $filtro['codigo'] . '%');
        }

        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.$fields['grupo'].'%');
        }

        if(!empty($filtro['subgrupo'])){
            $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo'].'%');
        }

        if(!empty($filtro['descricao'])){
            $query->where('descricao', 'ilike', '%'.$filtro['descricao'].'%');
        }

        if(!empty($filtro['marca'])){
            $query->where('marca', 'ilike', '%'.$filtro['marca'].'%');
        }

        if(!empty($filtro['linha'])){
            $query->where('linha', 'ilike', '%'.$filtro['linha'].'%');
        }
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $itens = [];
        $total = ["quantidade" => 0, "valor" => 0];
        foreach($result as $item){
            $liberado = false;
            if(!empty($filtro['cliente_nome'])){
                $liberado = empty($item->cliente)? false: true;
            }else{
                $liberado = true;
            }
            if($liberado){
                $itens[] = [
                    'cod_estabelecimento' => $item->estabelecimento,
                    'estabelecimento' => $estabelecimentos[intval($item->estabelecimento)],
                    'data' => empty($item->notasNasajon)? parserData($item->data_movimentacao) : parserData($item->notasNasajon->emissao),
                    'codigo' => $item->produto_codigo,
                    'descricao' => $item->descricao,
                    'quantidade' => parserQtd($item->quantidade),
                    'preco_total' => parserQtd($item->preco_total),
                    'nota_codigo' => $item->documento,
                    'id_nota' => empty($item->notasNasajon)? '': $item->notasNasajon->id,
                    'documento' => $item->documento,
                    'origem' => 'nasajon',
                    'pedido_origem' => 'nasajon',
                    'cliente' => $item->cliente->nome." - ".$item->cliente->cpf_cnpj
                ];
                $total["quantidade"] += $item->quantidade; 
                $total["valor"] += $item->preco_total; 
            }                      
        }
        $itens = $this->ajusteArrayParaValores($itens);
        $total = $this->ajusteArrayParaValores($total);

        return view('programs.consulta_cliente_grupo.modal.detalhes')->with(['itens' => $itens, 'total' => $total]);
    }

    function modalDetalhesCliente(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('grupo', 'data_inicial', 'data_final', 'filtro');

        $filtro = decrypt($fields['filtro']);

        $data_inicio = $filtro['data_inicio'];
        $data_fim = $filtro['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';

        $query = Movimentacao::select(DB::raw('*, ((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query->whereBetween('data_movimentacao', [$data_inicio, $data_fim]);
        $query->whereIn('cfop', $this->cfop_vendas);

        $query->with(['cliente' => function($query) use($filtro){
            if(!empty($filtro['cliente_nome'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($filtro['cliente_nome']).'%');
            }
        }]);        

        if(!empty($filtro['respresentantes'])){
            $query->whereIn('vendedor', $filtro['respresentantes']);
        }
        if(isset($fields['codigo'])){
            $query->where('produto_codigo', 'ilike', '%' . $filtro['codigo'] . '%');
        }

        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.$fields['grupo'].'%');
        }

        if(!empty($filtro['subgrupo'])){
            $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo'].'%');
        }

        if(!empty($filtro['descricao'])){
            $query->where('descricao', 'ilike', '%'.$filtro['descricao'].'%');
        }

        if(!empty($filtro['marca'])){
            $query->where('marca', 'ilike', '%'.$filtro['marca'].'%');
        }

        if(!empty($filtro['linha'])){
            $query->where('linha', 'ilike', '%'.$filtro['linha'].'%');
        }
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $itens = [];
        $total = ["quantidade" => 0, "valor" => 0];
        foreach($result as $item){
            if(empty($itens[$item->cliente->cliente_documento])){
                $filtro_cliente = $filtro;
                $filtro_cliente["cliente_nome"] = $item->cliente->nome." - ".$item->cliente->cpf_cnpj;
                $itens[$item->cliente->codigo] = [
                    'quantidade' => 0,
                    'preco_total' => 0,
                    'cliente' => $item->cliente->nome." - ".$item->cliente->cpf_cnpj,
                    'filtro' => encrypt($filtro_cliente),
                ];
            } 
            $itens[$item->cliente->codigo]['quantidade'] += $item->quantidade; 
            $itens[$item->cliente->codigo]['preco_total'] += $item->preco_total;       
            
            $total["quantidade"] += $item->quantidade; 
            $total["valor"] += $item->preco_total;  
        }
        $itens = $this->ajusteArrayParaValores($itens);
        $total = $this->ajusteArrayParaValores($total);

        $grupo = $fields['grupo'];
        $filtro = $fields['filtro'];

        return view('programs.consulta_cliente_grupo.modal.detalhes_clientes')->with([
            'itens' => $itens, 
            'total' => $total,
            'grupo' => $grupo,
            'data_inicial' => $data_inicio,
            'data_final' => $data_fim,
            'filtro' => $filtro
        ]);
    }

    function modalDetalhesVendedor(Request $request){
        set_time_limit(600);
        ini_set('memory_limit','1024M');
        $fields = $request->only('grupo', 'data_inicial', 'data_final', 'filtro');

        $filtro = decrypt($fields['filtro']);

        $data_inicio = $filtro['data_inicio'];
        $data_fim = $filtro['data_fim'];

        $data_inicio = Carbon::createFromFormat('d/m/Y', $data_inicio)->format('Y-m-d').' 00:00:00';
        $data_fim = Carbon::createFromFormat('d/m/Y', $data_fim)->format('Y-m-d').' 23:59:59';

        $notaItensNasajonObj = new NotaItensNasajon;
        $notasNasajonObj = new NotasNasajon;
        $vendedorComissaoNotaObj = new VendedorComissaoNota;

        $query = Movimentacao::select(DB::raw('*, ((quantidade * preco) + frete + ipi + seguro - desconto + case when preco_prepago is not null then preco_prepago else 0 end) as preco_total'));
        $query->whereBetween('data_movimentacao', [$data_inicio, $data_fim]);
        $query->whereIn('cfop', $this->cfop_vendas);

        $query->with(['detalhesVendedor', 'cliente' => function($query) use($filtro){
            if(!empty($filtro['cliente_nome'])){
                $query->where(DB::raw('CONCAT(TRIM(nome),\' - \', cpf_cnpj)'), 'ilike', '%'.($filtro['cliente_nome']).'%');
            }
        }]);        

        if(!empty($filtro['respresentantes'])){
            $query->whereIn('vendedor', $filtro['respresentantes']);
        }
        if(isset($fields['codigo'])){
            $query->where('produto_codigo', 'ilike', '%' . $filtro['codigo'] . '%');
        }

        if(!empty($fields['grupo'])){
            $query->where('grupo', 'ilike', '%'.$fields['grupo'].'%');
        }

        if(!empty($filtro['subgrupo'])){
            $query->where('subgrupo', 'ilike', '%'.$filtro['subgrupo'].'%');
        }

        if(!empty($filtro['descricao'])){
            $query->where('descricao', 'ilike', '%'.$filtro['descricao'].'%');
        }

        if(!empty($filtro['marca'])){
            $query->where('marca', 'ilike', '%'.$filtro['marca'].'%');
        }

        if(!empty($filtro['linha'])){
            $query->where('linha', 'ilike', '%'.$filtro['linha'].'%');
        }
        $result = $query->get();

        $estabelecimentos = returnEmpresasNasajonView();
        $itens = [];
        $total = ["quantidade" => 0, "valor" => 0];
        foreach($result as $item){
            if(empty($itens[$item->vendedor])){
                $filtro_vendedor = $filtro;
                $filtro_vendedor['respresentantes'] = [$item->vendedor];
                $itens[$item->vendedor] = [
                    'quantidade' => 0,
                    'preco_total' => 0,
                    'origem' => 'nasajon',
                    'pedido_origem' => 'nasajon',
                    'vendedor' => $item->vendedor." - ".$item->detalhesVendedor->name,
                    'filtro' => encrypt($filtro_vendedor),
                ];
            } 
            $itens[$item->vendedor]['quantidade'] += $item->quantidade; 
            $itens[$item->vendedor]['preco_total'] += $item->preco_total;       
            
            $total["quantidade"] += $item->quantidade; 
            $total["valor"] += $item->preco_total;  
        }
        $itens = $this->ajusteArrayParaValores($itens);
        $total = $this->ajusteArrayParaValores($total);

        $grupo = $fields['grupo'];
        $filtro = $fields['filtro'];

        return view('programs.consulta_cliente_grupo.modal.detalhes_vendedor')->with([
            'itens' => $itens, 
            'total' => $total,
            'grupo' => $grupo,
            'data_inicial' => $data_inicio,
            'data_final' => $data_fim,
            'filtro' => $filtro
        ]);
    }
}
