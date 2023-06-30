@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>DRE</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {!! Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control", 'placeholder' => 'Estabelecimento']) !!}
            </div>
            <div class="col-lg-2">
                <input type="text" class="data" name="ano" id="ano" placeholder="Ano" value="{{ date('Y') }}" maxlength="20">
            </div>
            <div class="col-lg-2">
                {!! Form::select("agrupar", $agrupadores, '', ["id" => "agrupar", "class"=>"form-control", 'placeholder' => 'Agrupar']) !!}
            </div>
            <div class="col-lg-2">
                <div class="form-check">
                    {!! Form::checkbox('prepago', 'true', true, ['id' => 'prepago', 'class' => 'form-check-input']) !!}
                    {!! Form::label('prepago', 'Pré-pago', ['class' => 'form-check-label']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-2">
                <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="subgrupo" id="subgrupo" value="" placeholder="Sub Grupo" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
            </div>
            <div class="col-lg-2">
                <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
            </div>
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters2">
        <thead>
            <tr>
                <th></th>
                @for ($i = 1; $i <= 12; $i++)
                <th class="tb_number">{{ parserNameMonth($i) }}</th>
                @endfor
                <th class="tb_number">total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    table_filters_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "orderMulti": false,
        "ordering": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column >= 1){
                                if(data != ''){
                                    numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" }
        ],
        "createdRow": function( row, data, dataIndex){
            if( data[0] ==  'Valor Pre-pago venda (+)'){
                $(row).addClass('success_inventario');
            }
        }
    };
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options);
    table_filters.draw();
    $(document).ready( function () {
        $(document).find('.data').mask('0000');
        $(document).find('.data').datepicker({
            language: 'pt-BR',
            format: 'yyyy',
            zIndex: 2000,
            autoHide: true
        });
        
        $(document).find("#btn-filterform").on("click", function(){
            filterAjax($(document).find("#form_filter").serialize());
        });
        table_filters.on('draw', function () {
            $(document).find('[data-toggle="tooltip"]').tooltip();
        });

        $(document).find("#marca").autocomplete(optionsAutoComplete("marca"));
        $(document).find("#linha").autocomplete(optionsAutoComplete("linha"));
        $(document).find("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $(document).find("#subgrupo").autocomplete(optionsAutoComplete("subgrupo"));
    });
    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    function aberturaMes($title, $mes, $chave, $hash){
        $.ajax({
            url: "{{ route('faturamento_vs_cmn.modal') }}",
            data: {
                _token: "{{ csrf_token() }}",
                mes: $mes,
                chave: $chave,
                hash: $hash
            },
            method: 'POST',
            success: function(callback){
                createModal("modal_abertura_mes", $title, callback, 'modal-lg');
            }
        });
        
    }
    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('faturamento_vs_cmn.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status == 'success'){
                    var data = callback.response;
                    if(Object.keys(data).length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            if(data[field].origem != "ICMS (+) Beneficio RO + TO"){
                                var temp_field = [
                                        data[field].origem,
                                        @for ($i = 1; $i <= 12; $i++)
                                        data[field].mes_{{ $i }},
                                        @endfor
                                        data[field].total 
                                ];
                                fields_filter.push(temp_field);
                            } 
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();
                    }
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $(document).find("#form_filter").find('input[name="'+index+'"],select[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $(document).find("#form_filter").find('input[name="'+index+'"],select[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $(document).find("#form_filter").find('input.error').eq(0).focus();
                }
            }
        });
    }

    function abriModalDespesa($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        var codigo_conta  = $($this).data("codigo_conta");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.despesa_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                codigo_conta: codigo_conta,
            },
            success: function(body){
                createModal('modal_detalhes_despesa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_despesa");
            }
        });
    }

    function abriModalDespesaArrayContas($this){
        var ano = $($this).data("ano");
        var title = $($this).data("title");
        var codigo_conta  = $($this).data("codigo_conta");
        $.ajax({
            url: '{{ route('acompanhamento_orcamentario.modal.despesa_array_contas_detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                ano: ano,
                codigo_conta: codigo_conta,
            },
            success: function(body){
                createModal('modal_detalhes_despesa', title, body, "modal-lg");
                var modal = $("#modal_detalhes_despesa");
            }
        });
    }
@endsection
