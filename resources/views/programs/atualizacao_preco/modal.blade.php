@extends('layouts.page-dialog')

@section('content')
    <div class="content-dialog-table" style='float: none;'>
        <table class='table table-striped table-filter-pedido-itens table-not-edit'>
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Linha</th>
                    <th>Grupo</th>
                    <th>Subgrupo</th>                    
                    @if (isset($produto['codigo_produto']) && !empty($produto['codigo_produto']))
                    <th>Código do Produto</th>
                    <th>Descrição</th>
                    @endif
                    <th>Unidade</th>
                    <th>Agrupados</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $produto['marca'] }}</td>
                    <td>{{ $produto['linha'] }}</td>
                    <td>{{ $produto['grupo'] }}</td>
                    <td>{{ $produto['subgrupo'] }}</td>                    
                    @if (isset($produto['codigo_produto']) && !empty($produto['codigo_produto']))
                    <td>{{ $produto['codigo_produto'] }}</td>
                    <td>{{ $produto['descricao'] }}</td>
                    @endif
                    <td>{{ $produto['unidade'] }}</td>
                    <td class='text-right'><a href="#" title='Produtos contidos' data-hash='{{ $produto['hash'] }}' onclick="modalProdutos($(this).data('hash'))">{{ $produto['produtos'] }}</a></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <hr>
    
    <form action="#" name="precos_atualizar" id="precos_atualizar" onsubmit="return false;">
        @csrf
        {{ Form::hidden('hash', $produto['hash'], ['id' => 'hash']) }}
        
        <div class="row">

            <div class="content-dialog-table col-6">
                <table class="table table-striped table-filter table-not-edit" style='float: none;'>
                    <thead>
                        <tr>
                            <th colspan='2'>Compras</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Data última entrada</th>
                            <td class="text-center">{{ $margens['ultima_compra_real'] }}</td>
                        </tr>
                        <tr>
                            <th>Valor última entrada R$ ( Custo Gerencial )</th>
                            <td class="text-right">{{ $margens['custo_real'] }}</td>
                        </tr>
                        <tr>
                            <th>Preço R$</th>
                            <td class="text-right">{{ $produto['preco_real'] }}</td>
                        </tr>
                        <tr>
                            <th>Preço Us$</th>
                            <td class="text-right">{{ $produto['preco_dolar'] }}</td>
                        </tr>
                        </tr>
                        <tr>
                            <th>Data última compra Us$</th>
                        <td class="text-center">{{ $margens['ultima_compra_dolar'] }}</td>
                        </tr>
                        <tr>
                            <th>Valor Us$ FOB Compra</th>
                            <td class="text-right">{{ $margens['custo_dolar'] }}</td>
                        </tr>
                        <tr>
                            <th>Pedido compra previsão entrada</th>
                            <td class="text-center">{{ $margens['entrega'] }}</td>
                        </tr>
                        <tr>
                            <th>Pedido compra valor</th>
                            <td class="text-right">{{ $margens['valor_compra'] }}</td>
                        </tr>
                        <tr>
                            <th>Número proforma</th>
                            <td>{{ $margens['proforma'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="content-dialog-table col-6" style='float: none;'>
                <table class='table table-striped table-filter table-not-edit'>
                    <thead>
                        <tr>
                            <th colspan="2">Informações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th>Custo médio contábil (Nasajon) </th>
                            <td class="text-right">{{ $margens['custo_medio'] }}</td>
                        </tr>
                        <tr>
                            <th>Custo médio contábil (Portal)</th>
                            <td class="text-right"><div data-id="" data-toggle="popover" data-trigger='hover' title="Custo médio contábil por empresa" data-content="{{ $produto['custo_portal']['popover_custo_contabil'] }}"><span>{{ $produto['custo_portal']['custo_contabil'] }}</span><span class="bt-detalhe"></span>&nbsp;&nbsp;</div></td>
                        </tr>
                        <tr>
                            <th>Custo médio Gerencial (Portal)</th>
                            <td class="text-right"><div data-id="" data-toggle="popover" data-trigger='hover' title="Custo médio Gerencial por empresa" data-content="{{ $produto['custo_portal']['popover_custo_gerencial'] }}"><span>{{ $produto['custo_portal']['custo_gerencial'] }}</span><span class="bt-detalhe"></span>&nbsp;&nbsp;</div></td>
                        </tr>
                        <tr>
                            <th>Estoque</th>
                            <td class="text-right">{{ $produto['estoque'] }}</td>
                        </tr>
                        <tr>
                            <th>Venda dos últimos 6 meses</th>
                            <td class="text-right">{{ $produto['venda_ultimos_meses'] }}</td>
                        </tr>
                        <tr>
                            <th>Venda média dos últimos 6 meses</th>
                            <td class="text-right">{{ $produto['venda_media'] }}</td>
                        </tr>
                        <tr>
                            <th>Estoque em meses</th>
                            <td class="text-right">{{  $produto['estoque_meses'] }}</td>
                        </tr>
                        @if(!empty($produto['ficha_tecnica_criacao']))
                            <tr>
                                <th>Ficha Técnica Criação</th>
                                <td class="text-center">{{  $produto['ficha_tecnica_criacao'] }}</td>
                            </tr>
                            <tr>
                                <th>Ficha Técnica Última Atualização</th>
                                <td class="text-center">{{  $produto['ficha_tecnica_update'] }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if(isset($movimentacoes) && !empty($movimentacoes))
                <div class="content-dialog-table col-12">
                    <table class='table table-striped table-filter table-not-edit'>
                        <tbody>
                            <tr>
                                <th>Movimentações</th>
                                <td>
                                    <a
                                        href="#"
                                        class="bt-estoque2"
                                        data-url="{{ route('produto.movimento_estoque.movimento_portal_grupo') }}"
                                        data-title="Movimento de Estoque Portal"
                                        data-codigos="{{ json_encode($movimentacoes) }}"
                                        data-toggle="tooltip"
                                        data-placement="top"
                                        title="Movimento de Estoque Portal"
                                    >
                                        <i class="far fa-list-alt" style="color: #000;float: left;font-size: 20px;"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="content-dialog-table col-sm-5 ml-3" style='float: none;'>
                    <table class="table table-striped table-filter table-not-edit" style='float: none;'>
                        <thead>
                            <th colspan="2">Margem</th>
                        </thead>
                        <tbody>
                            <tr>
                                <th>Preço venda / Custo médio</th>
                                <td class='text-right'>{{ $margens['preco_venda_x_custo_medio'] }}</td>
                            </tr>
                            <tr>
                                <th>Preço venda / Preço compra</th>
                                <td class='text-right'>{{ $margens['preco_venda_x_preco_compra'] }}</td>
                            </tr>
                            <tr>
                                <th>Preço venda Us$ / Preço proforma Us$ FOB</th>
                                <td class='text-right'>{{ $margens['preco_venda_x_preco_proforma_fob'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col" >
                    <div class="row mb-1">
                        <div class="col border-bottom">
                            Atualização de preços
                        </div>
                    </div>
                    <div class="row">
                        <div class="col text-center">
                        </div>
                        <div class="col-1"></div>
                        <div class="col text-center">
                            Valor em moeda
                        </div>
                        <div class="col text-center">
                            Valor base
                        </div>
                        <div class="col text-center">
                            Porcentagem modificada
                        </div>
                        <div class="col-1"></div>
                    </div>
                    <div class="row">
                        <div class="col">
                            Preço em real: 
                        </div>
                        <div class="col-1">
                            <a class='btn-book' title='Histórico de alterações' onclick='modalHistorico("real","{{ $produto['hash'] }}");'></a>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">R$</span>
                                </div>
                                {{ Form::text('preco_real', $produto['preco_real'], ['id' => 'preco_real', 'class' => 'valor form-control text-right']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon2">R$</span>
                                </div>
                                {{ Form::text('preco_real_antigo', $produto['preco_real'], ['id' => 'preco_real_antigo', 'class' => 'valor_antigo form-control text-right', 'disabled']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                {{ Form::text('preco_real_porcentagem', '', ['id' => 'preco_real_antigo', 'class' => 'porcentagem form-control text-right']) }}
                                <div class="input-group-append">
                                    <span class="input-group-text" id="basic-addon3">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-1 float-right">
                            <button type="button" data-campo='real' class="btn btn-success float-right salvar_precos">Atualizar</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            Preço em dolar: 
                        </div>
                        <div class="col-1">
                            <a class='btn-book' title='Histórico de alterações' onclick='modalHistorico("dolar","{{ $produto['hash'] }}");'></a>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">Us$</span>
                                </div>
                                {{ Form::text('preco_dolar', $produto['preco_dolar'], ['id' => 'preco_dolar', 'class' => 'valor form-control text-right']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon2">Us$</span>
                                </div>
                                {{ Form::text('preco_dolar_antigo', $produto['preco_dolar'], ['id' => 'preco_dolar_antigo', 'class' => 'valor_antigo form-control text-right', 'disabled']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                {{ Form::text('preco_dolar_porcentagem', '', ['id' => 'preco_dolar_porcentagem', 'class' => 'porcentagem form-control text-right']) }}
                                <div class="input-group-append">
                                    <span class="input-group-text" id="basic-addon3">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-1 float-right">
                            <button type="button" data-campo='dolar' class="btn btn-success float-right salvar_precos">Atualizar</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col">
                            Custo Compras Gerencial: 
                        </div>
                        <div class="col-1">
                            <a class='btn-book' title='Ver últimas dez notas' onclick='modalUltimasCompras("{{ $produto['hash'] }}");'></a>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon1">R$</span>
                                </div>
                                {{ Form::text('compra_real', $produto['compra_real'], ['id' => 'compra_real', 'class' => 'valor form-control text-right']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="basic-addon2">R$</span>
                                </div>
                                {{ Form::text('compra_real_antigo', $produto['compra_real'], ['id' => 'compra_real_antigo', 'class' => 'valor_antigo form-control text-right', 'disabled']) }}
                            </div>
                        </div>
                        <div class="col">
                            <div class="input-group mb-3">
                                {{ Form::text('preco_real_porcentagem', '', ['id' => 'preco_real_antigo', 'class' => 'porcentagem form-control text-right']) }}
                                <div class="input-group-append">
                                    <span class="input-group-text" id="basic-addon3">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-1 float-right">
                            @if(in_array(Auth::id(),  [13, 15, 83, 102, 230, 251, 452, 751]) || Auth::user()->hasRole('Administradores'))
                            <button type="button" data-campo='compra' class="btn btn-success float-right salvar_precos">Atualizar</button>
                            @endif
                        </div>
                    </div>
               </div>
            </div>
        </div>
    </form>
<script>
    $(document).ready(function(){
        $(document).find('.valor').maskMoney({allowZero: true, thousands:'.', decimal:','});
        $(document).find('.porcentagem').maskMoney({allowNegative: true, thousands:'.', decimal:','});

        $(document).find('.salvar_precos').on('click', function(){
            MensagemAtualizar(this);
        })

        $(document).find('.porcentagem').on('blur', function(){
            porcentagemParaPreco(this);
        });

        $(document).find('.valor').on('blur', function(){
            precoParaPorcentagem(this);
        });

        $(document).find('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body" style="float: left;padding: 5px 7px;"></div></div>'
        });
        $(document).find(".bt-estoque2").off("click");
        $(document).find(".bt-estoque2").on("click", function(event){
            event.stopPropagation();
            showModalMovimentoEstoque($(this));
        });
    });
    function showModalMovimentoEstoque($this){
        var url = $($this).data("url");
        var $codigos = $($this).data("codigos");
        var title = $($this).data("title");
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                codigos: $codigos,
            },
            success: function(body){
                createModal('modal_movimento_estoque', title, body, "modal-lg");
                var modal = $("#modal_movimento_estoque");
            }
        });
    }
    function MensagemAtualizar(campo){
        var $text = '<ul class="list-unstyled">\
                <li>\
                    <label>\
                        {{ Form::radio("atualiza_grupo_subgrupo", "grupo", false, ["id" => "atualiza_grupo"] ) }}\
                        Atualizar produtos do Grupo\
                    </label>\
                </li>\
                <li>\
                    <label>\
                        {{ Form::radio("atualiza_grupo_subgrupo", "linha", false, ["id" => "atualiza_linha"] ) }}\
                        Atualizar produtos da Linha,Grupo e Subgrupo\
                    </label>\
                </li>\
                <li>\
                    <label>\
                        {{ Form::radio("atualiza_grupo_subgrupo", "grupo_subgrupo", false, ["id" => "atualiza_gruposubgrupo"] ) }}\
                        Atualizar produtos do Grupo e Subgrupo\
                    </label>\
                </li>\
                <li>\
                    <label>\
                        {{ Form::radio("atualiza_grupo_subgrupo", "agrupado", true, ["id" => "atualiza_agrupado"] ) }}\
                        Atualizar produtos agrupados\
                    </label>\
                </li>\
            </ul>';
        $("<div></div>").html($text).dialog({
            title: 'Atenção',
            dialogClass: 'message-alert',
            minHeight: 300,
            width: 400,
            modal: true,
            buttons: {
                "Cancelar": function () {
                    $(this).dialog("close");
                },
                "Ok": function() {
                    enviaPrecos($(document).find('input[name="atualiza_grupo_subgrupo"]:checked').val(), $(campo).data('campo'));
                    $(this).dialog("close");
                }
            }
        });
    }

    function enviaPrecos(atualizacao, campo){
        form_data = $(document).find('#precos_atualizar').serialize();
        if(atualizacao != ''){
            form_data += '&atualiza_grupo_subgrupo='+atualizacao;
        }
        form_data += '&campo='+campo;
        $.ajax({
            url: "{{ route('atualizacao_preco.atualiza') }}",
            dataType: 'json',
            data: form_data,
            method: 'POST',
            success: function(data){
                if(data.total > 1){
                    message('Sucesso', 'Atualizados ' + data.total + ' produtos');

                }
                else if(data.total == 1){
                    message('Sucesso', 'Produto atualizado');
                }

                $(document).find("#modal_editar_precos").modal('hide');

                if($(document).find('#show_detalhe').length > 0){
                    $(document).find("[data-hash='{{ $produto['hash'] }}']").data('hash', data.hash);
                }
                else{
                    filterAjax($('#form_filter').serialize(), true);
                }
            },
            error: function(data){
                message('Erro', data.responseJSON.erro);
            }
        });
    }

    function porcentagemParaPreco($elemento){

        if($($elemento).val() == ''){
            return null;
        }
        
        var porcentagem = $($elemento).val();
        var valor_antigo = $($elemento).parent().parent().parent().find('.valor_antigo').val();

        porcentagem = parseFloat(porcentagem.replace('.','').replace(',', '.')) / 100;
        var valor = (parseFloat(valor_antigo.replace('.','').replace(',', '.')) * (1 + porcentagem)).toFixed(2);

        if(valor == 0 || isNaN(valor)){
            valor = '';
        }

        $($elemento).parent().parent().parent().find('.valor').val(String(valor).replace('.', ','));
    }


    function precoParaPorcentagem($elemento){

        if($($elemento).val() == ''){
            return null;
        }

        var valor = parseFloat($($elemento).val().replace('.','').replace(',', '.'));
        var valor_antigo = parseFloat($($elemento).parent().parent().parent().find('.valor_antigo').val().replace('.','').replace(',','.'));

        var porcentagem = ((-1 + (valor / valor_antigo)) * 100).toFixed(2);

        if(porcentagem == 0 || isNaN(porcentagem) || isFinite(porcentagem) == false){
            porcentagem = '';
        }

        $($elemento).parent().parent().parent().find('.porcentagem').val(String(porcentagem).replace('.', ','));
    }

    function modalUltimasCompras($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.ultimas_compras") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_ultimas_compras', 'Últimas compras', data, 'modal-lg');
            }
        });
    }

    function modalHistorico($preco, $hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.historico_alteracoes") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                preco: $preco,
                hash: $hash
            },
            success: function(callback){
                createModal('modal_historico_alteracoes', 'Histórico de alterações '+$preco, callback, 'modal-lg');
            }
        });
    }

</script>
@endsection
