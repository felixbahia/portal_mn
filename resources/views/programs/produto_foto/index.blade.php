@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter_produto_foto" id="form_filter_produto_foto" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">

        <div class="col-lg-2">
            {{ Form::text('grupo', '', ["id" => 'grupo', 'class' => 'form-control','placeholder' => 'Grupo']) }}
        </div>

        <div class="col-lg-2">
            {{ Form::text('codigo_produto', '', ["id" => 'codigo_produto', 'class' => 'form-control','placeholder' => 'Código do Produto']) }}
        </div>

        <div class="col-lg-2">
            {{ Form::text('descricao', '', ["id" => 'descricao', 'class' => 'form-control','placeholder' => 'Descrição']) }}
        </div>

        <div class="col-lg-2">
            {{ Form::text('marca', '', ["id" => 'marca', 'class' => 'form-control','placeholder' => 'Marca']) }}
        </div>

        <div class="col-lg-2">
            {{ Form::text('linha', '', ["id" => 'linha', 'class' => 'form-control','placeholder' => 'Linha']) }}
        </div>

    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <input type="button" name="btn-novo" id="btn-novo" class="btn btn-success float-right" value="Nova foto" />
    </div>
</form>	
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-produto-foto">
        <thead>
        	<tr>
                <th class="td_foto">Foto</th>
                <th>Grupo</th>
                <th>Código</th>
                <th>Descrição</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')
	$(document).ready( function(){
        $(document).find('#form_filter_produto_foto').on('submit', function(){
            filterAjax();
        });

        $(document).find('#btn-novo').on('click', function(){
            modalNovo();
        });

	});

    table_filters = $('#table-filters-produto-foto').DataTable({
        "searching": false,
        "paging": false,
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
                "targets": 'td_foto',
                width: '150px',
                height: '150px'
            }
        ],
    });

    function filterAjax(){

        $busca = $(document).find('#form_filter_produto_foto').serialize();
        table_filters.clear().draw();

        $.ajax({
            url: '{{ route('produto_foto.busca') }}',
            type: 'POST',
            data: $busca
        }).done(function(data){
            response = data.response.data;

            if(response.length > 0){
                var fields_filter = [];
                for(var field in response){
                    var temp_field = [
                        response[field].foto,
                        response[field].grupo,
                        response[field].codigo_produto,
                        response[field].descricao,
                        createBtnEdit(response[field].hash),
                        createBtnDeletar(response[field].hash)
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters.rows.add(fields_filter).order([ 1, 'asc' ] ).draw().nodes();
            }

            $(document).find(".foto-produto").fancybox(
                {
                    onComplete: function(){
     
                        $('#fancybox-content')
                            .on('mouseover', function(){
                                $(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
                            })
                            .on('mouseout', function(){
                                $(this).children('#fancybox-img').css({'transform': 'scale(1)'});
                            })
                            .on('mousemove', function(e){
                                $(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
                            });
                    }
                }
            );

        });
    }

    function createBtnEdit($hash){
        $html = "<a href=\"#\" class=\"bt-edit\" data-toggle='tooltip' data-html='true' title='Editar' onclick=\"modalEditar('"+$hash+"')\"></a>";

        return $html;
    }

    function createBtnDeletar($hash){
        var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle='tooltip' data-html='true' title='Excluir' onclick=\"modalExcluir('"+$hash+"')\"></a>";

        return $html;
    }

    function modalNovo(){
        $.ajax({
            url: '{{ route('produto_foto.modal.adicionar') }}',
            data: {
                _token: '{{ csrf_token() }}'
            },
            type: 'POST'
        }).done(function (data){
            $id = 'nova-foto-modal';
            $title = 'Nova foto';
            $body = data;
            $class = '';

            createModal($id, $title, $body, $class);
        });
    }

    function modalEditar($hash){
        $.ajax({
            url: '{{ route('produto_foto.modal.editar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            type: 'POST'
        }).done(function (data){
            $id = 'editar-foto-modal';
            $title = 'Nova foto';
            $body = data;
            $class = '';

            createModal($id, $title, $body, $class);
        });
    }

    function modalExcluir($hash){
        $.ajax({
            url: '{{ route('produto_foto.modal.excluir') }}',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            type: 'POST'
        }).done(function (data){
            $id = 'excluir-foto-modal';
            $title = 'Nova foto';
            $body = data;
            $class = '';

            createModal($id, $title, $body, $class);
        });
    }

    
@endsection
</script>
