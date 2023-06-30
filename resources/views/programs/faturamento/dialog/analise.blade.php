@extends('layouts.page-dialog')

@section('content')
<canvas id="faturamento_ano" width="400" height="90"></canvas>
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-dialog">
        <thead>
            <tr>
                <th>Mês</th>
                <th class="tb_number">{{ $ano_antes }}</th>
                <th class="tb_number">{{ $ano_atual }}</th>
                <th class="tb_number">% Sobre Ano Anterior</th>
                @if(!empty($valor_zerado_objetivo))
                    <th class="tb_number">Objetivo</th>
                    <th class="tb_number">% Objetivo</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($return['retorno_dados'] as $mes => $dado)
            <tr>
                <td>{{ $meses[intval($mes)] }}</td>
                <td class="tb_number">{{ $dado['ano_antes'] }}</td>
                <td class="tb_number">{{ $dado['ano_atual'] }}</td>
                <td class="tb_number">{{ $dado['porcentagem'] }}</td>
                @if(!empty($valor_zerado_objetivo))
                    <td class="tb_number">{{ $dado['objetivo'] }}</td>
                    <td class="tb_number">{{ $dado['objetivo_atingido'] }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            @if(intval($mes_atual)-1 > 0)
            <tr>
                <td><b>Total até: {{ $meses[intval($mes_atual)-1] }}</b></td>
                <td class="tb_number">{{ $return['total_mes_anterior']['ano_antes'] }}</td>
                <td class="tb_number">{{ $return['total_mes_anterior']['ano_atual'] }}</td>
                <td class="tb_number">{{ $return['total_mes_anterior']['porcentagem'] }}</td>
                @if(!empty($valor_zerado_objetivo))
                    <td class="tb_number">{{ $return['total_mes_anterior']['objetivo'] }}</td>
                    <td class="tb_number">{{ $return['total_mes_anterior']['objetivo_atingido'] }}</td>
                @endif
            </tr>
            @endif
            <tr>
                <td><b>Total até: {{ $meses[intval($mes_atual)] }}</b></td>
                <td class="tb_number">{{ $return['total_mes']['ano_antes'] }}</td>
                <td class="tb_number">{{ $return['total_mes']['ano_atual'] }}</td>
                <td class="tb_number">{{ $return['total_mes']['porcentagem'] }}</td>
                @if(!empty($valor_zerado_objetivo))
                    <td class="tb_number">{{ $return['total_mes']['objetivo'] }}</td>
                    <td class="tb_number">{{ $return['total_mes']['objetivo_atingido'] }}</td>
                @endif
            </tr>
            <tr>
                <td><b>Total do ano</b></td>
                <td class="tb_number">{{ $return['total_ano']['ano_antes'] }}</td>
                <td class="tb_number">{{ $return['total_ano']['ano_atual'] }}</td>
                <td class="tb_number">{{ $return['total_ano']['porcentagem'] }}</td>
                @if(!empty($valor_zerado_objetivo))
                    <td class="tb_number">{{ $return['total_ano']['objetivo'] }}</td>
                    <td class="tb_number">{{ $return['total_ano']['objetivo_atingido'] }}</td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        var ctx = document.getElementById('faturamento_ano');
        var myLineChart = new Chart(ctx, {
            type: 'line',
            data: parserDados(),
            options: {
                responsive: true,
                hoverMode: 'index',
                stacked: false,
                title: {
                    display: false
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        label: function(tooltipItem, data) {
                            var label = data.datasets[tooltipItem.datasetIndex].label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += (parseFloat(tooltipItem.value)).toFixed(2).replace('.', ',').replace(/(\d)(?=(\d{3})+\,)/g, "$1.");
                            return label;
                        }
                    }
                },
				plugins: {
					datalabels: {
						display: false
					}
				}
            }
        });
    });
    function parserDados(){
        resultado = {!! json_encode($return['retorno_tabela']) !!};
        label_tabela = {!! json_encode($return['label_tabela']) !!};
        $label = [];
        $.each(label_tabela, function(k, value){
            $label.push(value);
        });
        $return = {labels: $label, datasets: []};
        var $i = 1;
        $.each(resultado, function(ano, value){
            $dados = [];
            $.each(value, function(key, val){
                $dados.push(parseFloat(val));
            });
            $cor = randomColor();
            $dados = {
                "label": ano,
                "data": $dados,
                "fill":false,
                "borderColor":"rgb(75, 192, 192)",
                "lineTension":0.1,
                backgroundColor: $cor,
                borderColor: $cor
            };
            $return.datasets.push($dados);
            $i++;
        });
        return $return;
    }
    function randomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
      
</script>
@endsection
