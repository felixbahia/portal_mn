@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter_atrasos_equipe" id="form_filter_atrasos_equipe" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
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
                {{ Form::text('data_fim','',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Data Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
        </div>
        <div class="row">
            <div class="form-group col-lg-2 col-xl-3">
                <div class="col-md-6 ml-4 mt-7">
                    {{ Form::checkbox('titulos', '1', true,  ['id' => 'titulos', 'class' => 'form-check-input']) }}
                    {{ Form::label('titulos', 'Títulos', ['class' => 'form-check-label','for' => 'titulos']) }}
                </div>
            </div>
  
            <div class="form-group col-lg-2 col-xl-3">
                <div class="col-md-6 ml-4 mt-7">
                    {{ Form::checkbox('titulos_pre', '1', true,  ['id' => 'titulos_pre', 'class' => 'form-check-input']) }}
                    {{ Form::label('titulos_pre', 'Títulos Pré-venda', ['class' => 'form-check-label','for' => 'titulos_pre']) }}
                </div>
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
    <table class="table table-striped table-not-edit table-not-view"  id="table-filters-atrasos-equipe">
        <thead>
            <tr>
				<th rowspan="2" >Estabelecimento</th>
				<th rowspan="2" class='tb_number'>Aberto</th>
                <th colspan="4">Vencido Dias</th>
                <th rowspan="2" class='tb_number'>cenprot</th>
                <th rowspan="2"  class='tb_number'>Total</th>
                <th  colspan="2">Renegociado</th>
                   <th rowspan="2" class='tb_number'>Cobrança Judicial</th>
               
       
            </tr>
            <tr>
   
                <th class='tb_number'>1 a 10</th>
                <th class='tb_number'>11 a 30 </th>
                <th class='tb_number'>31 a 60</th>
                <th class='tb_number'>Mais de 60</th>
                     
                <th class='tb_number'>Aberto</th>
                <th class='tb_number'>Vencido</th>
      
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
                <td></td>
      
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                </tr>
            
        </tfoot>



        
       </tr>
    </table>
</div>
@endsection
@section('script-footer')
$(document).ready( function(){

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
    $("#btn-filterform").on("click", function(){
        filter();
    });
  table_filters_atrasos = $('#table-filters-atrasos-equipe').DataTable({
        "searching": false,
        "paging": true,
        "lengthChange": false,
        "info": false,  
        "pageLength": 15,
        "orderMulti": false,
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


function createBtViewClienteAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.cliente') }}\" data-filter=\""+$this.filter+"\" data-abertura='aberto' data-cliete='true' data-abertura-geral='false' data-total='false' data-title='TITULOS FATURADOS - CLIENTE - Representante - "+$this.estabelecimento+"' class='bt-view bt-clinete-atraso'></a>"
    return html;
}

function createBtViewRepresentanteAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.representantes') }}\" data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - Representante - "+$this.estabelecimento+"' data-abertura='total' data-total='false' data-representante='true'  class='bt-view bt-representante-atraso'></a>"
    return html;
}

function createBtViewAbertoAtraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='aberto' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - "+$this.estabelecimento+"' class='bt-titulos-abertos'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewVencido_1_Atraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_1' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO de 1 à 10 dias -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido_1+"</a>"
    return html;
}
function createBtViewVencido_2_Atraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_2' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO de 11 à 30 dias-"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido_2+"</a>"
    return html;
}
function createBtViewVencido_3_Atraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_3' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO de 31 à 60 dias  -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido_3+"</a>"
    return html;
}
function createBtViewVencido_4_Atraso($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_4' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO mais de 60 dias -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.vencido_4+"</a>"
    return html;
}
function createBtViewCenprot($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_cenprot') }}\" data-abertura='cenprot' data-total='false' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.cenprot+"</a>"
    return html;
}
function createBtViewRenegociadoAberto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='renegociado_aberto' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - Renegociado Aberto -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.renegociado_aberto+"</a>"
    return html;
}
function createBtViewRenegociadoVencido($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='renegociado_vencido' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS -  Renegociado Vencido -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.renegociado_vencido+"</a>"
    return html;
}
function createBtViewCobrancaJudicial($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='cobranca_judicial' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS -  Cobrança Judicial -"+$this.estabelecimento+"' class='bt-titulos-vencidos'>"+$this.cobranca_judicial+"</a>"
    return html;
}

function createBtViewTotal($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='estabelecimento' data-total='todos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - "+$this.estabelecimento+"' class='bt-titulos-total'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalAberto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='aberto' data-total='todos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - ABERTO - TODOS' class='bt-titulos-total-aberto'>"+$this.aberto+"</a>"
    return html;
}

function createBtViewTotalVencido_1($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_1' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido_1+"</a>"
    return html;
}

function createBtViewTotalVencido_2($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_2' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido_2+"</a>"
    return html;
}

function createBtViewTotalVencido_3($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_3' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido_3+"</a>"
    return html;
}
function createBtViewTotalVencido_4($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='vencido_4' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.vencido_4+"</a>"
    return html;
}
function createBtViewTotalCenprot($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_cenprot') }}\" data-abertura='cenprot' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - CENPROT' class='bt-titulos-total-vencido'>"+$this.cenprot+"</a>"
    return html;
}
function createBtViewTotalRenegociadoAberto($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='renegociado_aberto' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.renegociado_aberto+"</a>"
    return html;
}

function createBtViewTotalRenegociadoVencido($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='renegociado_vencido' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.renegociado_vencido+"</a>"
    return html;
}
function createBtViewTotalCobrancaJudicial($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='cobranca_judicial' data-total='excluir_titulos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - VENCIDO - TODOS' class='bt-titulos-total-vencido'>"+$this.cobranca_judicial+"</a>"
    return html;
}



function createBtViewTotalAberturas($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='total' data-total='todos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - TODOS' class='bt-titulos-total-geral'>"+$this.total+"</a>"
    return html;
}

function createBtViewTotalClientes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.cliente') }}\" data-abertura='total' data-abertura-geral='true' data-cliente='false' data-busca='' data-total='true' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS POR CLIENTE - TOTAL - TODOS' class='bt-view bt-titulos-total-clientes'></a>"
    return html;
}

function createBtViewTotalRepresentantes($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.representantes') }}\" data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS POR Representante - TOTAL - TODOS' data-abertura='total' data-total='true' data-representante='false' data-cliente='null' class='bt-view bt-titulos-total-representantes'></a>"
    return html;
}

function createBtViewTotalGeral($this){
    var html = "<a href=\"#\" data-route=\"{{ route('cobranca_ragazzi.modal.titulo.titulos_abertura') }}\" data-abertura='total' data-total='todos' data-filter=\""+$this.filter+"\" data-title='TITULOS FATURADOS - TOTAL - GERAL' class='bt-titulos-total'>"+$this.total+"</a>"
    return html;
}

function filter(){
    var $data_form = $("#form_filter_atrasos_equipe").serialize();
    table_filters_atrasos.clear().draw();
   $(table_filters_atrasos.column(0).footer()).html('');
    $(table_filters_atrasos.column(1).footer()).html('');
    $(table_filters_atrasos.column(2).footer()).html('');
    $(table_filters_atrasos.column(3).footer()).html('');
    $(table_filters_atrasos.column(4).footer()).html('');
    $(table_filters_atrasos.column(5).footer()).html('');
    $(table_filters_atrasos.column(6).footer()).html('');
    $(table_filters_atrasos.column(7).footer()).html('');
    $(table_filters_atrasos.column(8).footer()).html('');
    $(table_filters_atrasos.column(9).footer()).html('');
    $(table_filters_atrasos.column(10).footer()).html('');

    $.ajax({
        url: "{{ route('cobranca_ragazzi.filter') }}",
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
                            createBtViewVencido_1_Atraso(linhas[field]),
                            createBtViewVencido_2_Atraso(linhas[field]),
                            createBtViewVencido_3_Atraso(linhas[field]),
                            createBtViewVencido_4_Atraso(linhas[field]),
                            createBtViewCenprot(linhas[field]),
                            createBtViewTotal(linhas[field]),
                            createBtViewRenegociadoAberto(linhas[field]),
                            createBtViewRenegociadoVencido(linhas[field]),
                            createBtViewCobrancaJudicial(linhas[field]),
                     
                              ]);
                    }
                    $(table_filters_atrasos.column(0).footer()).html((callback.response.total.total) ? 'TOTAL' : '');
                    $(table_filters_atrasos.column(1).footer()).html((callback.response.total.aberto) ? createBtViewTotalAberto(callback.response.total) : '');
                    $(table_filters_atrasos.column(2).footer()).html((callback.response.total.vencido_1) ? createBtViewTotalVencido_1(callback.response.total) : '');
                    $(table_filters_atrasos.column(3).footer()).html((callback.response.total.vencido_2) ? createBtViewTotalVencido_2(callback.response.total) : '');
                    $(table_filters_atrasos.column(4).footer()).html((callback.response.total.vencido_3) ? createBtViewTotalVencido_3(callback.response.total) : '');
                    $(table_filters_atrasos.column(5).footer()).html((callback.response.total.vencido_4) ? createBtViewTotalVencido_4(callback.response.total) : '');
                    $(table_filters_atrasos.column(6).footer()).html((callback.response.total.total) ? createBtViewTotalCenprot(callback.response.total) : '');
                    $(table_filters_atrasos.column(7).footer()).html((callback.response.total.cenprot) ? createBtViewTotalGeral(callback.response.total) : '');
                    $(table_filters_atrasos.column(8).footer()).html((callback.response.total.renegociado_aberto) ? createBtViewTotalRenegociadoAberto(callback.response.total) : '');
                     $(table_filters_atrasos.column(9).footer()).html((callback.response.total.renegociado_vencido) ? createBtViewTotalRenegociadoVencido(callback.response.total) : '');
                    $(table_filters_atrasos.column(10).footer()).html((callback.response.total.cobranca_judicial) ? createBtViewTotalCobrancaJudicial(callback.response.total) : '');
                   
                  
               
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


function showModalAberturas($this){
    var url = $($this).data("route");
    var filter = $($this).data("filter");
    var title = $($this).data('title');
    var abertura = $($this).data('abertura');
    var total = $($this).data('total');
 
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
    $("#form_filter_atrasos_equipe").find("#cliente_nome").val($dados.find("td").eq(1).text());
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
@endsection