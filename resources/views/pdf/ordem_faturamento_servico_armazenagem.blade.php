<style>
    .table-dados{
        font-size: 18px;
        width: 100%;
        border-spacing: 5px;
    }
    .table-titulos{
        margin: auto;
        table-layout: fixed;
        align: center;
        font-size: 22px;
    }
    .td-titulos{
        border: 1px solid black;
    }
    .direita{
        font-size: 12px;
        text-align: right;
    }
    .esquerda{
        font-size: 12px;
        text-align: left;
    }
    .subtitutlo{
        font-size: 20px;
        text-decoration: underline;
    }
    .divisoria{
        border-bottom: 1px solid rgb(0,0,0);
    }                                 
</style>
<div class="esquerda">ARMAZENS GERAIS FILIAL 01</div>
<div class="direita">{{ $data_atual }}</div>
<br>
<table class="table-titulos">
    <tr>
        <td class="td-titulos">ORDEM FATURAMENTO SERVIÇO ARMAZENAGEM</td>
    </tr>
</table>
<br>
<br>
<div class="subtitulo"><u>CLIENTE</u></div>
<br>
<table class='table-dados'>
    <tr>
        <td colspan="2">{{ $empresa }}</td>
        <td>CEP</td>
        <td>{{ $cep }}</td>
    </tr>
    <tr>
        <td colspan="2">{{ $endereco }}</td>
        <td>CNPJ</td>
        <td>{{ $cnpj }}</td>
    </tr>
    <tr>
        <td>{{ $cidade }}</td>
        <td>{{ $estado }}</td>
        <td>INSC</td>
        <td>{{ $inscricao_estatual }}</td>
    <tr>
</table>
<br>
<table class='table-dados'>
    <tr>
        <td>SERVIÇO:</td>
        <td>SERVIÇOS DE ARMAZENAGEM</td>
    </tr>
    <tr>
        <td>PERIODO:</td>
        <td>mês completo de {{ $data }}</td>
    </tr>
    <tr>
        <td>BASE CALC:</td>
        <td colspan="2">ESTOQUE INICIAL({{ $peso_inicial }}) + ENTRADAS PERIODO({{ $peso_entrada }}) = {{ $peso_total }} KG</td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td><b></b></td>
        <td><b>P. UNIT / KG: {{ $valor_unitario }}</b></td>
        <td><b>VALOR: </b></td>
        <td><b>{{ $valor_total }}</b></td>
    </tr>
</table>
<br>
<table class='table-dados'>
    <tr>
        <td><b>NOTA FISCAL N.</b>_____________________</td>
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td><b>NF EMITIDA EM</b> ___/___/______</td>
        <td><b>ASS</b>________________________________________</td>
    </tr>
</table>
<br>
<br>
<div class="divisoria"></div>
<br>
<div class="subtitulo"><u>OBSERVAÇÕES:</u></div>