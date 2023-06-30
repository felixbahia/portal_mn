@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group-lg-2">
            {{ Form::select('estabelecimento', returnEmpresasPrologusView(), '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
        </div>

        <div class="form-group col-lg-1">
            {{ Form::text('cod_produto', '', ['id' => 'cod_produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) }}
        </div>

        <div class="form-group col-lg-2">
	        {{ Form::text('nome_produto', '', ['id' => 'nome_produto', 'class' => 'form-control', 'placeholder' => 'Descrição do Produto']) }}
    	</div>

	</div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Gerar nova ficha</button>
    </div>
</form>	
@endsection

@section('content')
<div class="content-table">
	    <table class="table table-striped table-filter" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cód. Prod.</th>
                <th>Descr.</th>
                <th>Valor</th>
                <th>Custo Estimado</th>
                <th>Unidade</th>
                <th>Inclusão</th>
                <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

    $(document).ready(function (){
        $("#btn-filterform").click(function(event) {
            event.stopPropagation();
            filterAjax($("#form_filter").serialize());
        });

        $("#btn-create").click(function(event) {
            modalCriar();
        });
    })

    table_filters = $('#table_filters').DataTable(table_filters_fichas_options);

    table_filters_fichas_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "processing": true,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
                "targets": [4,5],
                "className": 'number_format',
            },
        ]
    };

    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit("+$this.id+")\"></a>";
        return html;
    }

    function createBtDelete($this){
        var html = "<a href=\"#\" class=\"bt-delete\" onclick=\"modalExcluirPedido($(this).parents('tr'), "+$this.id+")\"></a>";
        return html;
    }

    function filterAjax(data_form){

        var form = $("#form_filter");
        table_filters.clear().draw();

        form.find('.error-message').remove();


        $.ajax({
            url: "{{ route('ficha_tecnica.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            data[field].cod_produto,
                            data[field].descricao,
                            data[field].valor,
                            data[field].custo,
                            data[field].unidade,
                            data[field].inclusão,
                            createBtEdit(data[field]),
                            createBtDelete(data[field]),

                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find("#table-filters").find(".bt-edit").off("click");
                    $(document).find("#table-filters").find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
                }
            },
            error: function(data){
                var errors = data.responseJSON.errors;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function modalCriar(){

        $.ajax({
            url: '{{ route('ficha_tecnica.modal.cadastra') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}' },
            success: function(data){
                createModal('criar', 'Criar nova ficha', data, '');
            }
        })   
    }

    function showModalEdit($id){
        $.ajax({
            url: '{{ route('ficha_tecnica.modal.edita') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            success: function(data){
                createModal('editar', 'Editar ficha', data, '');
            }
        })   
    }

@endsection
</script>