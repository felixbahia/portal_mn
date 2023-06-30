@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_grupos" id="form_grupos" onsubmit="return false;">
    @csrf
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table-dialog-grupos">
                <thead>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th>Grupo</th>
                    <th>Linha</th>
                    <th>Marca</th>
                    <th class="tb_number">Quantidade</th>
                    <th class="tb_number">Valor</th>
                </thead>
                <tbody>
                    @foreach ($produtos as $produto)
                        <tr>
                            <td>{{ $produto['codigo'] }}</td>
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                            <td>{{ $produto['grupo'] }}</td>
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['linha'] }}'>{{ $produto['linha'] }}</div></div></td>
                            <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $produto['marca'] }}'>{{ $produto['marca'] }}</div></div></td>
                            <td class="tb_number">{{ $produto['quantidade'] }}</td>
                            <td class="tb_number">{{ $produto['valor'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td class="tb_number">Total :</td>
                    <td class="tb_number" id="grupo_quantidade_total">{{ $total['quantidade'] }}</td>
                    <td class="tb_number" id="grupo_valor_total">{{ $total['valor'] }}</td>
                </tfoot>
            </table>
        </div>
    </div>
</form>
<script>
    $(document).ready( function () {
        table_grupos = $('#table-dialog-grupos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollY": $height,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                if(column >= 5){
                                    if(data != ''){
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date_150", targets: "tb_date_150" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
        table_grupos.draw();


        setTimeout(function(){
            table_grupos.draw(false);
        }, 150);
    });
</script>
@endsection