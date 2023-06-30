@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter_titulos_apagar" id="form_filter_titulos_apagar" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-3">
                <div class="input-group">
                    {{ Form::text('fornecedor_nome', '', ['id' => 'fornecedor_nome', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Fornecedor']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca" data-route="{{ route('fornecedor.busca.index') }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
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
    <table class="table table-striped" id="table-filters-atrasos-equipe">
        <thead>
            <tr>
				<th>Estabelecimento</th>
				<th>Aberto</th>
				<th>Vencido</th>
                <th>Total</th>
				<th>Fornecedor</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <th></th>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
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
    $(document).find("#fornecedor_nome").val('');
    $(document).find("#fornecedor_nome").autocomplete(optionsAutoCompleteFornecedor());
    $(document).find("#bt-search-fornecedor-busca").on("click", function(){
        showModalFornecedor($(this).data("route"), "Lista de Fornecedores");
    });

    table_filters_atrasos = $('#table-filters-atrasos-equipe').on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
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
                "targets": [1,2,3],
                className: 'number_format'
            },
            {
                "targets": [0,4],
                "width": '20%'
            },
        ],
        "order": [ 0, 'asc' ]
    });

    table_filters_atrasos.on('draw', function () {
        $(document).find(".bt-fornecedor-atraso").off("click");
        $(document).find(".bt-fornecedor-atraso").on("click", function(event){
            event.stopPropagation();
            showModalFornecedorAtraso($(this));
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
        $(document).find(".bt-titulos-total-fornecedores").off("click");
        $(document).find(".bt-titulos-total-fornecedores").on("click", function(event){
            event.stopPropagation();
            showModalFornecedorAtraso($(this));
        });
        $(document).find(".bt-titulos-total-representantes").off("click");
        $(document).find(".bt-titulos-total-representantes").on("click", function(event){
            event.stopPropagation();
            showModalOpenTitulosRepresentantesTotal($(this));
        });
    });
});

function optionsAutoCompleteFornecedor(){
    $(document).find(".error-message").remove();
    return {
        source: function (request, response) {
            request._token = "{{ csrf_token() }}";
            request.busca_pedido = true;
            $.post("{{ route('fornecedor.autocomplete') }}", request, response);
        },
        delay: 700,
        minLength: 2,
        open: function( event, ui ){
        },
        response: function( event, ui ) {
            if(ui.content.length === 0){
                message('Atenção', 'Nenhum fornecedor encontrado');
                event.stopPropagation();
                return false;
            }
        },
        select: function( event, ui ) {
            event.stopPropagation();
            $(document).find("#fornecedor_nome").val(ui.item.label);
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
    $(document).find("#fornecedor_atrasos_show").modal("hide");
    $("#form_filter_titulos_apagar").find("#fornecedor_nome").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
    $.ajax({
        url: '{{ route('cliente.salvaClientePadrao') }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            codcad: $dados.find("td").eq(0).text()
        }
    });
}

function showModalFornecedor(url, title){
    xhr = $.ajax({
        url: url,
        method: 'GET',
        success: function(body){
            createModal("fornecedor_atrasos_show", title, body, 'modal-lg');
            $(document).ready( function () {
                table_dialog.on('draw', function () {
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").off("click");
                    $(document).find("#fornecedor_atrasos_show").find("tbody").find("tr").on("click", function(){
                        returnDados($(this));
                    });
                });
                $(document).find("#fornecedor_atrasos_show").find(".bt-selected").on("click", function(){
                    returnDados($(this));
                });
            });
        }
    });
}


function createBtViewFornecedorAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.fornecedor') }}\" data-filter=\""+$this.filter+"\" data-abertura='aberto' data-fornecedor='true' data-abertura-geral='false' data-total='false' data-title='TITULOS FATURADOS - FORNECEDORES - "+$this.estabelecimento+"' class='bt-view bt-fornecedor-atraso'></a>"
    return html;
}

function createBtViewAbertoAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='aberto' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - "+$this.estabelecimento+"' class='bt-titulos-abertos'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewVencidoAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='vencido' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido+"</a>"
    return html;
}

function createBtViewTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='total' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - "+$this.estabelecimento+"' class='bt-titulos-total'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalAberto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='aberto' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - TODOS' class='bt-titulos-total-aberto'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewTotalVencido($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='vencido' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido+"</a>"
    return html;
}

function createBtViewTotalAberturas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.titulos_abertura') }}\" data-abertura='total' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - TODOS' class='bt-titulos-total-geral'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalFornecedores($this){
    var html = "<a href=\"#\" data-route=\"{{ route('titulos_apagar.modal.fornecedor') }}\" data-abertura='total' data-abertura-geral='true' data-fornecedor='false' data-busca='' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS POR FORNECEDOR - TOTAL - TODOS' class='bt-view bt-titulos-total-fornecedores'></a>"
    return html;
}

function filter(){
    var $data_form = $("#form_filter_titulos_apagar").serialize();
    table_filters_atrasos.clear().draw();
    $(table_filters_atrasos.column(0).footer()).html('');
    $(table_filters_atrasos.column(1).footer()).html('');
    $(table_filters_atrasos.column(2).footer()).html('');
    $(table_filters_atrasos.column(3).footer()).html('');
    $(table_filters_atrasos.column(4).footer()).html('');
    $.ajax({
        url: "{{ route('titulos_apagar.filtrar') }}",
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
                            createBtViewAbertoAtraso(linhas[field]),
                            createBtViewVencidoAtraso(linhas[field]),
                            createBtViewTotal(linhas[field]),
                            createBtViewFornecedorAtraso(linhas[field]),
                        ]);
                    }
                $(table_filters_atrasos.column(0).footer()).html((callback.response.total.total) ? 'TOTAL' : '');
                $(table_filters_atrasos.column(1).footer()).html((callback.response.total.aberto) ? createBtViewTotalAberto(callback.response.total) : '');
                $(table_filters_atrasos.column(2).footer()).html((callback.response.total.vencido) ? createBtViewTotalVencido(callback.response.total) : '');
                $(table_filters_atrasos.column(3).footer()).html((callback.response.total.total) ? createBtViewTotalAberturas(callback.response.total) : '');
                $(table_filters_atrasos.column(4).footer()).html((callback.response.total.total) ? createBtViewTotalFornecedores(callback.response.total) : '');
                
                table_filters_atrasos.rows.add(temp_field).draw().nodes();
                }else{
                    table_filters_atrasos.clear().draw();
                }
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
    });
}

function showModalFornecedorAtraso($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var abertura = $($this).data('abertura');
    var total = $($this).data('total');
    var $fornecedor = $($this).data("fornecedor");
    var $busca = $($this).data("busca");
    var $aberturageral = $($this).data("abertura-geral");

    xhr = $.ajax({
        url: url,
        data: {_token: "{{ csrf_token() }}", filters : filter, abertura : abertura, total : total, busca : $busca, aberturageral : $aberturageral},
        method: 'POST',
        success: function(body){
            createModal("model_analitico_view_fornecedor_atraso", title, body, 'modal-lg');
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

@endsection