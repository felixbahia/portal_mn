@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control",'placeholder' => 'Todos']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_inicio', '',['id' => 'data_inicio', 'class' => 'data', 'placeholder' => 'Início DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'data', 'placeholder' => 'Fim DD/MM/AAAA','maxlength' => '20']) }}
            </div>
            <div class="col-lg-2">
                {{ Form::text('fracao', '',['id' => 'fracao', 'placeholder' => 'Peça','maxlength' => '50']) }}
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
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-index">
        <thead>
            <tr>
                <th>Ação<br></th>
                <th>Produto</th>
                <th>Código</th>
                <th>Peça</th>
                <th>Em Posse de</th>
                <th>Proprietário</th>
                <th class='tb_number'>Documento</th>
                <th>Local Estoque</th>
                <th>Endereço</th>
                <th>Situação</th>
                <th>Usuário</th>
                <th class='tb_date'>Data Criação</th>
                <th class='tb_number'>Quantidade</th>
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

    $('#btn-filterform').on('click', function(){
        filtro();
    });

    table_filters_contas = $('#table-filters-index')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 15,
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
            { 
                "targets": 'tb_date',
                "class": 'tb_date',
            },
        ]
    });
    table_filters_contas.on('draw', function () {
        $(document).find(".bt-modal").off("click");
        $(document).find(".bt-modal").on("click", function(event){
            event.stopPropagation();
            showModal($(this));
        });

        $(document).find(".bt-view").off("click");
        $(document).find(".bt-view").on("click", function(event){
            event.stopPropagation();
            showModalDatas($(this));
        });
    });
});

function filtro(){

    table_filters_contas.clear().draw();
    
    $form = $("#form_filter");
    $data = $form.serialize();
    $('label.error-message').remove();
    $.ajax({
        url: '{{ route('rastreabilidade.filtro')}}',
        type: 'POST',
        data: $data,
        success: function(data){
            var out = [];
            for (var fields in data.response.response){
                out.push([
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.response[fields].acao+"\">"+data.response.response[fields].acao+"</div></div>",
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.response[fields].produto+"\">"+data.response.response[fields].produto+"</div></div>",
                    data.response.response[fields].codigo,
                    data.response.response[fields].fracao,
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.response[fields].detentor+"\">"+data.response.response[fields].detentor+"</div></div>",
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.response[fields].proprietario+"\">"+data.response.response[fields].proprietario+"</div></div>",
                    data.response.response[fields].documento,
                    data.response.response[fields].local,
                    data.response.response[fields].endereco,
                    data.response.response[fields].situacao,
                    "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data.response.response[fields].usuario+"\">"+data.response.response[fields].usuario+"</div></div>",
                    data.response.response[fields].data_criacao,
                    data.response.response[fields].quantidade,
                ]);
            }

            table_filters_contas.rows.add(out).draw();

            $('[data-toggle="popover"]').off('show.bs.popover');
            $('[data-toggle="popover"]').popover('hide');
    
            $('[data-toggle="popover"]').popover({
                container: 'body',
                html: true,
                show: true,
                trigger: 'hover',
                placement: 'right',
                template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
            });

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


@endsection