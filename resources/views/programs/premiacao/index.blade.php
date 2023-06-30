@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            @if(in_array(Auth::user()->tipo_usuario_id, [12, 16]))
                <div class="col-lg-6"> 
                    {{ Form::text('mes_ano', $data, ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
                </div>
                <div class="col-lg-6"> 
                    {{ Form::select('unidade_negocio', $unidades_negocios, '', ['id' => 'unidade_negocio', 'class' => 'form-control input-label', 'placeholder' => 'Selecione Unidade Negócio', 'maxlength' => '250']) }}
                </div>
                {!! Form::hidden('vendedor', Auth::user()->codigo_representante, ['id' => 'vendedor']) !!}
            @else
                <div class="col-lg-4"> 
                    {{ Form::text('mes_ano', $data, ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
                </div>
                <div class="col-lg-4"> 
                    {{ Form::select('unidade_negocio', $unidades_negocios, '', ['id' => 'unidade_negocio', 'class' => 'form-control input-label', 'placeholder' => 'Selecione Unidade Negócio', 'maxlength' => '250']) }}
                </div>
                <div class="col-lg-4"> 
                    {!! Form::select('vendedor', $representantes,'', ['id' => 'vendedor', 'class' => 'form-control', 'placeholder' => 'Todos os Vendedores']) !!}
                </div>
            @endif 
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-produto-analise" id="table-filters">
        <thead>
            <tr>
                <th>Equipe</th>
                <th class="tb_number">Meta</th>
                <th class="tb_number">Faturado</th>
                <th class="tb_number">% Atingimento</th>
                <th class="tb_number">Comissão</th>
                <th class="tb_number">Prêmio Valor</th>
                <th class="tb_number">Devolução</th>
                @if(in_array(Auth::user()->tipo_usuario_id, [16]))
                    <th class="tb_number">Prêmio Equipe</th>
                @endif
                <th class="tb_number">Prêmio a Pagar</th>
                @if(in_array(Auth::user()->tipo_usuario_id, [12,16]))
                    <th>Conf.</th>
                    <th class="tb_acao"></th>
                @endif
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total:</td>
                <td class="tb_number" id="total_meta"></td>
                <td class="tb_number" id="total_faturado"></td>
                <td class="tb_number" id="total_atingimento"></td>
                <td class="tb_number" id="total_comissa"></td>
                <td class="tb_number" id="total_premio"></td>
                <td class="tb_number" id="total_devolucao"></td>
                @if(in_array(Auth::user()->tipo_usuario_id, [16]))
                    <td class="tb_number" id="total_equipe"></td>
                @endif
                <td class="tb_number" id="total_premio_a_pagar"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        form = $(document).find("#form_filter");

        form.find("#btn-filterform").off("click");
        form.find("#btn-filterform").on("click", function(){
            filterClear();
            filterAjax();
        });

        form.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form.find('.data').mask('00/0000');

        table_filters.destroy();
        table_filters = $('#table-filters').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                }
            ],
        });

        table_filters.on('draw', function () {
            $(document).find(".bt-aprove").off("click");
            $(document).find(".bt-aprove").on("click", function(event){
                event.stopPropagation();
                aprovarPremio($(this));
            });
        });

        $("#btn-gerar_folha").off("click");
        $("#btn-gerar_folha").on("click", function(){
            showGerarFolha();
        });
    });

    function filterClear(){
        table_filters.clear().draw();
    }

    function filterAjax(){
        filterClear();
        form = $(document).find("#form_filter");
        data_form = form.serialize();
        filterClear();
        $.ajax({
            @if(in_array(Auth::user()->tipo_usuario_id, [12, 16]))
            url: '{{ route('premiacao.filtro_representante')}}',
            @else
            url: '{{ route('premiacao.filtro')}}',
            @endif
            data: data_form,
            method: 'POST',
            success: function(data){
                linhas = [];
                
                for (var unidade in data.response.premiacaos){
                    temp_array = [
                        @if(in_array(Auth::user()->tipo_usuario_id, [12, 16]))
                            data.response.premiacaos[unidade].unidade,
                        @else
                            createBtViewMembro(data.response.premiacaos[unidade], data.response.mes_ano, data.response.vendedor),
                        @endif
                        data.response.premiacaos[unidade].meta_valor,
                        data.response.premiacaos[unidade].faturado_valor,
                        data.response.premiacaos[unidade].atingimento_meta_porcetagem,
                        data.response.premiacaos[unidade].comissao_valor,
                        data.response.premiacaos[unidade].premio_valor,
                        data.response.premiacaos[unidade].devolucao,
                        @if(in_array(Auth::user()->tipo_usuario_id, [16]))
                            data.response.premiacaos[unidade].premio_equipe,
                        @endif
                        data.response.premiacaos[unidade].premio_a_pagar,
                        @if(in_array(Auth::user()->tipo_usuario_id, [12,16]))
                            createBtConfirmacao(data.response.premiacaos[unidade]),
                            createBtAprovarPremio(data.response.premiacaos[unidade])
                        @endif
                    ];
                    linhas.push(temp_array);
                }
                table_filters.rows.add(linhas).draw();

                $(document).find('#total_meta').html(data.response.total.meta_valor);
                $(document).find('#total_faturado').html(data.response.total.faturado_valor);
                $(document).find('#total_atingimento').html(data.response.total.atingimento_meta_porcetagem);
                $(document).find('#total_comissa').html(data.response.total.comissao_valor);
                $(document).find('#total_premio').html(data.response.total.premio_valor);
                $(document).find('#total_devolucao').html(data.response.total.devolucao);
                $(document).find('#total_premio_a_pagar').html(data.response.total.premio_a_pagar);
                @if(in_array(Auth::user()->tipo_usuario_id, [16]))
                    $(document).find('#total_equipe').html(data.response.total.premio_equipe);
                @endif
            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

    function createBtViewEquipeIndividual($value, $filtro, $title){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-filtro=\""+$filtro+"\" data-id_unidade_negocio=\""+$value.id+"\" title='Visualizar do Gráfico' data-title=\""+$title+"\" onclick=\"abrirModalEquipeIndividual($(this))\">"+$value.faturamento+"</a>";
    
        return html;
    }

    function createBtViewMembro($value, $mes_ano, $vendedor){
        @if(in_array(Auth::user()->tipo_usuario_id, [19]))
            html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-vendedor=\""+$vendedor+"\" data-mes_ano=\""+$mes_ano+"\" data-unidade_id=\""+$value.unidade_id+"\" data-title=\"Equipe: " + $value.unidade + "- Meta: " + $value.meta_valor + " - Faturamento: " + $value.faturado_valor + " - Atigimento: " + $value.atingimento_meta_porcetagem + "\" onclick=\"abrirModalMembros($(this))\" title='Por Vendedores'>"+$value.unidade+"</a>";
        @else{
            html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-vendedor=\""+$vendedor+"\" data-mes_ano=\""+$mes_ano+"\" data-unidade_id=\""+$value.unidade_id+"\" data-title=\"Equipe: " + $value.unidade + "- Meta: " + $value.meta_valor + " - Faturamento: " + $value.faturado_valor + " - Atigimento: " + $value.atingimento_meta_porcetagem + " - Comissão: " + $value.comissao_valor + "- Prêmio: " + $value.premio_a_pagar + "\" onclick=\"abrirModalMembros($(this))\" title='Por Vendedores'>"+$value.unidade+"</a>";
        }
        @endif
        return html;
    }

    function createBtViewDevolucao($value, $hash){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-hash=\""+$hash+"\"  onclick=\"showModalRepresentante($(this))\">"+$value+"</a>";
    
        return html;
    }

    function abrirModalEquipeIndividual($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var title = $($this).data("title");
        $.ajax({
            url: '{{ route('mapa_venda.modal.equipe_individual') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                title: title,
            },
            success: function(body){
                createModal('modal_equipe', title, body, "modal-lg");
                var modal = $("#modal_equipe");
            }
        });
    }

    function abrirModalMembros($this){
        var title = $($this).data("title");
        var mes_ano = $($this).data("mes_ano");
        var unidade_id = $($this).data("unidade_id");
        var vendedor = $($this).data("vendedor");
        $.ajax({
            url: '{{ route('premiacao.modal.membro') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                unidade_id: unidade_id,
                mes_ano: mes_ano,
                vendedor: vendedor,
            },
            success: function(body){
                createModal('modal_membro', title, body, "modal-lg");
                var modal = $("#modal_membro");
            }
        });
    }

    function createBtViewGrupoEquipe($value, $filtro, $title){
        html = "<a href=\"#\" data-toggle='tooltip' data-html='true' data-codigo_vendedor=\""+$value.vendedor_codigo+"\" data-filtro=\""+$filtro+"\" data-id_unidade_negocio=\""+$value.id+"\" title='Visualizar do Gráfico' data-title=\""+$title+"\" onclick=\"abrirModalGrupoEquipe($(this))\">"+$value.valor+"</a>";
    
        return html;
    }

    function createBtConfirmacao($this){
        var html = '';
        if ($this.confirmacao == 'Sim'){
            var html = "<center><i class='fa fa-check check-icon' aria-hidden='true'></i></center>";
        }
        return html;
    }

    function createBtAprovarPremio($this){
        html = '';

        if($this.confirmacao == 'Não' && $this.comissao_fechamento == true && $this.premio_a_pagar.length > 0){
            var html = "<a href='#' class='bt-aprove' data-toggle='tooltip' data-trigger='hover' data-premio_valor='"+$this.premio_valor+"' data-meta_valor='"+$this.meta_valor+"' data-periodo='"+$this.periodo+"' title='Aprovar'></a>";
        }
        return html;
    }

    function abrirModalGrupoEquipe($this){
        var filtro = $($this).data("filtro");
        var id_unidade_negocio = $($this).data("id_unidade_negocio");
        var codigo_cliente = "";
        var title = $($this).data("title");
        var codigo_vendedor = $($this).data("codigo_vendedor");
        $.ajax({
            url: '{{ route('mapa_venda.modal.grupo') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                filtro: filtro,
                id_unidade_negocio: id_unidade_negocio,
                codigo_cliente: codigo_cliente,
                title: title,
                codigo_vendedor: codigo_vendedor
            },
            success: function(body){
                createModal('modal_grupo', title, body, "modal-lg");
                var modal = $("#modal_grupo");
            }
        });
    }

    function showModalRepresentante($this){
        var devolucao_nota_status_id = '7';
        var hash = $($this).data("hash");
        var status_descricao = "Finalizado";
        $.ajax({
            url: "{{ route('consulta_devolucao.modal') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                 hash: hash,
                 devolucao_nota_status_id: devolucao_nota_status_id,
                 status_descricao: status_descricao
            },
            success: function(body){
                createModal('modal_devolucoes_representante', "Processos de devolução: " + status_descricao, body, 'modal-lg');
                $(document).find('#modal_devolucoes_representante').on('shown.bs.modal', function(){
                    table_filters_devolucoes_total.columns.adjust().draw()
                })
            }
        });
    }

    function aprovarPremio($this){
        var retorno = false;

        $.ajax({
            url: '{{ route('premiacao.aprovar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                premio_valor : $this.data("premio_valor"),
                periodo : $this.data("periodo"),
                meta_valor : $this.data("meta_valor")
            },
            async: false,
            method: 'POST',
            success: function(body){
                retorno = true;
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });

        if(retorno == true){
            filterAjax();
        }
    }


@endsection