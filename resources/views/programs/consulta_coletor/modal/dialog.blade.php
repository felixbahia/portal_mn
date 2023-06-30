@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog">
        <thead>
            <tr>
                <th>Colaborador</th>
                <th>Conferência</th>
                <th>Guarda</th>
                <th>Separação</th>
                <th>Total</th>
              
            </tr>
        </thead>
        <tbody>
           
            @foreach ($dados as $coletor_estoque)
             <tr>
                <td>{{ $coletor_estoque['colaborador'] }}</td>
               @if( $coletor_estoque['Conferencia'] >0 ) <td class="tb_number">{{ $coletor_estoque['Conferencia'] }}</td> @else  <td class="tb_number"></td> @endif
               @if(  $coletor_estoque['Guarda'] >0 ) <td class="tb_number">{{  $coletor_estoque['Guarda'] }}</td> @else  <td class="tb_number"></td> @endif
               @if( $coletor_estoque['Separação'] >0 ) <td class="tb_number">{{ $coletor_estoque['Separação'] }}</td> @else  <td class="tb_number"></td> @endif
               @if( $coletor_estoque['total'] >0 ) <td class="tb_number">{{ $coletor_estoque['total'] }}</td> @else  <td class="tb_number"></td> @endif
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
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
