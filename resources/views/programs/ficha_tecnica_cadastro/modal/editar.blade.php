@extends('layouts.page-dialog')

@section('content')

<div class="tab-content mb-2">
    <div class="row">
        <div class="col-sm-6">
            <b>Marca:</b> {!! $produto['marca'] !!}
        </div>
        <div class="col-sm-6">
            <b>Linha:</b> {!! $produto['linha'] !!}
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <b>Grupo:</b> {!! $produto['grupo'] !!}
        </div>
        <div class="col-sm-6">
            <b>Subgrupo:</b> {!! $produto['subgrupo'] !!}
        </div>
    </div>

    <div class="row">
        @if(!empty($produto['largura']))
        <div class="col-sm-6">
            <b>Largura:</b> {!! $produto['largura'] !!}
        </div>
        @endif
        @if(!empty($produto['gramatura']))
        <div class="col-sm-6">
            <b>Gramatura:</b> {!! $produto['gramatura'] !!}
        </div>
        @endif
    </div>

    <div class="row">
        <div class="col-sm-6">
            @if(!empty($produto['peso']))
            <b>Peso: </b> {{ $produto['peso'] }} kg
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            @if(!empty($produto['ficha_tecnica_criacao']))
            <b>Data Criação: </b> {{ $produto['ficha_tecnica_criacao'] }}
            @endif
        </div>
        <div class="col-sm-6">
            @if(!empty($produto['ficha_tecnica_update']))
            <b>Data Última Atualização: </b> {{ $produto['ficha_tecnica_update'] }}
            @endif
        </div>
    </div>
</div>

<div class='tab-content'>
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id="ficha-tecnica-consulmo-tab" data-toggle="tab" href="#ficha-tecnica-composicao" role="tab" aria-controls="ficha-tecnica-composicao" aria-selected="false">Composição & Mão de Obra</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id='ficha-tecnica-lavagem-tab' data-toggle="tab" href="#ficha-tecnica-lavagem" role="tab" aria-controls="ficha-tecnica-lavagem" aria-selected="false">Referência de Peça e Lavagem</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-montagem-tab" data-toggle="tab" href="#ficha-tecnica-montagem" role="tab" aria-controls="ficha-tecnica-montagem" aria-selected="false">Montagem</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-etiqueta-tab" data-toggle="tab" href="#ficha-tecnica-etiqueta" role="tab" aria-controls="ficha-tecnica-etiqueta" aria-selected="false">Etiqueta</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-medidas-tab" data-toggle="tab" href="#ficha-tecnica-medidas" role="tab" aria-controls="ficha-tecnica-medidas" aria-selected="false">Medidas</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="ficha-tecnica-sequencia-tab" data-toggle="tab" href="#ficha-tecnica-sequencia" role="tab" aria-controls="ficha-tecnica-sequencia" aria-selected="false">Sequência Operacional</a>
        </li>
    </ul>

    <div class="tab-content pt-3" id="LancamentoProjetoHeaderContainer">
        <div class="tab-pane show active" id="ficha-tecnica-composicao" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                {{ Form::button('Novo item de composição', ['class' => 'btn btn-primary float-right', 'id' => 'btn_novo_item']) }}
                <div class="content-table">
                    <h6>Composição</h6>
                    <br>
                    <table class="table table-striped table-filters-composicao" id="table-filters-composicao">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Linha</th>
                                <th class="td_codigo_produto">Código</th>
                                <th>Descrição</th>
                                <th class="tb_number">Consumo por unidade padrão</th>
                                <th class="tb_number">Custo Contábil Unitário</th>
                                <th class="tb_number">Custo Contábil Total</th>
                                <th class="tb_number">Custo Gerencial Unitário</th>
                                <th class="tb_number">Custo Gerencial Total</th>
                                <th class="tb_buttons"></th>
                                <th class="tb_buttons"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($tecidos as $tecido)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['grupo'] }}">{{ $tecido['grupo'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['linha'] }}">{{ $tecido['linha'] }}</div></div></td>
                            <td class="td_codigo_produto">{{ $tecido['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $tecido['descricao'] }}">{{ $tecido['descricao'] }}</div></div></td>
                            <td class="tb_number">{{ $tecido['consumo'] }}</td>
                            <td class="tb_number">{{ $tecido['custo'] }}</td>
                            <td class="tb_number">{{ $tecido['custo_total'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_unitario">{{ $tecido['custo_gerencial'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_total">{{ $tecido['custo_gerencial_total'] }}</td>
                            <td><a href="#" data-id="{{ $tecido['id'] }}" data-origem="tecido" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir"></a></td>
                            <td><a href="#" data-id="{{ $tecido['id'] }}" data-origem="tecido" class="bt-edit" data-toggle="tooltip" data-placement="top" title="Editar"></a></td>
                        </tr>
                        @endforeach
                        @foreach($insumos as $insumo)
                        <tr>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['grupo'] }}">{{ $insumo['grupo'] }}</div></div></td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['linha'] }}">{{ $insumo['linha'] }}</div></div></td>

                            <td>{{ $insumo['codigo'] }}</td>
                            <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $insumo['descricao'] }}">{{ $insumo['descricao'] }}</div></div></td>
                            <td class="tb_number">{{ $insumo['consumo'] }}</td>
                            <td class="tb_number">{{ $insumo['custo'] }}</td>
                            <td class="tb_number">{{ $insumo['custo_total'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_unitario">{{ $insumo['custo_gerencial'] }}</td>
                            <td class="tb_number td_preco td_custo_gerencial_total">{{ $insumo['custo_gerencial_total'] }}</td>
                            <td><a href="#" data-id="{{ $insumo['id'] }}" data-origem="insumo" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir"></a></td>
                            <td><a href="#" data-id="{{ $insumo['id'] }}" data-origem="insumo" class="bt-edit" data-toggle="tooltip" data-placement="top" title="Editar"></a></td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <td class='td_modal_grupo'></td>
                            <td class='td_modal_linha'></td>
                            <td class='td_modal_codigo'></td>
                            <td class='td_modal_descricao'></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number"><span id="custo-total-composicao">{{ $custo_composicao_total }}</span></td>
                            <td class="tb_number"></td>
                            <td class="tb_number"><span id="custo-total-composicao-gerencial">{{ $custo_gerencial_total }}</span></td>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="content-dialog-table">
                <div class="content-table">
                    <h6>Mão de obra</h6>
                    <table class="table table-striped table-filters-projeto-servico" id="table-filters-projeto-servico">
                        <thead>
                            <tr>
                                <th>Grupo</th>
                                <th>Linha</th>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th class="tb_number">Consumo por unidade padrão</th>
                                <th class="tb_number">Custo Gerencial Unitário</th>
                                <th class="tb_number">Custo Gerencial Total</th>
                                <th class="tb_buttons"></th>
                                <th class="tb_buttons"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servicos as $servico)
                                <tr>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['grupo'] }}">{{ $servico['grupo'] }}</div></div></td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['linha'] }}">{{ $servico['linha'] }}</div></div></td>
                                    <td>{{ $servico['codigo'] }}</td>
                                    <td><div><div data-toggle="tooltip" data-html='true' data-placement="right" title="{{ $servico['descricao'] }}">{{ $servico['descricao'] }}</div></div></td>
                                    <td class="tb_number td_preco">{{ $servico['consumo'] }}</td>
                                    <td class="tb_number td_preco">{{ $servico['custo'] }}</td>
                                    <td class="tb_number td_preco">{{ $servico['custo_total'] }}</td>
                                    <td><a href="#" data-id="{{ $servico['id'] }}" data-origem="servico" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir"></a></td>
                                    <td><a href="#" data-id="{{ $servico['id'] }}" data-origem="servico" class="bt-edit" data-toggle="tooltip" data-placement="top" title="Editar"></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <td class='td_modal_grupo'></td>
                            <td class='td_modal_linha'></td>
                            <td class='td_modal_codigo'></td>
                            <td class='td_modal_descricao'></td>
                            <td class="tb_number"></td>
                            <td class="tb_number">Total:</td>
                            <td class="tb_number"><span id="custo-total-servicos">{{ $custo_gerencial_servicos_total }}</span></td>
                        </tfoot>
                    </table>

                    <hr>

                    <h5 class='float-right'><span id='custo-total-produto'>{!! $custo_total !!}</span></h5>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-lavagem" role="tabpanel" aria-labelledby="dados-tab">
            <form id='referencia-peca-lavagem-form' action="#" onsubmit="return false;" enctype="multipart/form-data">
                @csrf
                {!! Form::hidden('ficha_id', $produto['id'], ['id' => 'ficha_id_modal']) !!}

                <div class="row">
                    <div id='info-adicional-foto-div' class="col-sm-1 mr-2 @if(!$info_adicional['imagem']) d-none @endif">
                       {!! $info_adicional['imagem_produto'] !!}
                    </div>
                    <div class="col">
                        <div class="row">
                            <div class="col" >
                                {!! Form::label('imagem_produto', 'Imagem do produto') !!}
                                {!! Form::file('imagem_produto', ['class' => 'form-control', 'id' => 'imagem_produto_modal', 'rows' => "4"]) !!}
                            </div>
                        </div>
                        <div class="row">
                            <div id='div-btn-excluir-imagem-produto' class="col mt-2 @if($info_adicional['imagem'] == false) d-none @endif">
                                <input type="button" value="Apagar imagem" class='btn btn-danger' id='btn-excluir-imagem'>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        {!! Form::label('lavagem', 'Lavagem') !!}
                        {!! Form::textarea('lavagem', $info_adicional['lavagem'], ['class' => 'form-control ficha-tecnica-textarea', 'id' => 'lavagem_modal', 'rows' => "4", 'maxlength' => 254 ]) !!}
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        {!! Form::label('encolhimento', 'Encolhimento') !!}
                        {!! Form::textarea('encolhimento', $info_adicional['encolhimento'], ['class' => 'form-control ficha-tecnica-textarea', 'id' => 'encolhimento_modal', 'rows' => "5", 'maxlength' => 254]) !!}
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        {{ Form::button('Salvar informações', ['class' => 'btn btn-primary float-right', 'id' => 'btn-salvar-referencia-peca-lavagem', 'onclick' => 'salvarReferenciaPecaLavagem()']) }}
                    </div>
                </div>
            </form>
        </div>

        <div class="tab-pane" id="ficha-tecnica-montagem" role="tabpanel" aria-labelledby="dados-tab">
            <form id='montagem-form' action="#" onsubmit="return false;" enctype="multipart/form-data">
                @csrf
                {!! Form::hidden('ficha_id', $produto['id']) !!}
                <div class="row">
                    <div class="col">
                        {!! Form::label('imagem_etiqueta_modal', 'Nova instrução de montagem') !!}
                        {!! Form::file('imagem', ['class' => 'form-control', 'id' => 'imagem_montagem_modal']) !!}
                        {!! Form::textarea('descricao', '', ['class' => 'form-control ficha-tecnica-textarea', 'id' => 'descricao_montagem_modal', 'rows' => "4", 'placeholder' => 'Descrição da instrução', 'maxlength' => 254]) !!}
                    </div>
                </div>
                <div class='row'>
                    <div class="col">
                        {{ Form::button('Salvar instrução', ['class' => 'btn btn-primary mt-2', 'id' => 'btn-salvar-montagem']) }}
                    </div>
                </div>
            </form>
            <hr>
            <div class="row">
                <div class="col mt-4 @if(empty($montagem)) d-none @endif" id="montagem-titulo">
                    <h6>INSTRUÇÕES SALVAS</h6>
                </div>
            </div>
            <div class="row" id='montagem-row'>
                @foreach($montagem as $linha)
                <div class="box-exibicao-300 m-1">
                    <div data-toggle='tooltip' data-html='true' data-placement='right' title="Clique para expandir" class='img-container-thumbnail-300'>
                        {!! $linha['imagem'] !!}<br>
                    </div>
                    <div style="text-align: center;">
                        <div class='montagem-descricao-text'>
                            {{ $linha['descricao'] }}<br>
                        </div>
                    </div>
                    <div style="text-align: center;">
                        {{ Form::button('Excluir', ['class' => 'btn btn-sm btn-primary mt-2 btn-apagar-instrucao', 'data-id' => $linha['id']]) }}
                    </div>
                </div>

                @endforeach
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-etiqueta" role="tabpanel" aria-labelledby="dados-tab">
            <form id='etiqueta-form' action="#" onsubmit="return false;" enctype="multipart/form-data">
                @csrf
                {!! Form::hidden('ficha_id', $produto['id']) !!}
                <div class="row">
                    <div class="col">
                        {!! Form::label('imagem_etiqueta_modal', 'Nova etiqueta') !!}
                        {!! Form::file('etiqueta', ['class' => 'form-control', 'id' => 'imagem_etiqueta_modal']) !!}
                    </div>
                </div>
                <div class='row'>
                    <div class="col">
                        {{ Form::button('Salvar etiqueta', ['class' => 'btn btn-primary mt-2', 'id' => 'btn-salvar-etiqueta']) }}
                    </div>
                </div>
            </form>
            <hr>
            <div class="row">
                <div class="col mt-4 @if(empty($etiquetas)) d-none @endif" id="etiquetas-titulo">
                    <h6>ETIQUETAS SALVAS</h6>
                </div>
            </div>
            <div class="row" id='etiqueta-row'>
                @foreach($etiquetas as $etiqueta)
                <div class="box-exibicao-100 m-1">
                    <div class='img-container-thumbnail' data-toggle='tooltip' data-html='true' data-placement='right' title="Clique para expandir" >
                        {!! $etiqueta['imagem'] !!}<br>
                    </div>
                    <div style="text-align: center;">
                        {{ Form::button('Excluir', ['class' => 'btn btn-sm btn-primary mt-2 btn-apagar-etiqueta', 'data-id' => $etiqueta['id']]) }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-medidas" role="tabpanel" aria-labelledby="dados-tab">
            <form id='medidas-form' action="#" onsubmit="return false;">
                <div class="row">
                    <div class="col text-right">
                        <h4 class='float-left m-3'>Medidas</h4>
                        {{ Form::button('Nova medida', ['class' => 'btn btn-primary mt-2 float-right', 'id' => 'btn-nova-medida']) }}
                    </div>
                </div>

                <div class="sortable" id='div-medidas'>
                    <div class="row m-2 titulos-sortable">
                        <div class="col-sm-4 border-right">
                            Descrição
                        </div>
                        <div class="col-sm-1 border-right">
                            P
                        </div>
                        <div class="col-sm-1 border-right">
                            M
                        </div>
                        <div class="col-sm-1 border-right">
                            G
                        </div>
                        <div class="col-sm-1 border-right">
                            GG
                        </div>
                        <div class="col-sm-1 border-right">
                            XG
                        </div>
                        <div class="col-sm-1 border-right">
                            XGG
                        </div>
                        <div class="col-sm-1 border-right">
                            Tolerância
                        </div>
                        <div class="mr-3">
                        </div>
                    </div>

                    <div class="row border rounded py-2 m-2 placeholder-sortable @if(!empty($medidas)) d-none @endif">
                        <div class="col text-center">
                            <i>Nenhuma medida cadastrada</i>
                        </div>
                    </div>

                    @foreach($medidas as $medida)
                    <div class="row border rounded py-2 m-2 linha-medida-div">
                        <div class="col-sm-4">
                            {!! Form::text('medida_descricao', $medida['descricao'], ['class' => 'form-control medida_descricao_modal', 'placeholder' => 'Descrição']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('medida_p', $medida['medida_p'], ['class' => 'form-control medida medida_p_modal', 'placeholder' => 'Medida P']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('medida_m', $medida['medida_m'], ['class' => 'form-control medida medida_m_modal', 'placeholder' => 'Medida M']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('medida_g', $medida['medida_g'], ['class' => 'form-control medida medida_g_modal', 'placeholder' => 'Medida G']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('medida_gg', $medida['medida_gg'], ['class' => 'form-control medida medida_gg_modal', 'placeholder' => 'Medida GG']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('medida_xg', $medida['medida_xg'], ['class' => 'form-control medida medida_xg_modal', 'placeholder' => 'Medida XG']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('mediga_xgg', $medida['medida_xgg'], ['class' => 'form-control medida medida_xgg_modal', 'placeholder' => 'Medida XGG']) !!}
                        </div>
                        <div class="col-sm-1">
                            {!! Form::text('tolerancia', $medida['tolerancia'], ['class' => 'form-control medida tolerancia_modal', 'placeholder' => 'Tolerância']) !!}
                        </div>
                        <div class="col my-2 mr-3 text-left">
                            <a href="#" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir medida" onclick="excluirMedida(this)"></a>
                        </div>
                        <div class="col my-2 mr-3">
                            <a href="#" class="bt-draggable"></a>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="row">
                    <div id='div-salvar-medidas' class="col text-right">
                        {{ Form::button('Salvar medidas', ['class' => 'btn btn-success', 'id' => 'btn-salvar-medidas']) }}
                    </div>
                </div>
            </form>

            <div id='medida_modal_modelo' class="row border rounded py-2 m-2 d-none linha-medida-div">
                <div class="col-sm-4">
                    {!! Form::text('medida_descricao', '', ['class' => 'form-control medida_descricao_modal', 'placeholder' => 'Descrição']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_p', '', ['class' => 'form-control medida medida_p_modal', 'placeholder' => 'Medida P']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_m', '', ['class' => 'form-control medida medida_m_modal', 'placeholder' => 'Medida M']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_g', '', ['class' => 'form-control medida medida_g_modal', 'placeholder' => 'Medida G']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_gg', '', ['class' => 'form-control medida medida_gg_modal', 'placeholder' => 'Medida GG']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_xg', '', ['class' => 'form-control medida medida_xg_modal', 'placeholder' => 'Medida XG']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('medida_xgg', '', ['class' => 'form-control medida medida_xgg_modal', 'placeholder' => 'Medida XGG']) !!}
                </div>
                <div class="col-sm-1">
                    {!! Form::text('tolerancia', '', ['class' => 'form-control medida tolerancia_modal', 'placeholder' => 'Tolerância']) !!}
                </div>
                <div class="col my-2 mr-3">
                    <a href="#" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir medida" onclick="excluirMedida(this)"></a>
                </div>
                <div class="col my-2 mr-3">
                    <a href="#" class="bt-draggable"></a>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="ficha-tecnica-sequencia" role="tabpanel" aria-labelledby="dados-tab">
            <div class="row">
                <div class="col">
                    <form id='sequencia-form' action="#" onsubmit="return false;">
                        <div class="row">
                            <div class="col text-right">
                                <h4 class='float-left m-3'>Sequência operacional</h4>
                                {{ Form::button('Novo item da sequência', ['class' => 'btn btn-primary mt-2 float-right', 'id' => 'btn-nova-sequencia']) }}
                            </div>
                        </div>
                        <div class="sortable" id='div-sequencia'>
                            <div class="row m-2 titulos-sortable">
                                <div class="col-sm-6 border-right">
                                    Operação
                                </div>
                                <div class="col-sm-5 border-right">
                                    Tipo de ponto
                                </div>
                                <div class="col my-2 mr-3">
                                </div>
                            </div>

                            <div class="row border rounded py-2 m-2 placeholder-sortable @if(!empty($sequencias)) d-none @endif">
                                <div class="col text-center">
                                    <i>Nenhum item cadastrado</i>
                                </div>
                            </div>
                            @foreach($sequencias as $sequencia)
                            <div class="row border rounded py-2 m-2 linha-sequencia-div">
                                <div class="col-sm-6">
                                    {!! Form::text('operacao', $sequencia['operacao'], ['class' => 'form-control operacao_modal', 'placeholder' => 'Operações']) !!}
                                </div>
                                <div class="col-sm-5">
                                    {!! Form::text('tipo_ponto', $sequencia['tipo_ponto'], ['class' => 'form-control tipo_ponto_modal', 'placeholder' => 'Tipo de ponto']) !!}
                                </div>
                                <div class="col my-2 mr-3">
                                    <a href="#" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir operação" onclick="excluirOperacao(this)"></a>
                                </div>
                                <div class="col my-2 mr-3">
                                    <a href="#" class="bt-draggable"></a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div class="row">
                            <div id='div-salvar-sequencia' class="col text-right">
                                {{ Form::button('Salvar sequência', ['class' => 'btn btn-success', 'id' => 'btn-salvar-sequencia']) }}
                            </div>
                        </div>
                    </form>

                    <div id='sequencia_modal_modelo' class="row border rounded py-2 m-2 d-none linha-sequencia-div">
                        <div class="col-sm-6">
                            {!! Form::text('operacao', '', ['class' => 'form-control operacao_modal', 'placeholder' => 'Operações']) !!}
                        </div>
                        <div class="col-sm-5">
                            {!! Form::text('tipo_ponto', '', ['class' => 'form-control tipo_ponto_modal', 'placeholder' => 'Tipo de ponto']) !!}
                        </div>
                        <div class="col my-2 mr-3">
                            <a href="#" class="bt-delete" data-toggle="tooltip" data-placement="top" title="Excluir operação" onclick="excluirOperacao(this)"></a>
                        </div>
                        <div class="col my-2 mr-3">
                            <a href="#" class="bt-draggable"></a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
    </div>
</div>
<script>
    $(document).ready( function () {

        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            trigger: 'hover',
            placement: 'right',
            template: '<div class="popover popover-estoque" role="popover"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $(document).find('#btn_novo_item').on('click', function(){
            novaComposicao();
        });

        table_filters_composicao.on('draw', function(){
            $(document).find('#table-filters-composicao').find('.bt-delete').off('click');
            $(document).find('#table-filters-composicao').find('.bt-delete').on('click', function(){
                deleteComposicao($(this));
            });

            $(document).find('#table-filters-composicao').find('.bt-edit').off('click');
            $(document).find('#table-filters-composicao').find('.bt-edit').on('click', function(){
                editComposicao($(this));
            });

            var total_composicao = 0;

            table_filters_composicao.rows().column(6).data().each(function(data){
                if($.isNumeric(parseFloat(data.replace(/,/g, '.')))){
                    total_composicao += parseFloat(data.replace(/,/g, '.'));
                }
            });

            total_composicao_gerencial = 0;
            
            table_filters_composicao.rows().column(8).data().each(function(data){
                if($.isNumeric(parseFloat(data.replace(/,/g, '.')))){
                    total_composicao_gerencial += parseFloat(data.replace(/,/g, '.'));
                }
            });

            $(document).find('#custo-total-composicao').html(total_composicao.toFixed(2).toString().replace(/\./g, ','));

            $(document).find('#custo-total-composicao-gerencial').html(total_composicao_gerencial.toFixed(2).toString().replace(/\./g, ','));
            
            custo_total_servicos = $(document).find('#custo-total-servicos').html();

            if(custo_total_servicos != "" || !$.isEmptyObject(custo_total_servicos)){
                var total_servicos = parseFloat(custo_total_servicos.replace(/,/g, '.'));
            }else{
                var total_servicos = 0;
            }

            $(document).find('#custo-total-produto').html("Custo contábil total do produto: " + (total_composicao + total_servicos).toFixed(2).toString().replace(/\./g, ',') + " - Custo gerencial total do produto: " + (total_composicao_gerencial + total_servicos).toFixed(2).toString().replace(/\./g, ','));
        });
        
        table_filters_servicos.on('draw', function(){
            $(document).find('#table-filters-projeto-servico').find('.bt-delete').off('click');
            $(document).find('#table-filters-projeto-servico').find('.bt-delete').on('click', function(){
                deleteComposicao($(this));
            });

            $(document).find('#table-filters-projeto-servico').find('.bt-edit').off('click');
            $(document).find('#table-filters-projeto-servico').find('.bt-edit').on('click', function(){
                editComposicao($(this));
            });

            var total_servicos = 0;

            table_filters_servicos.rows().column(6).data().each(function(data){
                if($.isNumeric(parseFloat(data.replace(/,/g, '.')))){
                    total_servicos += parseFloat(data.replace(/,/g, '.'));
                }
            });

            $(document).find('#custo-total-servicos').html(total_servicos.toFixed(2).toString().replace(/\./g, ','));
            
            custo_total_composicao = $(document).find('#custo-total-composicao').html();

            if(custo_total_composicao != "" || !$.isEmptyObject(custo_total_composicao)){
                var total_composicao = parseFloat(custo_total_composicao.replace(/,/g, '.'));
            }else{
                var total_composicao = 0;
            }

            custo_total_composicao_gerencial = $(document).find('#custo-total-composicao-gerencial').html();

            if(custo_total_composicao_gerencial != "" || !$.isEmptyObject(custo_total_composicao_gerencial)){
                var total_composicao_gerencial = parseFloat(custo_total_composicao_gerencial.replace(/,/g, '.'));
            }else{
                var total_composicao_gerencial = 0;
            }

            $(document).find('#custo-total-produto').html("Custo contábil total do produto: " + (total_composicao + total_servicos).toFixed(2).toString().replace(/\./g, ',') + " - Custo gerencial total do produto: " + (total_composicao_gerencial + total_servicos).toFixed(2).toString().replace(/\./g, ','));
        });

        $(document).find('#btn-excluir-imagem').on('click', function(){
            apagarImagemProduto();
        });

        $(document).find('#btn-salvar-etiqueta').on('click', function(){
            salvarEtiqueta();
        });

        $(document).find('.btn-apagar-etiqueta').on('click', function(){
            apagarEtiqueta(this);
        })

        $(document).find('.btn-apagar-instrucao').on('click', function(){
            apagarInstrucao(this);
        })

        $(document).find('.medida').maskMoney({thousands:'', decimal:','});

        $(document).find('#btn-nova-medida').on('click', function(){
            $(document).find('#medida_modal_modelo')
                .clone()
                .appendTo('#div-medidas')
                .prop('id', '')
                .removeClass('d-none');

            $(document).find('#div-medidas').find('.placeholder-sortable').addClass('d-none');
            $(document).find('.medida').maskMoney({thousands:'', decimal:','});
        })

        $(document).find('#btn-nova-sequencia').on('click', function(){
            $(document).find('#sequencia_modal_modelo')
                .clone()
                .appendTo('#div-sequencia')
                .prop('id', '')
                .removeClass('d-none');

            $(document).find('#div-sequencia').find('.placeholder-sortable').addClass('d-none');
        })
    
        $( ".sortable" ).sortable({
            items: 'div.row:not(.placeholder-sortable, .titulos-sortable)'
        });

        $( ".sortable" ).disableSelection();

        $(document).find('#btn-salvar-medidas').on('click', function(){
            salvarMedidas();
        });

        $(document).find('#btn-salvar-sequencia').on('click', function(){
            salvarSequencia();
        });

        $(document).find('#btn-salvar-montagem').on('click', function(){
            salvarInstrucao();
        });
        
        $(document).find('.medida').maskMoney({thousands:'', decimal:','});

        inicializaFancybox();
    });

    function inicializaFancybox(){
        $(document).find(".foto-thumb").fancybox(
            {
                onComplete: function(){
                    $('#fancybox-content')
                        .on('mouseover', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
                        })
                        .on('mouseout', function(){
                            $(this).children('#fancybox-img').css({'transform': 'scale(1)'});
                        })
                        .on('mousemove', function(e){
                            $(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
                            $(this).children('#fancybox-img').css({'cursor': 'pointer'});
                        })
                        .on('click', function(){
                            window.open($(this).children('#fancybox-img').prop('src'));
                        });
                }
            }
        );
    }
    table_filters_composicao = $(document).find('#table-filters-composicao').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
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
            { "width": "25%", "targets": "td_modal_descricao"},
            { "width": "25%", "targets": "td_modal_grupo"},
            { "width": "5%", 'class': 'tb_number td_preco', "targets": "tb_number" },
            { "width": "2%", "orderable": false, "targets": "tb_buttons" }
        ]
    });

    table_filters_servicos = $(document).find('#table-filters-projeto-servico').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
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
            { "width": "25%", "targets": "td_modal_descricao"},
            { "width": "25%", "targets": "td_modal_grupo"},
            { "width": "5%", 'class': 'tb_number td_preco', "targets": "tb_number" },
            { "width": "2%", "orderable": false, "targets": "tb_buttons" }

        ]
    });

    function deleteComposicao(obj){

        var $title = "Atenção";
        var $text = "<p>Isso irá apagar este item de composição da ficha técnica. Deseja continuar?</p>";
        var $name_option_ok = "deletar";
        var $class = "dialog_option_deletar";

        $(document).off("deletar");
        $(document).on("deletar", function(){

            $.ajax({
                url: "{{ route('ficha_tecnica.cadastro.composicao.excluir') }}",
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: obj.data('id'),
                    origem: obj.data('origem')
                },
                method: 'POST',
                success: function(data){
                    
                    obj.tooltip('hide');
                    
                    if(obj.data('origem') == 'tecido' || obj.data('origem') == 'insumo'){
                        table_filters_composicao.row(obj.parents('tr')).remove().draw();
                    }
                    else if(obj.data('origem') == 'servico'){
                        table_filters_servicos.row(obj.parents('tr')).remove().draw();
                    }
                },
                error: function(callback){
                    message("Atenção", "Ocorreu uma instabilidade, tente novamente!", 'message-erro-excluir');
                }
            })

        });

        $(document).off("cancelar");
        $(document).on("cancelar", function(){
            return null
        });

        message_option($title, $text, $class, $name_option_ok, '', "cancelar");

    }

    function novaComposicao(){
        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.composicao.modal.novo') }}",
            data: {
                _token: '{{ csrf_token() }}',
                ficha_id: '{{ $produto['id'] }}'
            },
            method: 'POST',
            success: function(data){
                createModal('modal-new-consumo', 'Novo consumo', data, '');
            },
            error: function(callback){
                message("Atenção", "Ocorreu uma instabilidade, tente novamente!", 'message-erro-excluir');
            }
        });
    }

    function editComposicao(obj){
        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.composicao.modal.editar') }}",
            data: {
                _token: '{{ csrf_token() }}',
                id: obj.data('id'),
                origem: obj.data('origem')
            },
            method: 'POST',
            success: function(data){
                createModal('modal-edit-consumo', 'Editar consumo', data, '');
            },
            error: function(callback){
                message("Atenção", "Ocorreu uma instabilidade, tente novamente!", 'message-erro-excluir');
            }
        });
    }

    function salvarReferenciaPecaLavagem(){

        var formData = new FormData($(document).find('#referencia-peca-lavagem-form')[0]);

        var form = $(document).find('#referencia-peca-lavagem-form');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.referecia_peca_lavagem.salvar') }}",
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            success: function(data){

                if(data.response.url != ''){
                    d = new Date();
                    
                    $(document)
                        .find("#info-adicional-foto")
                        .attr("src", data.response.url+"?"+d.getTime())
                        .parent()
                        .attr('href', data.response.url+"?"+d.getTime());

                    $(document).find('#info-adicional-foto-div').removeClass('d-none');
                    $(document).find("#div-btn-excluir-imagem-produto").removeClass('d-none');
                }

                $(document).find("#imagem_produto_modal").val('');
            },
            error: function(callback){
				var errors = callback.responseJSON.errors;
				for(var field in errors){
					showErrorsInputs(form, field, errors[field])
				}
            }
        });
    }

    function apagarImagemProduto(){
		$data = $(document).find('#referencia-peca-lavagem-form').serialize()
		form = $(document).find("#referencia-peca-lavagem-form");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');
		
		$.ajax({
			url: '{{ route('ficha_tecnica.cadastro.referecia_peca_lavagem.apagar_imagem') }}',
			type: 'POST',
			data: $data,
			success: function(data){
                d = new Date();

                $(document)
                    .find("#info-adicional-foto")
                    .attr("src", '')
                    .parent()
                    .attr('href', '');

                $(document).find('#info-adicional-foto-div').addClass('d-none');

                $(document).find("#div-btn-excluir-imagem-produto").addClass('d-none');
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				for(var field in errors){
					showErrorsInputs(form, field, errors[field])
				}
			}
		});
    }
    
    function salvarEtiqueta(){

        var formData = new FormData($(document).find('#etiqueta-form')[0]);

        var form = $(document).find('#etiqueta-form');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.etiqueta.salvar') }}",
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            success: function(data){
                d = new Date();
                $(document).find("#etiqueta-row").append(data.response.imagem);
                $(document).find("#imagem_etiqueta_modal").val('');
                $(document).find("#div-btn-excluir-imagem-produto").removeClass('d-none');

                $(document).find('.btn-apagar-etiqueta').off('click');
                $(document).find('.btn-apagar-etiqueta').on('click', function(){
                    apagarEtiqueta(this);
                })

                $(document).find("#etiquetas-titulo").removeClass('d-none');
                
                inicializaFancybox();
            },
            error: function(callback){
                var errors = callback.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function apagarEtiqueta(elemento){
        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.etiqueta.excluir') }}",
            data: {
                _token: '{{ csrf_token() }}',
                id: $(elemento).data('id')
            },
            method: 'POST',
            success: function(){
                $(elemento).parent().parent().remove();

                if($(document).find(".etiquetas-div").length == 0){
                    $(document).find("#etiquetas-titulo").addClass('d-none');
                }
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function showErrorsInputArray(input, message){
        input.after("<label class='error-message' for='"+input.attr('id')+"'>"+message+"</label>");
        input.addClass('error-input');
    }

    function excluirMedida(elemento){
        $(elemento).parent().parent().remove();
        $('.tooltip').tooltip('hide');

        if($(document).find('.linha-medida-div').not('.d-none').length == 0){
            $(document).find('#div-medidas').find('.placeholder-sortable').removeClass('d-none');
        }
    }
    
    function salvarMedidas(){

        $(document).find('#div-medidas').find('.error-message').remove();
        $(document).find('#div-medidas').find('.error-input').removeClass('error-input');
        
        var medidas = new Array();

        $(document).find('.linha-medida-div').not('#medida_modal_modelo').each(function (index){

            if(
                $.trim($(this).find('.medida_descricao_modal').val()) != '' ||
                $.trim($(this).find('.medida_p_modal').val()) != '' ||
                $.trim($(this).find('.medida_m_modal').val()) != '' ||
                $.trim($(this).find('.medida_g_modal').val()) != '' ||
                $.trim($(this).find('.medida_gg_modal').val()) != '' ||
                $.trim($(this).find('.medida_xg_modal').val()) != '' ||
                $.trim($(this).find('.medida_xgg_modal').val()) != '' ||
                $.trim($(this).find('.tolerancia_modal').val()) != ''
            ){
                var linha = {
                    'medida_descricao': $(this).find('.medida_descricao_modal').val(),
                    'medida_p': $(this).find('.medida_p_modal').val(),
                    'medida_m': $(this).find('.medida_m_modal').val(),
                    'medida_g': $(this).find('.medida_g_modal').val(),
                    'medida_gg': $(this).find('.medida_gg_modal').val(),
                    'medida_xg': $(this).find('.medida_xg_modal').val(),
                    'medida_xgg': $(this).find('.medida_xgg_modal').val(),
                    'tolerancia': $(this).find('.tolerancia_modal').val(),  
                };

                medidas.push(linha);
            }
            else{
                $(this).remove();
            }

        });

        var $data = {
            _token: '{{ csrf_token() }}',
            ficha_id: $(document).find('#ficha_id_modal').val(),
            medidas: medidas
        };
		
		$.ajax({
			url: '{{ route('ficha_tecnica.cadastro.medidas.salvar') }}',
			type: 'POST',
			data: $data,
			success: function(){
				message('Sucesso', "Informações salvas com sucesso");
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				for(var field in errors){
                    var linha = field.split('.');
                    var campo = $(document).find('.linha-medida-div').eq(linha[1]).find('[name="'+linha[2]+'"]');
					showErrorsInputArray(campo, errors[field])
				}
			}
		});
    }


    function excluirOperacao(elemento){
        $(elemento).parent().parent().remove();
        $('.tooltip').tooltip('hide');

        if($(document).find('.linha-sequencia-div').not('.d-none').length == 0){
            $(document).find('#div-sequencia').find('.placeholder-sortable').removeClass('d-none');
        }
    }

    function salvarSequencia(){
        var sequencia = new Array();

        $(document).find('#div-sequencia').find('.error-message').remove();
        $(document).find('#div-sequencia').find('.error-input').removeClass('error-input');

        $(document).find('.linha-sequencia-div').not('#sequencia_modal_modelo').each(function (index){
            if(
                $.trim($(this).find('.operacao_modal').val()) != '' ||
                $.trim($(this).find('.tipo_ponto_modal').val()) != ''
            ){

                var linha = {
                    operacao: $(this).find('.operacao_modal').val(),
                    tipo_ponto: $(this).find('.tipo_ponto_modal').val()
                }

                sequencia.push(linha);
            }
            else{
                $(this).remove();
            }
        });

        var $data = {
            _token: '{{ csrf_token() }}',
            ficha_id: $(document).find('#ficha_id_modal').val(),
            sequencia: sequencia
        };
		
		$.ajax({
			url: '{{ route('ficha_tecnica.cadastro.sequencia.salvar') }}',
			type: 'POST',
			data: $data,
			success: function(){
				message('Sucesso', "Informações salvas com sucesso");
			},
			error: function(data){
				var errors = data.responseJSON.errors;
				for(var field in errors){
                    var linha = field.split('.');
                    var campo = $(document).find('.linha-sequencia-div').eq(linha[1]).find('[name="'+linha[2]+'"]');
					showErrorsInputArray(campo, errors[field])
				}
			}
		});
    }

    function salvarInstrucao(){

        var formData = new FormData($(document).find('#montagem-form')[0]);

        var form = $(document).find('#montagem-form');

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.montagem.salvar') }}",
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            success: function(data){
                d = new Date();
                $(document).find("#montagem-row").append(data.response.imagem);
                $(document).find("#imagem_montagem_modal").val('');
                $(document).find("#descricao_montagem_modal").val('');

                $(document).find('.btn-apagar-instrucao').off('click');
                $(document).find('.btn-apagar-instrucao').on('click', function(){
                    apagarInstrucao(this);
                })

                $(document).find("#montagem-titulo").removeClass('d-none');

                inicializaFancybox();
            },
            error: function(callback){
                var errors = callback.responseJSON.errors;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function apagarInstrucao(elemento){
        $.ajax({
            url: "{{ route('ficha_tecnica.cadastro.montagem.excluir') }}",
            data: {
                _token: '{{ csrf_token() }}',
                id: $(elemento).data('id')
            },
            method: 'POST',
            success: function(){
                $(elemento).parent().parent().remove();

                if($(document).find(".montagem-div").length == 0){
                    $(document).find("#montagem-titulo").addClass('d-none');
                }
            }
        });
    }

</script>
@endsection
