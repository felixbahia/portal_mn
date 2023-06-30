@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Todos'])}}
            </div>
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('transportadora', '', ['id' => 'transportadora', 'class' => 'form-control input-label', 'placeholder' => 'Transportadora']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-transportadora-busca" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', date('01/m/Y'), ['id' => 'data_inicio', 'class' => 'data form-control', 'placeholder' => 'Data Início DD/MM/AAAA', 'maxlength' => "20"]) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', date('t/m/Y'), ['id' => 'data_fim', 'class' => 'data form-control', 'placeholder' => 'Data Fim DD/MM/AAAA', 'maxlength' => "20"]) }}
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2 ml-5">
                {{ Form::checkbox('cif', '1', true, ['id' => 'cif', 'class' => 'form-check-input']) }} 
                {{ Form::label('cif', 'CIF', ['class' => 'form-check-label']) }}
            </div>
            <div class="col-lg-2 ml-5">
                {{ Form::checkbox('fob', '1', true, ['id' => 'fob', 'class' => 'form-check-input']) }} 
                {{ Form::label('fob', 'FOB', ['class' => 'form-check-label']) }}
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-excel" id="btn-excel" class="btn btn-success float-right d-none">Exportar para Excel</button>

    </div>
</form>
@endsection
@section('content')
<div class="content-table notas-importadas">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
        <thead>
            <tr>
                <th rowspan='2'>Estabelecimento</th>
                <th class="mes-col tb_number" rowspan='2'>Faturamento</th>
                <th colspan='2'>Peso</th>
                <th colspan='5'>Frete</th>
                <th rowspan='2'></th>
            </tr>
            <tr>
                <th class="notas-col tb_number" >Pago</th>
                <th class="notas-col tb_number" >Transportado</th>
                <th class="compras-col tb_number">Cobrado</th>
                <th class="compras-col tb_number">% cobrado</th>
                <th class="compras-col tb_number">Pago</th>
                <th class="compras-col tb_number">% pago</th>
                <th class="compras-col tb_number" >Diferença</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td id="total_faturamento"></td>
                <td id='peso_transportado'></td>
                <td id='peso_cobrado'></td>
                <td id='total_frete_cobrado'></td>
                <td id='total_porcentagem_cobrado'></td>
                <td id='total_frete_pago'></td>
                <td id='total_porcentagem_pago'></td>
                <td id='total_diferenca'></td>
                <td><a href="#" class="bt-view d-none" id='btn-modal-total' data-estabelecimento="" data-data_inicio="" data-data_fim="" data-cif="" data-fob="" data-route="" data-toggle="tooltip" data-placement="top" title="Detalhes" onclick='modal(this)'></a></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: 100,
            autoHide: true
        });
        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
            $('#data_fim').datepicker('update');
        });
        table_filters_frete = $('#table-filters-index').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 20,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    customize: function ( xlsx ) {
                        var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        $('c[r=G7] t', sheet).attr( 's', '0' );
                    },
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                            }
                        }
                    },
                },
            ],
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
                    "class": "mes-col tb_number",
                    "targets": "mes-col tb_number"
                },
                {
                    "class": "notas-col tb_number",
                    "targets": "notas-col tb_number"
                },
                {
                    "class": "compras-col tb_number",
                    "targets": "compras-col tb_number"
                },
                {
                    "class": "lancadas-col tb_number",
                    "targets": "lancadas-col tb_number"
                },
            ],
            "order": [ 0, 'asc' ]
        });

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });

        $("#form_filter").find("#btn-clearform").on("click", function(){
            $(document).find('#btn-excel').addClass('d-none');
            $(document).find('#btn-modal-total').addClass('d-none');
            $(document).find('#table-filters-index').find('#total_faturamento').html('');
            $(document).find('#table-filters-index').find('#peso_transportado').html('');
            $(document).find('#table-filters-index').find('#peso_cobrado').html('');
            $(document).find('#table-filters-index').find('#total_frete_cobrado').html('');
            $(document).find('#table-filters-index').find('#total_porcentagem_cobrado').html('');
            $(document).find('#table-filters-index').find('#total_frete_pago').html('');
            $(document).find('#table-filters-index').find('#total_porcentagem_pago').html('');
            $(document).find('#table-filters-index').find('#total_diferenca').html('');
            table_filters_frete.clear().draw();
        });

        $(document).find('#btn-excel').on('click', function(){
            exportXlsx();
        });

        $(document).find("#transportadora").autocomplete(optionsAutoCompleteTransportador());

        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
        });

    });
    function buscaDados($form){
        table_filters_frete.clear().draw();
        $(document).find('#btn-excel').addClass('d-none');
        $(document).find('#btn-modal-total').addClass('d-none');
        $(document).find('#table-filters-index').find('#total_faturamento').html('');
        $(document).find('#table-filters-index').find('#peso_cobrado').html('');
        $(document).find('#table-filters-index').find('#peso_transportado').html('');
        $(document).find('#table-filters-index').find('#total_frete_cobrado').html('');
        $(document).find('#table-filters-index').find('#total_porcentagem_cobrado').html('');
        $(document).find('#table-filters-index').find('#total_frete_pago').html('');
        $(document).find('#table-filters-index').find('#total_porcentagem_pago').html('');
        $(document).find('#table-filters-index').find('#total_diferenca').html('');
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('frete_cobrado_x_pago.filter') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response.resultado;
                    var total = callback.response.total;
                    var fields = callback.response.fields;

                    var lines = [];
                    if(dados.length){

                        $(document).find('#btn-excel').removeClass('d-none');
                        $(document).find('#btn-modal-total').removeClass('d-none');

                        for(var field in dados){
                            var temp_field = [
                                dados[field].estabelecimento,
                                dados[field].faturamento,
                                dados[field].peso_transportado,
                                dados[field].peso_cobrado,
                                createLink("{{ route('frete_cobrado_x_pago.modal.notas') }}", dados[field].frete_cobrado, dados[field].estabelecimento_codigo, fields.data_inicio, fields.data_fim, fields.transportadora, fields.entrega, fields.cif, fields.fob),
                                dados[field].porcentagem_cobrado,
                                createCteLink(dados[field].frete_pago, dados[field].hash),
                                dados[field].porcentagem_pago,
                                dados[field].diferenca,
                                createBtnView("{{ route('frete_cobrado_x_pago.modal.index') }}", dados[field].estabelecimento_codigo, fields.data_inicio, fields.data_fim, fields.transportadora, fields.entrega, fields.cif, fields.fob)
                            ];
                            lines.push(temp_field);
                        }

                        $(document).find('#btn-modal-total').data('data_inicio', fields.data_inicio).data('data_fim', fields.data_fim).data('transportadora', fields.transportadora).data('cif', fields.cif!=undefined?1:'').data('fob', fields.fob!=undefined?1:'').data('route', "{{ route('frete_cobrado_x_pago.modal.index') }}");

                        var rows = table_filters_frete.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                        
                        $(document).find('#table-filters-index').find('#total_faturamento').html(total.faturamento);
                        $(document).find('#table-filters-index').find('#peso_cobrado').html(total.peso_cobrado);
                        $(document).find('#table-filters-index').find('#peso_transportado').html(total.peso_transportado);
                        $(document).find('#table-filters-index').find('#total_frete_cobrado').html(createLink("{{ route('frete_cobrado_x_pago.modal.notas') }}", total.frete_cobrado, '', fields.data_inicio, fields.data_fim, fields.transportadora, fields.entrega, fields.cif, fields.fob));
                        $(document).find('#table-filters-index').find('#total_porcentagem_cobrado').html(total.porcentagem_cobrado);
                        $(document).find('#table-filters-index').find('#total_frete_pago').html(createCteLink(total.frete_pago, total.hash));
                        $(document).find('#table-filters-index').find('#total_porcentagem_pago').html(total.porcentagem_pago);        
                        $(document).find('#table-filters-index').find('#total_diferenca').html(total.diferenca);
                    }
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
        }).always(function() {
            hide_loader();
        });
        
    }

    function exportXlsx(){

        $('<form action="{{ route('frete_cobrado_x_pago.export') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="estabelecimento" value="'+$(document).find("#estabelecimento").val()+'">\
                <input type="hidden" name="data_inicio" value="'+$(document).find("#data_inicio").val()+'" />\
                <input type="hidden" name="data_fim" value="'+$(document).find("#data_fim").val()+'"  />\
                <input type="hidden" name="transportadora" value="'+$(document).find("#transportadora").val()+'"  />\
                <input type="hidden" name="cif" value="'+ ($(document).find("#cif").is(':checked')?'1':'') +'"  />\
                <input type="hidden" name="fob" value="'+ ($(document).find("#fob").is(':checked')?'1':'') +'"  />\
                <input type="hidden" name="export" value="1"/>\
            </form>').appendTo('body').submit().remove();
    }

    function createBtnView($url, $estabelecimento, $data_inicio, $data_fim, $transportadora, $entrega, $cif, $fob){
        var $html = "<a href=\"#\" class=\"bt-view\" data-estabelecimento=\""+$estabelecimento+"\" data-data_inicio=\""+$data_inicio+"\" data-data_fim=\""+$data_fim+"\" data-transportadora=\""+$transportadora+"\" data-entrega=\""+($entrega != undefined ? $entrega : '')+"\" data-cif=\""+($cif != undefined ? $cif : '')+"\" data-fob=\""+($fob != undefined ? $fob : '')+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" data-title='Valores Cobrados x Valores Pagos' title=\"Detalhes\" onclick='modal(this)'></a>";
        return $html;
    }

    function modal($element){
        $.ajax({
            url: $($element).data('route'),
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estabelecimento: $($element).data('estabelecimento'),
                data_inicio: $($element).data('data_inicio'),
                data_fim: $($element).data('data_fim'),
                transportadora: $($element).data('transportadora'),
                entrega: $($element).data('entrega'),
                cif: $($element).data('cif'),
                fob: $($element).data('fob'),
            },
            success: function(body){
                createModal('modal_fretes_detalhe', $($element).data('title'), body, 'modal-lg');
            }
        });
    }

    function optionsAutoCompleteTransportador(){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                $(document).find("#transportadora").val(ui.item.label);
                return false;
            }

        };
    }

    function createCteLink($valor, $hash){
        var html = "<a href=\"#\" data-route=\"{{ route('notas_importadas.modal.notas') }}\" data-title='Valores pagos' data-filter=\""+$hash+"\" data-title='Valores Pagos' class='bt-modal' onclick='showModalNotas(this)'>"+$valor+"</a>"
        return html;
    }

    function showModalNotas($this){
        var url = $($this).data("route");
        var filter = $($this).data("filter");
        var title = $($this).data('title');
        var estabelecimento_prods = $($this).data('estabelecimento_prods');
        xhr = $.ajax({
            url: url,
            data: {_token: "{{ csrf_token() }}", filters: filter},
            method: 'POST',
            success: function(body){
                createModal("notas_importadas-modal-notas", title, body, 'modal-lg');
            }
        });
    }

    function createLink($url, $valor, $estabelecimento, $data_inicio, $data_fim, $transportadora, $entrega, $cif, $fob){
        var $html = "<a href=\"#\" data-estabelecimento=\""+$estabelecimento+"\" data-data_inicio=\""+$data_inicio+"\" data-data_fim=\""+$data_fim+"\" data-transportadora=\""+$transportadora+"\" data-entrega=\""+($entrega != undefined ? $entrega : '')+"\" data-cif=\""+($cif != undefined ? $cif : '')+"\" data-fob=\""+($fob != undefined ? $fob : '')+"\" data-route=\""+$url+"\" data-toggle=\"tooltip\" data-placement=\"top\" data-title='Valores Cobrados' title=\"Detalhes\" onclick='modal(this)'>"+ $valor +"</a>";
        return $html;
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route("transportador.index.dialog") }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(data){
                $(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
                            returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }
        });
    }

    function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#transportadora").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
        modal.modal('hide');
    }
@endsection
