@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::text('data_inicio', '', ['class' => 'data', 'id' => 'data_inicio', 'placeholder' => 'Início do período']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('data_fim', '', ['class' => 'data', 'id' => 'data_fim', 'placeholder' => 'Fim do período']) !!}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-novo" id="btn-novo" class="btn btn-success float-right" onclick="modalNew();">Novo período personalizado</button>
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class='periodo'>Período
                <th class='date-uk'>Data de Início</th>
                <th class='date-uk'>Data de término</th>
                <th class='tb_botao'></th>
                <th class='tb_botao'></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $('.data').mask('00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
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
        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_inicio').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
            $('#data_inicio').datepicker('update');
        });
        
        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                { "class": "tb_date", targets: "date-uk", "sType": "date-uk" },
                { "class": "tb_date", targets: "periodo", "sType": "mes-ano" },
                { "orderable": false, targets: 'tb_botao'}
            ],
            "order": [[ 0, 'desc' ]]
        });

        $(document).find("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($(document).find("#form_filter"));
        });
    });

    function buscaDados($form){
        table_filters.clear().draw();

        $('label.error-message').remove();

        $.ajax({
            url: '{{ route('comissao_data_fechamento.filter') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                var dados = callback.response;
                var lines = [];

                if(dados.length){
                    for(var field in dados){
                        var temp_field = [
                            dados[field].periodo,
                            dados[field].data_inicio,
                            dados[field].data_fim,
                            createEditButton(dados[field].id),
                            createDeleteButton(dados[field].id)
                        ];
                        lines.push(temp_field);
                    }

                    table_filters.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                }
            }
        });
    }

    function createEditButton($id){
        var $html = "<a href=\"#\" class=\"bt-edit\" onclick=\"modalEdit('"+$id+"')\"'></a>";

        return $html;
    }

    function createDeleteButton($id){
        var $html = "<a href=\"#\" class=\"bt-delete\" onclick=\"excluir('"+$id+"')\"'></a>";

        return $html;
    }

    function modalNew(){
        $.ajax({
            url: '{{ route('comissao_data_fechamento.modal.novo') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                createModal("new_data_fechamento_modal", "Nova data de fechamento personalizada", body, '');
            },
            error: function(callback) {
                message("Atenção", "Erro no processamento.");
            }
        });
    }

    function modalEdit($id){
        $.ajax({
            url: '{{ route('comissao_data_fechamento.modal.editar') }}',
            type: 'POST',
            data: {
                _token: '{!! csrf_token() !!}',
                'id': $id
            },
            success: function(body){
                createModal("editar_data_fechamento_modal", 'Editar data de fechamento', body, '');
            },
            error: function(data) {
                message("Atenção", "Erro no processamento.");
            }
        });
    }

    function excluir($id){
        $.ajax({
            url: '{{ route('comissao_data_fechamento.excluir') }}',
            type: 'POST',
            data: {
                _token: '{!! csrf_token() !!}',
                'id': $id
            },
            success: function(body){
                buscaDados($(document).find("#form_filter"));
            },
            error: function(data) {
                message("Atenção", "Erro no processamento.");
            }
        });
    }
@endsection
