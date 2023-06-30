@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_adicionar_promocao_ultra_black" id="form_adicionar_promocao_ultra_black" onsubmit="return false;">
    @csrf
    {!! Form::hidden('pedido_ultra_black', $pedido, ['id' => 'pedido_ultra_black']) !!}
    {!! Form::hidden('estabelecimento_ultra_black', $estabelecimento, ['id' => 'estabelecimento_ultra_black']) !!}
    {!! Form::hidden('cliente_ultra_black', $cliente, ['id' => 'cliente_ultra_black']) !!}
    {!! Form::hidden('tipo_ultra_black', $tipo, ['id' => 'tipo_ultra_black']) !!}
    {!! Form::hidden('editar_ultra_black', $editar, ['id' => 'editar_ultra_black']) !!}
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped">
                <thead>
                    <th>Item</th>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Grupo</th>
                    <th>Quantidade Metros</th>
                    <th>Quantidade KG</th>
                    <th>Preço Unitário Metros</th>
                    <th>Preço Unitário KG</th>
                </thead>
                <tbody>
                    @foreach($produtos as $index => $produto)
                        <tr>
                            <td>
                                {{($index + 1)}}
                                {!! Form::hidden("preco_segundario-".$produto['codigo'], $produto['preco_segundario'], ['id' => "preco_segundario-".$produto['codigo']]) !!}
                                {!! Form::hidden("ultra_black_produto_estoque_disponivel-".$produto['codigo'], $produto['estoque_disponivel'], ['id' => "ultra_black_produto_estoque_disponivel-".$produto['codigo']]) !!}
                                {!! Form::hidden("estoque_segundario-".$produto['codigo'], $produto['estoque_segundario'], ['id' => "estoque_segundario-".$produto['codigo']]) !!}
                                {!! Form::hidden("promocional_unidade-".$produto['codigo'], $produto['verificador_unidade'], ['id' => "promocional_unidade-".$produto['codigo']]) !!}
                                {!! Form::hidden("ultra_black_gml-".$produto['codigo'], $produto['gml'], ['id' => "ultra_black_gml-".$produto['codigo']]) !!}
                                {!! Form::hidden("ultra_black_unidade-".$produto['codigo'], $produto['unidade'], ['id' => "ultra_black_unidade-".$produto['codigo']]) !!}
                                {!! Form::hidden('ultra_black_produto_descricao-'.$produto['codigo'], $produto['descricao_pecas'], ['id' => 'ultra_black_produto_descricao-'.$produto['codigo']]) !!}
                            </td>
                            <td>{{$produto['codigo']}}</td>
                            <td>{{$produto['descricao']}}</td>
                            <td>{{$produto['grupo']}}</td>
                            <td class="tb_number">
                                @if($produto['verificador_unidade'] == 'metros')
                                    {{$produto['estoque_disponivel']}}
                                @else
                                    {{$produto['estoque_segundario']}}
                                @endif
                            </td>
                            <td class="tb_number">
                                @if($produto['verificador_unidade'] == 'quilo')
                                    {{$produto['estoque_disponivel']}}
                                @else
                                    {{$produto['estoque_segundario']}}
                                @endif
                            </td>                        
                            @if($produto['verificador_unidade'] == 'metros')
                                <td style="width: 150px;">
                                    {!! Form::text("ultra_black_produto_preco_unitario-".$produto['codigo'], $produto['preco_primario'], ['id' => "ultra_black_produto_preco_unitario-".$produto['codigo'], 'class' => 'form-control text-right moeda', '']) !!}
                                </td>
                            @else
                                <td class="tb_number">
                                    {{$produto['preco_segundario']}}
                                </td>
                            @endif 
                            @if($produto['verificador_unidade'] == 'quilo')
                                <td style="width: 150px;">
                                    {!! Form::text("ultra_black_produto_preco_unitario-".$produto['codigo'], $produto['preco_primario'], ['id' => "ultra_black_produto_preco_unitario-".$produto['codigo'], 'class' => 'form-control text-right moeda', '']) !!}
                                </td>
                            @else
                                <td class="tb_number">
                                    {{$produto['preco_segundario']}}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-sm-12 mt-5" id="button-bottom">
            {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar_ultra_black')) }}
        </div> 
    </div>
</form>
<script>
    array_produtos = [];

    @foreach($produtos as $produto) 
        array_produtos.push("{{ $produto['codigo'] }}");
    @endforeach

    $(document).ready(function(){
        var form_adicionar_promocao_ultra_black = $(document).find('#form_adicionar_promocao_ultra_black');
        form_adicionar_promocao_ultra_black.find(".moeda").maskMoney({thousands:'', decimal:','});

        form_adicionar_promocao_ultra_black.find("#btn-salvar_ultra_black").off("click");
        form_adicionar_promocao_ultra_black.find("#btn-salvar_ultra_black").on("click", function () {
            console.log('teste1');
            salvarProdutoUltraBlack();
		});
    });

    function salvarProdutoUltraBlack(){
        form_adicionar_promocao_ultra_black = $(document).find('#form_adicionar_promocao_ultra_black');

        form_adicionar_promocao_ultra_black.find('.error-message').remove();
        form_adicionar_promocao_ultra_black.find('.error-input').removeClass('error-input');

        var estabelecimento_ultra_black = form_adicionar_promocao_ultra_black.find("#estabelecimento_ultra_black").val();
        var pedido_ultra_black = form_adicionar_promocao_ultra_black.find("#pedido_ultra_black").val();
        var cliente_ultra_black = form_adicionar_promocao_ultra_black.find("#cliente_ultra_black").val();
        var tipo_ultra_black = form_adicionar_promocao_ultra_black.find("#tipo_ultra_black").val();
        var editar_ultra_black = form_adicionar_promocao_ultra_black.find("#editar_ultra_black").val();

        var var_produtos = [];

        array_produtos.forEach(function imprimir(item){
            var dados = [];
            dados.push(item);
            dados.push(form_adicionar_promocao_ultra_black.find("#ultra_black_produto_preco_unitario-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#ultra_black_produto_estoque_disponivel-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#ultra_black_produto_descricao-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#preco_segundario-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#estoque_segundario-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#ultra_black_gml-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#ultra_black_unidade-"+item).val());
            dados.push(form_adicionar_promocao_ultra_black.find("#promocional_unidade-"+item).val());
            var_produtos.push(dados);
        });

        
        $.ajax({
            url: '{{ Route("promocao_ultra_black.adicionar_ultra_black") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido: pedido_ultra_black,
                estabelecimento: estabelecimento_ultra_black,
                cliente: cliente_ultra_black,
                array_produtos: var_produtos,
                tipo: tipo_ultra_black,
                editar_ultra_black: editar_ultra_black,
            },
            success: function(data) {
                table_produtos.clear().draw();
                var fields_filter = [];
                for(var field in data.response.itens){
                    if(data.response.itens[field].token_promocional == 'ultra_black'){
                        var temp_field = [
                            data.response.itens[field].codigo,
                            data.response.itens[field].descricao,
                            data.response.itens[field].quantidade,
                            '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.response.itens[field].preco_unitario+'</div></div>',
                            data.response.itens[field].valor_total,
                            data.response.itens[field].comissao,
                            createBtEditarProdutoUltraBlack(data.response.itens[field]),
                            createBtExcluirProdutoUltraBlack(data.response.itens[field]),
                        ];
                    }else{
                        var temp_field = [
                            data.response.itens[field].codigo,
                            data.response.itens[field].descricao,
                            data.response.itens[field].quantidade,
                            '<div><div class="preco" data-preco_original="0,00" data-toggle="popover" data-placement="right" data-html="true" title="" data-content="" data-original-title="">'+data.response.itens[field].preco_unitario+'</div></div>',
                            data.response.itens[field].valor_total,
                            data.response.itens[field].comissao,
                            createBtEditarProduto(data.response.itens[field]),
                            createBtExcluirProduto(data.response.itens[field])
                        ];
                    }
                    
                    fields_filter.push(temp_field);
                }
                table_produtos.rows.add(fields_filter).draw().nodes();
                exibirBtnEnviarPedido();
                $(form_adicionar_promocao_ultra_black).parents('.modal').modal('hide');
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                form_adicionar_promocao_ultra_black.find('.error-message').remove();
                for(var field in errors){
                    console.log(errors[field], codigo_produto[field]);
                    showErrorsInputsModalUltraBlack(form_adicionar_promocao_ultra_black, field, errors[field], data.responseJSON.codigo_produto);
                }
            }
        });
	}

    function showErrorsInputsModalUltraBlack(form, input, message, codigo_produto){
        form_adicionar_promocao_ultra_black.find('#ultra_black_produto_preco_unitario-'+codigo_produto).addClass('error-input');
        form_adicionar_promocao_ultra_black.find('#ultra_black_produto_preco_unitario-'+codigo_produto).after("<label class='error-message' for='errultra_black_produto_preco_unitario-"+codigo_produto+"'>"+message+"</label>");
    }
</script>
@endsection