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
    .td-dados-foot-inicio{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        border-left: 1px solid rgb(0,92,141);
        border-top: 1px solid rgb(0,92,141); 
    }
    .td-dados-foot{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
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
        <tr id='dados'>
            <th class='th-dados'>Estabelecimento</th>
            <th class='th-dados'>Cliente</th>
            <th class='th-dados'>Duplicata</th>
            <th class='th-dados'>Parcela</th>
            <th class='th-dados'>Nota</th>
            <th class='th-dados'>Emissão</th>
            <th class='th-dados'>Vencimento</th>
            <th class='th-dados'>Pagamento</th>
            <th class='th-dados'>Valor da duplicata</th>
            <th class='th-dados'>Comissão %</th>
            <th class='th-dados'>Valor Comissão</th>
        </tr>
    </thead>
    <tbody>
        @foreach($titulos['titulos'] as $titulo)
        <tr>
            <td class="td-dados-inicio">{{ $titulo['estabelecimento'] }}</td>
            <td class='td-dados'>{{ $titulo['cliente'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['duplicata'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['parcela'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['nota_numero'] }}</td>
            <td class="tb_date td-dados">{{ $titulo['emissao'] }}</td>
            <td class="tb_date td-dados">{{ $titulo['vencimento'] }}</td>
            <td class="tb_date td-dados">{{ $titulo['data_pagamento'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['valor'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['porcentagem'] }}</td>
            <td class="numeros-td td-dados">{{ $titulo['comissao'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8" class='td-dados-foot-inicio'><b>Total:</b></td>
            <td class="numeros-td td-dados-foot" id='total_basecomissao'>{{ parserValor($titulos['total']['valor_total']) }}</td>
            <td class='td-dados-foot'></td>
            <td class="numeros-td td-dados-foot" id='total_comissao'>{{ parserValor($titulos['total']['comissao']) }}</td>
        </tr>
    </tfoot>
</table>
<br>
@if(!empty($lancamentos['dados']))
<table class='table-dados'>
    <thead>
        <tr>
            <td colspan="5"><h2>Lançamentos</h2></td>
        </tr>
        <tr>
            <th class="sort-date th-dados">Data</th>
            <th class="th-dados">Documento</th>
            <th class="th-dados">Motivo</th>
            <th  class='numeros th-dados td-tamanho'>Credito</th>
            <th  class='numeros th-dados td-tamanho'>Debito</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lancamentos['dados'] as $lancamento)
        <tr id="dados">
            <td class="td-dados-inicio">{{ $lancamento['data'] }}</td>
            <td class="td-dados">{{ $lancamento['documento'] }}</td>
            <td class="td-dados">{{ $lancamento['motivo'] }}</td>
            <td class='td-dados numeros-td td-tamanho'>{{ $lancamento['credito'] }}</td>
            <td class='td-dados numeros-td td-tamanho'>{{ $lancamento['debito'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr id="dados">
            <td class="td-dados-inicio td-dados-foot" colspan="3">Total Apurado:</td>
            <td class='td-dados numeros-td td-dados-foot'>{{ $lancamentos['total']['credito'] }}</td>
            <td class='td-dados numeros-td td-dados-foot'>{{ $lancamentos['total']['debito'] }}</td>
        </tr>
    </tfoot>
</table>
<br>
@endif
<table class='table-total'>
    <tr>
        <td><h4>Total Comissão:</h4></td>
        <td class='numeros-td'><h4>{{ $lancamentos['total']['comissao'] }}</h4></td>
    </tr>
    <tr></tr>
</table>
