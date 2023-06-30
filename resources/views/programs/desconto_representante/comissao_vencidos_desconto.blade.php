<table>
    <thead>
        <tr>
            <th colspan='11'>
                <h3>{{ $representante }} ({{ $cod_representante }})</h3>
            </th>
        </tr>
        <tr>
            <th>Estabelecimento</th>
            <th>Cliente</th>
            <th>Duplicata</th>
            <th>Parcela</th>
            <th>Nota</th>
            <th>Emissão</th>
            <th>Vencimento</th>
            <th>Valor da duplicata</th>
            <th>Comissão %</th>
            <th>Valor Comissão</th>
        </tr>
    </thead>
    <tbody>
        @foreach($titulos as $titulo)
        <tr>
            <td>{{ $titulo['estabelecimento'] }}</td>
            <td>{{ $titulo['cliente'] }}</td>
            <td>{{ $titulo['duplicata'] }}</td>
            <td>{{ $titulo['parcela'] }}</td>
            <td>{{ $titulo['numero_documento'] }}</td>
            <td>{{ parserData($titulo['emissao']) }}</td>
            <td>{{ parserData($titulo['vencimento']) }}</td>
            <td align="right">{{ $titulo['valor_total'] }}</td>
            <td align="right">{{ $titulo['porcentagem'] }}</td>
            <td align="right">{{ $titulo['comissao'] }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan='6'>Total:</td>
            <td>{{ $total['valor_total'] }}</td>
            <td></td>
            <td>{{ $total['comissao'] }}</td>
        </tr>
    </tfoot>
</table>
<br>
<table>
    <tr>
        <td><h4>Total dos descontos:</h4></td>
        <td><h4>{{ $total['comissao'] }}</h4></td>
    </tr>
    <tr></tr>
</table>
