@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table">
    <h4>Composição<h4>
    <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog2">
        <thead>
            <tr>
                <th class="tb_date">Mês/Ano</th>
                <th class="tb_number_column">Previsto Venda</th>
                <th class="tb_number_column">% Para Mês Atual</th>
                <th class="tb_number_column">Valor Para Mês Atual</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retorno as $index => $value)
            <tr>
                <td><span style="display: none;">{{ $value['data_string'] }}</span>{{ $index }}</td>
                <td> {{ $value['faturamento_venda'] }}</td>
                <td> {{ $value['porcetagem_referente_mes_atual'] }}</td>
                <td> {{ $value['valor_referente_mes_atual'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td>Total:</td>
                <td>{{ parserValor($mes_atual['valor']) }}</td>
            </tr>
        </tfoot>
    </table>
    <hr>
    <h4>Divisão Venda Prevista Mês Atual</h4>
    <table class="table table-striped table-not-edit table-not-view" id="table-dialog_3">
        <thead>
            <tr>
                <tr>
                    <th class="tb_date">Mês/Ano</th>
                    <th class="tb_number_column">% Mês</th>
                    <th class="tb_number_column">Valor Mês</th>
                </tr>
            </tr>
        </thead>
        <tbody>
            @foreach($data_tabela as $index => $value)
            <tr>
                <td><span style="display: none;">{{ $value['data_string'] }}</span>{{ $value['mes_ano'] }}</td>
                <td> {{ $value['porcetagem'] }}</td>
                <td> {{ $value['valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>Total:</td>
                <td>{{ parserValor($mes_atual['faturamento_previstos_valor']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $(document).find('#table-dialog2').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number_column" },
                { "class": "tb_number", type: 'html-num', targets: "tb_number_html" },
                { "class": "tb_date", targets: "tb_date"}
            ],
            "order": [[ 0, 'asc' ]]
        });

        $(document).find('#table-dialog_3').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number_column" },
                { "class": "tb_number", type: 'html-num', targets: "tb_number_html" },
                { "class": "tb_date", targets: "tb_date"}
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

</script>
@endsection
