@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
            {!! Form::text('documento_busca_filtro', '', ['id' => 'documento_busca_filtro', 'maxlength' => '250', 'placeholder' => 'Número Documento']) !!}
        </div>
        <div class="col-lg-4">
            {!! Form::text('vencimento_inicial_busca_filtro', '', ['id' => 'vencimento_inicial_busca_filtro', "class" => "data", 'maxlength' => '250', 'placeholder' => 'Vencimento Inicial']) !!}
        </div>
        <div class="col-lg-4">
            {!! Form::text('vencimento_final_busca_filtro', '', ['id' => 'vencimento_final_busca_filtro', "class" => "data", 'maxlength' => '250', 'placeholder' => 'Vencimento Final']) !!}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th>Documento</th> 
                <th class="tb_number">Valor U$</th>
                <th class="tb_number">Taxa Câmbio R$</th> 
                <th class="tb_date">Vencimento</th>  
                <th class="tb_number">Saldo</th>                  
                <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total: </td>
                <td class="tb_number" id='total_valor'></td>
                <td></td>
                <td></td>
                <td class="tb_number" id='total_saldo'></td> 
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#btn-create").off("click");
        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        $("#btn-filterform").off("click");
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
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
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
                {
                    'targets': 'tb_date',
                    'class': 'tb_date',
                }
            ],
        });

        $(document).find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        $(document).find('.data').mask('00/00/0000');

    });

    function showModalCreate(){
        $.ajax({
            url: '{{ route('red.modal.adicionar') }}',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}"},
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_motivo_cancelamento_adicionar', title, body, "");
            	var modal = $("#modal_motivo_cancelamento_adicionar");
            }
        });
    }

    function filterAjax(data_form){
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('red.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                var fields_filter = [];
                for(var field in data.reds){
                    var temp_field = [
                        createBtViewDetalhes(data.reds[field]),
                        data.reds[field].valor,
                        data.reds[field].taxa_cambio,
                        data.reds[field].vencimento,
                        data.reds[field].saldo,
                        createBtnEdit("{{ route('red.modal.editar') }}", data.reds[field].id),
                        createBtnDelete("{{ route('red.modal.deletar') }}", data.reds[field].id)
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters.rows.add(fields_filter).draw().nodes();

                $(document).find('#total_valor').html(data.total.valor);
                $(document).find('#total_saldo').html(data.total.saldo);
            }
        });
    }

    function createBtnEdit($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar\" onclick=\"showModal($(this))\"></a>";
        return $html;
    }

    function createBtnDelete($url, $id){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir {{ CustomView::programaName() }}\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir\" onclick=\"showModal($(this))\"></a>";
        return $html;
    }

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
                createModal('modal_motivo_cancelamento_edit_delete', title, body, modal_class);
                var modal = $("#modal_motivo_cancelamento_edit_delete");
            }
        });
    }

    function createBtViewDetalhes($dados){
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-id=\""+$dados.id+"\" data-numero_documento=\""+$dados.numero_documento+"\" onclick=\"abriModalDetalhes($(this))\">"+$dados.numero_documento+"</a>";

        return html;
    }

    function abriModalDetalhes($this){
        var id = $($this).data("id");
        var numero_documento = $($this).data("numero_documento");
        var title = "Hedge: " + numero_documento;
        $.ajax({
            url: '{{ route('red.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                id : id,
            },
            success: function(body){
                createModal("model_detalhes", title, body, 'modal-lg');
                var modal = $("#model_detalhes");
            }
        });
    }
@endsection