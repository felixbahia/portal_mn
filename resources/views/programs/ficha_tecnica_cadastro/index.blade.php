@extends('layouts.app')
@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca do produto']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha do produto']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo do produto']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('subgrupo', '', ['id' => 'subgrupo', 'class' => 'form-control', 'placeholder' => 'Subgrupo do produto']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Descrição do produto']) !!}
        </div>
    </div>
    <div class="content-buttons mt-3">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />

        <button name="btn-nova-ficha" id="btn-nova-ficha" class="btn btn-primary float-right mr-2">Nova Ficha</button>
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-ficha-tecnica table-not-edit" id="table-ficha-tecnica">
        <thead>
            <tr>
                <th class='td_codigo'>Código</th>
                <th class='td_marca'>Marca</th>
                <th class='td_linha'>Linha</th>
                <th class='td_grupo'>Grupo</th>
                <th class='td_subgrupo'>Subgrupo</th>
                <th class='td_descricao'>Descrição</th>
                <th class='td_duplicar'></th>
                <th class='td_editar'></th>
                <th class='td_excluir'></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });

        $(document).find("#btn-nova-ficha").on('click', function(){
            showModalNovo();
        });

    });

    table_filters = $('#table-ficha-tecnica').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollCollapse": true,
        "pageLength": 15,
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
            { "width": "25%", "targets": "td_descricao"},
            { "width": "8%", "targets": "td_codigo"},
            { "width": "7%", "targets": "td_linha"},
            { "width": "10%", "targets": "td_subgrupo"},
            { 'targets': "td_duplicar", 'orderable': false},
            { 'targets': "td_editar", 'orderable': false},
            { 'targets': "td_excluir", 'orderable': false}
        ]
    });

    function buscaDados(form){

        var data_form = form.serialize();

        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                table_filters.clear().draw();
                $(document).find('[data-toggle="tooltip"], .tooltip').tooltip("hide");

                var data = callback.response.produtos;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].codigo_produto,
                            data[field].marca,
                            data[field].linha,
                            data[field].grupo,
                            data[field].subgrupo,
                            data[field].descricao,
                            createBtnDuplicar(data[field].id),
                            createBtnEdit(data[field].id, data[field].codigo_produto, data[field].descricao_limpa),
                            createBtnExcluir(data[field].id, data[field].codigo_produto, data[field].descricao_limpa)
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw();
                }
            }
        });
    }

    function createBtnEdit($id, $codigo_produto, $descricao){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" data-codigo_produto=\""+$codigo_produto+"\" data-descricao=\""+$descricao+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\" onclick=\"showModalEdicao($(this))\"></a>";
        return $html;
    }

    function createBtnExcluir($id, $codigo_produto, $descricao){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" data-codigo_produto=\""+$codigo_produto+"\" data-descricao=\""+$descricao+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\" onclick=\"showModalExcluir($(this))\"></a>";
        return $html;
    }

    function createBtnDuplicar($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\"  class=\"bt-duplicar\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Duplicar\" onclick=\"showModalDuplicar($(this))\"></a>";
        return $html;
    }

    function showModalNovo(){
        $.ajax({
            data: {
                _token: '{{ csrf_token() }}'
            },
            url: '{{ route('ficha_tecnica.cadastro.modal.novo') }}',
            method: 'POST',
            success: function(data){
                var title = 'Ficha técnica do produto';
                createModal('modal_ficha_tecnica_novo', title, data, "");
            },
        });
    }

    function showModalDuplicar($this){
        $.ajax({
            data: {
                _token: '{{ csrf_token() }}',
                id: $this.data('id'), 
            },
            url: '{{ route('ficha_tecnica.cadastro.modal.duplicar') }}',
            method: 'POST',
            success: function(data){
                var title = 'Duplicar Ficha técnica do produto';
                createModal('modal_ficha_tecnica_duplicar', title, data, "");
            },
        });
    }

    function showModalEdicao($this){

        $.ajax({
            data: {
                id: $this.data('id'),
                _token: '{{ csrf_token() }}',
                campo: 'linha',
            },
            url: '{{ route('ficha_tecnica.cadastro.modal.editar') }}',
            method: 'POST',
            success: function(data){
                var title = 'Ficha técnica do produto: ' + $this.data('codigo_produto') + ' - ' + $this.data('descricao');
                createModal('modal_ficha_tecnica_editar', title, data, "modal-lg");

                $(document).find('#modal_ficha_tecnica_editar').on('shown.bs.modal', function(){
                    table_filters_composicao.columns.adjust().draw();
                    table_filters_servicos.columns.adjust().draw();
                });
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function showModalExcluir($this){
        $.ajax({
            data: {
                id: $this.data('id'),
                _token: '{{ csrf_token() }}',
            },
            url: '{{ route('ficha_tecnica.cadastro.modal.excluir') }}',
            method: 'POST',
            success: function(data){
                var title = 'Ficha técnica do produto: ' + $this.data('codigo_produto') + ' - ' + $this.data('descricao');
                createModal('modal_ficha_tecnica_excluir', title, data, "");
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
@endsection
