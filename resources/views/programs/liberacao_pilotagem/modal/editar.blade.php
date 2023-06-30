@extends('layouts.page-dialog')

@section('content')
@if (!empty($dados['motivo_recusa']))
<div class="alert alert-danger" role="alert">
	<p>Este ajuste foi rejeitado.<br>
	Motivo: {{$dados['motivo_recusa']}}</p> 
</div>
@endif
<form action="" name="form_digitacao_ajuste_pilotagem_edit" id="form_digitacao_ajuste_pilotagem_edit" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-3">
            {!! Form::hidden('id', $dados['id'], ['id' => 'id_pilotagem']) !!}
            {!! Form::hidden('id', $dados['id'], ['id' => 'id_comissao']) !!}
            {!! Form::label('estabelecimento', 'Estabelecimento', []) !!}
            {{ Form::hidden('abonar_pilotagem_alterar_comissao',  $dados['abonar_pilotagem_alterar_comissao'], ["id" => 'abonar_pilotagem_alterar_comissao_edit'])}}
            {!! Form::select('estabelecimento', $estabelecimentos, $dados['estabelecimento'], ['id' => 'estabelecimento_edit', 'class' => 'form-control', 'placeholder' => 'Selecione o Estabelecimento']) !!}
        </div>
        <div class="form-group col-sm-3">
                {!! Form::label('vendedor_representante', 'Representante', []) !!}
            <div class="input-group">
                {{ Form::select('vendedor_representante', $vendedor_representante, $dados['vendedor_representante'], ["id" => 'vendedor_representante_edit', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
            </div>
        </div>
        <div class="form-group col-sm-3">
                {!! Form::label('abonar_pilotagem_alterar_comissao', 'Abonar Pilotagem / Alterar Comissão', []) !!}
            <div class="input-group">
                {{ Form::text('abonar_pilotagem_alterar_comissao', $dados['abonar_pilotagem_alterar_comissao'], ["id" => 'abonar_pilotagem_alterar_comissao_edit', 'readonly', 'class' => 'form-control', 'placeholder' => 'Pilotagem / Comissão'])}}
            </div>
        </div>
    </div>
    <div class="form-row filtro_abonar_pilotagem_edit">
        <div class="content-filter-dialog">	
            <div class="form-row">
                <div class="form-group col-sm-2">
                    {!! Form::label('nota', 'Nota', []) !!}
                    {!! Form::text('nota', $dados['nota'], ['id' => 'nota_edit', 'class' => 'form-control', 'maxlength' => '9', 'placeholder' => 'Nº da Nota']) !!}
                </div>
            </div>
            <div class="content-buttons">
            <button name="btn-filterform_pilotagem_edit" id="btn-filterform_pilotagem_edit" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform_pilotagem" id="btn-clearform_pilotagem" class="btn-clear" value="Limpar busca" />
            </div>
        </div>
    </div>
    <div class="form-row filtro_abonar_pilotagem_edit">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-pilotagem_edit">
                    <thead>
                        <th>Estabelecimento</th>
                        <th>Representante</th>
                        <th>Nota</th>
                        <th class="valor_desconto">Valor Desconto</th>
                        <th class="valor_credito tb_number">Valor Crédito</th>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="form-row filtro_comissao_edit">
        <div class="content-filter-dialog">	
            <div class="form-row">
                <div class="form-group col-sm-2">
                    {!! Form::label('titulo', 'Título', []) !!}
                    {!! Form::text('titulo', $dados['titulo'], ['id' => 'titulo_edit', 'class' => 'form-control', 'maxlength' => '13', 'placeholder' => 'Nº do Título']) !!}
                </div>
                <div class="form-group col-sm-1">
                    {!! Form::label('parcela', 'Parcela', []) !!}
                    {!! Form::number('parcela', $dados['parcela'], ['id' => 'parcela_edit', 'class' => 'form-control', 'maxlength' => '2', 'placeholder' => 'Nº']) !!}
                </div>
            </div>
            <div class="content-buttons">
                <button name="btn-filterform_comissao_edit" id="btn-filterform_comissao_edit" class="btn-filter">Buscar</button>
                <input type="reset" name="btn-clearform_comissao" id="btn-clearform_comissao" class="btn-clear" value="Limpar busca" />
            </div>
        </div>
    </div>
    <div class="form-row filtro_comissao_edit">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-comissao_edit">
                    <thead>
                        <th>Estabelecimento</th>
                        <th>Representante</th>
                        <th>Nota</th>
                        <th>Pedido Venda</th>
                        <th class="tb_date">Data Emissão</th>
                        <th class="tb_date">Data Vencimento</th>
                        <th class="tb_number">Valor Título</th>
                        <th class="tb_number">% Comissão Atual</th>
                        <th class="tb_number">% Comissão Alterada</th>
                    </thead>
                    <tbody>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12">
            {!! Form::label('motivo', 'Motivo', []) !!}
            {!! Form::select('motivo', $motivos, $dados['motivo'], ['id' => 'motivo_edit', 'class' => 'form-control', 'placeholder' => 'Selecione o Motivo']) !!}
        </div>
    </div>
    <div class="form-row">
        <div>
            {!! Form::hidden('id', $dados['id'], ['id' => 'validar_registro_tabela']) !!}
        </div>
    </div>
    <div class="content-buttons">
        {{ Form::button('Salvar', array('class' => 'btn btn-success float-right', 'id' => 'btn-salvar')) }}
    </div>
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find("#form_digitacao_ajuste_pilotagem_edit");

        if(form_modal.find("#abonar_pilotagem_alterar_comissao_edit").val() == 'Abonar Pilotagem'){
            form_modal.find(".filtro_abonar_pilotagem_edit").show();
            form_modal.find(".filtro_comissao_edit").hide();
            filtroPilotagemEdit(form_modal);
        }else{
            form_modal.find(".filtro_abonar_pilotagem_edit").hide();
            form_modal.find(".filtro_comissao_edit").show();
            filtroComissaoEdit(form_modal);
        }

        form_modal.find("#btn-salvar").off('click');
        form_modal.find("#btn-salvar").on('click', function(){
                salvarAlteracaoEdit(form_modal);
        });

        initTableEdit();

        form_modal.find("#btn-filterform_pilotagem_edit").off('click');
        form_modal.find("#btn-filterform_pilotagem_edit").on('click', function(){
            filtroPilotagem(form_modal);
        });
        form_modal.find("#btn-filterform_comissao_edit").off('click');
        form_modal.find("#btn-filterform_comissao_edit").on('click', function(){
            filtroComissao(form_modal);
        });

        $(document).find(".number").maskMoney({thousands:'', decimal:','});
    });
    

    function salvarAlteracaoEdit(form_modal){
            $.ajax({
            url: "{{ route('liberacao_pilotagem.editar') }}", 
            dataType: 'json',
            data: form_modal.serialize(),
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    $(form_modal).parents('.modal').modal('hide');
                    message("Atenção", "Alteração realizada com sucesso!");
                }
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroModalEdit();
                mensagemErroModalEdit(dados);
            }
        });
    }


    function limparMesagemErroModalEdit(){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, button').removeClass('error-input');
    }

    function mensagemErroModalEdit(json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModalEdit(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModalEdit(form_modal, input, message){
        if(input == 'validar_registro_comissao' || input == 'validar_registro_pilotagem' ){
            form_modal.find(".content-dialog-table").append().after("<label class='error-message'>"+message+"</label>");
        }
        var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"'], div[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function initTableEdit(){
        table_filters_pilotagem_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "26vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhuma Nota Encontrada",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Peça Encontrada",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "valor_desconto", targets: "valor_desconto", width: "100px"},
                { "class": "valor_credito", targets: "valor_credito", width: "100px"}
            ]
        };
        table_pilotagem = $(document).find('#table-filters-pilotagem_edit').DataTable(table_filters_pilotagem_options);
        table_pilotagem.on('draw', function () {
            $(document).find(".bt-modal-nota").off('click');
            $(document).find(".bt-modal-nota").on('click', function(){
                event.stopPropagation();
                abrirModalNota($(this));
            });
        });

        table_filters_comissao_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "26vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Título Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Local de Estoque Encontrada",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_date", targets: "tb_date"}
            ]
        };
        table_comissao = $(document).find('#table-filters-comissao_edit').DataTable(table_filters_comissao_options);
        table_comissao.on('draw', function () {
            $(document).find(".bt-modal-pedido").off('click');
            $(document).find(".bt-modal-pedido").on('click', function(){
                event.stopPropagation();
                abrirModalPedido($(this));
            });
            $(document).find(".bt-modal-nota").off('click');
            $(document).find(".bt-modal-nota").on('click', function(){
                event.stopPropagation();
                abrirModalNota($(this));
            });
        });
    }

    function filtroPilotagem(form_modal){
        table_pilotagem.clear().draw();
        data_form = form_modal.serialize();
        limparMesagemErroModalEdit(form_modal);
        $.ajax({
            url: '{{ route("liberacao_pilotagem.filtro_adicionar")}}',
            data: data_form,
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.saida){
                    temp_array = [
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].estabelecimento + "''>" + data.response.saida[fields].estabelecimento + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].representante + "''>" + data.response.saida[fields].representante + "</div></div>",
                        abrirNotaDetalhes(data.response.saida[fields]),
                        data.response.saida[fields].valor_desconto+filters(data.response.saida[fields]),
                        inputAbonarPilotagem(data.response.saida[fields])+validarRegistroTabelaPilotagem(data.response.saida[fields]),
                    ];
                    linhas.push(temp_array);    
                }
                table_pilotagem.rows.add(linhas).draw();
                
                $(document).find(".number").maskMoney({thousands:'', decimal:','});
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroModalEdit(form_modal);
                mensagemErroModalEdit(dados, form_modal);
            }
        });
    }

    function filtroComissao(form_modal){
        table_comissao.clear().draw();
        data_form = form_modal.serialize();
        limparMesagemErroModalEdit(form_modal);
        $.ajax({
            url: '{{ route("liberacao_pilotagem.filtro_adicionar")}}',
            data: data_form,
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.saida){
                    temp_array = [
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].estabelecimento + "''>" + data.response.saida[fields].estabelecimento + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].representante + "''>" + data.response.saida[fields].representante + "</div></div>",
                        abrirNotaDetalhes(data.response.saida[fields]),
                        abrirPedidoDetalhes(data.response.saida[fields]),
                        data.response.saida[fields].data_emissao,
                        data.response.saida[fields].data_vencimento,
                        data.response.saida[fields].valor_titulo,
                        data.response.saida[fields].comissao_atual+filters(data.response.saida[fields]),
                        inputAlterarComissao(data.response.saida[fields])+validarRegistroTabelaComissao(data.response.saida[fields])
                    ];
    
                    linhas.push(temp_array);    
                }

                table_comissao.rows.add(linhas).draw();
                
                $(document).find(".number").maskMoney({thousands:'', decimal:','});
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroModalEdit(form_modal);
                mensagemErroModalEdit(dados, form_modal);
            }
        });
    }

    function filtroPilotagemEdit(form_modal){
        data_form = form_modal.serialize();
        limparMesagemErroModalEdit(form_modal);
        $.ajax({
            url: '{{ route("liberacao_pilotagem.filtro_editar")}}',
            data: data_form,
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.saida){
                    temp_array = [
                        data.response.saida[fields].estabelecimento,
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].representante + "''>" + data.response.saida[fields].representante + "</div></div>",
                        abrirNotaDetalhes(data.response.saida[fields]),
                        data.response.saida[fields].valor_desconto+filters(data.response.saida[fields]),
                        inputAbonarPilotagem(data.response.saida[fields])+validarRegistroTabelaPilotagem(data.response.saida[fields]),
                    ];
    
                    linhas.push(temp_array);    
                }
                table_pilotagem.rows.add(linhas).draw();
                $(document).find(".number").maskMoney({thousands:'', decimal:','});
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroModalEdit(form_modal);
                mensagemErroModalEdit(dados, form_modal);
            }
        });
    }

    function filtroComissaoEdit(form_modal){
        data_form = form_modal.serialize();
        limparMesagemErroModalEdit(form_modal);
        $.ajax({
            url: '{{ route("liberacao_pilotagem.filtro_editar")}}',
            data: data_form,
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.saida){
                    temp_array = [
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].estabelecimento + "''>" + data.response.saida[fields].estabelecimento + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + data.response.saida[fields].representante + "''>" + data.response.saida[fields].representante + "</div></div>",
                        abrirNotaDetalhes(data.response.saida[fields]),
                        abrirPedidoDetalhes(data.response.saida[fields]),
                        data.response.saida[fields].data_emissao,
                        data.response.saida[fields].data_vencimento,
                        data.response.saida[fields].valor_titulo,
                        data.response.saida[fields].comissao_atual+filters(data.response.saida[fields]),
                        inputAlterarComissao(data.response.saida[fields])+validarRegistroTabelaComissao(data.response.saida[fields])
                    ];
                    linhas.push(temp_array);    
                }
                table_comissao.rows.add(linhas).draw();
                
                $(document).find(".number").maskMoney({thousands:'', decimal:','});
            },
            error: function(data){
                var dados = data.responseJSON;
                limparMesagemErroModalEdit(form_modal);
                mensagemErroModalEdit(dados, form_modal);
            }
        });
    }

    function inputAlterarComissao($value){
        if($value.comissao_alterada.length > 0){
            var comissao_valor_formatado = $value.comissao_alterada.replace(".", ",");
            var html = "<input id=\"nova_comissao\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"nova_comissao\" type=\"text\" value=\""+comissao_valor_formatado+"\"  autocomplete=\"off\">";
        }else{
            var html = "<input id=\"nova_comissao\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"nova_comissao\" type=\"text\" value=\"\"  autocomplete=\"off\">";
        }
        return html;
    }

    function inputAbonarPilotagem($value){
        if($value.valor_credito.length > 0){
            var valor_credito = parseFloat($value.valor_credito);
            valor_credito = numberToReal(valor_credito.toFixed(2));
            var html = "<input id=\"valor_credito\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"valor_credito\" type=\"text\" value=\""+valor_credito+"\"  autocomplete=\"off\">";
        }else{
            var html = "<input id=\"valor_credito\" class=\"form-control text-right number\" style=\"height: inherit\" name=\"valor_credito\" type=\"text\" value=\"\"  autocomplete=\"off\">";
        }

        return html;
    }

    function abrirNotaDetalhes($value){
        var html = "<a href=\"#\" data-route=\"{{ route('notas_nasajon.modal.exibir') }}\" data-nota_id=\""+$value.nota_id+"\" data-title='DETALHES DA NOTA: "+$value.nota+"' class='bt-modal-nota'>"+$value.nota +"</a>"
        return html;
    }

    function abrirPedidoDetalhes($value){
        var html = "<a href=\"#\" data-route=\"{{ route('pedidos_orcamentos.show') }}\" data-pedido_venda_id=\""+$value.pedido_venda_id+"\" data-title='DADOS DO PEDIDO' data-origem='nasajon' class='bt-modal-pedido'>"+$value.pedido_venda +"</a>"
        return html;
    }

    function validarRegistroTabelaPilotagem($value){
        var html = "<input id=\"validar_registro_pilotagem_edit\"  name=\"validar_registro_pilotagem\" type=\"hidden\"  value=\""+$value.nota_id+"\">";

        return html;
    }

    function filters($value){
        var html = "<input id=\"filters\"  name=\"filters\" type=\"hidden\"  value=\""+$value.filters+"\">";
        return html;
    }

    function validarRegistroTabelaComissao($value){
        var html = "<input id=\"validar_registro_comissao_edit\"  name=\"validar_registro_comissao\" type=\"hidden\"  value=\""+$value.nota_id+"\">";

        return html;
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function abrirModalNota($this){
        var url = $($this).data("route");
        var nota_id = $($this).data("nota_id");
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {
                _token: "{{ csrf_token() }}", id_nota: nota_id},
            method: 'POST',
            success: function(body){
                createModal("modal_nota_detalhes", title, body, 'modal-lg');
            }
        });
    }

    function abrirModalPedido($this){
        var url = $($this).data("route");
        var pedido_id = $($this).data("pedido_venda_id");
        var origem = $($this).data('origem');
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {
                _token: "{{ csrf_token() }}", pedido: pedido_id, origem: origem},
            method: 'POST',
            success: function(body){
                createModal("modal_pedido_detalhes", title, body, 'modal-lg');
            }
        });
    }
</script>
@endsection