@extends('layouts.page-dialog')

@section('content')
<canvas id="grafico_equipe" width="400" height="90"></canvas>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-dialog-equipes">
            <thead>
                <tr>
                    <th>Equipe</th> 
                    <th class="tb_number">Meta</th>
                    <th class="tb_number">Valor Atingido</th>
                    <th class="tb_number">Atingimento %</th>
                    <th class="tb_number">Dif. Meta</th>
                    <th class="td_acao">Cliente</th>
                    <th class="td_acao">Produto</th>
                    <th class="td_acao">Devolução</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unidades as $unidade)
                    <tr>
                        <td>{{ $unidade['unidade_negocio'] }}</td> 
                        <td class="tb_number">{{ $unidade['meta'] }}</td>
                        <td class="tb_number">{{ $unidade['valor'] }}</td>
                        <td class="tb_number">{{ $unidade['atingimento_metal_porcetagem'] }}</td>
                        <td class="tb_number">{{ $unidade['diferenca_meta'] }}</td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                        <td class="td_acao"></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number" id="total_metas">{{ $total_meta }}</td>
                    <td class="tb_number" id="total_valor">{{ $total_valor }}</td>
                    <td class="tb_number" id="total_atingimento_metal_porcetagem">{{ $atingimento_metal_porcetagem }}</td>
                    <td class="tb_number" id="total_diferenca_meta">{{ $diferenca_meta }}</td>
                    <td class="td_acao" id="total_cliente"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" title="" onclick="abrirModalCliente($(this))" data-original-title="Cliente"></a></td>
                    <td class="td_acao" id="total_produto"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" title="" onclick="abrirModalProduto($(this))" data-original-title="Produto"></a></td>
                    <td class="td_acao" id="total_devolucao"><a href="#" class="bt-view" data-toggle="tooltip" data-html="true" data-filtro="{{ $filtro }}" title="" onclick="abrirModalDevolucao($(this))" data-original-title="Visualizar"></a></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var ctx = document.getElementById('grafico_equipe');
        var myBarChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels:[
                    @foreach($unidades as $unidade) 
                    "{{$unidade['unidade_negocio']}}",
                    @endforeach
                ],
                datasets: [
                    {
                        label: "Meta 2020",
                        type: "bar",
                        data: [ @foreach($unidades as $unidade) 
                        {{$unidade['meta_codigo']}},
                        @endforeach ""],
                        backgroundColor: randomColor()
                    },{
                        label: "Atingido",
                        type: "bar",
                        backgroundColor: randomColor(),
                        data: [@foreach($unidades as $unidade) 
                        {{$unidade['valor_codigo']}},
                        @endforeach ""],
                    },
                ]
            },
            options: {
                scales: {
                    xAxes: [{
                        gridLines: {
                            offsetGridLines: true
                        }
                    }]
                }
            }
        });

        table_dialog_equipes_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Registro Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Registro Encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
            ],
            "order": [[ 5, "desc" ]],
        };
        table_equipes = '';
        table_equipes = $(document).find('#table-dialog-equipes').DataTable(table_dialog_equipes_options);
        table_equipes.draw();

        setTimeout(function(){
            table_equipes.draw(false);
        }, 150);
    });

    function randomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }

</script>