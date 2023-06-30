@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table-dialog-fornecedores" style="width:100%">
        <thead>
            <tr>
                <th>Estabelcimento</th>
                <th>Numero</th>
                <th class="tb_date">Emissao</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_date">Pagamento</th>
                <th class="tb_number">Valor</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos as $value)
                <tr>
                    <td>{{ $value['estabelecimento'] }}</td> 
                    <td>{{ $value['numero'] }}</td>
                    <td><span style="display:none">{{ $value['data_emissao_codigo'] }}</span>{{ $value['data_emissao'] }}</td>
                    <td><span style="display:none">{{ $value['data_vencimento_codigo'] }}</span>{{ $value['data_vencimento'] }}</td>
                    <td><span style="display:none">{{ $value['data_baixa_codigo'] }}</span>{{ $value['data_baixa'] }}</td>
                    <td>{{ $value['valor'] }}</td>
                    <td>{{ $value['situacao'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number">Total :</td> 
                <td class="tb_number">{{ $total }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-dialog-fornecedores').DataTable({
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
                { "class": "tb_date", "targets": "tb_date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });
</script>
@endsection