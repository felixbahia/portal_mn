@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-4">
	        <select name="origem" id="origem">
	            <option value=''>Todos os estabelecimentos</option>
                @foreach($estabelecimentos as $key => $value)
                <option value="{{ $key }}">{{ $value }}</option>
	            @endforeach
	        </select>
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
    <table class="table table-striped" id="table-filters-margem-prazo">
        <thead>
            <tr>
                <th rowspan="2">Origem</th>
                <th rowspan="2">Fator diário</th>				
                <th colspan='3'>Faixas de Comissão</th>
                <th rowspan="2">Editar</th>
				<th rowspan="2">Apagar</th>
            </tr>
            <tr>
                <th>A</th>
                <th>B</th>
                <th>C</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')
    $(document).ready( function () {

        $("#btn-create").on("click", function(){
            showModal();
        });

        $("#btn-filterform").on("click", function(){
        
            filterAjax($("#form_filter").serialize());
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-view").off("click");
            $(document).find(".bt-view").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });
	});

    table_filters = $('#table-filters-margem-prazo').DataTable({
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
            }        },
        "columnDefs": [
            {
                "targets": [-1,-2],
                "orderable": false,
            },
            {
                "targets": [1,2,3,4],
                "className": 'number_format',
                "width": '10% !important'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });
    

    function showModal(){
        $.ajax({
            url: "{{ route('margem_prazo.formCadastro') }}",
            data: {_token: '{{ csrf_token() }}' },
            method: 'POST',
            success: function(data){
                createModal('criar_margem', 'Editar margem', data);
                ajaxForm($("#criar_margem"));
            }
        });
    }

    function showModalEdit(id){
        $.ajax({
            url: "{{ route('margem_prazo.formEdit') }}",
            data: {_token: '{{ csrf_token() }}', id:id},
            method: 'POST',
            success: function(data){
                createModal('editar_margem', 'Editar margem', data);
                ajaxForm($("#editar_margem"));
            }
        });
    }


    function showModalDelete(id){

        $.ajax({
            url: "{{ route('margem_prazo.formDelete') }}",
            data: {_token: '{{ csrf_token() }}', id:id},
            method: 'POST',
            success: function(data){                
                createModal('deletar_margem', 'Excluir margens', data);
            }
        });
    }

    function ajaxForm($model){
        $($model).find('[type="submit"]').on("click", function(event){
            event.stopPropagation();
            var form = $(this).parents('form');
            var form_data = form.serialize();
            var url = form.attr("action");
            $.ajax({
                url: url,
                dataType: 'json',
                data: form_data,
                method: 'POST',
                success: function(data){
                    $($model).modal('hide');
                    filterAjax($("#form_filter").serialize());
                    message("Atenção", "Dados salvos com sucesso!");
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }
            });
        });
    }

    function showErrorsInputs(form, input, message){
            var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
    }

    function createBodyPopOver($this){
        var $return = "";
        $.each($this,function(index, el) {
            $return += "<p>"+this+"</p>";
        });
        return $return;
    }

    function createBtEdit($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-edit\" data-toggle=\"popover\" data-trigger='hover' title=\"Editar\" onclick=\"showModalEdit("+$this.id+")\"></a>";
        return html;
    }

    function createBtDelete($this){
        var html = "<a href=\"#\" data-id=\""+$this.id+"\" class=\"bt-delete\" data-toggle=\"popover\" data-trigger='hover' title=\"Apagar\" onclick=\"showModalDelete("+$this.id+")\"></a>";

        return html;
    }

    function parserDataJson(data){
        var $return = [];
        $.each(data, function(index, el) {
            var temp = {
                "empresa": this.empresa,
                "fator_diario": this.fator_diario,
                "preco_a": this.preco_a,
                "preco_b": this.preco_b,
                "ṕreco_c": this.ṕreco_c,
                "editar": createBtEdit(this),
                "apagar": createBtDelete(this)
            };
            $return.push(temp);
        });
        return $return;
    }

    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('margem_prazo.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                    
                var linhas = data.data;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in data.data){
                        var temp_field = [
                            linhas[field].empresa,
                            linhas[field].fator_diario,
                            linhas[field].preco_a,
                            linhas[field].preco_b,
                            linhas[field].preco_c,
                            createBtEdit(linhas[field]),
                            createBtDelete(linhas[field])
                        ];

                        fields_filter.push(temp_field);
                    }
                        
                    console.log(fields_filter);

                    table_filters.rows.add(fields_filter).draw().nodes();
                    $(document).find(".bt-edit").off("click");
                    $(document).find(".bt-edit").on("click", function(event){
                        event.stopPropagation();
                    });
                }
            }
        });
    }
@endsection
</script>