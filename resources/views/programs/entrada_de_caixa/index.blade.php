@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::select("estabelecimento", $estabelecimentos, '', ["class"=>"form-control"]) !!}
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
    <table class="table table-striped" id="table-filters2">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class="tb_date">Data</th>
                <th class="tb_number">Valor</th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
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
        "pageLength": 15,
        "orderMulti": false,
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
            
        ],
        "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
    };
    table_filters = $(document).find('#table-filters2').DataTable(table_filters_options);
    table_filters.draw();
    $(document).ready( function () {
        
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });
        $("#btn-create").on("click", function(){
            showModalCreate();
        });
        table_filters.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
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
                createModal('modal_entrada_de_caixa_edit_delete', title, body, modal_class);
                var modal = $("#modal_entrada_de_caixa_edit_delete");
            }
        });
    }
    function showModalCreate(){
        $.ajax({
            url: '{{ route('entrada_de_caixa.modal.adicionar') }}',
            method: 'GET',
            success: function(body){
            	var title = 'Cadastro de {{ CustomView::programaName() }}';
    			createModal('modal_entrada_de_caixa', title, body, '');
            }
        });
    }
    function createBtnEdit($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Editar\" class=\"bt-edit\" title=\"Editar\"></a>";
        return $html;
    }
    function createBtnDelete($url, $dados){
        var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"\" data-title_modal=\"Excluir\" class=\"bt-delete\" title=\"Excluir\"></a>";
        return $html;
    }
    function filterAjax(data_form){
        var $return;
        $.ajax({
            url: "{{ route('entrada_de_caixa.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                if(callback.status == 'success'){
                    var data = callback.response;
                    table_filters.clear().draw();
                    if(data.length > 0){
                        var fields_filter = [];
                        for(var field in data){
                            var temp_field = [
                                data[field].estabelecimento,
                                data[field].data,
                                data[field].valor,
                                createBtnEdit("{{ route('entrada_de_caixa.modal.editar') }}", data[field]),
                                createBtnDelete("{{ route('entrada_de_caixa.modal.deletar') }}", data[field])
                            ];
                            fields_filter.push(temp_field);
                        }
                        table_filters.rows.add(fields_filter).draw().nodes();
                    }
                }
            }
        });
    }
@endsection
