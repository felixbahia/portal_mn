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
            <th class="tb_number th-dados">Nota</th>
            <th class="th-dados">Cliente</th>
            <th class="tb_number th-dados">Valor Vendido</th>
            <th class="tb_number th-dados">Devolução</th>
            <th class="tb_number th-dados">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($notas['titulos'] as $titulos)
        <tr id="dados">
            <td class="td-dados-inicio">{{ $titulos['estabelecimento'] }}</td>
            <td class="td-dados">{{ $titulos['data_emissao'] }}</td>
            <td class="td-dados">{{ $titulos['numero_documento'] }}</td>
            <td class="td-dados">{{$titulos['numero_nota']}}</td>
            <td class="td-dados">{{ $titulos['cliente'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['valor_nota'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['valor_devolucao'] }}</td>
            <td class="numeros-td td-dados"> {{ $titulos['total'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr id="dados">
            <td class='td-dados-inicio td-dados-foot' colspan="5">Total:</td>
            <td class='numeros-td td-dados td-dados-foot'>{{ $notas['total']['valor_nota'] }}</td>
            <td class='numeros-td td-dados td-dados-foot'>{{ $notas['total']['devolucao'] }}</td>
            <td class='numeros-td td-dados td-dados-foot'>{{ $notas['total']['total'] }}</td></td>
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
