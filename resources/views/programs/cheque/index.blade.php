@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-sm-3">
                <div class="input-group">
                    <input type="text" name="cliente" id="cliente" class='form-control input-label' placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-sm-2">
                    <select name="vencer_vencidos" id="vencer_vencidos">
                        <option value='' selected>Todos</option>
                        <option class="form-check-label" value="a_vencer">A vencer</option>
                        <option class="form-check-label" value="vencidos">Vencidos</option>
                    </select>
                </div>
            <div class="col-sm">
                <input type="text" name="data_inicio" id="data_inicio" value="" placeholder="Bom para de" maxlength="250" />
            </div>
            <div class="col-sm">
                <input type="text" name="data_fim" id="data_fim" value="" placeholder="Bom para até" maxlength="250" />
            </div>
            <div class="col-sm">
                <input type="text" name="valor_de" id="valor_de" value="" placeholder="Valor de" maxlength="250" />
            </div>
            <div class="col-sm">
                <input type="text" name="valor_ate" id="valor_ate" value="" placeholder="Valor até" maxlength="250" />
            </div>
        </div>
        
        <div class="row">
            <div class="col-sm-2">
                <select name="tipo" id="tipo">
                    <option value='' selected>Tipo de lançamento</option>
                    <option class="form-check-label" value="cheque">Cheque</option>
                    <option class="form-check-label" value="deposito">Depósito</option>
                    <option class="form-check-label" value="dinheiro">Dinheiro</option>
                    <option class="form-check-label" value="cancelamento">Cancelamento</option>
                </select>
            </div>
            <div class="col-sm-2">
                <select name="status" id="status">
                    <option value='' selected>Situação</option>
                    <option class="form-check-label" value="aberto">Em aberto</option>
                    <option class="form-check-label" value="baixado">Baixados</option>
                    <option class="form-check-label" value="devolvido">Devolvidos</option>
                </select>
            </div>
            <div class="col-sm-2">
                    <input type="text" name="data_modificacao_inicio" id="data_modificacao_inicio" value="" placeholder="" maxlength="250" />
            </div>
            <div class="col-sm-2">
                <input type="text" name="data_modificacao_fim" id="data_modificacao_fim" value="" placeholder="" maxlength="250" />
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-novo" id="btn-novo" class="btn btn-success float-right">Adicionar</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <div id="saldos" class="border rounded mt-2 pl-3 p-2 row">
        <div id="total_saldo" class="col-sm d-none">
            <b>Saldos</b><br>  
            <span id="totais"></span> 
        </div>
        <div id="total_vencido" class="col-sm d-none">
            <b>Vencido</b><br>  
            <span id="totais"></span> 
        </div>
        <div id="total_a_vencer" class="col-sm d-none">
            <b>A vencer</b><br>  
            <span id="totais"></span> 
        </div>
    </div>
    <table class="table table-striped table-not-view" id="table-filters-cheques">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Cliente</th>
                <th class="number_format">Banco</th>
                <th class="number_format">Agência</th>
                <th class="number_format">Conta</th>
                <th class="number_format">Número do lançamento</th>
                <th class="number_format">Saldo</th>
                <th class="number_format">Valor</th>
                <th class="date_format">Bom para</th>
                <th>Status</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
    
@endsection

@section('script-footer')

    $(document).ready(function(){
        
        $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente('cliente'));
        
        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });

        datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        };

        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });

        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
        });

        $('#data_modificacao_inicio').on('pick.datepicker', function (e) {
            if($('#data_modificacao_fim').datepicker('getDate') < e.date){
                $('#data_modificacao_fim').val('');
            }
            $('#data_modificacao_fim').datepicker('setStartDate', e.date);
        });

        $('#data_modificacao_fim').on('pick.datepicker', function (e) {
            if($('#data_modificacao_fim').datepicker('getDate') > e.date){
                $('#data_modificacao_inicio').val('');
            }
            $('#data_modificacao_inicio').datepicker('setEndDate', e.date);
        });

        $(document).find("#data_inicio").datepicker(datepicker_options);
        $(document).find("#data_inicio").mask("00/00/0000");
        
        $(document).find("#data_fim").datepicker(datepicker_options);
        $(document).find("#data_fim").mask("00/00/0000");

        $(document).find("#data_modificacao_inicio").datepicker(datepicker_options);
        $(document).find("#data_modificacao_inicio").mask("00/00/0000");

        $(document).find("#data_modificacao_fim").datepicker(datepicker_options);
        $(document).find("#data_modificacao_inicio").mask("00/00/0000");

        $(document).find("#valor_de").maskMoney({thousands:'.', decimal:','});
        $(document).find("#valor_ate").maskMoney({thousands:'.', decimal:','});

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $(document).find('#btn-novo').on('click', function(){
            modalNovo();
        });

        $(document).find("#btn-clearform").on('click', function(){
            table_filters_cheques.clear().draw();
            $(document).find('#data_modificacao_inicio').parent().addClass('d-none');
            $(document).find('#data_modificacao_fim').parent().addClass('d-none');
            $(document).find('#data_modificacao_inicio').val('');
            $(document).find('#data_modificacao_fim').val('');
            $(document).find('#totais').html('');
            $(document).find('#saldos').addClass('d-none');
        });

        $(document).find("#status").change(function(event){

            if ($(event.target).val() == 'devolvido'){
                $(document).find('#data_modificacao_inicio').prop('placeholder', 'Devolvido de');
                $(document).find('#data_modificacao_fim').prop('placeholder', 'Devolvido até');
                $(document).find('#data_modificacao_inicio').parent().removeClass('d-none');
                $(document).find('#data_modificacao_fim').parent().removeClass('d-none');
            }
            else if ($(event.target).val() == 'baixado'){
                $(document).find('#data_modificacao_inicio').prop('placeholder', 'Baixado de');
                $(document).find('#data_modificacao_fim').prop('placeholder', 'Baixado até');
                $(document).find('#data_modificacao_inicio').parent().removeClass('d-none');
                $(document).find('#data_modificacao_fim').parent().removeClass('d-none');
            } 
            else{
                $(document).find('#data_modificacao_inicio').parent().addClass('d-none');
                $(document).find('#data_modificacao_fim').parent().addClass('d-none');
                $(document).find('#data_modificacao_inicio').val('');
                $(document).find('#data_modificacao_fim').val('');
            }
        })

        $(document).find("#status").trigger('change');
    });

    table_filters_cheques = $('#table-filters-cheques').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "autoWidth": false,
        "language": {
            "decimal":        ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ",",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum registro encontrado",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
        "columnDefs": [
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
            {
                "targets": [-1, -2],
                'orderable': false
            }
        ]
    });

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal(modal_class, title, body, '');
            },
            error: function(data){
                message('Erro!', data.responseJSON.message);
            }
        });
    }

    function createBtnEdit($url, $id, $nome, $status){
        if($status != 'Devolvido'){
            var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"editar-modal\" data-title_modal=\""+$nome+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar lançamento\" onclick=\"showModal(this);\"></a>";
        }
        else{
            var $html = '';
        }
        return $html;
    }
    function createBtnExcluir($id, $mostrar_exclusao){
        if($mostrar_exclusao === true){
            var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle='tooltip' data-html='true' title='Excluir' onclick=\"excluirCheque($(this).parents('tr'), '"+$id+"')\"></a>";
        }
        else{
            var $html = "";
        }
        return $html;
    }

    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}'
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteBusca($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBusca($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }

    function modalNovo(){
        var title = "Busca de Clientes";
        $.ajax({
            url: '{{ route('cheque.modal.novo') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}'
            },
            success: function(body){
                createModal('modal_novo_cheque', 'Cadastrar novo lançamento', body, '');
            }
        });
    }

    function filterAjax(data_form){
        var $return;
        table_filters_cheques.clear().draw();
        $.ajax({
            url: "{{ route('cheque.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    if(callback.response.totais.saldo != ''){
                        $(document).find('#total_saldo').find('#totais').html(callback.response.totais.saldo);
                        $(document).find('#total_saldo').removeClass('d-none');
                    }
                    else{
                        $(document).find('#total_saldo').find('#totais').find('#totais').html('');
                        $(document).find('#total_saldo').addClass('d-none');
                    }

                    if(callback.response.totais.a_vencer != ''){
                        $(document).find('#total_a_vencer').find('#totais').html(callback.response.totais.a_vencer);
                        $(document).find('#total_a_vencer').removeClass('d-none');
                    }
                    else{
                        $(document).find('#total_a_vencer').find('#totais').find('#totais').html('');
                        $(document).find('#total_a_vencer').addClass('d-none');
                    }

                    if(callback.response.totais.vencido != ''){
                        $(document).find('#total_vencido').find('#totais').html(callback.response.totais.vencido);
                        $(document).find('#total_vencido').removeClass('d-none');
                    }
                    else{
                        $(document).find('#total_vencido').find('#totais').find('#totais').html('');
                        $(document).find('#total_vencido').addClass('d-none');
                    }
                    table_filters_cheques.clear().draw();
                    if(callback.response.data.length > 0){
                        $(document).find('.rodape').show();
                        var data = callback.response.data;
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].tipo,
                                data[field].cliente,
                                data[field].banco,
                                data[field].agencia,
                                data[field].conta,
                                data[field].numero_cheque,
                                data[field].saldo,
                                data[field].valor,
                                data[field].bom_para,
                                data[field].status,
                                createBtnEdit('{{ route('cheque.modal.editar') }}', data[field].id, 'Editar lançamento', data[field].status),
                                createBtnExcluir(data[field].id, data[field].mostrar_exclusao),
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters_cheques.rows.add(fields_filter).draw();
                    }
                }
            }
        });
    }

    function optionsAutoCompleteCliente($elemento){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#" + $elemento).val(ui.item.label);
                return false;
            }
        };
    }
    function excluirCheque(obj, $id){
        $.ajax({
            url: '{{ Route("cheque.modal.excluir") }}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id
            },
            success: function(body){
                createModal('excluir-modal', 'Excluir pedido', body, '');
            },
            error: function(data){
                message('Erro!', data.responseJSON.message);
            }
        });        
    }


@endsection