@extends('layouts.page-dialog')

@section('content')
<div class="col-lg-12">
	<div class="row">
		<div class="col-sm-4">
            <b>Vendedor</b><br />
            @if((Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105]) ) && $info_pedido['permitir_edicao'] === true)
                {{ Form::select('vendedor', $info_pedido['vendedores_array'], $info_pedido['vendedor'], ['id' => 'vendedor', 'class' => 'form-control'])}}
                <button class='btn btn-modifica float-left' id="aplicar" onclick="aplicarNovoVendedor('{{ $info_pedido['hash'] }}')">Modificar vendedor</button>
            @else
            {{ $info_pedido['vendedores_array'][$info_pedido['vendedor']] }}
            @endif
        </div>
        @if(!is_null($info_pedido['id']))
        <div class="col-sm-4">
            <b>Pedido Web</b> <br />
            <a href="#" onclick="abrirPedidoWeb('{{ $info_pedido['id'] }}');">{{ $info_pedido['id'] }}</a>
        </div>
        @endif
        <div class="col-sm-4">
            @if (empty($info_pedido['origem']))
            <b>Pedido Prologos</b> <br />
            <a href="#" onclick="showItens('{{ $info_pedido['pedido_gerado'] }}', 'pedido');">{{ $info_pedido['pedido_gerado'] }}</a>
            @else 
            <b>Pedido Nasajon</b> <br />
            <a href="#" onclick="showItens('{{ $info_pedido['id_pedido'] }}', 'nasajon');">{{ $info_pedido['pedido_gerado'] }}</a>
            @endif

        </div>
	</div>
	<div class='pedido_detalhes_content'>
        <div class="row">
            <div class="col-sm-4">
                <b>Origem</b> <br />
                {{ $info_pedido['estabelecimento'] }}
            </div>
            <div class="col-sm-4">
                <b>Destino</b> <br />
                {{ $info_pedido['localizacao_cliente'] }}
            </div>
            <div class="col-lg-4">
                <b>Frete aplicado nos itens:</b><br />
                {{ strtoupper($info_pedido['frete_preco']) }} - {{ $info_pedido['frete_aplicado'] }}
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-lg-4">
                <b>Prazo Médio, em dias:</b><br />
                {{ strtoupper($info_pedido['prazo_medio']) }}
            </div>
            <div class="col-lg-4">
                <b>Valor total do pedido</b><br />
                {{ $info_pedido['valor_total'] }}
            </div>
            <div class="col-lg-4">
                <b>Valor da comissão</b><br />
                {{ $info_pedido['comissao_pedido_valor'] }}
            </div>
        </div>
        <div class='row mt-4'>
            <div class="col-lg-4">
                <b>Comissão do Pedido</b><br />
                Comissão na nota: {{ $info_pedido['comissao_na_nota']??0 }}% <br />
                Comissões dos itens/Total do pedido
                @if((Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105])) && $info_pedido['permitir_edicao'] === true)
                    <div class="input-group">
                        {{ Form::text('comissao', (isset($info_pedido['retorno_pre']) && $info_pedido['retorno_pre'] == true) ? parserValor($info_pedido['comissao_pedido'] / 2) : parserValor($info_pedido['comissao_pedido']), ['id' => 'comissao', 'class' => 'form-control'])}}
                        <div class="input-group-append">
                            <div class="input-group-text">%</div>
                        </div>
                    </div>
                    <br />
                    <button class='btn btn-modifica float-left' id="aplicar" onclick="aplicarNovaComissao('{{ $info_pedido['hash'] }}')">Aplicar nova comissão</button>
                @else
                    {{ $info_pedido['comissao_pedido'] }}%
                @endif
            </div>
            @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                <div class="col-lg-4">
                <b>Fator de cálculo para o prazo</b><br />
                Prazo médio x (0,04% ao dia) =  {{ $info_pedido['fator_prazo'] }}%
            </div>
            @endif
            
        </div>
    </div>
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
            <thead>
                <tr>
                        <th rowspan='2'>Código</th>
                        {{-- <th rowspan='2'>Descrição</th> --}}
                        <th rowspan='2'>Qtd. no portal</th>
                        <th rowspan='2'>Qtd. na nota</th>
                        {{-- <th rowspan='2'>Devolvidos</th> --}}

                        <th class="tb_number" rowspan='2'>Preço Portal</th>
                        <th class="tb_number" rowspan='2'>Preço na Nota</th>
                        <th rowspan='2'>Total Pedido</th>
                        <th rowspan='2'>Total Nota</th>
                        @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                        <th class="tb_number" rowspan='2'>Comissão Pedido</th>
                        @endif
                        {{-- <th class="tb_number" rowspan='2'>Preço Base</th>
                        <th class="tb_number" rowspan='2'>Alíquota Base</th> --}}
                        <th class="tb_number" rowspan='2'>Alíquota Aplicada</th>
                        <th class="tb_number" rowspan='2'>IPI Aplicado</th>
                        <th colspan='5'>Faixas de Comissão</th>
                        <th class="tb_number" rowspan='2'>Comissão</th>
                        <th class="tb_number" rowspan='2'>Comissão Campanha</th>
                        <th class="tb_number" rowspan='2'>Total  Perc. Comissão</th>
                        <th class="tb_number" rowspan="2">Comissão em Reais<i class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" data-original-title="Cálculo de Comissão: (QTD. Na Nota * Preço na Nota) * Total Perc. Comissão / 100" do="" pedido.="" style="color: black;"></i></th>
                    </tr>
                    <tr>
                        <th class="tb_number">2%</th>
                        <th class="tb_number">2,5%</th>
                        <th class="tb_number">3%</th>
                        <th class="tb_number">4%</th>
                        <th class="tb_number">5%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($info_pedido['itens'] as $value)
                        @if(!empty($value['incentivo_comissao']))
                            <tr>
                                <td><b>{{ $value['codigo'] }}</b></td>
                                {{-- <td>{{ $value['nome'] }}</td> --}}
                                <td class='text-right'><b>{{ $value['quantidade'] }}</b></td>
                                <td class='text-right'><b>{{ $value['quantidade_pedido'] }}</td>
                                {{-- <td>{{ $value['devolvidos'] }}</td> --}}
                                <td class='text-right'><b>{{ $value['preco_unitario_digitado'] }}</b></td>
                                <td class='text-right'><b>{{ $value['preco_unitario_pedido'] }}</b></td>
                                <td class='text-right'><b>{{ $value['total'] }}</b></td>
                                <td class='text-right'><b>{{ $value['total_nota'] }}</b></td>
                                @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                                <td class='text-right'><b>{{ $value['comissao_calculada'] }}</b></td>
                                @endif
                                {{-- <td class='text-right'>{{ $value['preco_base'] }}</td>
                                <td class='text-right'>{{ $value['aliquota_base'] }}</td> --}}
                                <td class='text-right'><b>{{ $value['aliquota_aplicada'] }}</b></td>
                                <td class='text-right'><b>{{ $value['ipi_aplicado'] }}</b></td>
                                <td class='text-right'><b>< {{ $value['dois'] }}</b></td>
                                <td class='text-right'><b>< {{ $value['dois_e_meio'] }}</b></td>
                                <td class='text-right'><b>>= {{ $value['coluna_a'] }}</b></td>
                                <td class='text-right'><b>>= {{ $value['coluna_b'] }}</b></td>
                                <td class='text-right'><b>>= {{ $value['coluna_c'] }}</b></td>
                                <td class='text-right'><b>{{ $value['comissao_recalculada'] }} <i class='btn-informacao-sem-alinhamento' data-toggle='tooltip' data-placement='right' data-original-title='Percentual Base' do Pedido. style='color: black;'></i></b></td>
                                <td class='text-right'><b>{{ $value['incentivo_comissao'] }}</b></td>
                                <td class='text-right'><b>{!! !empty(($value['total_percentual'])) ? $value['total_percentual']  : ''!!}</b></td>
                                <td class='text-right'><b>{{ $value['comissao_valor'] }}</b></td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $value['codigo'] }}</td>
                                {{-- <td>{{ $value['nome'] }}</td> --}}
                                <td class='text-right'>{{ $value['quantidade'] }}</td>
                                <td class='text-right'>{{ $value['quantidade_pedido'] }}</td>
                                {{-- <td>{{ $value['devolvidos'] }}</td> --}}
                                <td class='text-right'>{{ $value['preco_unitario_digitado'] }}</td>
                                <td class='text-right'>{{ $value['preco_unitario_pedido'] }}</td>
                                <td class='text-right'>{{ $value['total'] }}</td>
                                <td class='text-right'>{{ $value['total_nota'] }}</td>
                                @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                                <td class='text-right'>{{ $value['comissao_calculada'] }}</td>
                                @endif
                                {{-- <td class='text-right'>{{ $value['preco_base'] }}</td>
                                <td class='text-right'>{{ $value['aliquota_base'] }}</td> --}}
                                <td class='text-right'>{{ $value['aliquota_aplicada'] }}</td>
                                <td class='text-right'>{{ $value['ipi_aplicado'] }}</td>
                                <td class='text-right'>< {{ $value['dois'] }}</td>
                                <td class='text-right'>< {{ $value['dois_e_meio'] }}</td>
                                <td class='text-right'>>= {{ $value['coluna_a'] }}</td>
                                <td class='text-right'>>= {{ $value['coluna_b'] }}</td>
                                <td class='text-right'>>= {{ $value['coluna_c'] }}</td>
                                <td class='text-right'>{{ $value['comissao_recalculada'] }}<i class='btn-informacao-sem-alinhamento' data-toggle='tooltip' data-placement='right' data-original-title='Percentual do Pedido' do Pedido. style='color: black;'></i></td>
                                <td class='text-right'></td>
                                <td class='text-right'>{!! !empty(($value['total_percentual'])) ? $value['total_percentual']  : ''!!}</td>
                                <td class='text-right'>{{ $value['comissao_valor'] }}</td>
                            </tr>
                        @endif
                    @endforeach
                
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('#comissao').maskMoney({thousands:'', decimal:','});
    });

    @if(Auth::user()->tipo_usuario->nome == 'Administrador' || in_array(Auth::id(), [46, 26, 105]))
    function aplicarNovaComissao($hash){
        $.ajax({
            url: '{{ route('revisao_comissao.aplicar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash,
                comissao: $(document).find("#comissao").val(),
            },
            method: 'POST',
            success: function(data){
                $('#pedido_modal_comissoes').modal('hide');
                message('Sucesso', 'Comissão aplicada com sucesso!');
            },
            error: function (data){
                message('Erro', 'Erro ao aplicar a comissão!');
            }
        });
    }

    function aplicarNovoVendedor($hash){
        $.ajax({
            url: '{{ route('revisao_comissao.nasajon.aplicar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash,
                vendedor: $(document).find("#vendedor").val(),
                comissao: '{{ $info_pedido['comissao_na_nota'] }}'
            },
            method: 'POST',
            success: function(data){
                $('#pedido_modal_comissoes').modal('hide');
                message('Sucesso', 'Novo vendedor aplicado com sucesso');
            },
            error: function (data){
                message('Erro', 'Erro ao atualizar.');
            }
        });
    }

    @endif

    function abrirPedidoWeb($id){
        $.ajax({
            url: '{{ route('pedido_portal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Pedido', data, 'modal-lg');
            }
        });
    }

    function showItens($pedido, $origem){
        if ($origem == 'nasajon'){
            var title = "Dados do pedido";
        }
        else{
            var title = "Dados do pedido: "+$pedido;
        }
        xhr = $.ajax({
            url: "{{ route('pedidos_orcamentos.show') }}",
            data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido},
            method: 'POST',
            success: function(body){
                createModal("itens_pedido", title, body, 'modal-lg');
            }
        });
    }
</script>
@endsection