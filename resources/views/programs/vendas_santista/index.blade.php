@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, $filtro['estabelecimento'], ['class'=>'form-control', 'placeholder' => 'Estabelecimento']) }}
        </div>
        <div class="col-lg-3">
            <div class="input-group">
                {{ Form::text('cliente', $filtro['cliente'], ['id' => 'cliente', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-sm-1">
            <input type="text" class="data" name="data_inicio" id="data_inicio" value='{{ $filtro['data_inicio'] }}' placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
        </div>
        <div class="col-sm-1">
            <input type="text" class="data" name="data_fim" id="data_fim" value='{{ $filtro['data_fim'] }}' placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
        </div>
        <div class="col-lg-2">
            {{ Form::select('representante', $representantes, $filtro['representante'], ['class'=>'form-control', 'placeholder' => 'Representante']) }}
        </div>
        <div class="col-lg-2">
            {{ Form::select('status', ['true' => 'Confirmado', 'false' => 'Não confirmado'], $filtro['status'], ['class'=>'form-control', 'placeholder' => 'Status']) }}
        </div>
    </div>
    <div class="content-buttons row">
        <div class="col-xs-12 col-sm-6">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </div>
</form>

@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped table-filters-vendas-santista" id="table-filters-vendas-santista" style="width:100%">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th>Representante</th>
                <th class='number_format'>Série</th>
                <th class='number_format'>Nota</th>
                <th class='date_format'>Emissão</th>
                <th>Produto</th>
                <th class='number_format'>QTD</th>
                <th class='number_format'>Valor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

$(document).ready(function(){
    $(document).find("#cliente").autocomplete(optionsAutoCompleteClienteFiltro());

    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });

    $(document).find('#form_filter').on('submit', function(){
        filterAjax($(document).find('#form_filter').serialize());
    });

    table_filters_vendas_santista.on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
    });

    @if(!empty($filtro['estabelecimento']) ||
    !empty($filtro['data_inicio']) ||
    !empty($filtro['data_fim']) ||
    !empty($filtro['cliente']) ||
    !empty($filtro['representante']))
    filterAjax($(document).find('#form_filter').serialize());
    @endif
});

table_filters_vendas_santista = $('#table-filters-vendas-santista').DataTable({
    "searching": false,
    "lengthChange": false,
    "info": false,
    "pageLength": 15,
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
        {
            'targets': [0, 3, 4, 5, 7, 8],
            'width': '5%'
        },
        {
            'targets': [1, 2, 6],
            'width': '20%'
        },
        {
            'targets': 'number_format',
            "className": 'number_format',
        },
        {
            "targets": 'date_format',
            "className": 'date_format',
        },
    ]
});

function optionsAutoCompleteClienteFiltro(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('clientes.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum cliente encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente").val(ui.item.label);
            $.ajax({
                url: '{{ route('cliente.salvaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    codcad: ui.item.value
                }
            });
            return false;
        }
    };
}
function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: 'POST',
        data: {
            _token: '{{csrf_token()}}'
        },
        success: function(body){
            $(document).find('#cliente_searsh_show').remove();

            var title = 'Consulta de clientes';

            createModal("cliente_searsh_show", title, body, 'modal-lg');
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    modal.find('tbody').find("tr").off("click");
                    modal.find('tbody').find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
}

function filterAjax(data_form){
    var $return;
    var form = $("#form_filter");
    table_filters_vendas_santista.clear().draw();

    form.find('.error-message').remove();
    form.find('.error-input').removeClass('error-input');
    
    $.ajax({
        url: "{{ route('vendas_santista.filter') }}",
        dataType: 'json',
        data: data_form,
        method: 'POST',
        success: function(resposta){

            console.log(resposta);

            var data = resposta.response.dados;

            if(data.length > 0){
                var fields_filter = [];
                for(var field in data){
                    var temp_field = [
                        data[field].estabelecimento,
                        data[field].cliente,
                        data[field].representante,
                        data[field].serie,
                        createLinkNf(data[field]),
                        data[field].emissao,
                        data[field].produto,
                        data[field].quantidade,
                        data[field].valor,
                        data[field].status
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters_vendas_santista.rows.add(fields_filter).draw().nodes();
            }
        },
        error: function(data){
            var errors = data.responseJSON.errors;

            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

function showErrorsInputs(form, input, message){
    var $input = $(form).find("input[name='"+input+"']");
    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

function createLinkNf($this){
    var html = "<a href='#' onclick=\"showNotasDetalhesNasajon('" + $this.nota_id + "')\">" + $this.nota + "</a>";
    return html;
}
function showNotasDetalhesNasajon($id_nota){
    $.ajax({
        url: '{{ route('notas_nasajon.modal.exibir')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id_nota: $id_nota,
            link_pedido: true,
            origem: 'NASAJON'
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })
        }
    });
}
@endsection