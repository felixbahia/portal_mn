@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Estabelecimento', 'class' => 'form-control' ]) !!}
        </div>
        <div class="col-sm-2">
            <div class="input-group">
                {!! Form::text('cliente', '', ['id' => 'cliente', 'class' => 'form-control input-label', 'placeholder' => 'Cliente', 'maxlength' => '250']) !!}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            {!! Form::text('titulo', '', ['id' => 'titulo', 'placeholder' => 'Título', 'class' => 'form-control', 'maxlength' => '40']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('data_envio_inicial', '', ['id' => 'data_envio_inicial', 'class' => 'data form-control', 'placeholder' => 'Data Envio Inicial DD/MM/YYYY', 'maxlength' => '20']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('data_envio_final', '', ['id' => 'data_envio_final', 'class' => 'data form-control', 'placeholder' => 'Data Envio Final DD/MM/YYYY', 'maxlength' => '20']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::select('cenprot_status', $cenprot_status, '', ['id' => 'cenprot_status', 'placeholder' => 'Status', 'class' => 'form-control' ]) !!}
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
    <table class="table table-striped" id="table-filters-titulos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th class="tb_number">Título</th>
                <th class="tb_number">Título Cenprot</th>
                <th class="text_date">Emissão</th>
                <th class="text_date">Vencimento</th>
                <th class="text_date">Data Envio</th>
                <th>Enviado Por</th>
                <th>Removido Por</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td colspan="2"><h5>Legenda:</h5>
                <span class="status-titulo status-roxo">H</span> - Histórico do Título<br>
                <span class="status-titulo status-azul">R</span> - Reenvio do Título<br>
                <br>
            </td>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        form = $(document).find('#form_filter');

        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 100,
            autoHide: true
        });
        form.find('#data_envio_inicial').on('pick.datepicker', function (e) {
            if(form.find('#data_envio_final').datepicker('getDate') < e.date){
                form.find('#data_envio_final').val('');
            }
            form.find('#data_envio_final').datepicker('setStartDate', e.date);
            form.find('#data_envio_final').datepicker('update');
        });

        form.find("#cliente").autocomplete(optionsAutoCompleteCliente());

        form.find("#bt-search-cliente-busca").off("click");
        form.find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalCliente($(this).data("route"));
            return false;
        });

        form.find("#btn-filterform").on("click", function(){
            filterAjax();
        });

        table_filters = $('#table-filters-titulos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "paging": true,
            "autoWidth": false,
            "language": {
                "decimal": ",",
                "thousands": ".",
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
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: 'Comissão Ragazzi',
                    footer: true,
                    autoFilter: true,
                    exportOptions: {
                        modifier: {
                            page: 'all'
                        },
                        columns: ':visible',
                        format: {
                            body: function ( data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column !== 0 && column !== 1 && column !== 2 && column !== 6 && column !== 7 && column !== 9 && column !== 10){
                                    if(data != ''){
                                        numero = data.replace( /[$.]/g, '' ).replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    }
                },
            ],
            "columnDefs": [
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },{
                    'targets': 'text_date',
                    'class': 'text_date',
                },
            ],
        });
    });

    function filterAjax(){
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            url: '{{ route('cenprot.filter')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var fields in data.response){
                    temp_array = [
                        data.response[fields].estabelecimento,
                        ajusteTamanhoTable(data.response[fields].cliente),
                        data.response[fields].titulo,
                        data.response[fields].titulo_cenprot,
                        data.response[fields].emissao,
                        data.response[fields].vencimento,
                        data.response[fields].data_envio,
                        data.response[fields].criado_por,
                        data.response[fields].removido_por,
                        data.response[fields].status,
                        btnCenprot(data.response[fields]),
                    ];
                    linhas.push(temp_array)
                }
                table_filters.rows.add(linhas).draw();            

            }
        });
    }

    function filterClear(){
        table_filters.clear().draw();
    }

    function optionsAutoCompleteCliente(){
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
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
                $(document).find("#cliente").val(ui.item.label);
                return false;
            }
        }
    }
    
    function showModalCliente(url){
        var title = "Busca de Clientes";
        esconderPopoverTooltip();
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosCliente($(this), event);
                        });
                    });
                });
            }
        });
    }
    function returnDadosCliente($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function btnCenprot($value){
        html = "<a href='#' class='status-titulo status-roxo' data-html='true' title='Histórico do Título - CENPROT - Título: "+$value.titulo+"'><span data-toggle='tooltip' data-html='true' title='Histórico do Título - CENPROT - Título: "+$value.titulo+"' data-titulo='"+$value.titulo+"' data-cenprot_id='"+$value.cenprot_id+"' data-cliente='"+$value.cliente+"' onclick='modalCenprotHistorico($(this))'>H</span></a>";

        if($value.status == 'ERRO AO COLETAR'){
            html = html + "  " + "<a href='#' class='status-titulo status-azul' data-html='true' title='Reenvio do Título - CENPROT - Título: "+$value.titulo+"'><span data-toggle='tooltip' data-html='true' title='Reenvio do Título - CENPROT - Título: "+$value.titulo+"' data-titulo='"+$value.titulo+"' data-cenprot_id='"+$value.cenprot_id+"' data-cliente='"+$value.cliente+"' onclick='reenviarCenprot($(this))'>R</span></a>";
        }

        return html;
    }

    function modalCenprotHistorico($value){
        var titulo = $value.data("titulo");
        var cliente = $value.data("cliente");
        var cenprot_id = $value.data("cenprot_id");
        esconderPopoverTooltip();
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                titulo: titulo,
                cenprot_id: cenprot_id,
            },
            url: '{{ route('cenprot.modal.historico') }}',
            success: function(data){
                createModal('cenprot_historico_modal', 'CENPROT - Histórico do Título: '+titulo+' - '+cliente, data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function reenviarCenprot($value){
        var titulo = $value.data("titulo");
        var cenprot_id = $value.data("cenprot_id");
        var $class = "dialog_option_reenviar_cenprot";
        var $name_option_ok_reenviar_cenprot = "ok_reenviar_cenprot";
        var $name_option_cancelar_reenviar_cenprot = "cancelar_reenviar_cenprot"; 

        $(document).off("ok_reenviar_cenprot");
        $(document).on("ok_reenviar_cenprot", function(){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ route('cenprot.reenviar_titulo') }}',
                type: 'POST',
                async: false,
                data: {
                    _token: '{{ csrf_token() }}',
                    titulo: titulo,
                    cenprot_id: cenprot_id,
                },
                success: function (body){
                    hide_loader();
                    filterClear();
                    filterAjax();
                    message("Atenção", "Título reenviado para Cenprot com Sucesso!");
                },
                error: function (callback){
                    message("Atenção", callback.responseJSON.message);
                }
            });
        });

        $(document).off("cancelar_reenviar_cenprot");
        $(document).on("cancelar_reenviar_cenprot", function(){
            return null; 
        });
        message_option("Atenção", "Deseja reenviar o título " + titulo + " para CENPROT?", $class, $name_option_ok_reenviar_cenprot, '', $name_option_cancelar_reenviar_cenprot, '');
        esconderPopoverTooltip();
    }
@endsection