@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                @if (count($cod_fornecedor)>1)
                <th>Fornecedor</th>
                @endif
                <th>Titulo</th>
                <th class="tb_number">Parcela</th>
                <th class="sort-date">Data de Emissão</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor Original</th>
                <th class="tb_number">Valor (Saldo)</th>
                <th class="tb_number">Juros</th>
                <th class="tb_number">Juros Diários</th>
                <th class="sort-date">Início Juros</th>
                <th class="tb_number">Desconto</th>
                <th>Boleto</th>
                <th>Posição de Cobrança</th>
                <th>Banco</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento"] }}">{{ $value["estabelecimento"] }}</div></div>
                </td>
                @if (count($cod_fornecedor)>1)
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["nome_fornecedor"] }}">{{ $value["nome_fornecedor"] }}</div></div></td>
                @endif
                <td>
                    @if(!empty($value["documento_id"]))
                    <a href="#" onclick="showNotaEntradaDetalhes('{{ $value["documento_id"] }}')">{{ $value["documento"] }}</a>
                    @else
                    {{ $value["documento"] }}
                    @endif
                </td>
                <td>{{ $value["parcela"] }}</td>
                <td>{{ parserData($value["data_emissao"]) }}</td>
                <td>{!! $value["data_vencimento"] !!}</td>
                <td>{{ parserValor($value["valor_original"]) }}</td>
                <td>{{ parserValor($value["valor"]) }}</td>
                <td>{{ $value["multa"] >0?parserValor($value["multa"]):'' }}</td>
                <td>{{ $value["juros_diarios"] >0?parserValor($value["juros_diarios"]):'' }}</td>
                <td>{{ !empty($value["data_juros"])?parserData($value["data_juros"]):'' }}</td>
                <td>{{ $value["desconto"] >0?parserValor($value["desconto"]):'' }}</td>
                <td>{{ $value["numero_boleto"] }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["POSICAO_CR"]." - ".$value["POSICAO_CR_DESCRICAO"] }}">{{ $value["POSICAO_CR_DESCRICAO"] }}</div></div></td>
                <td>{{ $value['banco'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td @if (count($cod_fornecedor)>1)colspan="6" @else colspan="5" @endif class='text-right'>Totais:</td>
                <td class='tb_number'>{{ $totalizadores['valor'] }}</td>
                <td class='tb_number'>{{ $totalizadores['saldo'] }}</td>
                <td class='tb_number'>{{ $totalizadores['juros'] }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">

    $(document).ready(function(){
        $(document).find('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-notas" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
    })
    
    table_filters_dialog_titulos = $('#table-filters-dialog_titulos').DataTable({
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
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });

    function showNotaEntradaDetalhes($this){
		var url = '{{ route('notas_entradas_nasajon.nota')}}';
		var title = 'Detalhes da nota';
		var id = $this;
		xhr = $.ajax({
			url: url,
			data: {_token: "{{ csrf_token() }}", id: id},
			method: 'POST',
			success: function(body){
                if(body.status === 'error'){
                    message('Erro',body.message,'');
                }else{
                    createModal("modal_nota_entrada", title, body, 'modal-lg');
                }
			}
		});
	}
</script>
@endSection