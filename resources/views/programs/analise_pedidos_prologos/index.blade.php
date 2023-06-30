@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
        </div>
        <div class="col-lg-2">
            <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="{{ date('d/m/Y') }}" maxlength="20">
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_number">Pedidos digitados no Portal</th>
                <th class="tb_number">Pedidos digitados na Nasajon</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
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
        });
        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            ],
            "order": [[ 0, 'asc' ]]
        });

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });
    });
    function creteBtnQtd($action, $quantidade, $estabelecimento){
        var html = "<a href=\"#\" onclick=\"modalPedidos('"+$estabelecimento+"', '"+$action+"')\">"+$quantidade+"</a>";
        return html;
    }

    function modalPedidos($estabelecimento, $action){
		$.ajax({
			url: '{{ route('analise_pedidos.modal.abertura') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
                estabelecimento: $estabelecimento,
                action: $action,
                data_inicio: $(document).find('#data_inicio').val(),
                data_fim: $(document).find('#data_fim').val()
			},
			success: function(body){
				createModal('modal_entrada_pedido', "Analise de entrada do estabelecimento "+$estabelecimento, body, 'modal-lg');
			}
		});
    }
    function buscaDados($form){
        table_filters.clear().draw();
        
        $.ajax({
            url: '{{ route('analise_pedidos.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){
                    var dados = callback.response;
                    table_filters.clear().draw();
                    var lines = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].estabelecimento,
                            dados[field].quantidade_portal,
                            creteBtnQtd('nasajon', dados[field].quantidade_nasajon, field),
                            creteBtnQtd('todos', dados[field].quantidade_total, field)
                        ];
                        lines.push(temp_field);
                    }
                    table_filters.rows.add(lines).draw().nodes();
                }
            }
        }).always(function() {
            hide_loader();
        });
        
    }
@endsection
