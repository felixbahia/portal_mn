@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <div class="content-table">
        <table class="order-column table-striped" id="table-analise-vendas-grupo">
            <thead>
                <tr>
                    <th rowspan="3">Grupo</th>
                </tr>
                <tr>
                    @foreach($head as $heads)
                        <th colspan="4">{{ $heads }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach($head as $heads)
                        <th class="tb_number">QTD VENDIDA</th>
                        <th class="tb_number">VALOR</th>
                        <th class="tb_number">PREÇO MÉDIO</th>
                        <th class="tb_number">% VALOR TOTAL <a href="#" class="btn-informacao-sem-width" data-toggle="tooltip" data-placement="top" title="" data-original-title="Cálculo: Valor Total do Grupo / Valor Total Geral dos Grupos"></a> </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($retorno as $key => $dados)
                    <tr>
                        <td> <div><div data-toggle='tooltip' data-html='true' title='' data-original-title="{{$key}}">{{$key}} </div></div></td>
                        <td class="tb_number"> {{$dados['quantidade_vendida_ano_busca']}} </td> 
                        <td class="tb_number"> {{$dados['valor_ano_busca']}} </td>
                        <td class="tb_number"> {{$dados['preco_medio_ano_busca']}} </td>
                        <td class="tb_number"> {{$dados['percentual_ano_busca']}} </td>
                        <td class="tb_number"> {{$dados['quantidade_vendida_ano_anterior']}} </td>
                        <td class="tb_number"> {{$dados['valor_ano_anterior']}} </td>
                        <td class="tb_number"> {{$dados['preco_medio_ano_anterior']}} </td>
                        <td class="tb_number"> {{$dados['percentual_ano_anterior']}} </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total:</td>
                    <td class='tb_number'>{{ $total['quantidade_vendida_busca'] }}</td>
                    <td class='tb_number'>{{ $total['valor_busca'] }}</td>
                    <td class='tb_number'>{{ $total['preco_medio_busca'] }}</td>
                    <td class='tb_number'>{{ $total['percentual_busca'] }}</td>
                    <td class='tb_number'>{{ $total['quantidade_vendida_anterior'] }}</td>
                    <td class='tb_number'>{{ $total['valor_anterior'] }}</td>
                    <td class='tb_number'>{{ $total['preco_medio_anterior'] }}</td>
                    <td class='tb_number'>{{ $total['percentual_anterior'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    var table_grupo = $('#table-analise-vendas-grupo').DataTable({
        "scrollX": false,
        "searching": false,
        "lengthChange": false,
        "searching": false,
        "info": false,
        "pageLength": 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Análise de Produto',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 1 || column === 2 || column === 3 || column === 4 || column === 5 || column === 6 || column === 7 || column === 8){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
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
    });
    
    setTimeout(function(){
        table_grupo.draw();
    }, 200);
});

</script>
@endsection        
