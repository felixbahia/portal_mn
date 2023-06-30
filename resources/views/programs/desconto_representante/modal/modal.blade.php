@extends('layouts.page-dialog')

@section('content')

    <div id="tudo">
        <div class="row mt-3">
            <div class="col"><b>Representante:</b> {{ $representante }}</b></div>
            <div class="col"><b>Data de corte:</b> {{ $data }}</b></div>
            <div class="col text-right">
                @if($mostrar_botoes_lancamentos == true)
                {!! Form::button('Gerar parcelado', ['class' => 'btn btn-primary', 'onclick' => 'gerarParcelado("parcelado")']) !!}
                {!! Form::button('Gerar parcela única', ['class' => 'btn btn-primary', 'onclick' => 'gerarParcelado("nao_parcelado")']) !!}
                @endif
            </div>
        </div>
        <div class="row mt-3">
            <div class="col"><b>Total de desconto:</b> <a href="#" onclick='titulosDetalhes()'>{{ $total }}</a></b></div>
        </div>
        @if($tipo_usuario == 16 &&!empty($bonus))
        <div class="row mt-3">
            <div class="col"><b>Total das comissões:</b> {{ $total_sem_bonus }}</div>
        </div>
        <div class="row mt-3">
            <div class="col"><b>Adiantamento prêmio 10/2020:</b> {{ $bonus }}</div>
        </div>
        <div class="row mt-3">
            <div class="col align-right"><b>Total:</b> {{ $valor_total }}</div>
        </div>
        @endif
        <div class="content-dialog-table" style="overflow: auto">
            <div class="tab-content pt-3">
                <table class="table table-striped table-not-edit" id="table-dialog-parcelas">
                    <thead>
                        <tr>
                            <th class='tb_date'>Parcela</th>
                            <th class='tb_number'>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($result as $linha)
                        <tr>
                            <td data-order='{{ $linha['data_sql'] }}'>{{ $linha['parcela'] }}</td>
                            <td>{{ $linha['valor'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total:</td>
                            <td>{{ $valor_total }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>

<script>
	
	table_filters_itens = $('#table-dialog-parcelas').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		'paging': false,
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
				"targets": "tb_number"
            },
            {
				"class": "tb_date", 
				"targets": "tb_date"
			},
		],
		"order": [[ 0, 'asc' ]]
    });
    
    function titulosDetalhes(){
        $.ajax({
            url: '{{ route('desconto_representante.titulos')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                representante: '{{ $codigo_representante }}'
            },
            success: function(body){
                createModal("titulos_detalhes", "Detalhes dos Títulos - {{ $representante }}", body, 'modal-lg');
            }
        });
    }

    function gerarParcelado($parcelado){
        $.ajax({
            url: '{{ route('desconto_representante.lancar_debitos')}}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                representante: '{{ $codigo_representante }}',
                parcelamento: $parcelado
            },
            success: function(body){
                message('Sucesso', 'Lançamentos gerados com sucesso!');
                $(document).find('#desconto_representantes').modal('hide');
            }
        });
    }

</script>
@endsection