<style>
    .table-dados{
        font-size: 12px;
        width: 100%;
        border-spacing: 0;
        border-bottom: 1px solid rgb(0,92,141);
    }
    .table-total{
        width: 100%;
        border-spacing: 0;
    }
    tr:nth-child(even) {
        background-color: #FFF;
    }
    tr:nth-child(odd) {
        background-color: rgb(238, 238, 238);
    }

    .td-tamanho{
        width: 90px;
        border-spacing: 0;
    }

    .th-dados{
        background-color: #FFF;
        text-transform: uppercase;
        font-weight: normal;
        border-bottom: 1px solid rgb(0,92,141);
    }

    .td-dados{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        /* border-left: 1px solid rgb(0,92,141); */
        /* border-bottom: 1px solid rgb(0,92,141); */
    }
    .td-dados-inicio{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        border-left: 1px solid rgb(0,92,141);
        /* border-bottom: 1px solid rgb(0,92,141); */
    }
    .td-dados-foot{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        border-left: 1px solid rgb(0,92,141);
        border-top: 1px solid rgb(0,92,141); 
    }

    .numeros{
        width: 5%;
    }
    .numeros-td{
        text-align: right;
    }
</style>

<h3>{{ $representante }} ({{ $cod_representante }}) - Período {{ $data_inicio }} até {{ $data_fim }}</h3>

<table class='table-dados'>
    <thead>
        <tr id="dados">
            <th class="th-dados">Estabelecimento</th>
            <th class="sort-date th-dados">Data Emissão</th>
            <th class="th-dados">Titulo</th>
            <th class="th-dados">Parcela</th>
            <th class="tb_number th-dados">Nota</th>
            <th class="th-dados">Cliente</th>
            <th class="tb_number th-dados">Valor da duplicata</th>
            <th class="tb_number th-dados">Comissão %</th>
            <th class="tb_number th-dados">Valor Comissão</th>
        </tr>
    </thead>
    <tbody>
        @foreach($titulos['titulos'] as $titulos)
        <tr id="dados">
            <td class="td-dados-inicio">{{ $titulos['estabelecimento'] }}</td>
            <td class="td-dados">{{ $titulos['data_emissao'] }}</td>
            <td class="td-dados">{{ $titulos['duplicata'] }}</td>
            <td class="td-dados">{{ $titulos['parcela'] }}</td>
            <td class="td-dados">{{ $titulos['nota_numero']}}</td>
            <td class="td-dados">{{ $titulos['cliente'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['valor_total'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['porcentagem'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['comissao'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr id="dados">
            <td class='td-dados-inicio td-dados-foot' colspan="6">Total:</td>
            <td class='numeros-td td-dados td-dados-foot'>{{ $total['valor_total'] }}</td>
            <td class='td-dados td-dados-foot'></td>
            <td class='numeros-td td-dados td-dados-foot'>{{ $total['comissao'] }}</td>
        </tr>
    </tfoot>
</table>
<br>
<table class='table-total'>
    <tr>
        <td><h4>Total Comissão:</h4></td>
        <td class='numeros-td'><h4>{{ $total['comissao'] }}</h4></td>
    </tr>
    <tr></tr>
</table>
