@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th>Duplicata</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($retorno as $valores)
                <tr>
                    <td>{{$valores['duplicata']}}</td> 
                    <td class="tb_date">{{$valores['vencimento']}}</td>
                    <td class="tb_number">{{$valores['valor']}}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total :</td> 
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
</script>
@endsection