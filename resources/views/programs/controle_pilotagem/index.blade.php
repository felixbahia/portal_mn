@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            @if ($check_gerentes === true || $check_supervisores === true || $check_vendedor_representante === true)
                @if($check_gerentes === true)
                <div class="form-group col-lg-2">
                    {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
                </div>
                @endif
                @if($check_vendedor_representante === true)
                <div class="form-group col-lg-2">
                    {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
                </div>
                @endif
            @endif
            <div class="col-lg-2">
                <div class="input-group">
                    {{ Form::text('cliente_nome', '', ['id' => 'cliente_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio','',['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Data Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2">
                {{ Form::text('grupo', '', ['id' => 'grupo', 'class' => 'form-control', 'placeholder' => 'Grupo']) }}
            </div>
            <div class="form-group col-lg-2">
                {{ Form::text('produto', '', ['id' => 'produto', 'class' => 'form-control', 'placeholder' => 'Código do Produto']) }}
            </div>  
            <div class="form-group col-lg-2">
                {{ Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control', 'placeholder' => 'Nome do Produto']) }}
            </div>
            <div class="form-group col-lg-2">
                {{ Form::text('marca', '', ['id' => 'marca', 'class' => 'form-control', 'placeholder' => 'Marca']) }}
            </div>
            <div class="form-group col-lg-2">
                {{ Form::text('linha', '', ['id' => 'linha', 'class' => 'form-control', 'placeholder' => 'Linha']) }}
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
    <table class="table table-striped table-not-edit table-not-view" id="table-palitagem-index">
        <thead>
            <tr>
                <th>Estabelecimento<br></th>
                <th>Clientes</th>
                <th>Itens Enviados</th>
                <th>Itens Vendidos</th>
                <th>Efetividade%</th>
                <th>Cliente</th>
                <th>Produto</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
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
        zIndex: 2000,
        autoHide: true
    });
    $(document).find("#cliente_nome").val('');
    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());
    $(document).find("#bt-search-cliente-busca").on("click", function(){
        showModalCliente($(this).data("route"), "Lista de Clientes");
    });
    $("#nome").autocomplete(optionsAutoComplete("nome"));
    $("#marca").autocomplete(optionsAutoComplete("marca"));
    $("#linha").autocomplete(optionsAutoComplete("linha"));
    $("#grupo").autocomplete(optionsAutoComplete("grupo"));
    $('#btn-filterform').on('click', function(){
        filtro();
    });
    $(document).find(".busca_left").on('change', function(event){
        var campos = $(document).find("select:visible");
        var indice = campos.index(event.target) + 1;
        var seletor = $(campos[indice]);
        checkDadosUser(seletor, $(this).val());
    });
    table_filter_controle_pilotagem = $('#table-palitagem-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('c[r=G7] t', sheet).attr( 's', '0' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
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
                "targets": [1,2,3,4]
            },
        ]
    });
    table_filter_controle_pilotagem.on('draw', function () {
        $(document).find(".bt-modal-produto").off("click");
        $(document).find(".bt-modal-produto").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });

        $(document).find(".bt-open-view-cliente").off("click");
        $(document).find(".bt-open-view-cliente").on("click", function(event){
            event.stopPropagation();
            showCliente($(this));
        });

        $(document).find(".bt-open-view-produto-acumulado").off("click");
        $(document).find(".bt-open-view-produto-acumulado").on("click", function(event){
            event.stopPropagation();
            showProdutoAcumulado($(this));
        });
    });
});

function filtro(){

    table_filter_controle_pilotagem.clear().draw();
    $(table_filter_controle_pilotagem.column(0).footer()).html('');
    $(table_filter_controle_pilotagem.column(1).footer()).html('');
    $(table_filter_controle_pilotagem.column(2).footer()).html('');
    $(table_filter_controle_pilotagem.column(3).footer()).html('');
    $(table_filter_controle_pilotagem.column(4).footer()).html('');
    
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('controle_pilotagem.filter') }}',
        type: 'POST',
        data: $data,
        success: function(data){
            if(data.status == 'sucess'){
                var out = [];
                for (var fields in data.response.response){
                    out.push([
                        data.response.response[fields].estabelecimento,
                        data.response.response[fields].clientes,
                        createBtViewProduto(data.response.response[fields]),
                        data.response.response[fields].produtos_venda,
                        data.response.response[fields].efetividade,
                        createBtViewCliente(data.response.response[fields]),
                        createBtViewProdutoAcumulado(data.response.response[fields]),
                    ]);
                }

                $(table_filter_controle_pilotagem.column(0).footer()).html('Total');
                $(table_filter_controle_pilotagem.column(1).footer()).html(data.response.total.clientes);
                $(table_filter_controle_pilotagem.column(2).footer()).html(createBtViewProdutoTotal(data.response.total));
                $(table_filter_controle_pilotagem.column(3).footer()).html(data.response.total.produtos_venda);
                $(table_filter_controle_pilotagem.column(4).footer()).html(data.response.total.efetividade);
                $(table_filter_controle_pilotagem.column(5).footer()).html(createBtViewClienteTotal(data.response.total));
                $(table_filter_controle_pilotagem.column(6).footer()).html(createBtViewProdutoAcumuladoTotal(data.response.total));

                table_filter_controle_pilotagem.rows.add(out).draw();
            }else{
                message('Atenção','Tente novamente mais tarde','error');
            }
        },
        error: function(callback){
            if((callback.responseJSON)){
                var data = callback.responseJSON.error;
                $.each(data, function(index, el) {
                    $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                    $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                });
                $form.find('input.error').eq(0).focus();
            }
        }
    }).always(function() {
        hide_loader();
    });
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
            createModal("cliente_atrasos_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#cliente_atrasos_show").find("tbody").find("tr").off("click");
                    $(document).find("#cliente_atrasos_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#cliente_atrasos_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}

function showCliente($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro, total: total},
        method: 'POST',
        success: function(body){
            createModal("model_produtos_pilotagem_cliente", title, body, 'modal-lg');
        }
    });
}

function showProdutoAcumulado($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro, total: total},
        method: 'POST',
        success: function(body){
            createModal("model_produtos_pilotagem_acumulado", title, body, 'modal-lg');
        }
    });
}

function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente_atrasos_show").modal("hide");
    $("#form_filter").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function createBtViewProduto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_pilotagem.modal.produto') }}\" data-filtro=\""+$this.filtro+"\" data-total='false' data-title='Produtos '\""+$this.estabelecimento+"\"' class='bt-modal-produto'>"+$this.produtos_pilotagem+"</a>"
    return html;
}

function createBtViewProdutoTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('controle_pilotagem.modal.produto') }}\" data-filtro=\""+$this.filtro+"\" data-total='true' data-title='Produtos '\""+$this.estabelecimento+"\"' class='bt-modal-produto'>"+$this.produtos_pilotagem+"</a>"
    return html;
}

function createBtViewCliente($this){
    var html = "<a href=\"#\" class=\"bt-view bt-open-view-cliente\" data-title='ANALITÍCO POR CLIENTE' data-route=\"{{ route('controle_pilotagem.modal.cliente') }}\" data-total='false' data-filtro=\""+$this.filtro+"\" ></a>"
    return html;
}

function createBtViewClienteTotal($this){
    var html = "<a href=\"#\" class=\"bt-view bt-open-view-cliente\" data-title='ANALITÍCO POR CLIENTE' data-route=\"{{ route('controle_pilotagem.modal.cliente') }}\" data-total='true' data-filtro=\""+$this.filtro+"\" ></a>"
    return html;
}

function createBtViewProdutoAcumulado($this){
    var html = "<a href=\"#\" class=\"bt-view bt-open-view-produto-acumulado\" data-title='ANALITÍCO POR PRODUTO' data-route=\"{{ route('controle_pilotagem.modal.produto_acumulado') }}\" data-total='false' data-filtro=\""+$this.filtro+"\" ></a>"
    return html;
}

function createBtViewProdutoAcumuladoTotal($this){
    var html = "<a href=\"#\" class=\"bt-view bt-open-view-produto-acumulado\" data-title='ANALITÍCO POR PRODUTO' data-route=\"{{ route('controle_pilotagem.modal.produto_acumulado') }}\" data-total='true' data-filtro=\""+$this.filtro+"\" ></a>"
    return html;
}

function showModal($this){
    var url = $($this).data("route");
    var filtro = $($this).data("filtro");
    var title = $($this).data('title');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filtro: filtro, total: total},
        method: 'POST',
        success: function(body){
            createModal("model_produtos_pilotagem", title, body, 'modal-lg');
        }
    });
}

@if ($check_gerentes === true || $check_vendedor_representante === true)
    function checkDadosUser(campo_busca, valor){
        primeira_opcao = $(campo_busca).find("option:first").html();
        $(campo_busca).html("");
        var campos = "<option value=\"\">" + primeira_opcao + "</option>";
        $.ajax({
            url: "{{ route('usuario.dados_subordinados_outros') }}",
            dataType: 'json',
            data: {_token:'{{ csrf_token() }}', user: valor},
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    var response = callback.response;
                    for(var line in response){
                        campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                    }
                }
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        }).done(function(){
            $(campo_busca).html(campos).focus();
        });
    }
@endif

@endsection