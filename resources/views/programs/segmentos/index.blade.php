@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-sm-2 col-xl-3">
            {{ Form::text('descricao', '', ['id' => 'descricao', 'class' => 'form-control', 'placeholder' => 'Descrição']) }}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Novo segmento</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-segmentos">
        <thead>
            <tr>
                <th>Descrição</th>
                <th class="posicao">Posição</th>
                <th class="imagem"></th>
                <th class="editar"></th>
                <th class="deletar"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready(function(){
        $(document).find('#btn-filterform').on('click', function(){
            buscarSegmentos();
        });

        $(document).find('#btn-create').on('click', function(){
            modalNovo();
        });

        $(document).find('#btn-clearform').on('click', function(){
            table_filters_segmentos.clear().draw();
        });

        table_filters_segmentos = $('#table-filters-segmentos')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
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
            "columnDefs":[
                {
                    "targets": "imagem",
                    "orderable": false
                },
                {
                    "targets": "editar",
                    "orderable": false
                },
                {
                    "targets": "deletar",
                    "orderable": false
                },
                {
                    "targets": "posicao",
                    "width": "100px"
                }
            ]
        });

    })

    function buscarSegmentos(){
        var form = $(document).find('#form_filter');

        table_filters_segmentos.clear().draw();

        $.ajax({
            url: '{{ route('segmentos.filter') }}',
            data: form.serialize(),
            method: 'POST',
            success: function(response){

                var data = response.response;

                var fields_filter = [];

                    for(var field in data){
                        var temp_field = [
                            data[field].descricao,
                            data[field].posicao,
                            checarImagem(data[field]),
                            createBtnEdit(data[field].id),
                            createBtnDelete(data[field].id),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_segmentos.rows.add(fields_filter).draw();
            }
        });    
    }

    function createBtnEdit($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar segmento\" onclick=\"modalEditar($(this))\"></a>";
        return $html;
    }

    function createBtnDelete($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Excluir segmento\" onclick=\"modalExcluir($(this))\"></a>";
        return $html;
    }

    function checarImagem($this){
        if($this.imagem.length > 0){
            var $html = "<div><div data-toggle='tooltip' data-html='true' data-original-title='Tem imagem'><i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>";
        }else{
            var $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não tem imagem'><i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>";
        }
        return $html;
    }

    function modalNovo(){
        $.ajax({
            url: '{{ route('segmentos.modal.novo') }}',
            data: {
                '_token': '{{ csrf_token() }}'
            },
            method: 'POST',
            success: function(body){
                var id = 'modal-novo-segmento';
                var title = 'Novo segmento';
                var classe = '';

                createModal(id, title, body, classe);
            }
        })
    }

    function modalEditar($this){
        $.ajax({
            url: '{{ route('segmentos.modal.editar') }}',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $this.data('id')
            },
            method: 'POST',
            success: function(body){
                var id = 'modal-editar-segmento';
                var title = 'Editar segmento';
                var classe = '';

                createModal(id, title, body, classe);
            }
        })
    }

    function modalExcluir($this){
        $.ajax({
            url: '{{ route('segmentos.modal.deletar') }}',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $this.data('id')
            },
            method: 'POST',
            success: function(body){
                var id = 'modal-deletar-segmento';
                var title = 'Deletar segmento';
                var classe = '';

                createModal(id, title, body, classe);
            }
        });
    }

@endsection