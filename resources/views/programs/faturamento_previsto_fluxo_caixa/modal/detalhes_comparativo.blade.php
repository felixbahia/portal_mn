@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table">
    <h4>Composição<h4>
    <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog2">
        <thead>
            <tr>
                <th class="tb_date">Mês/Ano</th>
                <th class="tb_number_column">Venda Prevista</th>
                <th class="tb_number_column">% Para Mês Atual</th>
                <th class="tb_number_column">Valor Para Mês Atual</th>
                <th class="tb_number_column">Venda Realizada</th>
                <th class="tb_number_column">% Para Mês Atual</th>
                <th class="tb_number_column">Valor Para Mês Atual</th>
                <th class="tb_number_column">Diferença Previsto X Realizado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retorno as $index => $value)
            <tr>
                <td><span style="display: none;">{{$value['ordenacao']}}</span>{{ $index }}</td>
                <td> {{ $value['faturamento_venda'] }}</td>
                <td> {{ $value['porcetagem_referente_mes_atual'] }}</td>
                <td> {{ $value['valor_referente_mes_atual'] }}</td>
                <td> {{ $value['venda_realizada'] }}</td>
                <td> {{ $value['porcetagem_referente_mes_atual_realizada'] }}</td>
                <td> {{ $value['valor_referente_mes_atual_realizada'] }}</td>
                <td> {{ $value['diferenca'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td>Total:</td>
                <td>{{ parserValor($mes_atual['valor']) }}</td>
                <td></td>
                <td></td>
                <td>{{ parserValor($total['realizado_venda']) }}</td>
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
                { "class": "tb_date", type: 'monthYear', targets: "tb_date"}
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

</script>
@endsection
