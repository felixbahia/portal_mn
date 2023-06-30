@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog">
        <thead>
            <tr>
                <th>Proprietário</th>
                <th>Detentor</th>
                <th>Produto</th>
                <th>Documento Fiscal</th>
                <th>Tipo Documento</th>
                <th class="tb_date">Data Hora</th>
                <th>Ação</th>
                <th>Usuário</th>
                <th class="tb_number">Quantidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $ajuste_estoque)
             <tr>
                <td>{{ $ajuste_estoque['proprietario'] }}</td>
                <td>{{ $ajuste_estoque['detentor'] }}</td>
                <td>{{ $ajuste_estoque['produto'] }}</td>
                <td>
                    @if($ajuste_estoque['tipo_documento'] == 'Saída')
                        <a href="#"  data-toggle="tooltip" data-placement="top" onclick="abrirNotaSaida('{{ $ajuste_estoque['id_documento'] }}','{{ $ajuste_estoque['documento'] }}');">{{ $ajuste_estoque['documento'] }}</a>
                    @elseif($ajuste_estoque['tipo_documento'] == 'Entrada')
                        <a href="#"  data-toggle="tooltip" data-placement="top" onclick="abrirNotaEntrada('{{ $ajuste_estoque['id_documento'] }}','{{ $ajuste_estoque['documento'] }}');">{{ $ajuste_estoque['documento'] }}</a>
                    @else
                        {{ $ajuste_estoque['documento'] }}
                    @endif
                </td>
                <td>{{ $ajuste_estoque['tipo_documento'] }}</td>
                <td>{{ $ajuste_estoque['data'] }}</td>
                <td>{{ $ajuste_estoque['acao'] }}</td>
                <td>{{ $ajuste_estoque['usuario'] }}</td>
                <td>{{ $ajuste_estoque['quantidade'] }}</td>
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>{{ $total}}</td>
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
        "autoWidth": false,
        "language": {
            "decimal":        ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ",",
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
                "targets": "tb_number",
            },
            { 
                "class": "tb_date", 
                "targets": "tb_date" 
            },
        ],
        "order": [[ 5, "desc" ]]
    });

    setTimeout(function(){
        table_dialog.draw(false);
    }, 200);

function abrirNotaSaida($id, $nota){
    $.ajax({
        url: '{{ route('notas_nasajon.modal.exibir')}}',
        type: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            id_nota: $id,
            link_pedido: true,
            origem: 'NASAJON'
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota: " + $nota, body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}

function abrirNotaEntrada($id){
    $.ajax({
        url: '{{ route('notas_entradas_nasajon.nota')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id,
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
        }

    });
}
</script>
@endsection
