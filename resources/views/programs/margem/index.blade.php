@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
	        {!! Form::select('empresa', $estabelecimentos, '', ['id' => 'empresa', 'placeholder' => 'Empresa']) !!}
        </div>
        <div class="col-lg-2">
            <input type="text" name="grupo" id="grupo" value="" placeholder="Grupo" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="produto" id="produto" value="" placeholder="Código de produto" maxlength="250" />
        </div>
        <div class="col-lg-2">
            <input type="text" name="nome" id="nome" value="" placeholder="Nome Produto" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="marca" id="marca" value="" placeholder="Marca" maxlength="250" />
        </div>
        <div class="col-lg-1">
            <input type="text" name="linha" id="linha" value="" placeholder="Linha" maxlength="250" />
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
    <table class="table table-striped" id="table-filters-margems">
        <thead>
            <tr>
                <th>Empresa</th>
                <th>Grupo</th>
                <th>Produto</th>
                <th>Nome</th>
                <th>Marca</th>
                <th>Linha</th>
				<th>Margem %</th>
                <th>Editar</th>
                <th>Apagar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-modal')

{{-- Modal de adição --}}
<div class="modal fade" id="modal_margem" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>

{{-- Modal de edição --}}
<div class="modal fade" id="modal_margem_edit" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>

{{-- Modal de exclusão --}}
<div class="modal fade" id="modal_margem_delete" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalLabel"><span></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            </div>
            <div class="modal-footer content-buttons">
            </div>
        </div>
    </div>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {
        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoComplete("marca"));
        $("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $("#linha").autocomplete(optionsAutoComplete("linha"));
        
        $("#empresa").on("change", function(){
            table_filters.clear().draw();
        });

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
    
    function optionsAutoComplete($name, element = null){

        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
        	},
            select: function( event, ui ) {
                setTimeout(function(){
                    table_filters.draw();
                }, 100);
            }
        };
    }

    function showModal(){
        $("#modal_margem").modal("toggle");
        $("#modal_margem").off('shown.bs.modal');
        $("#modal_margem").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('margem.formCadastro') }}",
                data: {_token: '{{ csrf_token() }}' },
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);

                	modal.find('.modal-title').html("Criar nova margem");

                    modal.find("#empresa_modal").val($("#empresa").val());

        	        modal.find("#produto_modal").autocomplete(optionsAutoComplete("nome"));
			        modal.find("#marca_modal").autocomplete(optionsAutoComplete("marca"));
			        modal.find("#linha_modal").autocomplete(optionsAutoComplete("linha"));
			        modal.find("#grupo_modal").autocomplete(optionsAutoComplete("grupo"));

                    modal.find('#margem_a').mask("#0,99", {reverse: true});

			        $(document).find('.ui-widget-content').css('z-index', "2000 !important");
                    ajaxForm($("#modal_margem"));
                }
            });
        });
        $("#modal_margem").off('hidden.bs.modal');
        $("#modal_margem").on('hidden.bs.modal', function (e) {
            $("#modal_margem").find('.modal-body').html('');
            $("#btn-create").on("click", function(){
                showModal();
            });
        });
    }

    function showModalEdit(id){
        $("#modal_margem_edit").modal("toggle");
        $("#modal_margem_edit").off('shown.bs.modal');
        $("#modal_margem_edit").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('margem.formEdit') }}",
                data: {id: id, _token: '{{ csrf_token() }}' },
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);

                    modal.find('.modal-title').html("Editar margem");

                    modal.find("#produto_modal").autocomplete(optionsAutoComplete("nome"));
                    modal.find("#marca_modal").autocomplete(optionsAutoComplete("marca"));
                    modal.find("#linha_modal").autocomplete(optionsAutoComplete("linha"));
                    modal.find("#grupo_modal").autocomplete(optionsAutoComplete("grupo"));

                    modal.find('#margem_a').mask("#0,99", {reverse: true});

                    $(document).find('.ui-widget-content').css('z-index', "2000 !important");
                    ajaxForm($("#modal_margem_edit"));
                }
            });
        });
        $("#modal_margem_edit").off('hidden.bs.modal');
        $("#modal_margem_edit").on('hidden.bs.modal', function (e) {
            $("#modal_margem_edit").find('.modal-body').html('');

        });
    }

    function showModalDelete(id){
        $("#modal_margem_delete").modal("toggle");
        $("#modal_margem_delete").off('shown.bs.modal');
        $("#modal_margem_delete").on('shown.bs.modal', function (event) {
            var modal = $(this);
            $.ajax({
                url: "{{ route('margem.formDelete') }}",
                data: {id: id, _token: '{{ csrf_token() }}' },
                method: 'POST',
                success: function(data){
                    modal.find('.modal-body').html(data);

                    modal.find('.modal-title').html("Excluir margem");
                    ajaxForm($("#modal_margem_delete"));
                    $("#cancelar_modal").on("click", function(){
                        $("#modal_margem_delete").modal("toggle");
                    });
                }
            });
        });
        $("#modal_margem_delete").off('hidden.bs.modal');
        $("#modal_margem_delete").on('hidden.bs.modal', function (e) {
            $("#modal_margem_delete").find('.modal-body').html('');
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
        if (input == 'empresa'){
            var $input = $(form).find("select[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
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
                "grupo": this.grupo,
                "produto": this.produto,
                "nome": this.nome,
                "marca": this.marca,
                "linha": this.linha,
                "margem_a": this.margem_a,
                "editar": createBtEdit(this),
                "apagar": createBtDelete(this)
            };
            $return.push(temp);
        });
        return $return;
    }

    table_filters = $('#table-filters-margems').DataTable({
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
                "targets": ($('#table-filters-margems thead th').length - 1),
                "orderable": false
            },
            {
                "targets": ($('#table-filters-margems thead th').length - 2),
                "orderable": false
            },
            {
                "targets": 6,
                className: 'number_format'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function filterAjax(data_form){
        var $return;
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('margem.filter') }}",
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
                            linhas[field].grupo,
                            linhas[field].produto,
                            linhas[field].nome,
                            linhas[field].marca,
                            linhas[field].linha,
                            linhas[field].margem_a,
                            createBtEdit(linhas[field]),
                            createBtDelete(linhas[field]),
                        ];

                        fields_filter.push(temp_field);
                    }
                        
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