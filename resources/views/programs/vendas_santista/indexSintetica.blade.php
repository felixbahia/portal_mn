@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <div class="content-fields">
            <div class="col-sm-2">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-sm-2">
                <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-sm-3">
                <div class="input-group">
                    <input type="text" class="form-control input-label" name="cliente" id="cliente" value="{{ CustomView::retornaClientePadraoNome() }}" placeholder="Cliente" maxlength="250" />
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::select('representante', $representantes, '', ['class'=>'form-control', 'placeholder' => 'Representante']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::select('status', ['true' => 'Confirmado', 'false' => 'Não confirmado'], '', ['class'=>'form-control', 'placeholder' => 'Status']) }}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
        </div>
    </form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-santista-sintetica">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2">Clientes</th>
                <th rowspan="2">Representantes</th>
                <th colspan="3">Valores</th>
                <th rowspan="2"></th>

            </tr>
            <tr>
                <th class='number_format valores'>Conferido</th>
                <th class='number_format valores'>Não Conferido</th>
                <th class='number_format valores'>Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {

        $(document).find("#cliente").autocomplete(optionsAutoCompleteCliente());
        
        $(document).find("#form_filter").find("#bt-search").on("click", function(){
            showModalClientes($(this).data("route"), "Lista de Clientes");
        });

        $("#btn-filterform").on("click", function(){        
            filterAjax($("#form_filter").serialize());
        });

        $(document).find(".bt-view").off("click");
        $(document).find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showModalClientes($(this));
        });

        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
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

        $(document).find(".btn-clear").on("click", function(){
            $form = $(this).parents('form');
            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input').not('[class^=btn-]').not('[name=_token]').val('');
                    $form.find('select').each(function(){
                        $(this).val($(this).find('option').eq(0).val());
                    });
                }
            });
        });
    });

    table_filters_vendas = $('#table-filters-santista-sintetica').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
                "className": 'number_format',
                "targets": [1,2,3,4,5],
            },
            {
                'targets': -1,
                'ordeable': false
            }
        ],
        "order": [[ 0, 'asc' ]]
    });

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function filterAjax(data_form){
        var $return;
        var form = $("#form_filter");
        table_filters_vendas.clear().draw();

        form.find('.error-message').remove();
        
        $.ajax({
            url: "{{ route('vendas_santista.sintetica.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function($data){

                var data = $data.response.dados;

                if(Object.keys(data).length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            criarLinkAnalitica(data[field].hash, data[field].clientes),
                            criarLinkAnalitica(data[field].hash, data[field].representantes),
                            data[field].valor_conferido,
                            data[field].valor_nao_conferido,
                            data[field].valor_total,
                            createLinkAnalitica(data[field].link)
                        ];
                        fields_filter.push(temp_field);
                    }                    
                    table_filters_vendas.rows.add(fields_filter).draw().nodes();
                    table_filters_vendas.columns.adjust().draw();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
                }
            },
            error: function(data){
                var errors = data.responseJSON.error;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
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
        };
    }

    function showModalClientes(title){
        $.ajax({
            url: '{{ route("cliente.index.dialogCadastro") }}',
            method: 'POST',
            data: {_token: '{{ csrf_token() }}' },
            success: function(body){

                var title = 'Consulta de clientes';
                
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").off("click");
                        $(document).find("#cliente_searsh_show").find('tbody').find("tr").on("click", function(){
                            returnDadosCliente($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosCliente($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_searsh_show").modal("hide");
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + ' - ' + $dados.find("td").eq(3).text());
    }

    function criarLinkAnalitica($hash, $texto){
        var html = "<a href='#' onclick=\"modalAnalitico('" + $hash + "')\">" + $texto + "</a>";
        return html;
    }

    function modalAnalitico($hash){
        $.ajax({
            url: "{{ route('vendas_santista.modal') }}",
            data: {_token: "{{ csrf_token() }}", hash: $hash},
            method: 'post',
            success: function(data){
                var $id = "analitica";
                var $title = "Visão analítica";
                var $body = data;

                createModal($id, $title, $body, 'modal-lg');
            }
        });
    }

    function createLinkAnalitica($link){

        var html = "<a href='"+$link+"'><i class='btn-link'></i></a>";

        return html;

    }

@endsection
