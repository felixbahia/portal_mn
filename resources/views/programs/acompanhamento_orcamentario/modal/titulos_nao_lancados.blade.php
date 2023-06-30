@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th>Estabelcimento</th>
                <th>Fornecedor</th>
                <th>Nota</th>
                <th>Fatura</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retorno as $valores)
                <tr>
                    <td>{{$valores['estabelecimento']}}</td> 
                    <td><div><div data-toggle="tooltip" data-placement="right" data-html="true" title="" data-original-title="{{ $valores['fornecedor'] }}">{{$valores['fornecedor']}}</div></div></td>
                    <td><a href='#' onclick="mostrarNota('{{ $valores['id'] }}')">{{ $valores['nota'] }}</a></td>
                    <td>{{ $valores['fatura'] }}</td>
                    <td class="tb_date">{{ $valores['vencimento'] }}</td>
                    <td>{{$valores['valor']}}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td> 
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number">{{$total}}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var table_dialog = $('#table-dialog-despesas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
            "scrollCollapse": true,
            "scrollY": "70vh",
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
                { "class": "tb_date", targets: "tb_date" }
            ],
        });

        setTimeout(function(){
            table_dialog.draw(false);
        }, 180);
    });

function mostrarNota($id){
    $.ajax({
        url: '{{ route('modal.notas.importadas')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id
        },
        success: function(body){
            createModal("nota_detalhes_importada_entrada", "Detalhes da nota", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })
        }
    });
}

function duplicata($id){
    $.ajax({
        url: '{{ route('acompanhamento_orcamentario.modal.compras_titulos_duplicatas')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id
        },
        success: function(body){
            createModal("nota_detalhes_importada_entrada", "Detalhes da Fatura", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })
        }
    });
}
</script>
@endsection