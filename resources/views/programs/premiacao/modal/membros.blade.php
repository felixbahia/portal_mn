@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id="acompanhamento_comissao_vendedores-tab" data-toggle="tab" href="#acompanhamento_comissao_vendedores" role="tab" aria-controls="acompanhamento_comissao_vendedores" aria-selected="false">Vendedores</a>
    </li>
    @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16]))
        <li class="nav-item">
            <a class="nav-link" id="acompanhamento_comissao_gerente-tab" data-toggle="tab" href="#acompanhamento_comissao_gerente" role="tab" aria-controls="acompanhamento_comissao_gerente" aria-selected="false">Gerente</a>
        </li>
    @endif
</ul>
<div class="tab-content pt-3" id="AcompanhamentoComissaoHeaderContainer">
    <div class="tab-pane show active" id="acompanhamento_comissao_vendedores" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-dialog-table">
            <table class="table table-striped table-not-edit table-not-view" id="table-vendedores">
                <thead>
                    <tr>
                        <th rowspan="2">Vendedor</th>
                        <th class="tb_number_150" rowspan="2">Meta</th>
                        <th class="tb_number_150" rowspan="2">Faturamento</th>
                        <th class="tb_number_150" rowspan="2">Atingimento</th>
                        <th class="tb_number_150" rowspan="2">Comissão</th>
                        <th class="tb_number_150" rowspan="2">% Prêmio</th>
                        <th class="tb_number_150" rowspan="2">Prêmio</th>
                        <th colspan="3">Devolução</th>
                        <th class="tb_number_150" rowspan="2">Prêmio A Pagar</th>
                        @if(in_array($unidade_id, $habilitado_comissao_equipe))
                        <th class="tb_number_150" rowspan="2">% Prêmio Equipe</th>
                        <th class="tb_number_150" rowspan="2">Prêmio Equipe</th>
                        <th class="tb_number_150" rowspan="2">Prêmio Total</th>
                        @endif
                        <th rowspan="2">Conf.</th>
                        @if(in_array(Auth::user()->tipo_usuario_id, [13]))
                            <th class="tb_acao" rowspan="2"></th>
                        @endif
                    </tr>
                    <tr>
                        <th class="tb_number_150">Qtd</th>
                        <th class="tb_number_150">% Prêmio</th>
                        <th class="tb_number_150">Prêmio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendedores as $vendedor)
                    <tr>
                        <td><div><div data-toggle='tooltip' data-html='true' data-placement='right' title='{{ $vendedor['vendedor'] }}'>{{ $vendedor['vendedor'] }}</div></div></td>
                        <td class="tb_number_150">{{ $vendedor['meta_valor'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['faturado_valor'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['atingimento_meta_porcetagem'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['comissao_valor'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premiacao_porcetagem'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_valor'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['devolucao'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_devolucao_porcetagem'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_devolucao_valor'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_a_pagar'] }}</td>
                        @if(in_array($unidade_id, $habilitado_comissao_equipe))
                        <td class="tb_number_150">{{ $vendedor['premio_porcetagem_equipe'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_equipe'] }}</td>
                        <td class="tb_number_150">{{ $vendedor['premio_total'] }}</td>
                        @endif
                        <td>
                            @if($vendedor['confirmacao'] == 'Sim')
                                <center><i class='fa fa-check check-icon' aria-hidden='true'></i></center>
                            @endif
                        </td>
                            <td>
                                @if(in_array(Auth::user()->tipo_usuario_id, [13]) && Auth::id() == $vendedor['id_user'])
                                    @if($vendedor['confirmacao'] == 'Não' && $vendedor['verifica_fechamento'] == true && !empty($vendedor['premio_valor']))
                                        <a href='#' class='bt-aprove' data-toggle='tooltip' data-trigger='hover' data-premio_valor='{{ $vendedor["premio_valor"] }}' data-meta_valor='{{ $vendedor["meta_valor"] }}' data-periodo='{{ $vendedor["periodo"] }}' title='Aprovar'></a>
                                    @endif
                                @endif
                            </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="tb_number_150">Total:</td>
                        <td class="tb_number_150">{{ $total['meta_valor'] }}</td>
                        <td class="tb_number_150">{{ $total['faturado_valor'] }}</td>
                        <td class="tb_number_150">{{ $total['atingimento_meta_porcetagem'] }}</td>
                        <td class="tb_number_150">{{ $total['comissao_valor'] }}</td>
                        <td class="tb_number_150"></td>
                        <td class="tb_number_150">{{ $total['premio_valor'] }}</td>
                        <td class="tb_number_150">{{ $total['devolucao'] }}</td>
                        <td class="tb_number_150"></td>
                        <td class="tb_number_150">{{ $total['premio_devolucao_valor'] }}</td>
                        <td class="tb_number_150">{{ $total['premio_a_pagar'] }}</td>
                        @if(in_array($unidade_id, $habilitado_comissao_equipe))
                        <td class="tb_number_150"></td>
                        <td class="tb_number_150">{{ $total['premio_equipe'] }}</td>
                        <td class="tb_number_150">{{ $total['premio_total'] }}</td>
                        @endif
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @if(!in_array(Auth::user()->tipo_usuario_id, [12, 16]))
        <div class="tab-pane" id="acompanhamento_comissao_gerente" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <table class="table table-striped table-not-edit table-not-view" id="table-gerente">
                    <thead>
                        @if(!empty($gerente['premio_devolucao_valor']))
                            <tr>
                                <th rowspan="2">Gerente</th>
                                <th class="tb_number_150" rowspan="2">Meta Equipe</th>
                                <th class="tb_number_150" rowspan="2">Faturamento Equipe</th>
                                <th class="tb_number_150" rowspan="2">Atingimento Equipe</th>
                                <th class="tb_number_150" rowspan="2">Comissão</th>
                                <th class="tb_number_150" rowspan="2">%Prêmio</th>
                                <th class="tb_number_150" rowspan="2">Prêmio</th>
                                <th colspan="3">Devolução</th>
                                <th class="tb_number_150" rowspan="2">Prêmio A Pagar</th>
                                <th class="tb_number_150" rowspan="2">Confirmação</th>
                                @if(in_array(Auth::user()->tipo_usuario_id, [19,14]))
                                <th class="tb_acao" rowspan="2"></th>    
                                @endif        
                            </tr>
                            <tr>
                                <th class="tb_number_150">Qtd</th>
                                <th class="tb_number_150">% Prêmio</th>
                                <th class="tb_number_150">Prêmio</th>
                            </tr>                       
                            @else
                            <tr>
                                <th>Gerente</th>
                                <th class="tb_number_150">Meta Equipe</th>
                                <th class="tb_number_150">Faturamento Equipe</th>
                                <th class="tb_number_150">Atingimento Equipe</th>
                                <th class="tb_number_150">Comissão</th>
                                <th class="tb_number_150">%Prêmio</th>
                                <th class="tb_number_150">Prêmio</th>
                                <th class="tb_number_150">Prêmio A Pagar</th>
                                <th class="tb_number_150">Confirmação</th>
                                @if(in_array(Auth::user()->tipo_usuario_id, [19,14]))
                                <th class="tb_acao"></th>    
                                @endif    
                            </tr>                    
                        @endif
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $gerente['nome'] }}</td>
                            <td class="tb_number_150">{{ $total['meta_valor'] }}</td>
                            <td class="tb_number_150">{{ $total['faturado_valor'] }}</td>
                            <td class="tb_number_150">{{ $total['atingimento_meta_porcetagem'] }}</td>
                            <td class="tb_number_150">{{ $gerente['comissao'] }}</td>
                            <td class="tb_number_150">{{ $gerente['premio_porcetagem'] }}</td>
                            <td class="tb_number_150">{{ $gerente['premio_valor'] }}</td>
                            @if(!empty($gerente['premio_devolucao_valor']))
                                <td class="tb_number_150">{{ $gerente['devolucao'] }}</td>
                                <td class="tb_number_150">{{ $gerente['premio_devolucao_porcentagem'] }}</td>
                                <td class="tb_number_150">{{ $gerente['premio_devolucao_valor'] }}</td>
                            @endif
                            <td class="tb_number_150">{{ $gerente['premio_a_pagar'] }}</td>
                            <td class="tb_number_150">
                                @if($gerente['confirmacao'] == 'Sim')
                                    <center><i class='fa fa-check check-icon' aria-hidden='true'></i></center>
                                @endif
                            </td>
                            @if(in_array(Auth::user()->tipo_usuario_id, [19,14]))
                                <td>
                                    @if($gerente['confirmacao'] == 'Não' && $gerente['comissao_fechamento'] == true && !empty($gerente['premio_valor']))
                                        <a href='#' class='bt-aprove' data-toggle='tooltip' data-trigger='hover' data-premio_valor='{{ $gerente["premio_valor"] }}' data-meta_valor='{{ $total["meta_valor"] }}' data-periodo='{{ $gerente["periodo"] }}' title='Aprovar'></a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
<script>
    $(document).ready( function () {
        $(document).find(".bt-aprove").on("click", function(event){
            event.stopPropagation();
            aprovarPremio($(this));
        });

        $(document).find('#table-vendedores').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
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
                    'targets': 0,
                    'min-width': '200px'
                },
                { "class": "tb_number_150", type: 'num-fmt', targets: "tb_number_150"},
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        }).on('draw', function () {
            $(document).find(".bt-aprove").off("click");
            $(document).find(".bt-aprove").on("click", function(event){
                event.stopPropagation();
                aprovarPremio($(this));
            });
        });
    });

    function aprovarPremio($this){
        $.ajax({
            url: '{{ route("premiacao.aprovar") }}',
            data: {
                _token: '{{ csrf_token() }}',
                premio_valor : $this.data("premio_valor"),
                periodo : $this.data("periodo"),
                meta_valor : $this.data("meta_valor")
            },
            method: 'POST',
            success: function(data){
                $($this).parents(".modal").modal("hide");
                message("Atenção", "Prêmio confirmado com sucesso!");
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
</script>
@endsection