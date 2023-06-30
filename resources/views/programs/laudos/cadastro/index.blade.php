@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            <input type="text" name="grupo" id="grupos" value="" placeholder="GRUPO" maxlength="60" require/>
        </div>
        <div class="form-group col-lg-2 col-xl-2">
            {{ Form::select("status", $status, "todos", ["id" => "status", "class" => "form-control", "placeholder" => "Status"]) }}
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
    <table class="table table-striped" id="table-filters">
        <thead>
            <tr>
                <th class="down-up"></th> 
                <th>Laudos</th>
                <th> Status</th>
                <th style="width: 50px" >Ficha</th>
                <th>Editar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

@endsection
@section('script-footer')



var modificador = 0;
$(document).ready(function(){

    $(document).find("#grupos").autocomplete(optionsAutoCompleteGrupo());

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterLaudo();
    });

    $(document).find("#btn-clearform").on("click", function(){
        filterClear();
    });
    table_filters.on('draw', function () {
        $(document).find(".bt-edit").off("click");
        $(document).find(".bt-edit").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });

        $(document).find('.details-control-plus').off('click');
        $(document).find('.details-control-plus').on('click', function () {
            dados = carregarRowChild($(this));
            var tr = $(this).closest('tr');
            var td = $(this).closest('div.teste');
            var row = table_filters.row( tr );


            if ( row.child.isShown() ) {
                td.addClass('details-control-plus');
                td.removeClass('details-control-minus');
                row.child.hide();
                tr.removeClass('shown');
            }
            else {
                td.addClass('details-control-minus');
                td.removeClass('details-control-plus');
                row.child( cricaoDeTabelaProdutos(dados)).show();
                tr.addClass('shown');
            }
        } );
    } );
    
});

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


function filterLaudo(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('laudo.filter')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            for (var fields in data.response){
                temp_array = [
                    '<div class="teste details-control-plus" data-route="{{ route('laudo.produtos.grupo') }}" data-id="'+data.response[fields].grupo+'"><a href="#"></a></div>',
                    data.response[fields].grupo,
                    data.response[fields].status,
                    createdBtFichaComercial(  data.response[fields].id,data.response[fields].grupo),
                    createBtnEdit("{{ route('laudo.modal.editar') }}", data.response[fields].id),
                ];
                produtos.push(temp_array)
            }
            table_filters.rows.add(produtos).draw();            

        }
    });
}

function filterClear(){

    table_filters.clear().draw();
}

function createBtnEdit($url, $dados){
    if($dados.exibe_botao === false){
        return "";
    }
    var $html = "<div> <div><a href=\"#\" data-route=\""+$url+"\" data-id=\""+$dados+"\" data-modal=\"modal-lg\" data-title_modal=\"Editar {{ CustomView::programaName() }}\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar {{ CustomView::programaName() }}\"></a></div> </div>";
    
    return $html;
}

function createdBtFichaComercial($id, $descricao){
    html = '<div> <div><a href="#" class="btn-pedido" title="Ficha Técnica Comercial" data-modal=\"modal-lg\" data-title="Ficha Técnica" data-id="'+$id+'" onclick="modalFichaComercialDetalhes($(this))" data-toggle=\"tooltip\" data-placement=\"top\" "></a></div> </div>';

    return html; 
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
            createModal('modal_laudo_cadastro_edit_delete', title, body,modal_class);
            var modal = $("#modal_laudo_cadastro_edit_delete");
        }
    });
}

function cricaoDeTabelaProdutos ( d ) {
    
    table = '<table class="table table-striped">'+
            '<thead>'+
                '<tr>'+
                    '<th>Codigo</th>'+
                    '<th>Produto</th>'+
                '</tr>'+
        '<thead>';
    for (var fields in d){
        table = table + '<tr>'+
                            '<td>'+d[fields].codigo_produto+'</td>'+
                            '<td>'+d[fields].descricao+'</td>'+
                        '</tr>';
    }
    table = table + '</table>';
    return table;
}

function carregarRowChild($this){
    var url = $($this).data("route");
    var $id = $($this).data("id");
    var retorno = '';
    $.ajax({
        url: url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", grupo: $id},
        async: false,
        success: function(data){
            retorno = data.response;
        }
    });
    return retorno;
}

function modalFichaComercialDetalhes($this){
    var $id = $this.data("id");//Grupo do produto
    var $title = $this.data("title"); //Título deve ser Ficha Técnica Comercial - Código do Produto - Descrição do Produto     
    
    esconderPopoverTooltip();
  
    $.ajax({
        url: '{{ route('ficha_tecnica_comercial.modal.grupo') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id: $id
        },
        success: function (data){
            createModal('detalhes_ficha_comercial', $title, data, 'modal-md');
        }
    });
}

@endsection
