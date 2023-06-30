<style>
    table{
        font-size: 12px;
        width: 100%;
        border-spacing: 0;
        border-bottom: 1px solid rgb(0,92,141);
    }
    tr:nth-child(even) {
        background-color: #FFF;
    }
    tr:nth-child(odd) {
        background-color: rgb(238, 238, 238);
    }

    th{
        background-color: #FFF;
        text-transform: uppercase;
        font-weight: normal;
        border-bottom: 1px solid rgb(0,92,141);
    }

    td{
        border-right: 1px solid rgb(0,92,141);
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        /* border-left: 1px solid rgb(0,92,141); */
        /* border-bottom: 1px solid rgb(0,92,141); */
    }

    .numeros{
        width: 5%;
    }
    .numeros-td{
        text-align: right;
    }
</style>

<h3>{{ $titulo }} - Gerado {{ $gerado }}</h3>

<table>
    <thead>
        <tr>
            <th rowspan='2'>LINHA</th>
            <th rowspan='2'>GRUPO</th>
            <th rowspan='2'>COMPOSIÇÃO</th>
            <th rowspan='2' class='numeros'>GML</th>
            <th rowspan='2' class='numeros'>LARG.</th>
            <th rowspan='2' class='numeros'>UN.</th>
            <th colspan='7'>PRAZOS</th>
        </tr>
        <tr>
            <th class='numeros'>À VISTA</th>
            <th class='numeros'>15</th>
            <th class='numeros'>30</th>
            <th class='numeros'>45</th>
            <th class='numeros'>60</th>
            <th class='numeros'>75</th>
            <th class='numeros'>90</th>
        </tr>
    </thead>
    <tbody>    
    @foreach ($lista as $linha)
        <tr>
            <td>{{ $linha['linha'] }}</td>
            <td>{{ $linha['grupo'] }}</td>
            <td>{{ $linha['composicao']??'' }}</td>
            <td class='numeros-td'>{{ $linha['gramatura']??'' }}</td>
            <td class='numeros-td'>{{ $linha['largura']??'' }}</td>
            <td class='numeros-td'>{{ $linha['unidade']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_vista']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_15']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_30']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_45']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_60']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_75']??'' }}</td>
            <td class='numeros-td'>{{ $linha['prazo_90']??'' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
