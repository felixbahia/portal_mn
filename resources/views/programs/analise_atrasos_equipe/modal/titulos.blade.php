@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                @if (count($cod_cliente)>1)
                <th>Cliente</th>
                @endif
                <th>Título</th>
                <th class="tb_number">Parcela</th>
                <th>Cheque</th>
                <th class="sort-date">Data de Emissão</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor Original</th>
                <th class="tb_number">Juros</th>
                <th class="tb_number">Valor (Saldo)</th>
                <th class="tb_number">Juros Diários</th>
                <th class="sort-date">Início Juros</th>
                <th class="tb_number">Desconto</th>
                <th class='icone'>Nota</th>
                <th>Posição de Cobrança</th>
                <th>Banco</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento"] }}">{{ $value["estabelecimento"] }}</div></div>
                </td>
                @if (count($cod_cliente)>1)
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["nome_cliente"] }}">{{ $value["nome_cliente"] }}</div></div></td>
                @endif
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $value["numero"] !!}'>{!! $value["numero"] !!}</div></div></td>
                <td>{{ $value["parcela"] }}</td>
                <td>{{ $value["cheque"] }}</td>
                <td data-order="{{ $value["data_emissao"] }}">{{ parserData($value["data_emissao"]) }}</td>
                <td data-order="{{ $value["data_vencimento_sql"] }}">{!! $value["data_vencimento"] !!}</td>
                <td data-order="{{ $value['valor_original'] }}">{{ parserValor($value["valor_original"]) }}</td>
                <td data-order="{{ $value['juros_cobrados'] }}">{{ $value["juros_cobrados"] >0?parserValor($value["juros_cobrados"]):'' }}</td>
                <td data-order="{{ $value['valor'] }}">{{ parserValor($value["valor"]) }}</td>
                <td>{{ $value["percentual_juros_diarios"] >0?parserQtd3CasaDecimais($value["percentual_juros_diarios"]):'' }}</td>
                <td data-order="{{ $value["data_juros"] }}">{{ !empty($value["data_juros"])?parserData($value["data_juros"]):'' }}</td>
                <td data-order="{{ $value['desconto'] }}">{{ $value["desconto"] >0?parserValor($value["desconto"]):'' }}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{!! $value["nota_numero"] !!}">{!! $value["nota_numero"] !!}</div></div></td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["POSICAO_CR"]." - ".$value["POSICAO_CR_DESCRICAO"] }}">{{ $value["POSICAO_CR_DESCRICAO"] }}</div></div></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $value['banco'] }}'>{{ $value['banco'] }}</div></div></td>
                <td>@if(isset($value['observacao']))<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $value['observacao'] }}'>{{ $value['observacao'] }}</div></div>@endif</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td @if (count($cod_cliente)>1)colspan="7" @else colspan="6" @endif class='text-right'>Totais:</td>
                <td class='tb_number'>{{ $totalizadores['valor'] }}</td>
                <td class='tb_number'>{{ $totalizadores['juros'] }}</td>
                <td class='tb_number'>{{ $totalizadores['saldo'] }}</td>
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
        
        $('.devolucoes_link').popover().on('shown.bs.popover', function() {
            $(document).find('.devolucoes_alert').off('click');
            $(document).find('.devolucoes_alert').on('click', function(){
                visualizar_nota($(this).data('id'));
                $(document).find('[data-toggle="popover"]').popover('hide');
            });
        })

    })

    function visualizar_nota($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.modal.visualizar') }}',
            success: function(data){
                createModal('editar-devolucao-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }
    
    table_filters_dialog_titulos = $('#table-filters-dialog_titulos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: 'Títulos Faturados',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column >= 7 && column <= 10){
                                if(data.indexOf(".") !== -1){
                                    numero = data.replace(/\./g,'').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : '';
                                }
                            }
                            return data;
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                                if(data.indexOf(".") !== -1){
                                    numero = data.replace(/\./g,'').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : '';
                                }
                            return data;
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
            { "class": "tb_date", targets: "sort-date" },
            { "class": "tb_icone", targets: "icone"}
        ],
        "order": [[ 1, 'asc' ]]
    }).on('draw', function(){
        $('[data-toggle="tooltip"]').tooltip();
        $('[data-toggle="popover"]').popover();
    });
</script>
@endSection