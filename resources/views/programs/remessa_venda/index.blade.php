@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div class='form-row'>
        <div class="col-lg-2">
            {!! Form::select('estabelecimento_filtro', $estabelecimentos, '', ['id' => 'estabelecimento_filtro', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Estabelecimento']) !!}
        </div>
        <div class="form-group col-lg-6">
            <div class="input-group">
                {{ Form::text("cliente", '', ["id" => "cliente_filtro", "class" => "form-control input-label", "placeholder" => "Nome do Cliente"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            {{ Form::text("data_emissao_inicio", $primeiro_dia_do_mes, ["id" => "data_emissao_inicio", "class" => "form-control input-label data", "placeholder" => "Data de emissão de"]) }}
        </div>
        <div class="col-lg-2">
            {{ Form::text("data_emissao_fim", $ultimo_dia_do_mes, ["id" => "data_emissao_fim", "class" => "form-control input-label data", "placeholder" => "Data de emissão até"]) }}
        </div>
    </div>
    
    <div class="content-buttons mt-2">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-remessa_venda">
        <thead>
            <tr>
                <th colspan="6">Dados da NF do Armazém</th>
                <th colspan="6">Dados da NF da Textil</th>
            </tr>
            <tr>
                <th>Número</th>
                <th class="tb_number">CFOP</th>
                <th class="tb_date">Emissão</th>
                <th>Cliente</th>
                <th class="tb_number">ICMS</th>
                <th class="tb_number">Valor</th>
                <th>Estabelecimento</th>
                <th>Número</th>
                <th class="tb_number">CFOP</th>
                <th class="tb_date">Emissão</th>
                <th>Cliente</th>
                <th class="tb_number">Valor</th>                
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function () {
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 100,
        autoHide: true
    });

    $(document).find("#bt-search-cliente-busca").off("click");
    $(document).find("#bt-search-cliente-busca").on("click", function(event){
        event.stopPropagation();
        showModalClienteBusca($(this).data("route"));
        return false;
    });

    $(document).find("#cliente_filtro").autocomplete(optionsAutoCompleteClienteFiltro());

    $(document).find("#btn-filterform").on("click", function(){
        filterClear();
        filterAjax();
    });

    table_filters = $('#table-filters-remessa_venda').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column == 4 || column == 5 || column == 11){
                                if(data != ''){
                                    numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "tb_date" },
        ],
        "order": [[ 0, 'asc' ]]
    });
});

function filterAjax(){
    form = $(document).find("#form_filter");
    data_form = form.serialize();
    filterClear();
    $.ajax({
        url: '{{ route('remessa_venda.filtro')}}',
        data: data_form,
        method: 'POST',
        success: function(data){
            produtos = [];
            
            for (var fields in data.response){
                temp_array = [
                    createLinkNf(data.response[fields].nota_id, data.response[fields].numero_armazem),
                    data.response[fields].cfop_armazem,
                    data.response[fields].emissao_armazem,
                    ajusteTamanhoTable(data.response[fields].cliente_armazem),
                    data.response[fields].icms_armazem,
                    data.response[fields].valor_armazem,
                    data.response[fields].estabelecimento_textil,
                    createLinkNf(data.response[fields].nota_id, data.response[fields].numero_textil),
                    data.response[fields].cfop_textil,
                    data.response[fields].emissao_textil,
                    ajusteTamanhoTable(data.response[fields].cliente_textil),
                    data.response[fields].valor_textil,
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

function ajusteTamanhoTable($value){
    $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";

    return $html;
}

function optionsAutoCompleteClienteFiltro(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route("clientes.autocomplete") }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message("Atenção", "Nenhum cliente encontrado");
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#cliente_id_filtro").val(ui.item.value);
            $(document).find("#cliente_filtro").val(ui.item.label);
            return false;
        }
    };
}

function showModalClienteBusca(url){
    var title = "Busca de Clientes";
    $.ajax({
        url: url,
        method: "POST",
        data: {
            _token: "{{csrf_token()}}"
        },
        success: function(body){
            $(document).find("#cliente_searsh_show").remove();
            createModal("cliente_searsh_show", title, body, "modal-lg");
            var modal = $(document).find("#cliente_searsh_show");
            $(document).ready( function () {
                table_dialog.on("draw", function () {
                    modal.find("tbody").find("tr").off("click");
                    modal.find("tbody").find("tr").on("click", function(event){
                        returnDadosClienteBusca($(this), event);
                    });
                });
            });
        }
    });
}

function returnDadosClienteBusca($dados, event){
    if($dados.find("td").eq(0).hasClass("dataTables_empty")){
        return false;
    }
    $(document).find("#cliente_id_filtro").val($dados.find("td").eq(0).text());
    $(document).find("#cliente_filtro").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $(document).find("#cliente_searsh_show").modal("hide");
    $.ajax({
        url: "{{ route("cliente.salvaClientePadrao") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function createLinkNf($id_nota, $numnfe){
        var html = "";

        html = "<a href='#' onclick=\"showNotasDetalhesNasajon('" + $id_nota + "')\">" + $numnfe + "</a>";

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