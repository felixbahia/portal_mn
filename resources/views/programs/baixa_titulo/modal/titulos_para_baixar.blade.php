@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
    <form action="#" name="form_filter_titulos_para_baixar" id="form_filter_titulos_para_baixar" onsubmit="return false;">
        @csrf
        {!! Form::hidden('codigo', $codigo, ["id" => 'codigo']) !!}
        {!! Form::hidden('unico', $unico, ["id" => 'unico']) !!}
        <div class="content-fields">
            <div class="col-lg-4">
                <input type="text" name="titulo" id="titulo" value="" placeholder="Titulo" maxlength="250" />
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform-baixa-titulos" id="btn-filterform-baixa-titulos" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th>Estab.</th>
                @if (count($cod_cliente)>1)
                <th>Cliente</th>
                @endif
                <th>Título</th>
                <th class="tb_number">Pa.</th>
                <th class="sort-date">Data de Emissão</th>
                <th class="sort-date">Data de Vencimento</th>
                <th class="tb_number">Valor Original</th>
                <th class="tb_number">Juros</th>
                <th class="tb_number">Valor (Saldo)</th>
                <th class="tb_number">Juros Diários</th>
                <th class="sort-date">Início Juros</th>
                <th class="tb_number">Desconto</th>
                <th class='icone'>Nota</th>
                <th>Baixar</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento_nome"] }}">{{ $value["estabelecimento"] }}</div></div>
                </td>
                @if (count($cod_cliente)>1)
                    <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["nome_cliente"] }}">{{ $value["nome_cliente"] }}</div></div></td>
                @endif
                <td>{!! $value["numero"] !!}</td>
                <td>{{ $value["parcela"] }}</td>
                <td>{{ parserData($value["data_emissao"]) }}</td>
                <td>{!! $value["data_vencimento"] !!}</td>
                <td>{{ parserValor($value["valor_original"]) }}</td>
                <td>{{ $value["juros_cobrados"] >0?parserValor($value["juros_cobrados"]):'' }}</td>
                <td>{{ parserValor($value["valor"]) }}</td>
                <td>{{ $value["percentual_juros_diarios"] >0?parserQtd3CasaDecimais($value["percentual_juros_diarios"]):'' }}</td>
                <td>{{ !empty($value["data_juros"])?parserData($value["data_juros"]):'' }}</td>
                <td>{{ $value["desconto"] >0?parserValor($value["desconto"]):'' }}</td>
                <td>{!! $value["nota_numero"] !!}</td>
                <td><div class="btn-pedido" data-toggle="tooltip" data-trigger='hover' title="Baixar Título" data-title="Baixar do Título: {{ $value["numero"] }}" data-titulo_id="{{ $value['titulo_id'] }}"  onclick="showModalBaixaTitulo($(this))"></div></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td @if (count($cod_cliente)>1)colspan="6" @else colspan="5" @endif class='text-right'>Totais:</td>
                <td class='tb_number' id="totalizador_valor" name="totalizador_valor">{{ $totalizadores['valor'] }}</td>
                <td class='tb_number' id="totalizador_juros" name="totalizador_juros">{{ $totalizadores['juros'] }}</td>
                <td class='tb_number' id="totalizador_saldo" name="totalizador_saldo">{{ $totalizadores['saldo'] }}</td>
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

        table_filters_dialog_titulos = $('#table-filters-dialog_titulos').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "autoWidth": false,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" },
                { "class": "tb_icone", targets: "icone"}
            ],
            "order": [[ 1, 'asc' ]]
        }).on('draw', function(){
            $('[data-toggle="tooltip"]').tooltip();
            $('[data-toggle="popover"]').popover();
        });

        table_filters_dialog_titulos.draw();

        $(document).find("#btn-filterform-baixa-titulos").on("click", function(){
            filterClearModalTitulosBaixa();
            filterAjaxModalTitulosBaixa();
        });
    })

    function showModalBaixaTitulo($this){
        var title = $this.data("title");
        var titulo_id = $this.data("titulo_id");
        $.ajax({
            url: '{{ route('baixar_titulo.modal.baixar_titulo') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                titulo_id: titulo_id,
            },
            success: function (body){
                createModal('modal_baixar_titulos',  title, body, '');
            },
            error: function (callback){
                message("Atenção", callback.responseJSON.message);
            }
        }); 
    }

    function filterAjaxModalTitulosBaixa(){
        form = $(document).find("#form_filter_titulos_para_baixar");
        data_form = form.serialize();
        filterClearModalTitulosBaixa();
        $.ajax({
            url: '{{ route('baixar_titulo.filtro_titulos_para_baixar')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                titulos = [];
                
                for (var fields in data.response.titulos_faturados){
                    temp_array = [
                        ajusteTamanhoTable(data.response.titulos_faturados[fields].estabelecimento_nome),
                        @if (count($cod_cliente)>1)
                        ajusteTamanhoTable(data.response.titulos_faturados[fields].nome_cliente),
                        @endif
                        data.response.titulos_faturados[fields].numero,
                        data.response.titulos_faturados[fields].parcela,
                        data.response.titulos_faturados[fields].data_emissao,
                        data.response.titulos_faturados[fields].data_vencimento,
                        data.response.titulos_faturados[fields].valor_original,
                        data.response.titulos_faturados[fields].juros_cobrados,
                        data.response.titulos_faturados[fields].valor,
                        data.response.titulos_faturados[fields].percentual_juros_diarios,
                        data.response.titulos_faturados[fields].data_juros,
                        data.response.titulos_faturados[fields].desconto,
                        data.response.titulos_faturados[fields].nota_numero,
                        btnBaixa(data.response.titulos_faturados[fields]),
                    ];
                    titulos.push(temp_array)
                }
                table_filters_dialog_titulos.rows.add(titulos).draw();    
                
                $('.dataTables_scrollFootInner').find('#totalizador_valor').html(data.response.totalizadores.valor);
                $('.dataTables_scrollFootInner').find('#totalizador_juros').html(data.response.totalizadores.juros);
                $('.dataTables_scrollFootInner').find('#totalizador_saldo').html(data.response.totalizadores.saldo);
                hide_loader();
            }
        });
    }
    
    function filterClearModalTitulosBaixa(){
        table_filters_dialog_titulos.clear().draw();
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function btnBaixa($value){
        $html ='<div class="btn-pedido" data-toggle="tooltip" data-trigger=\'hover\' title="Baixar Título" data-title="Baixar do Título: ' + $value.numero + '" data-titulo_id="'+$value.titulo_id+'"  onclick="showModalBaixaTitulo($(this))"></div>';
    
        return $html;
    }
</script>