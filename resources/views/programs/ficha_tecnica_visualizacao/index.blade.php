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
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
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
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready( function () {

        $(document).find("#marca").autocomplete(optionsAutoComplete("marca"));
        $(document).find("#linha").autocomplete(optionsAutoComplete("linha"));
        $(document).find("#grupo").autocomplete(optionsAutoComplete("grupo"));
        $(document).find("#subgrupo").autocomplete(optionsAutoComplete("subgrupo"));
        $(document).find("#descricao").autocomplete(optionsAutoComplete("nome"));

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });
    });

    function optionsAutoComplete($name){
        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
            },
            select: function( event, ui ) {
            }
        };
    }

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
            { 'targets': -1, 'orderable': false}
        ]
    });

    function buscaDados(form){

        var data_form = form.serialize();

        $.ajax({
            url: "{{ route('ficha_tecnica.visualizacao.filtro') }}",
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
                            createBtnView(data[field].id),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw();
                }
            }
        });
    }

    function createBtnView($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Visualizar\" onclick=\"showModalDetalhes($(this))\"></a>";
        return $html;
    }

    function showModalDetalhes($this){

        $.ajax({
            data: {
                id: $this.data('id'),
                _token: '{{ csrf_token() }}',
                exibicao_custo_fixo: false,
            },
            url: '{{ route('ficha_tecnica.visualizacao.modal') }}',
            method: 'POST',
            success: function(data){
                var title = 'Ficha técnica do produto: ';
                createModal('modal_ficha_tecnica_exibir', title, data, "modal-lg");

                $(document).find('#modal_ficha_tecnica_exibir').on('shown.bs.modal', function(){
                    table_filters_composicao.columns.adjust().draw();
                    table_filters_servicos.columns.adjust().draw();
                });
            },
            error: function(callback){
            }
        });
    }

@endsection
