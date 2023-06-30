@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <div class="content-fields">
        {!! Form::hidden('respresentantes', $respresentantes, ['id' => 'respresentantes']) !!}
        <div class="row">
            <div class="col-lg-6">
                <div class="input-group">
                    {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-3">
                <input type="text" class="data" name="data_inicio" id="data_inicio" placeholder="Data Início DD/MM/AAAA" value="" maxlength="20">
            </div>
            <div class="col-lg-3">
                <input type="text" class="data" name="data_fim" id="data_fim" placeholder="Data Fim DD/MM/AAAA" value="" maxlength="20">
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'placeholder' => 'Grupo', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
            <div class="col-sm-2">
                {{ Form::text('codigo', '', ['id' => 'codigo', 'placeholder' => 'Código do produto', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
            <div class="col-sm-2">
                {{ Form::text('descricao', '', ['id' => 'descricao', 'placeholder' => 'Nome do Produto', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
            <div class="col-sm-2">
                {{ Form::text('marca', '', ['id' => 'marca', 'placeholder' => 'Marca', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
            <div class="col-sm-2">
                {{ Form::text('linha', '', ['id' => 'linha', 'placeholder' => 'Linha', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
            <div class="col-sm-2">
                {{ Form::text('subgrupo', '', ['id' => 'subgrupo', 'placeholder' => 'Subgrupo', 'class' => 'form-control', 'maxlength' => '250']) }}
            </div>
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
        <table class="table table-striped" id="table-filters-vendas">
            <thead>
                <tr>
                    <th>Grupo</th>
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Valor</th>
                    <th>Cliente</th>
                    @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16, 11]))
                    <th>Vendedor</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number" id="total_quantidade"></td>
                    <td class="tb_number" id="total_valor"></td>
                    <td id="total_cliente"></td>
                    @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16, 11]))
                        <td id="total_vendedor"></td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>
@endsection

@section('script-footer')
$(document).ready( function () {
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        endDate: new Date(),
        zIndex: 100,
        autoHide: true
    });
    $('#data_inicio').on('pick.datepicker', function (e) {
        if($('#data_fim').datepicker('getDate') < e.date){
            $('#data_fim').val('');
        }
        $('#data_fim').datepicker('setStartDate', e.date);
        $('#data_fim').datepicker('update');
    });
    carregarData();
    form = $(document).find('#form_filter');

    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());

    form.find("#bt-search-cliente-busca").on("click", function(){
        showModalCliente($(this).data("route"), "Lista de Clientes");
    });

    $("#descricao").autocomplete(optionsAutoComplete("nome"));
    $("#marca").autocomplete(optionsAutoComplete("marca"));
    $("#linha").autocomplete(optionsAutoComplete("linha"));
    $("#grupo").autocomplete(optionsAutoComplete("grupo"));

    $("#btn-filterform").on("click", function(){
        filterAjax($("#form_filter"));
    });

    table_filters = $('#table-filters-vendas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "pageLength": 20,
        "autoWidth": true,
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
                            if(column >= 1){
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
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
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
                "class": "tb_number", 
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
    });
    $('#btn-clearform').on("click", function(){
        filterClear();
    });

});

function optionsAutoCompleteCliente(){
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
            $(document).find("#cliente_nome").val(ui.item.label);
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

function showModalCliente(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("cliente_searsh_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#cliente_searsh_show").find("tbody").find("tr").off("click");
                    $(document).find("#cliente_searsh_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#cliente_searsh_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}

function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente_searsh_show").modal("hide");
    $("#form_filter").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
}

function optionsAutoComplete($name){
    return {
        source: function (request, response) {
            request.name = $name;
            request._token = "{{ csrf_token() }}";
            $.post("{{ route('produto.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 3
    };
}

function filterAjax(form){
    filterClear();
    limparMesagemErro();
    xhr = $.ajax({
        url: "{{ route('consulta_cliente_grupo.filtro') }}",
        dataType: 'json',
        data: form.serialize(),
        method: 'POST',
        success: function(callback){
            var data = callback.response;
            produtos = [];
            console.log(data);
            for (var fields in data.itens){
                temp_array = [
                    btnModalProdutoCor(data.itens[fields]),
                    data.itens[fields].quantidade,
                    data.itens[fields].preco_total,
                    btnModalCliente(data.itens[fields]),
                    @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16, 11]))
                        btnModalVendedor(data.itens[fields]),
                    @endif
                ];
                produtos.push(temp_array)
            }

            table_filters.rows.add(produtos).draw();

            $(document).find('#total_quantidade').html(data.total.quantidade);
            $(document).find('#total_valor').html(data.total.valor);
            $(document).find('#total_cliente').html(btnModalCliente(data.total));
            @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16, 11]))
                $(document).find('#total_vendedor').html(btnModalVendedor(data.total));
            @endif
        },
        error:function(callback){
            var dados = callback.responseJSON;
            mensagemErro(dados);
        }
    });
}

function filterClear(){
    table_filters.clear().draw();
}

function limparMesagemErro(){      
    var form = $("#form_filter");
    form.find('.error-message').remove();
    form.find('input, select, span').removeClass('error-input');
}

function mensagemErro(json_error){
    var form = $("#form_filter");
    if(Object.keys(json_error).length > 0){
        for(var field in json_error.errors){
            console.log(json_error.errors[field]);
            showErrorsInputs(form, field, json_error.errors[field]);
        }
    }
}

function showErrorsInputs(form, input, message){
    if(input.localeCompare('cliente_nome') == 0){
        var $input = $(form).find("#bt-search-cliente-busca");
        $(form).find("input[name='cliente_nome']").addClass('error-input');
    }else{
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
    }

    $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
    $input.addClass('error-input');
}

function createLinkPedido($dados, $texto){
    var $html = "";
    $html = "<a href=\"#\" id=\"bt_link\" onclick=\"showItens('"+$dados.pedido_id+"', '"+$dados.pedido_origem+"')\">"+$texto+"</a>";
    return $html;
}

function createLinkNota($dados, $texto){
    var $html = "";
    $html = "<a href=\"#\" id=\"bt_link\" onclick=\"showNotasDetalhes('"
    +$dados.cod_estabelecimento+"', '"
    +$dados.id_nota+"')\">"+$texto+"</a>";
    return $html;
}

function showItens($pedido, $origem){
    var title = "Dados do pedido: "+$pedido;
    xhr = $.ajax({
        url: "{{ route('pedidos_orcamentos.show') }}",
        data: {_token: "{{ csrf_token() }}", origem: $origem, pedido: $pedido},
        method: 'POST',
        success: function(body){
            createModal("itens_pedido", title, body, 'modal-lg');
            var modal = $(document).find("#itens_pedido");
            modal.css("z-index", 11);
            $(".modal-backdrop").css("z-index", 9);
        }
    });
}

function showNotasDetalhes(estabelecimento, $id_nota){
    $.ajax({
		url: '{{ route('notas_nasajon.modal.exibir')}}',
		type: 'POST',
		data: {
			_token: '{{csrf_token()}}',
			id_nota: $id_nota,
			link_pedido: true,
			origem: 'NASAJON',
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

function carregarData(){
    var d = new Date();
    var anoC = d.getFullYear();
    var mesC = d.getMonth();

    var d1 = new Date (anoC, mesC, 1);
    var d2 = new Date (anoC, mesC+1, 0);
    $('#data_inicio').val(dataAtualFormatada(d1));
    $('#data_fim').val(dataAtualFormatada(d2));
}

function dataAtualFormatada(data){
        dia  = data.getDate().toString().padStart(2, '0'),
        mes  = (data.getMonth()+1).toString().padStart(2, '0'), //+1 pois no getMonth Janeiro começa com zero.
        ano  = data.getFullYear();
    return dia+"/"+mes+"/"+ano;
}

function btnModalProdutoCor($dados){
   var html = '';

   var title = "Detalhes Grupo: " + $dados.grupo + " (" + $dados.data_inicial + " - " + $dados.data_final + ")";

   html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-grupo=\""+$dados.grupo+"\" data-data_inicial=\""+$dados.data_inicial+"\" data-data_final=\""+$dados.data_final+"\" data-filtro=\""+$dados.filtro+"\"  title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalDetalhes($(this))\">"+$dados.grupo+"</a>";

   return html; 
}

function abriModalDetalhes($this){
    var grupo = $($this).data("grupo");
    var data_inicial = $($this).data("data_inicial");
    var data_final = $($this).data("data_final");
    var filtro = $($this).data('filtro');
    var title = $($this).data("title");
    $.ajax({
        url: '{{ route('consulta_cliente_grupo.modal.detalhes') }}',
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            grupo: grupo,
            data_inicial: data_inicial,
            data_final: data_final,
            filtro: filtro,
        },
        success: function(body){
            createModal('modal_detalhes', title, body, "modal-lg");
            var modal = $("#modal_detalhes");
        }
    });
}

function btnModalCliente($dados){
    var html = '';

    var title = "Detalhes Grupo: " + $dados.grupo + " (" + $dados.data_inicial + " - " + $dados.data_final + ")";

    html = "<a href=\"#\" class=\"bt-view\"  data-toggle='tooltip' data-html='true' data-grupo=\""+$dados.grupo+"\" data-data_inicial=\""+$dados.data_inicial+"\" data-data_final=\""+$dados.data_final+"\" data-filtro=\""+$dados.filtro+"\"  title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalDetalhesCliente($(this))\"></a>";

    return html; 
}

function abriModalDetalhesCliente($this){
    var grupo = $($this).data("grupo");
    var data_inicial = $($this).data("data_inicial");
    var data_final = $($this).data("data_final");
    var filtro = $($this).data('filtro');
    var title = $($this).data("title");
    $.ajax({
        url: '{{ route('consulta_cliente_grupo.modal.detalhes_clientes') }}',
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            grupo: grupo,
            data_inicial: data_inicial,
            data_final: data_final,
            filtro: filtro,
        },
        success: function(body){
            createModal('modal_detalhes_clientes', title, body, "modal-lg");
            var modal = $("#modal_detalhes_clientes");
        }
    });
}

function btnModalVendedor($dados){
    var html = '';

    var title = "Detalhes Grupo: " + $dados.grupo + " (" + $dados.data_inicial + " - " + $dados.data_final + ")";

    html = "<a href=\"#\" class=\"bt-view\"  data-toggle='tooltip' data-html='true' data-grupo=\""+$dados.grupo+"\" data-data_inicial=\""+$dados.data_inicial+"\" data-data_final=\""+$dados.data_final+"\" data-filtro=\""+$dados.filtro+"\"  title='Visualizar' data-title=\""+title+"\" onclick=\"abriModalDetalhesVendedor($(this))\"></a>";

    return html; 
}

function abriModalDetalhesVendedor($this){
    var grupo = $($this).data("grupo");
    var data_inicial = $($this).data("data_inicial");
    var data_final = $($this).data("data_final");
    var filtro = $($this).data('filtro');
    var title = $($this).data("title");
    $.ajax({
        url: '{{ route('consulta_cliente_grupo.modal.detalhes_vendedor') }}',
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            grupo: grupo,
            data_inicial: data_inicial,
            data_final: data_final,
            filtro: filtro,
        },
        success: function(body){
            createModal('modal_detalhes_vendedor', title, body, "modal-lg");
            var modal = $("#modal_detalhes_vendedor");
        }
    });
}
@endsection 
