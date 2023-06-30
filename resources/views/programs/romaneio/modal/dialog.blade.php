@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view"  id="table-dialog">
        <thead>
            <tr>
    
                <th  rowspan="2">Codigo</th>
                <th rowspan="2">Endereço</th>
                <th rowspan="2">Produto</th>
                <th rowspan="2">Peça</th>
                <th colspan="3">Quantidade</th>

                
            </tr>

            <tr>
                <th   class="tb_number">Romaneio</th>
                <th class="tb_number">Conferido</th>
                <th class="tb_number">Diferença</th>
                <th>Conferido</th>
                <th>Endereçado</th>

            
           </tr>
        </thead>
        <tbody>
           
            @foreach ($dados as $romaneio)
             <tr>
         

                <td>{{ $romaneio['produto_codigo'] }}</td>
                <td>{{ $romaneio['endereco'] }}</td>
          
              <td>  <div><div data-toggle=tooltip data-html=true  data-placement=left data-original-title=  {{$romaneio['produto_descricao'] }} > {{$romaneio['produto_descricao'] }} </div></div></td>
                <td>{{ $romaneio['peca_codigo'] }}</td>
                <td class="tb_number">{{ $romaneio['peca_quantidade'] }}</td>
                <td class="tb_number">{{ $romaneio['quantidade_coletor'] }}</td>
                <td class="tb_number">{{ $romaneio['diferenca'] }}</td>

                @if( $romaneio['conferido']=='true') 
                <td ><center><div class="fa fa-check check-icon" {{ $romaneio['conferido'] }} ></div></center></td>
              @else
                <td ><center><div class="fa fa-times error-icon" {{ $romaneio['conferido'] }} ></div></center></td>
              @endif
              @if( $romaneio['enderecado']=='true') 
              <td ><center><div class="fa fa-check check-icon" {{ $romaneio['enderecado'] }} ></div></center></td>
            @else
              <td ><center><div class="fa fa-times error-icon" {{ $romaneio['enderecado'] }} ></div></center></td>
            @endif
       
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>

                <td>Total:</td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number" id='total_romaneio'>  {{ $totais['total_romaneio'] }}</td>
                <td class="tb_number" id='total_coletor'> {{ $totais['total_coletor'] }}</td>
                <td class="tb_number" id='total_diferenca'> {{ $totais['total_diferenca'] }}</td>
                <td><center><div>{{ $totais['total_peca_conferido'] }} </div></center></td>
                <td><center><div> {{ $totais['total_peca_enderecado'] }}  </div></center></td>

               </tr>
        </tfoot>
    </table>
</div>
<script>
    table_dialog = $('#table-dialog').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "orderMulti": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Acompanhamento de Entradas',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
        ],
        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
            {
                "class": "tb_number", 
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
    });

    setTimeout(function(){
        table_dialog.draw(false);
    }, 200);


</script>
@endsection
