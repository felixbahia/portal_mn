@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div>Última Atualização: <span id="atualizacao">-</span></div>
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-sm-2">
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'placeholder' => 'Todos Estabelecimentos']) !!}
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
    <table class="table table-striped table-not-view table-not-edit" id="table-filters-documentos">
        <thead>
            <tr>
                <th>Tipo</th>
                <th class='tb-number'>Quantidade</th>
                <th class='sort-date'>Mais antigo</th>
                <th>Diferença</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready(function(){
        $(document).find('#btn-filterform').on('click', function(){
            filter();
        });
        
        filter();

        setInterval(filter, 300000);
    })

    table_filter_documentos = $("#table-filters-documentos").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": true,
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
            { "class": "tb_date", targets: "sort-date" },
            { "class": "tb_number", targets: "tb-number" },
        ],
        "order": [[ 1, 'asc' ]]
        }
    );

    function filter(){
        
        table_filter_documentos.clear().draw();
        $(document).find('#atualizacao').html('');

        $.ajax({
            url: '{{ route("documentos_nao_processados.filter") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                var response = data.response.retorno;
                var linhas = [];

                for(var field in response){
                    
                    tipo_linha = [
                        response[field].tipo,
                        response[field].quantidade,
                        response[field].mais_antigo,
                        response[field].mais_antigo_diferenca
                    ]

                    linhas.push(tipo_linha);
                }

                table_filter_documentos.rows.add(linhas).nodes().draw();

                $(document).find('#atualizacao').html(data.response.agora);

            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

@endsection
