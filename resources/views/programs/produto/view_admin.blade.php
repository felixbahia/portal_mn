@extends('layouts.page-dialog')

@section('content')
    <div class="content-view-cliente">
        <div class="content-dialog-table" style='float: none;'>
            <table class='table table-striped table-filter-pedido-itens table-not-edit'>
                <thead>
                    <tr>
                        <th>Marca</th>
                        <th>Grupo</th>
                        <th>Subgrupo</th>
                        <th>Linha</th>
                        <th>Unidade</th>
						@if(isset($dados['ficha_tecnica_id']))
                        <th>Ficha técnica</th>
						@endif
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $dados['marca'] }}</td>
                        <td>{{ $dados['grupo'] }}</td>
                        <td>{{ $dados['subgrupo'] }}</td>
                        <td>{{ $dados['linha'] }}</td>
                        <td>{{ $dados['unidade'] }}</td>
						@if(isset($dados['ficha_tecnica_id']))
                        <td><a href="#" data-id="{{ $dados['ficha_tecnica_id'] }}" class="bt-view" data-toggle="tooltip" data-placement="top" title="Visualizar Ficha técnica" onclick="showDetalhesFichaTecnica($(this))"></a></td>
						@endif
                    </tr>
                </tbody>
            </table>
        </div>
        @if(in_array(Auth::user()->tipo_usuario_id, [1,15]) || in_array(Auth::user()->detalhesModelHasRoles->detalhesRoles->name, ['Analista Compra', 'gerente Compras', 'Gerencia Produto']))
            <div class="row-table">
                <div class="row">
                    <div class="content-dialog-table col">
                        <table class="table table-striped table-filter table-not-edit" style='float: none;'>
                            <thead>
                                <tr>
                                    <th colspan='2'>Compras</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>Data última entrada</th>
                                    <td class="text-center">{{ $dados['ultima_compra_real'] }}</td>
                                </tr>
                                <tr>
                                    <th>Valor última entrada R$ ( Custo Gerencial )</th>
                                    <td class="text-right">{{ $dados['custo_real'] }}</td>
                                </tr>
                                <tr>
                                    <th>Preço R$</th>
                                    <td class="text-right">{{ $dados['preco_real'] }}</td>
                                </tr>
                                <tr>
                                    <th>Preço Us$</th>
                                    <td class="text-right">{{ $dados['preco_dolar'] }}</td>
                                </tr>
                                <tr>
                                    <th>Data última compra Us$</th>
                                    <td class="text-center">{{ $dados['ultima_compra_dolar'] }}</td>
                                </tr>
                                <tr>
                                    <th>Valor Us$ FOB Compra</th>
                                    <td class="text-right">{{ $dados['custo_dolar'] }}</td>
                                </tr>
                                <tr>
                                    <th>Pedido compra previsão entrada</th>
                                    <td class="text-center">{{ $dados['entrega'] }}</td>
                                </tr>
                                <tr>
                                    <th>Pedido compra valor</th>
                                    <td class="text-right">{{ $dados['valor_compra'] }}</td>
                                </tr>
                                <tr>
                                    <th>Número proforma</th>
                                    <td>{{ $dados['proforma'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="content-dialog-table col" style='float: none;'>
                        <table class='table table-striped table-filter table-not-edit'>
                            <thead>
                                <tr>
                                    <th colspan="2">Informações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>Custo médio contábil (Nasajon) </th>
                                    <td class="text-right">{{ $dados['custo_medio'] }}</td>
                                </tr>
                                <tr>
                                    <th>Custo médio contábil (Portal)</th>
                                    <td class="text-right"><div data-id="" data-toggle="popover" data-trigger='hover' title="Custo médio contábil por empresa" data-content="{{ $dados['custo_portal']['popover_custo_contabil'] }}"><span>{{ $dados['custo_portal']['custo_contabil'] }}</span><span class="bt-detalhe"></span>&nbsp;&nbsp;</div></td>
                                </tr>
                                <tr>
                                    <th>Custo médio Gerencial (Portal)</th>
                                    <td class="text-right"><div data-id="" data-toggle="popover" data-trigger='hover' title="Custo médio Gerencial por empresa" data-content="{{ $dados['custo_portal']['popover_custo_gerencial'] }}"><span>{{ $dados['custo_portal']['custo_gerencial'] }}</span><span class="bt-detalhe"></span>&nbsp;&nbsp;</div></td>
                                </tr>
                                <tr>
                                    <th>Estoque</th>
                                    <td class="text-right">{{ $dados['estoque_total']['estoque'] }}</td>
                                </tr>
                                <tr>
                                    <th>Venda dos últimos 6 meses</th>
                                    <td class="text-right">{{ $dados['venda_ultimos_meses'] }}</td>
                                </tr>
                                <tr>
                                    <th>Venda média dos últimos 6 meses</th>
                                    <td class="text-right">{{ $dados['venda_media'] }}</td>
                                </tr>
                                <tr>
                                    <th>Estoque em meses</th>
                                    <td class="text-right">{{  $dados['estoque_meses'] }}</td>
                                </tr>
                                <tr>
                                    <th>Data de Cadastro</th>
                                    <td class="text-center">{{  $dados['data_de_cadastro'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>
        @endif

        <div class="row-table">
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Sub-Grupo</div></div>
                <div class="value">{!! $dados["sub_grupo"] !!}</div>
            </div>
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">IPI</div></div>
                <div class="value">{!! $dados["ipi"] !!}</div>
            </div>
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">NCM</div></div>
                <div class="value">{!! $dados["ncm"] !!}</div>
            </div>
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">EAN</div></div>
                <div class="value">{!! $dados["ean"] !!}</div>
            </div>
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Peso</div></div>
                <div class="value">{!! $dados["peso"] !!}</div>
            </div>
        </div>

        <div class="row-table-estoque">
            <div class="cel-table width-100">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Estoque</div></div>
            </div>
        </div>
        <div class="row-table-estoque">
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Unidade</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Fiscal</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Em Terceiro<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Estoque em Em Terceiro = Estoque Em Terceiro - Movimento Não Efetivado." style="color: black;"></a></div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Em Trânsito<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Estoque em Trânsito = Estoque Fiscal + Movimento Não Efetivado." style="color: black;"></a></div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Compra</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Estoque<a href="#" class="btn-informacao" data-toggle="tooltip" data-placement="top" title="" data-original-title="Estabelecimento 03 e 04, estoque: Estoque Em Terceiro - Movimento Não Efetivado. Outros estabelecimentos, estoque: Estoque Fiscal." style="color: black;"></a></div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Reserva</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Disponível</div></div>
            </div>
        </div>
        @foreach($dados["estoque"] as $value)
        <div class="row-table-estoque">
            <div class="cel-table-8-colunas">
                <div class="value"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $value["estabel"] }}</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $value["fiscal"] }}</div></div>
            </div>
            <div class="cel-table-8-colunas">
                @if(intval($value['original']['cod_estabel']) == 5 || intval($value['original']['cod_estabel']) == 6)
                    <a href="#" class="bt-estoque" data-url="{{ route('produto.movimento_estoque.movimento_portal_terceiro') }}" data-title="Movimento de Estoque - {{ $value["estabel"] }} - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }} - Compras: {{ $value["compra"] }} - Reserva: {{ $value["reserva"] }} - Disponível: {{ $value["disponivel"] }}"data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}" data-inicial="2018-07-01" data-fim="{{ date('Y-m-d') }}" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque"></a> 
                @endif
                <div class="value">
                    <div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">
                        {{ $value["em_terceiros"] }}
                    </div>
                </div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $value["em_transito"] }}</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">
                    @if($value["compra"] !== "0,00")
                        <div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">
                            <a href="#" class="btn-view-estoque" data-title="Compras - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }}" data-url="{{ route('produto.compras') }}" data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}">
                                {{ $value["compra"] }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="cel-table-8-colunas">
                    @if(intval($value['original']['cod_estabel']) == 5 || intval($value['original']['cod_estabel']) == 6)
                    <a href="#" class="bt-estoque" data-url="{{ route('produto.movimento_estoque.dialog') }}" data-title="Movimento de Estoque - {{ $value["estabel"] }} - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }} - Compras: {{ $value["compra"] }} - Reserva: {{ $value["reserva"] }} - Disponível: {{ $value["disponivel"] }}"data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}" data-inicial="2018-07-01" data-fim="{{ date('Y-m-d') }}" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque"></a> 
                    @else
                    <a href="#" class="bt-estoque" data-url="{{ route('produto.movimento_estoque.dialog') }}" data-title="Movimento de Estoque - {{ $value["estabel"] }} - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }} - Compras: {{ $value["compra"] }} - Reserva: {{ $value["reserva"] }} - Disponível: {{ $value["disponivel"] }}"data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}" data-inicial="2019-07-05" data-fim="{{ date('Y-m-d') }}" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque"></a>
                    @endif
                    @if (
                        Auth::user()->hasRole('Administradores') ||
                        Auth::user()->hasRole('Diretoria') ||
                        Auth::user()->hasRole('Contabil') 
                    )
                    <a href="#" class="bt-estoque2"  data-url="{{ route('produto.movimento_estoque.movimento_portal') }}" data-title="Movimento de Estoque Portal - {{ $value["estabel"] }} - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }}" data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}" data-inicial="2019-07-05" data-fim="{{ date('Y-m-d') }}" data-toggle="tooltip" data-placement="top" title="Movimento de Estoque Portal"><i class="far fa-list-alt" style="color: #000;float: left;font-size: 20px;"></i></a>
                    @endif
                <div class="value">
                    @if($value["estoque"] !== "0,00")
                    <div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">     
                            <a href="#" class="btn-view-estoque" data-title="Estoque - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }}" data-url="{{ route('produto.estoque') }}" data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}">
                                {{ $value["estoque"] }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($value["reserva"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top"><a href="#" class="btn-view-estoque" data-title="Reserva - {{ $dados["original"]["codigo"] }} - {{ $dados["original"]["grupo"] }} - {{ $dados["original"]["descricao"] }}" data-url="{{ route('produto.reserva') }}" data-estabel="{{ $value["original"]["estabel"] }}" data-codigo="{{ $dados["original"]["codigo"] }}">{{ $value["reserva"] }}</a></div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($value["disponivel"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $value["disponivel"] }}</div>@endif</div>
            </div>
        </div>
        @endforeach
        <div class="row-table-estoque">
            <div class="cel-table-8-colunas">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Total</div></div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["fiscal"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["fiscal"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["em_terceiros"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["em_terceiros"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["em_transito"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["em_transito"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["compra"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["compra"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["estoque"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["estoque"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["reserva"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["reserva"] }}</div>@endif</div>
            </div>
            <div class="cel-table-8-colunas">
                <div class="value">@if($dados["estoque_total"]["disponivel"] !== "0,00")<div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">{{ $dados["estoque_total"]["disponivel"] }}</div>@endif</div>
            </div>
        </div>
    </div>
    <script>
        $(document).find('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });
        $(document).find('[data-toggle="popover"]').on('show.bs.popover', function () {
            var $this = $(this);
            $('.popover').not($this).each(function(){
                $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
            });
            $("body").on("keyup", function(e){
                if(e.keyCode == 27){
                    $($this).popover('hide');
                }
            });
        });
        $('.content-view-cliente').find(".value, .title").find("div").off('mouseenter');
        $('.content-view-cliente').find(".value, .title").find("div").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
        $(document).ready( function () {
            $(document).find(".bt-estoque").off("click");
            $(document).find(".bt-estoque").on("click", function(event){
                event.stopPropagation();
                showModalMovimentoEstoque($(this));
            });
            $(document).find(".bt-estoque2").off("click");
            $(document).find(".bt-estoque2").on("click", function(event){
                event.stopPropagation();
                showModalMovimentoEstoque($(this));
            });
        });
        function showModalMovimentoEstoque($this){
            var url = $($this).data("url");
            var $estabel = $($this).data("estabel");
            var $codigo = $($this).data("codigo");
            var $inicial = $($this).data("inicial");
            var $fim = $($this).data("fim");
            var title = $($this).data("title");
            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}", 
                    estabel: $estabel,
                    codigo: $codigo,
                    inicial: $inicial,
                    fim: $fim
                },
                success: function(body){
                    createModal('modal_movimento_estoque', title, body, "modal-lg");
                    var modal = $("#modal_movimento_estoque");
                }
            });
        }
		function showDetalhesFichaTecnica($this){
			$.ajax({
				data: {
					id: $this.data('id'),
					_token: '{{ csrf_token() }}',
                    exibicao_custo_fixo: true,
				},
				url: '{{ route('ficha_tecnica.visualizacao.modal') }}',
				method: 'POST',
				success: function(data){
					var title = 'Ficha técnica do produto: {{ $dados["original"]["descricao"] }}';
					createModal('modal_ficha_tecnica_exibir', title, data, "modal-lg");

					$(document).find('#modal_ficha_tecnica_exibir').on('shown.bs.modal', function(){
						table_filters_composicao.columns.adjust().draw();
						table_filters_servicos.columns.adjust().draw();
					});
				},
				error: function(callback){
				}
			});
		}
    </script>
@endsection
