@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-3">
            {!! Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) !!}
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
    <table class="table table-striped" id="table-filters-books">
        <thead>
            <tr>
                <th class="down-up"></th> 
                <th>Grupo</th>
                <th class="td_acao">Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

@endsection
@section('script-footer')
var modificador = 0;
$(document).ready( function () {

    $(document).find('#form_filter').find("#grupo").autocomplete(optionsAutoCompleteGrupo());
    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
    table_filters_books.on('draw', function () {
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
        $(document).find('.details-control-plus').off('click');
        $(document).find('.details-control-plus').on('click', function () {
            dados = carregarRowChild($(this));
            var tr = $(this).closest('tr');
            var td = $(this).closest('div.teste');
            var row = table_filters_books.row( tr );


            if ( row.child.isShown() ) {
                td.addClass('details-control-plus');
                td.removeClass('details-control-minus');
                row.child.hide();
                tr.removeClass('shown');
            }
            else {
                td.addClass('details-control-minus');
                td.removeClass('details-control-plus');
                row.child( format(dados)).show();
                tr.addClass('shown');
            }
        } );
    } );
});

var table_filters_books = $(document).find('#table-filters-books').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": 15,
        "processing": true,
        "orderMulti": false,
        "scrollCollapse": false,
        "autoWidth": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum produto inserido",
            "infoPostFix":    "",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Nenhum  produto inserido",
            "paginate": {
                "first":      "<<",
                "last":       ">>",
                "next":       ">",
                "previous":   "<"
            }
        },
        "columnDefs": [
            {
                'targets': 'down-up',
                'class': 'down-up',
                'width': '1px',
                'orderable': false
            },
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                'orderable': false
            }
        ],
        "order": [[ 1, 'asc' ]]
    }
);


function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    $.ajax({
        url: '{{ route('book_virtual_new.cadastro.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    '<div class="teste details-control-plus" data-route="{{ route('book_virtual_new.cadastro.filter_child') }}" data-grupo="'+data.response[fields].grupo+'"><a href="#"></a></div>',
                    data.response[fields].grupo,
                    createBtnEdit("{{ route('book_virtual_new.cadastro.modal.editar') }}", data.response[fields]),
                ];
                produtos.push(temp_array)
            }
            table_filters_books.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){
    table_filters_books.clear().draw();
}

function createBtnEdit($url, $dados){
    if($dados.exibe_botao === false){
        return "";
    }
    var $html = "<a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados.id+"\" data-modal=\"modal-lg\" data-title_modal=\"Editar Grupo  "+$dados.grupo+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar {{ CustomView::programaName() }}\"></a>";
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
            createModal('modal_book_virtual_cadastro_edit_delete', title, body, 'modal-lg');
            var modal = $("#modal_book_virtual_cadastro_edit_delete");
        }
    });
}

function format ( d ) {
    // `d` is the original data object for the row
    
    table = '<table class="table table-striped">'+
            '<thead>'+
                '<tr>'+
                    '<th>Codigo</th>'+
                    '<th>Produto</th>'+
                '</tr>'+
        '<thead>';
    for (var fields in d){
        table = table + '<tr>'+
                            '<td>'+d[fields].codigo+'</td>'+
                            '<td>'+d[fields].descricao+'</td>'+
                        '</tr>';
    }
    table = table + '</table>';
    return table;
}

function carregarRowChild($this){
    var url = $($this).data("route");
    var $grupo = $($this).data("grupo");
    var retorno = '';
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", grupo: $grupo},
        async: false,
        success: function(data){
            retorno = data.response;
        }
    });
    return retorno;
}

function optionsAutoCompleteGrupo(){
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.grupo.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3,
        open: function( event, ui ){
            $('.ui-autocomplete').css("z-index", (parseInt($(document).find('#table table-striped').css('z-index')) + 1));
        },
        select: function( event, ui ) {
            setTimeout(function(){
            }, 100);
        }
    };
}


@endsection
