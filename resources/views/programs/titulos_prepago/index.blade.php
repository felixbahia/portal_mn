@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-sm-3">
            <div class="input-group">
                <input type="text" name="cliente" id="cliente" class='form-control input-label' placeholder="Cliente" maxlength="250" />
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-sm-2">
            <input type="text" name="data_inicio" id="data_inicio" value="" placeholder="de" maxlength="250" />
        </div>
        <div class="col-sm-2">
            <input type="text" name="data_fim" id="data_fim" value="" placeholder="até" maxlength="250" />
        </div>
        <div class="col-sm-2">
            <select name="abertos_encerrados" id="abertos_encerrados">
                <option selected>Status dos títulos</option>
                <option class="form-check-label" value="abertos">Títulos em aberto</option>
                <option class="form-check-label" value="encerrados">Títulos encerrados</option>
            </select>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-not-view" id="table-filters-prepago">
        <thead>
            <tr>
                <th class='number_format'>Pedido</th>
                <th>Cliente</th>
                <th class='date_format'>Emissão do Pedido</th>
                <th class='number_format'>Nota Fiscal</th>
                <th class='date_format'>Emissão da Nota</th>
                <th class='valores_prepago'>Valor do Título</th>
                <th class='valores_prepago'>Valor pago</th>
                <th class='valores_prepago'>Saldo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Totais:</td>
                <td colspan='4'></td>
                <td id='titulos_total'></td>
                <td id='valor_pago'></td>
                <td id='valor_saldo_total'></td>
            </tr>
        </tfoot>
    </table>
</div>
    
@endsection

@section('script-footer')

    $(document).ready( function () {

        $(document).find('.rodape').hide();

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
            autoHide: true,
            endDate: new Date()
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

        $(document).find("#data_inicio").datepicker(datepicker_options);
        $(document).find("#data_inicio").mask("00/00/0000");
        
        $(document).find("#data_fim").datepicker(datepicker_options);
        $(document).find("#data_fim").mask("00/00/0000");

        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $(document).find("#btn-clearform").on('click', function(){
            table_filters_prepago.clear().draw();
        });
    });

    table_filters_prepago = $('#table-filters-prepago').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
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
                "targets": 'valores_prepago',
                "className": 'valores_prepago',
                'type': 'num-fmt',
            },
            {
                "targets": -1,
                "orderable": false
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
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function showModalPortal($this){
        var url = '{{ route('pedido_portal.detalhes') }}';
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", pedido_id: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function showModalNota($this){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function createLinkPedidoPortal($id){

        var title_modal = 'Detalhes do pedido: ' + $id;

        var html = "<a href=\"#\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Portal\" onclick=\"showModalPortal(this);\">"+$id+"</a>";

        return html;
    }
    
    function createLinkPedidoNasajon($numero, $id){
        var url = '{{  route('pedidos_orcamentos.modal') }}';
        var title_modal = 'Detalhes do pedido: ' + $numero;

        var html = "<a href=\"#\" data-route=\""+url+"\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModal(this);\">"+$numero+"</a>";

        return html;
    }

    function createLinkNota($numero, $nota){
        var title_modal = 'Detalhes da nota: ' + $numero;

        if($numero.length > 0 && $nota.length > 0){
            var html = "<a href=\"#\" data-id=\""+$nota+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNota(this);\">"+$numero+"</a>";
        }
        else{
            html = '';
        }

        return html;
    }

    function filterAjax(data_form){
        var $return;
        table_filters_prepago.clear().draw();
        $.ajax({
            url: "{{ route('titulos_prepago.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters_prepago.clear().draw();
                if(callback.status == 'success'){
            
                    if(callback.response.data.length > 0){

                        $(document).find('.rodape').show();

                        var data = callback.response.data;
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                createLinkPedidoNasajon(data[field].pedido_nasajon, data[field].id_pedido),
                                data[field].cliente,
                                data[field].data_pedido_nasajon,
                                createLinkNota(data[field].nota_fiscal, data[field].id_nota),
                                data[field].data_nota_fiscal,
                                data[field].valor_titulo,
                                data[field].valor_baixado,
                                data[field].saldo,
                                createLinkCheques(data[field].id)
                            ];
                            fields_filter.push(temp_field);
                        }

                        table_filters_prepago.rows.add(fields_filter).draw();

                        $(document).find("#titulos_total").html(callback.response.totalizadores.titulos);
                        $(document).find("#valor_pago").html(callback.response.totalizadores.valor_pago);
                        $(document).find("#valor_saldo_total").html(callback.response.totalizadores.saldo);
                    }
                    else{
                        $(document).find('.rodape').hide();
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

    function createLinkCheques($id){

        var html = "<a href=\"#\" class=\"bt-detalhe\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Lançamentos vinculados\" onclick=\"showModalCheques("+$id+");\"></a>";

        return html;
    }
    
    function showModalCheques($id){
        var title = "Lista de lançamentos vinculados";
        $.ajax({
            url: '{{ route('titulos_prepago.modal') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cheques-mdal", title, body, 'modal-lg');
            }
        });
    }

@endsection