@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-despesas" style="width:100%">
        <thead>
            <tr>
                <th></th> 
                <th class="tb_number">Jan</th>
                <th class="tb_number">Fev</th>
                <th class="tb_number">Mar</th>
                <th class="tb_number">Abr</th>
                <th class="tb_number">Mai</th>
                <th class="tb_number">Jun</th>
                <th class="tb_number">Jul</th>
                <th class="tb_number">Ago</th>
                <th class="tb_number">Set</th>
                <th class="tb_number">Out</th>
                <th class="tb_number">Nov</th>
                <th class="tb_number">Dez</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados['despesas_realizadas'] as $despesa_realizada)
                <tr>
                    <td>{{$despesa_realizada['descricao']}}</td> 
                    <td>{{$despesa_realizada['inteiro_1']}}</td>
                    <td>{{$despesa_realizada['inteiro_2']}}</td>
                    <td>{{$despesa_realizada['inteiro_3']}}</td>
                    <td>{{$despesa_realizada['inteiro_4']}}</td>
                    <td>{{$despesa_realizada['inteiro_5']}}</td>
                    <td>{{$despesa_realizada['inteiro_6']}}</td>
                    <td>{{$despesa_realizada['inteiro_7']}}</td>
                    <td>{{$despesa_realizada['inteiro_8']}}</td>
                    <td>{{$despesa_realizada['inteiro_9']}}</td>
                    <td>{{$despesa_realizada['inteiro_10']}}</td>
                    <td>{{$despesa_realizada['inteiro_11']}}</td>
                    <td>{{$despesa_realizada['inteiro_12']}}</td>
                    <td>{{$despesa_realizada['inteiro_total']}}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total :</td> 
                <td class="tb_number">{{$dados['total']['inteiro_1']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_2']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_3']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_4']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_5']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_6']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_7']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_8']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_9']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_10']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_11']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_12']}}</td>
                <td class="tb_number">{{$dados['total']['inteiro_total']}}</td>
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
                { "class": "tb_date", targets: "sort-date" }
            ],
        });

        setTimeout(function(){
            table_dialog.draw(false);
        }, 180);
    });
</script>
@endsection