@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" class="data" name="data" id="data" placeholder="Data MM/AAAA" value="{{ date('m/Y') }}" maxlength="20">
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
                <th>Estabelecimento</th>
                <th class="tb_number">número de notas</th>
                <th class="tb_number">número de parcelas</th>
                <th class="tb_number">valor total</th>
                <th class="tb_number">valor medio</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tfoot> 
    </table>
</div>
@endsection

@section('script-footer')
    table_filters_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        "ordering": false,
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "text_date", targets: "tb_date" },
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            {
                'targets': 'sistema',
                'visible': false
            }
            
        ],
        "order": [[ 0, 'asc' ]]
    };
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options);
    table_filters.draw();
    $(document).ready( function () {
        $(document).find(".valor").maskMoney({thousands:'.', decimal:','});
        $('.data').mask('00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            zIndex: 2000,
            autoHide: true
        });
        
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
    });

    function filterAjax(data_form){
        $(table_filters.column(0).footer()).html('');
        $(table_filters.column(1).footer()).html('');
        $(table_filters.column(2).footer()).html('');
        $(table_filters.column(3).footer()).html('');
        $(table_filters.column(4).footer()).html('');

        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('analise_de_entrada.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status == 'success'){
                    var data = callback.response.response;
                    if(Object.keys(data).length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].estabelecimento,
                                data[field].numero_notas,
                                data[field].numero_parcelas,
                                data[field].valor_total,
                                data[field].valor_medio,
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();
                    }

                    $(table_filters.column(0).footer()).html('Total');
                    $(table_filters.column(1).footer()).html(callback.response.total.numero_notas);
                    $(table_filters.column(2).footer()).html(callback.response.total.numero_parcelas);
                    $(table_filters.column(3).footer()).html(callback.response.total.valor_total);
                    $(table_filters.column(4).footer()).html(callback.response.total.valor_medio);
                }
            }
        });
    }
@endsection
