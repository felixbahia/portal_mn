@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter_atrasos_equipe" id="form_filter_atrasos_equipe" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
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
            <div class="col-lg-4">
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
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>	
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped @if(Auth::user()->tipo_usuario_id == "16" || Auth::user()->tipo_usuario_id == "12") table-not-edit @endif" id="table-filters-atrasos-equipe">
        <thead>
            <tr>
				<th>Estabelecimento</th>
                <th class='tb_number'>Total</th>
                <th>Cliente</th>
                @if(Auth::user()->tipo_usuario_id != 16 && Auth::user()->tipo_usuario_id != 12)
                <th>Representante</th>
                @endif
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <th></th>
                <td></td>
                <td></td>
                @if(Auth::user()->tipo_usuario_id != "16" && Auth::user()->tipo_usuario_id != "12")
                <td></td>
                @endif
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function(){
    $("#btn-filterform").on("click", function(){
        filter();
    });
    $('.data').mask('00/00/0000');
    $('.data').datepicker({
        language: 'pt-BR',
        format: 'dd/mm/yyyy',
        zIndex: 2000,
        autoHide: true
    });
    $(document).find(".busca_left").on('change', function(event){
        var campos = $(document).find("select:visible");
        var indice = campos.index(event.target) + 1;
        var seletor = $(campos[indice]);
        checkDadosUser(seletor, $(this).val());
    });
    $(document).find("#cliente_nome").val('');
    $(document).find("#cliente_nome").autocomplete(optionsAutoCompleteCliente());
    $(document).find("#bt-search-cliente-busca").on("click", function(){
        showModalCliente($(this).data("route"), "Lista de Clientes");
    });

    table_filters_atrasos = $('#table-filters-atrasos-equipe').on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "paging": true,
        "lengthChange": false,
        "info": false,  
        "pageLength": 15,
        "orderMulti": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '{{ CustomView::programaName() }}',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();

                            if(column === 1 || column === 2 || column ===3){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                            
                        }
                    },
                    columns : [0,1,2,3]
                }
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
                "targets": "tb_number",
                "className": 'tb_number',
                "type": "html-numeric-comma"
            },
            {
                @if(Auth::user()->tipo_usuario_id != "16" && Auth::user()->tipo_usuario_id != "12")
                "targets": [0,2,3],
                @else
                "targets": [0,2],
                @endif
                "width": '20%'
            },
        ],
        "order": [ 0, 'asc' ]
    });

    table_filters_atrasos.on('draw', function () {
        $(document).find(".bt-clinete-atraso").off("click");
        $(document).find(".bt-clinete-atraso").on("click", function(event){
            event.stopPropagation();
            showModalClienteAtraso($(this));
        });
        $(document).find(".bt-representante-atraso").off("click");
        $(document).find(".bt-representante-atraso").on("click", function(event){
            event.stopPropagation();
            showModalRepresentanteAtraso($(this));
        });
        $(document).find(".bt-titulos-abertos").off("click");
        $(document).find(".bt-titulos-abertos").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-vencidos").off("click");
        $(document).find(".bt-titulos-vencidos").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-total").off("click");
        $(document).find(".bt-titulos-total").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-total-aberto").off("click");
        $(document).find(".bt-titulos-total-aberto").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-total-vencido").off("click");
        $(document).find(".bt-titulos-total-vencido").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-total-geral").off("click");
        $(document).find(".bt-titulos-total-geral").on("click", function(event){
            event.stopPropagation();
            showModalAberturas($(this));
        });
        $(document).find(".bt-titulos-total-clientes").off("click");
        $(document).find(".bt-titulos-total-clientes").on("click", function(event){
            event.stopPropagation();
            showModalClienteAtraso($(this));
        });
        $(document).find(".bt-titulos-total-representantes").off("click");
        $(document).find(".bt-titulos-total-representantes").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulosRepresentantesTotal($(this));
        });
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

function returnDados($dados){
    if($dados.find("td").eq(0).hasClass('dataTables_empty')){
        return false;
    }
    $(document).find("#cliente_atrasos_show").modal("hide");
    $("#form_filter_atrasos_equipe").find("#cliente_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
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

function createBtViewClienteAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.cliente') }}\" data-filter=\""+$this.filter+"\" data-abertura='aberto' data-cliete='true' data-abertura-geral='false' data-total='false' data-title='TITULOS FATURADOS - CLIENTE - Representante - "+$this.estabelecimento+"' class='bt-view bt-clinete-atraso'></a>"
    return html;
}

function createBtViewRepresentanteAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.representantes') }}\" data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - Representante - "+$this.estabelecimento+"' data-abertura='total' data-total='false' data-representante='true'  class='bt-view bt-representante-atraso'></a>"
    return html;
}

function createBtViewAbertoAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='aberto' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - "+$this.estabelecimento+"' class='bt-titulos-abertos'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewVencidoAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='vencido' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido+"</a>"
    return html;
}

function createBtViewTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='total' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - "+$this.estabelecimento+"' class='bt-titulos-total'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalAberto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='aberto' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - TODOS' class='bt-titulos-total-aberto'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewTotalVencido($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='vencido' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido+"</a>"
    return html;
}

function createBtViewTotalAberturas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.titulos_abertura') }}\" data-abertura='total' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - TODOS' class='bt-titulos-total-geral'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalClientes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.cliente') }}\" data-abertura='total' data-abertura-geral='true' data-cliente='false' data-busca='' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS POR CLIENTE - TOTAL - TODOS' class='bt-view bt-titulos-total-clientes'></a>"
    return html;
}

function createBtViewTotalRepresentantes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulo_excluido.modal.representantes') }}\" data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS POR Representante - TOTAL - TODOS' data-abertura='total' data-total='true' data-representante='false' data-cliente='null' class='bt-view bt-titulos-total-representantes'></a>"
    return html;
}

function filter(){
    var $data_form = $("#form_filter_atrasos_equipe").serialize();
    table_filters_atrasos.clear().draw();
    $(table_filters_atrasos.column(0).footer()).html('');
    $(table_filters_atrasos.column(1).footer()).html('');
    $(table_filters_atrasos.column(2).footer()).html('');
    @if(Auth::user()->tipo_usuario_id != "16" && Auth::user()->tipo_usuario_id != "12")
    $(table_filters_atrasos.column(3).footer()).html('');
    @endif
    $.ajax({
        url: "{{ route('titulo_excluido.filtro') }}",
        data: $data_form,
        method: 'POST',
        success: function(callback){
            if(callback.status == 'sucess'){
                var linhas = callback.response.response;
                if(linhas){
                    var temp_field = [];
                    for(var field in linhas){
                        temp_field.push([
                            linhas[field].estabelecimento,
                            createBtViewTotal(linhas[field]),
                            createBtViewClienteAtraso(linhas[field]),
                            @if(Auth::user()->tipo_usuario_id != "16" || Auth::user()->tipo_usuario_id != "12")
                            createBtViewRepresentanteAtraso(linhas[field])
                            @endif
                        ]);
                    }
                    $(table_filters_atrasos.column(0).footer()).html((callback.response.total.total) ? 'TOTAL' : '');
                    $(table_filters_atrasos.column(1).footer()).html((callback.response.total.total) ? createBtViewTotalAberturas(callback.response.total) : '');
                    $(table_filters_atrasos.column(2).footer()).html((callback.response.total.total) ? createBtViewTotalClientes(callback.response.total) : '');
                    @if(Auth::user()->tipo_usuario_id != "16" && Auth::user()->tipo_usuario_id != "12")
                    $(table_filters_atrasos.column(3).footer()).html((callback.response.total.total) ? createBtViewTotalRepresentantes(callback.response.total) : '');
                    @endif
                    
                    table_filters_atrasos.rows.add(temp_field).draw().nodes();
                }else{
                    table_filters_atrasos.clear().draw();
                }
                
            }
        },
        error: function(callback){
            if((callback.responseJSON)){
                if(callback.responseJSON.error){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                        $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    $form.find('input.error').eq(0).focus();
                }
            }
        }
    });
}

function showModalClienteAtraso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var abertura = $($this).data('abertura');
    var total = $($this).data('total');
    var $cliente = $($this).data("cliente");
    var $busca = $($this).data("busca");
    var $aberturageral = $($this).data("abertura-geral");

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, abertura : abertura, total : total, busca : $busca, aberturageral : $aberturageral},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_cliente_atraso", title, body, 'modal-lg');
        }
    });
}

function showModalRepresentanteAtraso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var $abertura = $($this).data("abertura");
    var $total = $($this).data("total");
    var $representante = $($this).data("representante");
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, abertura : $abertura, total : $total , representante : $representante},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_representante_atraso", title, body, 'modal-lg');
        }
    });
}

function showModalAberturas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var abertura = $($this).data('abertura');
    var total = $($this).data('total');
    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, abertura : abertura, total : total},
        method: 'POST',
        success: function(body){
            createModal("model_aberturas_titulos", title, body, 'modal-lg');
        }
    });
}

function showModalOpenTitulosRepresentantesTotal($this){
    var $url = $($this).data("route");
    var $filter = $($this).data("filter");
    var $title = $($this).data("title");
    var $clientes = $($this).data("clientes");
    var $abertura = $($this).data("abertura");
    var $total = $($this).data("total");
    var $representante = $($this).data("representante");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", filters: $filter, clientes : $clientes, abertura : $abertura, total : $total , representante : $representante},
        success: function(body){
            createModal("analise_atrasos_equipe_representante_total", $title, body, 'modal-lg');
        }
    });
}
@endsection