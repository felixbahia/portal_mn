@extends('layouts.page-dialog')
@section('content')
<ul class="nav nav-tabs" id="PedidoOrcamentoTabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link" id="enderecos-tab" data-toggle="tab" href="#por_endereco" role="tab" aria-controls="por_endereco" aria-selected="true">Por Endereço</a>
	</li>
    <li class="nav-item">
        <a class="nav-link active" id="pecas-tab" data-toggle="tab" href="#por_peca" role="tab" aria-controls="por_peca" aria-selected="false">Por Produto</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="operador-tab" data-toggle="tab" href="#por_operador" role="tab" aria-controls="por_operador" aria-selected="false">Por Operador</a>
    </li>
</ul>
<div class="tab-content" id="PedidoOrcamentoTabsContent">
	<div class="tab-pane show" id="por_endereco" role="tabpanel" aria-labelledby="dados-tab">
        <div style="overflow: hidden;">
            <div class="content-dialog-table">
                <table class="table table-striped table-not-edit table-not-view" id="table_enderecos_anaslise">
                    <thead>
                        <tr>
                            <th>Endereços</th>
                            <th class="tb_number">Quantidade lida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enderecos as $item)
                            <tr>
                                <td>{{ $item['endereco'] }}</td>
                                <td class="tb_number">{{ $item['quantidade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
	<div class="tab-pane show active" id="por_peca" role="tabpanel" aria-labelledby="dados-tab">
        @if(Auth::user()->hasRole('Administradores') || in_array(Auth::id(), [18, 21, 45]))
        {{ Form::button('Efetivar inventario', array('class' => 'btn btn-primary float-right', 'onclick'=>'aplicarEstoque()' )) }}
        @endif
        <hr style="float: left;width: 100%">
        <div class="content-dialog-table">
            <table class="table table-striped table-not-edit table-not-view" id="table_pecas_anaslise">
                <thead>
                    <tr>
                        <th rowspan="2">Produto</th>
                        <th colspan="2" style="text-align: center;">Inventario</th>
                        <th colspan="2" style="text-align: center;">Estoque</th>
                        <th colspan="2" style="text-align: center;">Diferença</th>
                        <th rowspan="2" style="width: 50px; max-width: 50px;"><input type="checkbox" name="todos_aplicar" id="todos_aplicar" value="todos" /></th>
                        <th rowspan="2" style="width: 50px; max-width: 50px;"></th>
                    </tr>
                    <tr>
                        <th style="width: 150px;max-width: 150px;">Volumes</th>
                        <th style="width: 150px;max-width: 150px;">Quantidade</th>

                        <th style="width: 150px;max-width: 150px;">Volumes</th>
                        <th style="width: 150px;max-width: 150px;">Quantidade</th>

                        <th style="width: 150px;max-width: 150px;">Volumes</th>
                        <th style="width: 150px;max-width: 150px;">Quantidade</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($produtos as $item)
                    <tr class="row_produto_{{ $item['produto']['codigo'] }}">
                        <td><a href="#" onclick="openProduto('{{ $item['criterios'] }}','{{ $item['produto']['codigo'] . " - " . $item['produto']['descricao'] }}')">{{ $item['produto']['codigo'] . " - " . $item['produto']['descricao'] }}</a></td>
                        <td class="tb_number" style="width: 150px;max-width: 150px;">{{ $item['inventario']['volumes'] }}</td>
                        <td class="tb_number" style="width: 150px;max-width: 150px;">{{ $item['inventario']['quantidade'] }}</td>
                        <td class="tb_number" style="width: 150px;max-width: 150px;">{{ $item['estoque']['volumes'] }}</td>
                        <td class="tb_number" style="width: 150px;max-width: 150px;">{{ $item['estoque']['quantidade'] }}</td>
                        <td class="tb_number" style="width: 150px;max-width: 150px; text-align: right;">
                            <a href="#" onclick="openDiferenca('{{ $item['criterios'] }}','{{ $item['produto']['codigo'] . " - " . $item['produto']['descricao'] }}')">{{ $item['diferenca']['volumes'] }}</a>
                        </td>
                        <td class="tb_number" style="width: 150px;max-width: 150px; text-align: right;">
                            <a href="#" onclick="openDiferenca('{{ $item['criterios'] }}','{{ $item['produto']['codigo'] . " - " . $item['produto']['descricao'] }}')">{{ $item['diferenca']['quantidade'] }}</a>
                        </td>
                        <td style="width: 50px;max-width: 50px; text-align: center;">
                            @if( $item['atualizar_estoque'] === true)
                            <input type="checkbox" name="aplicar_estoque[]" id="aplicar_estoque[]" value="{{ $item['criterios'] }}" />
                            @elseif($item['atualizar_estoque'] === 'movimento')
                            <div data-toggle="tooltip" data-trigger="hover" title="" data-original-title="Produto com movimento no período do inventario"><i class="fa fa-times error-icon" aria-hidden="true"></i></div>
                            @endif
                        </td>
                        <td><a href="#" class="bt-delete" data-toggle="tooltip" data-placement="left" data-html="true" title="Excluir" onclick="modalExcluirProdutosInventario('{{ $item['criterios'] }}', $(this).parents('tr'))"></a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="tab-pane show" id="por_operador" role="tabpanel" aria-labelledby="dados-tab">
        <div style="overflow: hidden;">
            <div class="content-dialog-table">
                <table class="table table-striped table-not-edit table-not-view" id="table_operador_anaslise">
                    <thead>
                        <tr>
                            <th>Operador</th>
                            <th class="tb_number">Quantidade lida</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($operadores as $item)
                            <tr>
                                <td>{{ $item['operador'] }}</td>
                                <td class="tb_number">{{ $item['quantidade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready( function () {
        $(document).find('#todos_aplicar').off('change');
        $(document).find('#todos_aplicar').on('change', function(event){
            event.preventDefault();
            event.stopPropagation();
            habilitaTudo(this);
        });
    });
    function habilitaTudo(campo){
        if($(campo).prop("checked") === true){
            $(document).find('input:checkbox').prop('checked', true);
        }else{
            $(document).find('input:checkbox').prop('checked', false);
        }
    }
</script>
@endsection