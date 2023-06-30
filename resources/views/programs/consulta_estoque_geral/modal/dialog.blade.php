@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped" id="table_consulta_estoque_dialog">
        <thead>
            <tr>
                
                <th rowspan='2' class="align-middle">Grupo</th>
                <th rowspan='2' class="align-middle">Subgrupo</th>
                <th rowspan='2' class="align-middle">Linha</th>
                <th rowspan='2' class="align-middle">Marca</th>
                <th rowspan='2' class="align-middle tb_number">Qtd Uni.</th>
                <th rowspan='2' class="align-middle tb_number">Uni.</th>
                <th colspan='3' class="align-middle border-right">Contábil (Nasajon)</th>
                <th colspan='3' class="align-middle border-right">Contábil Médio (Portal)</th>
                <th colspan='3' class="align-middle border-right">Gerencial Médio (Portal)</th>
                <th colspan='3' class="align-middle">Gerencial (Portal)</th>
                <th colspan='3' class="align-middle">Médio  Armazém </th>
            </tr>
            <tr>
                <th class="tb_number">Custo R$</th>
                <th class="tb_number">Total R$</th>
                <th class="tb_number border-right">Total %</th>

                <th class="tb_number">Custo R$</th>
                <th class="tb_number">Total R$</th>
                <th class="tb_number border-right">Total %</th>

                <th class="tb_number">Custo R$</th>
                <th class="tb_number">Total R$</th>
                <th class="tb_number border-right">Total %</th>

                <th class="tb_number">Custo R$</th>
                <th class="tb_number">Total R$</th>
                <th class="tb_number">Total %</th>

                <th class="tb_number">Custo R$</th>
                <th class="tb_number">Total R$</th>
                <th class="tb_number">Total %</th>
            </tr>
        </thead>
        <tbody>
           @foreach($estabelecimentos as $values)
            <tr>
                
                <td>{!! $values['grupo'] !!}</td>
                <td>{!! $values['subgrupo'] !!}</td>
                <td>{!! $values['linha'] !!}</td>
                <td>{!! $values['marca'] !!}</td>
                <td>{{ $values['qtd_unidade'] }}</td>
                <td>{{ $values['unidade'] }}</td>

                <td>{{ $values['custo_unitario_contabil'] }}</td>
                <td id="custo_contabil">{{ $values['custo_contabil'] }}</td>
                <td>{{ $values['valorPorcentContabil'] }}</td>

                <td>{{ $values['custo_unitario_contabil_portal'] }}</td>
                <td id="custo_contabil">{{ $values['custo_contabil_portal']}}</td>
                <td>{{ $values['valorPorcentContabil_portal'] }}</td>

                <td>{{ $values['custo_unitario_gerencial_portal'] }}</td>
                <td id="custo_contabil">{{ $values['custo_gerencial_portal'] }}</td>
                <td>{{ $values['valorPorcentGerencial_portal'] }}</td>

                <td>
                    {{ $values['custo_unitario_gerencial'] }}
                    <a href="#" class="bt-edit-money" title='Editar preço' data-hash='{{ $values['hash'] }}' onclick="modal($(this).data('hash'))"></a>
                </td>
                <td id="custo_gerencial">{{ $values['custo_gerencial'] }}</td>
                <td>{{ $values['valorPorcentGerencial'] }}</td>
                <td>{{ $values['custo_unitario_armazem'] }}</td>
                <td id="custo_armazem">{{ $values['custo_armazem'] }}</td>
                <td>{{ $values['valorPorcentArmazem'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <th class='text-left'>Total:</th>
            <td class="tb_number number_format">&nbsp;</td>
            <td colspan="5"></td>
            <td class="tb_number number_format">&nbsp;</td>
            <td class="tb_number number_format">R$ {{ parserValor($totalCustoContabil)}}</td>
            <td colspan='2'></td>
            <td class="tb_number number_format">R$ {{ parserValor($totalCustoContabil_portal)}}</td>
            <td colspan='2'></td>
            <td class="tb_number number_format">R$ {{ parserValor($totalCustoGerencial_portal)}}</td>
            <td colspan='2'></td>
            <td class="tb_number number_format">R$ {{ parserValor($totalCustoGerencial)}}</td>
            <td colspan='2'></td>
            <td class="tb_number number_format">R$ {{ parserValor($totalCustoArmazem)}}</td>
        </tfoot>
    </table>
</div>

<script type="text/javascript">

    $(document).ready( function(){
        table_dialog_estoque.on('draw', function () {
            $(document).find('[data-toggle="tooltip"]').tooltip();
        } );

        setTimeout(function(){
            table_dialog_estoque.columns.adjust().draw();
        }, 500);
    })

    table_dialog_estoque = $('#table_consulta_estoque_dialog').DataTable({
        "searching": false,
        "paging": true,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
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
        "columnDefs": [
            { 
                "targets": 'tb_number',
                "class": 'number_format',
                "width": '5px' 
            },
            {
                "targets": "tb_estabelecimento",
                "width": '5%',
                "orderable": false,
            },
            {
                "targets": 1,
                "width": "15%"
            }
        ],
        "order": [[ 1, "asc" ],[2, "asc" ], [3, "asc" ], [4, "asc" ], [5, "asc" ]]

    });

    function modal($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.editar") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_editar_precos', 'Editar preços', data, 'modal-lg');
            },
            error: function(){
                message("Atenção", "Ocorreu uma instabilidade!<br> Tente novamente mais tarde!");
            }
        });
    }

    function modalProdutos($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.produtos") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_produtos', 'Produtos contidos neste agrupamento', data, 'modal-lg');
            },
            error: function(){
                message("Atenção", "Ocorreu uma instabilidade!<br> Tente novamente mais tarde!");
            }
        });
    }


</script>
@endsection
