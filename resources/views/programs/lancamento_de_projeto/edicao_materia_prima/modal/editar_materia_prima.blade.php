@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link active" id="edicao_materia_prima-tecido-tab" data-toggle="tab" href="#edicao_materia_prima_tecido" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_tecido" aria-selected="false">Tecido/Fios</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="edicao_materia_prima-insumo-tab" data-toggle="tab" href="#edicao_materia_prima_insumo" role="tab" aria-controls="lancamento_projeto_revisao_em_massa_insumo" aria-selected="false">Insumo/Acessório</a>
    </li>
</ul>

<div class="tab-content pt-3" id="EdicaoMateriaPrimaHeaderContainer">
    <div class="tab-pane show active" id="edicao_materia_prima_tecido" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_edicao_materia_prima_tecido" id="form_edicao_materia_prima_tecido" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $id_projeto, ['id' => 'id_projeto']) !!}
            <div class="content-dialog-table">
                <div class="content-table">
                    @foreach($produtos as $produto)
                        <hr><h5>{{$produto['indice']}} - {{$produto['descricao']}} - QTD: {{$produto['quantidade']}}</h5><br>
                        <table class="table table-striped">
                            <thead>
                                <th>Item</th>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Consumo</th>
                                <th>Consumo Total</th>
                                <th>Status</th>
                            </thead>
                            <tbody>
                            @foreach($produto['tecidos'] as $tecido)
                                <tr>
                                    <td class="tb_number" style="width: 10px;">{{$tecido['indice']}}</td>
                                    <td>
                                        <div style="display: inline-flex;align-items: center;position: relative;margin-top: 6px;">
                                            <input style="height: inherit;" id="codigo-{{$tecido['tecido']}}" name="codigo-{{$tecido['tecido']}}" type="text" class="form-control" value="{{$tecido['codigo']}}" data-tecido="{{$tecido['tecido']}}" @if(!$tecido['liberado']) disabled @endif>
                                            @if($tecido['liberado']) 
                                                <span class="input-group-addon border rounded-right" id="bt-search-tecido"  data-route="{{ route("produto.modal_pesquisa") }}" data-nome_campo_codigo="codigo-{{$tecido['tecido']}}" data-nome_campo_descricao="descricao-{{$tecido['tecido']}}" onclick="buscarTecido($(this))"><i class="bt-view m-2"></i></span>
                                            @endif
                                            <input id="codigo_anterior-{{$tecido['tecido']}}" name="codigo_anterior-{{$tecido['tecido']}}" type="hidden" value="{{$tecido['codigo']}}" autocomplete="off">
                                        </div>
                                    </td>
                                    <td>
                                        <input style="height: inherit;" id="descricao-{{$tecido['tecido']}}" name="descricao-{{$tecido['tecido']}}" type="text" class="form-control" value="{{$tecido['descricao']}}" data-tecido="{{$tecido['tecido']}}" data-nome_campo_codigo="codigo-{{$tecido['tecido']}}" data-nome_campo_descricao="descricao-{{$tecido['tecido']}}" onkeyup="optionsTecido($(this))" @if(!$tecido['liberado']) disabled @endif>
                                    </td>
                                    <td style="width: 150px;">
                                        <input style="height: inherit;" id="consumo-{{$tecido['tecido']}}" name="consumo-{{$tecido['tecido']}}" type="text" class="form-control text-right moeda3" value="{{$tecido['consumo']}}"  maxlength="8" data-tecido="{{$tecido['tecido']}}" onkeyup="calculoConsumoTecido($(this))" @if(!$tecido['liberado']) disabled @endif>
                                        <input id="quantidade-{{$tecido['tecido']}}" name="quantidade-{{$tecido['tecido']}}" type="hidden" value="{{$tecido['quantidade']}}" autocomplete="off">
                                        <input id="consumo_anterior-{{$tecido['tecido']}}" name="consumo_anterior-{{$tecido['tecido']}}" type="hidden" value="{{$tecido['consumo']}}" autocomplete="off">
                                    </td>
                                    <td style="width: 150px;">
                                        <input style="height: inherit;" id="consumo_total-{{$tecido['tecido']}}" name="consumo_total-{{$tecido['tecido']}}" type="text" class="form-control text-right moeda3" value="{{$tecido['consumo_total']}}"  maxlength="8" disabled>
                                    </td>
                                    <td>@if($tecido['liberado']) Não Enviado @else Enviado @endif</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endforeach
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar_tecido')) }}
                </div> 
            </div>
        </form>
    </div>

    <div class="tab-pane" id="edicao_materia_prima_insumo" role="tabpanel" aria-labelledby="dados-tab">
        <form action="post" name="form_edicao_materia_prima_insumo" id="form_edicao_materia_prima_insumo" onsubmit="return false;">
            @csrf
            {!! Form::hidden('id_projeto', $id_projeto, ['id' => 'id_projeto']) !!}
            <div class="content-dialog-table">
                <div class="content-table">
                    @if(!empty($produto['insumos']))
                        @foreach($produtos as $produto)
                            <hr><h5>{{$produto['indice']}} - {{$produto['descricao']}} - QTD: {{$produto['quantidade']}}</h5><br>
                            <table class="table table-striped">
                                <thead>
                                    <th>Item</th>
                                    <th>Código</th>
                                    <th>Descrição</th>
                                    <th>Consumo Total</th>
                                    <th>Status</th>
                                </thead>
                                <tbody>
                                @foreach($produto['insumos'] as $insumo)
                                    <tr>
                                        <td class="tb_number" style="width: 10px;">{{$insumo['indice']}}</td>
                                        <td>
                                            <div style="display: inline-flex;align-items: center;position: relative;margin-top: 6px;">
                                                <input style="height: inherit;" id="codigo-{{$insumo['insumo']}}" name="codigo-{{$insumo['insumo']}}" type="text" class="form-control" value="{{$insumo['codigo']}}" data-insumo="{{$insumo['insumo']}}" @if(!$insumo['liberado']) disabled @endif>
                                                @if($insumo['liberado'])
                                                    <span class="input-group-addon border rounded-right" id="bt-search-insumo"  data-route="{{ route("produto.modal_pesquisa_limitado") }}" data-nome_campo_codigo="codigo-{{$insumo['insumo']}}" data-nome_campo_descricao="descricao-{{$insumo['insumo']}}" onclick="buscarInsumo($(this))"><i class="bt-view m-2"></i></span>
                                                @endif
                                                <input id="codigo_anterior-{{$insumo['insumo']}}" name="codigo_anterior-{{$insumo['insumo']}}" type="hidden" value="{{$insumo['codigo']}}" autocomplete="off">
                                            </div>
                                        </td>
                                        <td>
                                            <input style="height: inherit;" id="descricao-{{$insumo['insumo']}}" name="descricao-{{$insumo['insumo']}}" type="text" class="form-control" value="{{$insumo['descricao']}}" data-insumo="{{$insumo['insumo']}}" data-nome_campo_codigo="codigo-{{$insumo['insumo']}}" data-nome_campo_descricao="descricao-{{$insumo['insumo']}}" onkeyup="optionsInsumo($(this))" @if(!$insumo['liberado']) disabled @endif>
                                        </td>
                                        <td style="width: 150px;">
                                            <input style="height: inherit;" id="consumo_total-{{$insumo['insumo']}}" name="consumo_total-{{$insumo['insumo']}}" type="text" class="form-control text-right moeda" value="{{$insumo['consumo_total']}}"  maxlength="8">
                                            <input id="consumo_total_anterior-{{$insumo['insumo']}}" name="consumo_total_anterior-{{$insumo['insumo']}}" type="hidden" value="{{$insumo['consumo_total']}}" autocomplete="off">
                                        </td>
                                        <td>@if($insumo['liberado']) Não Enviado @else Enviado @endif</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endforeach
                    @endif
                </div>
                <div class="col-sm-12 mt-5" id="button-bottom">
                    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar_insumo')) }}
                </div> 
            </div>
        </form>
    </div>
</div>
<script>
    array_tecidos = [];
    array_insumos = [];

    @foreach($produtos as $produto)
        @foreach($produto['tecidos'] as $tecido)
            array_tecidos.push("{{ $tecido['tecido'] }}");
        @endforeach
    @endforeach

    @foreach($produtos as $produto)
        @foreach($produto['insumos'] as $insumo)
            array_insumos.push("{{ $insumo['insumo'] }}");
        @endforeach
    @endforeach

    $(document).ready( function () {
        $(document).find(".moeda").maskMoney({thousands:'', decimal:','});
        $(document).find(".moeda3").maskMoney({thousands:'', decimal:',', precision: 3});

        var form_tecido = $(document).find('#form_edicao_materia_prima_tecido');
        form_tecido.find("#btn-salvar_tecido").off("click");
        form_tecido.find("#btn-salvar_tecido").on("click", function(){
            salvarEdicao();
        });

        var form_insumo = $(document).find('#form_edicao_materia_prima_insumo');
        form_insumo.find("#btn-salvar_insumo").off("click");
        form_insumo.find("#btn-salvar_insumo").on("click", function(){
            salvarEdicao();
        });
    });

    function calculoConsumoTecido($value){
        var tecido = $value.data("tecido");
        var form_tecido = $(document).find('#form_edicao_materia_prima_tecido');

        var consumo = form_tecido.find("#consumo-"+tecido).val();
        var quantidade = form_tecido.find("#quantidade-"+tecido).val();

        var consumo_total = Math.round((consumo.replace(".","").replace(",", ".") * quantidade)* 1000) / 1000;

        consumo_total = consumo_total.toFixed(3);

        form_tecido.find("#consumo_total-"+tecido).val(numberToReal(consumo_total));
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function salvarEdicao(){
        var form_tecido = $(document).find("#form_edicao_materia_prima_tecido");
        var form_insumo = $(document).find("#form_edicao_materia_prima_insumo");

        id_projeto = form_tecido.find("#id_projeto").val();

        var_tecidos = [];
        var_insumos = [];

        array_tecidos.forEach(function imprimir(item){
            if(
                form_tecido.find("#codigo-"+item).val() != form_tecido.find("#codigo_anterior-"+item).val() ||
                form_tecido.find("#consumo-"+item).val() != form_tecido.find("#consumo_anterior-"+item).val()
            ){
                var dados = [];
                dados.push(item);
                dados.push(form_tecido.find("#codigo-"+item).val());
                dados.push(form_tecido.find("#codigo_anterior-"+item).val());
                dados.push(form_tecido.find("#consumo-"+item).val());
                dados.push(form_tecido.find("#consumo_anterior-"+item).val());
                dados.push(form_tecido.find("#quantidade-"+item).val());
                var_tecidos.push(dados);
            }
        });

        array_insumos.forEach(function imprimir(item){
            if(
                form_insumo.find("#codigo-"+item).val() != form_insumo.find("#codigo_anterior-"+item).val() ||
                form_insumo.find("#consumo_total-"+item).val() != form_insumo.find("#consumo_total_anterior-"+item).val()
            ){
                var dados = [];
                dados.push(item);
                dados.push(form_insumo.find("#codigo-"+item).val());
                dados.push(form_insumo.find("#codigo_anterior-"+item).val());
                dados.push(form_insumo.find("#consumo_total-"+item).val());
                dados.push(form_insumo.find("#consumo_total_anterior-"+item).val());
                var_insumos.push(dados);   
            }
        });

        $.ajax({
            url: "{{ route('lancamento_projeto.edicao_materia_prima.salvar') }}", 
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                id_projeto: id_projeto,
                array_tecidos: var_tecidos,
                array_insumos: var_insumos,
            },
            method: 'POST',
            async: false,
            success: function(callback){
                 message("Atenção", "Alterado com sucesso");
                 $(form_tecido).parents('.modal').modal('hide');
            },
            error: function(callback){
                var dados = callback.responseJSON;

                limparMesagemErro(form_tecido);
                limparMesagemErro(form_insumo);
                mensagemErro(dados, form_tecido);
            }
        });
    }

    function buscarTecido($this){
        showModalTecido($this.data("route"), "Lista de Tecidos/Fios", $this.data("nome_campo_codigo"), $this.data("nome_campo_descricao"));
    }

    function buscarInsumo($this){
        showModalInsumo($this.data("route"), "Lista de Insumos/Acessórios", $this.data("nome_campo_codigo"), $this.data("nome_campo_descricao"));
    }

    function showModalTecido(url, title, nome_campo_codigo, nome_campo_descricao){
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                createModal("tecido_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
                        $(document).find("#tecido_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#tecido_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosTecido($(this), nome_campo_codigo, nome_campo_descricao);
                        });
                    });
                });
            }
        });
    }

    function showModalInsumo(url, title, nome_campo_codigo, nome_campo_descricao){
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                campo: "linha",
                condicao: "INSUMO"
            },
            success: function(body){
                createModal("insumo_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_filters_produtos_busca.on('draw', function () {
                        $(document).find("#insumo_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#insumo_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosInsumo($(this), nome_campo_codigo, nome_campo_descricao);
                        });
                    });
                });
            }
        });
    }

    function returnDadosTecido($this, nome_campo_codigo, nome_campo_descricao){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#tecido_search_show").modal("hide");
        form_tecido = $(document).find('#form_edicao_materia_prima_tecido');
        form_tecido.find("#"+nome_campo_codigo).val($this.find("td").eq(1).text());
        form_tecido.find("#"+nome_campo_descricao).val($this.find("td").eq(2).text());
    }

    function returnDadosInsumo($this, nome_campo_codigo, nome_campo_descricao){
        if($this.find("td").eq(1).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#insumo_search_show").modal("hide");
        form_insumo = $(document).find('#form_edicao_materia_prima_insumo');
        form_insumo.find("#"+nome_campo_codigo).val($this.find("td").eq(1).text());
        form_insumo.find("#"+nome_campo_descricao).val($this.find("td").eq(2).text());
    }

    function optionsTecido($this){
        $this.autocomplete(optionsAutoCompleteTecido($this));   
    }

    function optionsInsumo($this){
        $this.autocomplete(optionsAutoCompleteInsumo($this));   
    }

    function optionsAutoCompleteTecido($this){
        $(document).find(".error-message").remove();

        nome_campo_codigo = $this.data("nome_campo_codigo");
        nome_campo_descricao = $this.data("nome_campo_descricao");

        form_tecido = $(document).find('#form_edicao_materia_prima_tecido');
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('produto.tecido.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_materia_prima').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum tecido encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_tecido.find("#"+nome_campo_descricao).val(ui.item.label);
                form_tecido.find("#"+nome_campo_codigo).val(ui.item.value);
                return false;
            }
        };
    }

    function optionsAutoCompleteInsumo($this){
        $(document).find(".error-message").remove();

        nome_campo_codigo = $this.data("nome_campo_codigo");
        nome_campo_descricao = $this.data("nome_campo_descricao");

        form_insumo = $(document).find('#form_edicao_materia_prima_insumo');
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.linha = "INSUMO";
                $.post("{{ route('produto.insumo.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_editar_materia_prima').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum insumo encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_insumo.find("#"+nome_campo_descricao).val(ui.item.label);
                form_insumo.find("#"+nome_campo_codigo).val(ui.item.value);
                return false;
            }
        };
    }

    function mensagemErro(json_error, form_modal){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        console.log(input);
        var $input = $(document).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErro(form_modal){   
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');
    }
</script>
@endsection