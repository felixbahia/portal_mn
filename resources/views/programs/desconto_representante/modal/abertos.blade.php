@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_comissao_vencidas" id="form_comissao_vencidas" onsubmit="return false;">
    {{ Form::button('Exportação XLSX', array('class' => 'btn btn-primary float-right ml-2', 'id' => 'btn_exportar')) }}
    {{ Form::button('Exportação PDF', array('class' => 'btn btn-primary float-right ml-2', 'id' => 'btn_exportar_pdf')) }} 
</form>

<div class="content-dialog-table">
    <table class="table table-striped" id="table-duplicatas-vencidas-desconto">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th class="tb_number_html ">Duplicata</th>
                <th class="tb_number_html ">Parcela</th>
                <th class='tb_number_html'>Nota</th>
                <th class="sort-date">Emissão</th>
                <th class="sort-date">Vencimento</th>
                <th class="tb_number_column">Valor da duplicata</th>
                <th class="tb_number_column">Comissão %</th>
                <th class="tb_number_column">Valor Comissão</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos as $titulo)
            <tr>
                <td>{{ $titulo['estabelecimento'] }}</td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $titulo['cliente'] }}'>{{ $titulo['cliente'] }}</div></div></td>
                <td class="tb_number">{{ $titulo['duplicata'] }}</td>
                <td class="tb_number">{{ $titulo['parcela'] }}</td>
                <td><a href='#' onclick="showNotasDetalhes('{{ $titulo['nota_id'] }}', '{{ $titulo['estabelecimento_not_parse'] }}')">{{ $titulo['numero_documento'] }}</a>
                <td data-order='{{ $titulo['emissao'] }}' class="tb_date">{{ parserData($titulo['emissao']) }}</td>
                <td data-order='{{ $titulo['vencimento'] }}' class="tb_date">{{ parserData($titulo['vencimento']) }}</td>
                <td class="tb_number">{{ $titulo['valor_total'] }}</td>
                <td class="tb_number">{{ $titulo['porcentagem'] }}</td>
                <td class="tb_number">{{ $titulo['comissao'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td>Total:</td>
                <td class="tb_number" id='total_valor'>{{ $totais['valor_total'] }}</td>
                <td></td>
                <td class="tb_number" id='total_comissao'>{{ $totais['comissao'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">

    $(document).ready( function () {
        
        form_modal = $(document).find("#form_comissao_vencidas");

        form_modal.find("#btn_exportar").off("click");
        form_modal.find("#btn_exportar").on("click", function(){
            var form =  $(document).find("#form_comissao_vencidas");
            gerarExportacao(form);
        });

        form_modal.find("#btn_exportar_pdf").off("click");
        form_modal.find("#btn_exportar_pdf").on("click", function(){
            var form =  $(document).find("#form_comissao_vencidas");
            gerarExportacaoPDF(form);
        });

        

        table_filters_duplicatas_vencidas.draw();
    
    });

    function showNotasDetalhes($id_nota, estabelecimento){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                estabelecimento: estabelecimento,
                origem: 'NASAJON',
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }
        });
    }

    var table_filters_duplicatas_vencidas = $(document).find('#table-duplicatas-vencidas-desconto').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                }
            },
            "columnDefs": [
                { "class": "tb_number", "type": 'numeric-comma-ftm', "targets": "tb_number_column" },
                { "class": "tb_number", "type": 'html-num-ftm', "targets": "tb_number_html" },
                { "class": "tb_date", "targets": "sort-date", "width": "1%" }
            ]
        });

    function gerarExportacao(form){
        $('<form action="{{ route('desconto_representante.modal.abertos_xlsx') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="exportar" value="{{ $exportar }}"/>\
                </form>').appendTo('body').submit().remove()   
    }

    function gerarExportacaoPDF(form){
        $('<form action="{{ route('desconto_representante.modal.abertos_pdf') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="exportar" value="{{ $exportar }}"/>\
                </form>').appendTo('body').submit().remove()   
    }

    
</script>
@endsection
