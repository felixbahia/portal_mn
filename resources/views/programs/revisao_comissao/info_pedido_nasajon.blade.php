@extends('layouts.page-dialog')

@section('content')
<div class="col-lg-12">
	<div class="row">
		<div class="col-sm-4">
            <b>Vendedor</b><br />
            @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
                {{ Form::select('vendedor', $info_pedido['vendedores_array'], $info_pedido['vendedor'], ['id' => 'vendedor', 'class' => 'form-control'])}}
            @else
            {{ $info_pedido['vendedores_array'][$info_pedido['vendedor']] }}
            @endif
        </div>
        <div class="col-sm-4">
            <b>Pedido Web</b> <br />
            <a href="#" onclick="abrirPedidoWeb('{{ $info_pedido['id'] }}');">{{ $info_pedido['id'] }}</a>
        </div>
        <div class="col-sm-4">
            <b>Pedido Nasajon</b> <br />
            <a href="#" onclick="showItens('{{ $info_pedido['id_pedido'] }}', 'nasajon', '{{ $info_pedido['pedido_gerado'] }}');">{{ $info_pedido['pedido_gerado'] }}</a>
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
                Comissão na nota: {{ $info_pedido['comissao_nota']??0 }}% <br />
                Comissões dos itens/Total da nota
                @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
                    <div class="input-group">
                        {{ Form::text('comissao', $info_pedido['comissao_pedido'], ['id' => 'comissao', 'class' => 'form-control'])}}
                        <div class="input-group-append">
                            <div class="input-group-text">%</div>
                        </div>
                    </div>
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
            @if(isset($info_pedido['id_projeto']))
            <div class="col-sm-4">
                <b>Projeto</b> <br />
                <a href="#" onclick="abrirProjeto();">{{ $info_pedido['id_projeto'] }}</a>
            </div>
            @endif
        </div>
        @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
        <div class="row float-right my-2">
            <div class="col-lg-12">
                <button class='btn btn-modifica float-left' id="aplicar" onclick="aplicar('{{ $info_pedido['hash'] }}')">Modificar informações de comissão</button>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
            <thead>
                <tr>
                    <th rowspan='2'>Código</th>
                    <th class="tb_number" rowspan='2'>Qtd. no portal</th>
                    <th class="tb_number" rowspan='2'>Qtd. na nota</th>
                    <th class="tb_number" rowspan='2'>Preço Digitado no Portal</th>
                    <th class="tb_number" rowspan='2'>Preço na Nota</th>
                    <th rowspan='2'>Total</th>
                    @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                    <th class="tb_number" rowspan='2'>Comissão no pedido</th>
                    @endif
                    <th class="tb_number" rowspan='2'>Alíquota Aplicada</th>
                    <th class="tb_number" rowspan='2'>IPI Aplicado</th>
                    <th colspan='5'>Faixas de Comissão</th>
                    <th class="tb_number" rowspan='2'>Comissão</th>
                    <th class="tb_number" rowspan="2">Comissão em Reais</th>
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
                <tr>
                    <td>{{ $value['codigo'] }}</td>
                    <td class='text-right'>{{ $value['quantidade'] }}</td>
                    <td class='text-right'>{{ $value['quantidade_pedido'] }}</td>
                    <td class='text-right'>{{ $value['preco_unitario_digitado'] }}</td>
                    <td class='text-right'>{{ $value['preco_unitario_pedido'] }}</td>
                    <td>{{ $value['total'] }}</td>
                    @if(Auth::user()->tipo_usuario->nome == 'Administrador')
                    <td class='text-right'>{{ $value['comissao_calculada'] }}</td>
                    @endif
                    <td class='text-right'>{{ $value['aliquota_aplicada'] }}</td>
                    <td class='text-right'>{{ $value['ipi_aplicado'] }}</td>
                    <td class='text-right'>< {{ $value['dois'] }}</td>
                    <td class='text-right'>< {{ $value['dois_e_meio'] }}</td>
                    <td class='text-right'>>= {{ $value['coluna_a'] }}</td>
                    <td class='text-right'>>= {{ $value['coluna_b'] }}</td>
                    <td class='text-right'>>= {{ $value['coluna_c'] }}</td>
                    <td class='text-right'>{{ $value['comissao_recalculada'] }}</td>
                    <td class='text-right'>{{ $value['comissao_valor'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('#comissao').maskMoney({thousands:'', decimal:','});
    });

    @if(Auth::user()->tipo_usuario->nome == 'Administrador' || Auth::user()->id == 92 )
    function aplicar($hash){
        $.ajax({
            url: '{{ route('revisao_comissao.nasajon.aplicar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash,
                comissao: $(document).find("#comissao").val(),
                vendedor: $(document).find("#vendedor").val(),
            },
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    $(document).find('#pedido_modal_comissoes').modal('hide');
                    message('Sucesso', 'Comissão aplicada com sucesso');
                }else{
                    message('Erro', callback.message);
                }
            },
            error: function (data){
                message('Erro', 'Erro ao atualizar.');
            }
        });
    }
    @endif

    function abrirPedidoWeb($id, $numero = null){
        var title = "Dados do pedido: " + $id;
        $.ajax({
            url: '{{ route('pedido_portal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', title, data, 'modal-lg');
            }
        });
    }

    function showItens($pedido, $origem, $numero_pedido = null){
        if ($numero_pedido == null){
            var title = "Dados do pedido";
        }
        else{
            var title = "Dados do pedido: "+$numero_pedido;
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
    
    @if(isset($info_pedido['id_projeto']))
    function abrirProjeto(){

        var id_projeto = "{!! $info_pedido['id_projeto'] !!}";
        var titulo = "{!! $info_pedido['titulo_modal_projeto'] !!}";

        $.ajax({
            url: '{{ route('lancamento_projeto.modal.view') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_projeto: id_projeto 
            },
            success: function (data){
                createModal('detalhes_projeto', titulo, data, 'modal-lg');
            }
        });
    }
    @endif

</script>
@endsection