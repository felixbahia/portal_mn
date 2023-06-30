@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_titulos">
        <thead>
            <tr>
                <th rowspan='2'>Estab.</th>
                @if (count($cod_cliente)>1)
                <th rowspan='2'>Cliente</th>
                @endif
                <th rowspan='2'>Status</th>
                <th rowspan='2'>Título</th>
                <th rowspan='2' class="tb_number">Pa.</th>
                <th rowspan='2' class="sort-date">Data de Emissão</th>
                <th rowspan='2' class="sort-date">Data de Vencimento</th>
                <th rowspan='2' class="tb_number">Valor Original</th>
                <th rowspan='2' class="tb_number">Pagamento parcial</th>
                <th rowspan='2' class="tb_number">Valor atual</th>
                <th rowspan='2' class="tb_number">Juros</th>
                <th rowspan='2' class="tb_number">Valor (Saldo)</th>
                <th rowspan='2' class="tb_number">Juros Diários</th>
                <th rowspan='2' class="sort-date">Início Juros</th>
                <th rowspan='2' class="tb_number">Desconto</th>
                <th rowspan='2' class='icone'>Nota</th>
                <th rowspan='2'>Posição de Cobrança</th>
                <th rowspan='2'>Banco</th>
                <th colspan='2'>Título renegociado</th>
                <th rowspan='2'>Observação</th>
                @if(in_array(Auth::id(), [82, 97, 1386, 1388, 1464, 8313, 95, 112,57,682,42, 10330, 27, 12541]) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Juridico'))
                <th rowspan='2'>Cenprot</th>
                @endif
                <th rowspan='2' class="sort-date">Renegociação</th>
                @if(in_array(Auth::id(), [42]) || Auth::user()->tipo_usuario_id == 1)
                    <th rowspan='2' class='icone'></th>
                @endif
            </tr>
            <tr>
                <th>Número</th>
                <th class="sort-date">Data de vencimento</th>
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
                <td class='tb_status'>{!! $value['status'] !!}</td>
                <td>{!! $value["numero"] !!}</td>
                <td>{{ $value["parcela"] }}</td>
                <td>{{ parserData($value["data_emissao"]) }}</td>
                <td>{!! $value["data_vencimento"] !!}</td>
                <td>{{ parserValor($value["valor_original"]) }}</td>
                <td>{{ $value["pagamento_parcial"] > 0 ? parserValor($value["pagamento_parcial"]) : '' }}</td>
                <td>{{ parserValor($value["valor_atual"]) }}</td>
                <td>{{ $value["juros_cobrados"] >0?parserValor($value["juros_cobrados"]):'' }}</td>
                <td>{{ parserValor($value["valor"]) }}</td>
                <td>{{ $value["percentual_juros_diarios"] >0?parserQtd3CasaDecimais($value["percentual_juros_diarios"]):'' }}</td>
                <td>{{ !empty($value["data_juros"])?parserData($value["data_juros"]):'' }}</td>
                <td>{{ $value["desconto"] >0?parserValor($value["desconto"]):'' }}</td>
                <td>{!! $value["nota_numero"] !!}</td>
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $value["POSICAO_CR"]." - ".$value["POSICAO_CR_DESCRICAO"] }}">{{ $value["POSICAO_CR_DESCRICAO"] }}</div></div></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $value['banco'] }}'>{{ $value['banco'] }}</div></div></td>
                <td>{{ $value["numero_titulo_renegociado"] }}</td>
                <td>{{ $value["vencimento_titulo_renegociado"] }}</td>
                <td>@if(isset($value['observacao']))<div><div data-toggle="tooltip" data-html="true" title="{{ $value['observacao'] }}">{{ $value['observacao'] }}</div></div>@endif</td>
                @if(in_array(Auth::id(), [82, 97, 1386, 1388, 1464, 8313, 95, 112,57,682,42, 10330, 27, 12541]) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Juridico'))
                <td>{!! $value['cenprot'] !!}</td>
                @endif
                <td>
                    @if($value['titulo_renegociado'] == false && $value['observacao'] == 'Renegociação pelo portal')
                        <div id="{{ $value["id_titulo"] }}"><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não Liberado'> <i class='fa fa-times error-icon' aria-hidden='true'></i></div></div>
                    @else
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Liberado'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
                    @endif
                </td>
                @if(in_array(Auth::id(), [42]) || Auth::user()->tipo_usuario_id == 1)
                    <td>
                        @if($value['titulo_renegociado'] == false && $value['observacao'] == 'Renegociação pelo portal')
                            <div id="btn_{{ $value["id_titulo"] }}">
                                <a data-toggle='tooltip' data-html='true' data-id_titulo="{{ $value["id_titulo"] }}" class="liberar-titulo" title='' data-original-title='Liberar'> <i class="bt-aprove" aria-hidden='true'></i></a>
                            </div>
                        @endif
                    </td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td @if (count($cod_cliente)>1)colspan="7" @else colspan="6" @endif class='text-right'>Totais:</td>
                <td class='tb_number'>{{ $totalizadores['valor'] }}</td>
                <td class='tb_number'>{{ $totalizadores['pagamento_parcial'] }}</td>
                <td class='tb_number'>{{ $totalizadores['valor_atual'] }}</td>
                <td class='tb_number'>{{ $totalizadores['juros'] }}</td>
                <td class='tb_number'>{{ $totalizadores['saldo'] }}</td>
            </tr>
            <tr>
                <td  @if (count($cod_cliente)>1)colspan="12" @else colspan="11" @endif><h5>Legenda:</h5>

                    <span class="status-titulo status-verde">P</span> - Prorrogado<br>
                    <span class="status-titulo status-roxo">CJ</span> - Cobrança Jurídica - Título vencidos há mais de trinta dias<br>    
                    <span class="status-titulo status-amarelo">CA</span> - Cobrança Administrativa Ragazzi - Título vencidos de dez a trinta dias<br>
                    @if(!Auth::user()->hasRole('Juridico') || Auth::user()->codigo_representante != '998')
                    <span class="status-titulo status-laranja">CM</span> - Cobrança Administrativa MN - Título vencidos de dez a trinta dias<br>
                    @endif
                    <span class="status-titulo status-azul">D</span> - Com processo de devolução<br>
                    <span class="status-titulo status-vermelho">C</span> - Cartório - Título em cartório<br>
                    <br>
                </td>
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

        $(document).find('.liberar-titulo').off('click');
        $(document).find('.liberar-titulo').on('click', function(){
            liberarTituloRenegociado($(this));   
        });

        table_filters_dialog_titulos.on('draw', function(){
            $('.devolucoes_link').popover().on('shown.bs.popover', function() {
                $(document).find('.devolucoes_alert').off('click');
                $(document).find('.devolucoes_alert').on('click', function(){
                    visualizar_devolucao($(this).data('id'));
                    $(document).find('[data-toggle="popover"]').popover('hide');
                });
            })
        });

        table_filters_dialog_titulos.draw();
    })
    
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

    function visualizar_devolucao($id){
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

    function enviarCenprot($value){
        var titulo = $value.data("titulo");
        var cenprot_id = $value.data("cenprot_id");
        var $class = "dialog_option_enviar_cenprot";
        var $name_option_ok_envio_cenprot = "ok_enviar_cenprot";
        var $name_option_cancelar_envio_cenprot = "cancelar_enviar_cenprot";

        $(document).off("ok_enviar_cenprot");
        $(document).on("ok_enviar_cenprot", function(){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ route('cenprot.enviar_titulo') }}',
                type: 'POST',
                async: false,
                data: {
                    _token: '{{ csrf_token() }}',
                    titulo: titulo,
                    cenprot_id: cenprot_id,
                },
                success: function (body){
                    message("Atenção", "Título enviado com Sucesso!");
                    hide_loader();
                    filterClearModal();
                    filterAjaxModal();
                },
                error: function (callback){
                    message("Atenção", callback.responseJSON.message);
                }
            }); 
        });

        $(document).off("cancelar_enviar_cenprot");
        $(document).on("cancelar_enviar_cenprot", function(){
            return null; 
        });
        message_option("Atenção", "Deseja enviar o título " + titulo + " para CENPROT", $class, $name_option_ok_envio_cenprot, '', $name_option_cancelar_envio_cenprot, '');
        esconderPopoverTooltip();
    }

    function removerCenprot($value){
        var titulo = $value.data("titulo");
        var cenprot_id = $value.data("cenprot_id");
        var $class = "dialog_option_retirar_cenprot";
        var $name_option_ok_retirar_cenprot = "ok_retirar_cenprot";
        var $name_option_cancelar_retirar_cenprot = "cancelar_retirar_cenprot"; 

        $(document).off("ok_retirar_cenprot");
        $(document).on("ok_retirar_cenprot", function(){
            esconderPopoverTooltip();
            $.ajax({
                url: '{{ route('cenprot.remover_titulo') }}',
                type: 'POST',
                async: false,
                data: {
                    _token: '{{ csrf_token() }}',
                    titulo: titulo,
                    cenprot_id: cenprot_id,
                },
                success: function (body){
                    hide_loader();
                    filterClearModal();
                    filterAjaxModal();
                    message("Atenção", "Título removido do Protesto com Sucesso!");
                },
                error: function (callback){
                    message("Atenção", callback.responseJSON.message);
                }
            });
        });

        $(document).off("cancelar_retirar_cenprot");
        $(document).on("cancelar_retirar_cenprot", function(){
            return null; 
        });
        message_option("Atenção", "Deseja retirar o título " + titulo + " para CENPROT", $class, $name_option_ok_retirar_cenprot, '', $name_option_cancelar_retirar_cenprot, '');
        esconderPopoverTooltip();
    }
    
    function filterClearModal(){
        table_filters_dialog_titulos.clear().draw();
    }

    function filterAjaxModal(){
        var codigo = '{{ $codigo }}';
        var unico = '{{ $unico }}';
        var coluna = '{{ $coluna }}';
        loader();
        $.ajax({
            url: '{{ route('cliente.posicao_sintetica.filter_titulos_faturados')}}',
            data: {
                _token: '{{csrf_token()}}',
                codigo: codigo,
                unico: unico,
                coluna: coluna,
            },
            method: 'POST',
            async: false,
            success: function(data){
                linhas = [];
                
                for (var fields in data.response){
                    temp_array = [
                        ajusteTamanhoTable(data.response[fields].estabelecimento_nome),
                        @if (count($cod_cliente)>1)
                        ajusteTamanhoTable(data.response[fields].nome_cliente),
                        @endif
                        data.response[fields].status,
                        data.response[fields].numero,
                        data.response[fields].parcela,
                        data.response[fields].data_emissao,
                        data.response[fields].data_vencimento_sql,
                        data.response[fields].valor_original,
                        data.response[fields].juros_cobrados,
                        data.response[fields].valor,
                        data.response[fields].percentual_juros_diarios,
                        data.response[fields].data_juros,
                        data.response[fields].desconto,
                        data.response[fields].nota_numero,
                        ajusteTamanhoTable(data.response[fields].POSICAO_CR_DESCRICAO),
                        ajusteTamanhoTable(data.response[fields].banco),
                        data.response[fields].numero_titulo_renegociado,
                        data.response[fields].vencimento_titulo_renegociado,
                        ajusteTamanhoTable(data.response[fields].observacao),
                        @if(in_array(Auth::id(), [82, 97, 1386, 1388, 1464, 8313, 95, 112,57,682,42, 10330, 27, 12541]) || Auth::user()->hasRole('Administradores') || Auth::user()->hasRole('Juridico'))
                        data.response[fields].cenprot,
                        @endif
                    ];
                    linhas.push(temp_array)
                }
                table_filters_dialog_titulos.rows.add(linhas).draw();            
                hide_loader();
            }
        });

        esconderPopoverTooltip();
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function esconderPopoverTooltip(){
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('[data-toggle="popover"]').popover('hide');
    }

    function modalCenprotHistorico($value){
        var titulo = $value.data("titulo");
        var cliente = $value.data("cliente");
        var cenprot_id = $value.data("cenprot_id");
        esconderPopoverTooltip();
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                titulo: titulo,
                cenprot_id: cenprot_id,
            },
            url: '{{ route('cenprot.modal.historico') }}',
            success: function(data){
                createModal('cenprot_historico_modal', 'CENPROT - Histórico do Título: '+titulo+' - '+cliente, data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function liberarTituloRenegociado($this){
        var id_titulo = $this.data("id_titulo");
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_titulo: id_titulo
            },
            url: '{{ route("cliente.posicao_sintetica.liberar_titulos") }}',
            success: function(data){
                html = "<div data-toggle='tooltip' data-html='true' title='' data-original-title='Liberado'> <i class='fa fa-check check-icon' aria-hidden='true'></i></div>"
                $(document).find('#'+id_titulo).html('');
                $(document).find('#'+id_titulo).html(html);
                $(document).find('#btn_'+id_titulo).html('');
                message('Atenção!', 'Liberado com sucesso!')
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

</script>
@endSection