@extends('layouts.page-dialog')

@section('content')

<div class="row">
    <div class="col-sm-4">
        <p>
            <b>Estabelecimento</b><br>
            {{ $estabelecimento }}
        </p>
    </div>
    <div class="col-sm-4">
        <p>
            <b>Número da Nota</b><br>
            <a href="#" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNotaModal('{!! $nota_id !!}')">{!! $nota_fiscal !!}</a>
        </p>
    </div>
    <div class="col-sm-4">
        <p>
            <b>Cliente</b><br>
            {{ $cliente }}
        </p>
    </div>
</div>
<div class="row">
    <div class="col-sm-4">
        <p>
            <b>Data de emissão</b><br>
            {!! $emissao !!}
        </p>
    </div>
    <div class="col-sm-4">
        <p>
            <b>Valor</b><br>
            {{ $valor }}
        </p>
    </div>
    @if($valor_parcial === true) 
    <div class="col-sm-4" id='valor-div'>
        <p>
            <b>Valor devolvido</b><br>
            {!! $valor_devolvido !!}
        </p>
    </div>
    @endif
    <div class="col-sm-4">
        <p>
            <b>Motivo</b><br>
            {!! $motivo !!}
        </p>
    </div>
    <div class="col-sm-4">
        <p>
            <b>Status</b><br>
            {!! $status !!}
        </p>
    </div>
    <div class="col-sm-4">
        <p>
            <b>Tipo de devolução</b><br>
            {!! $tipo_devolucao !!}
        </p>
    </div>
    @if(!empty($motivo_reprovacao))
        <div class="col-sm-4">
            <p>
                <b>Motivo da Reprovação</b><br>
                {!! $motivo_reprovacao !!}
            </p>
        </div>
    @endif
    @if(isset($responsabilidade_frete) && !empty($responsabilidade_frete))
    <div class="col-sm-4">
        <p>
            <b>Responsabilidade do frete</b><br>
            {!! $responsabilidade_frete !!}
        </p>
    </div>
    @endif
    @if(isset($frete_valor) && !empty($frete_valor))
    <div class="col-sm-4">
        <p>
            <b>Valor do Frete</b><br>
            {!! $frete_valor !!}
        </p>
    </div>
    @endif
    @if(isset($nota_remessa_id) && !empty($nota_remessa_id))
    <div class="col-sm-4">
        <p>
            <b>Nota de remessa</b><br>
            <a href="#" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNotaModal('{!! $nota_remessa_id !!}')">{!! $nota_remessa !!}</a>
        </p>
    </div>
    @endif
</div>

<div class="row">
    <div class="col">
        @if(isset($laudo_tecnico) && !empty($laudo_tecnico))
            <a href="{{ $laudo_tecnico }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> Laudo técnico</a> 
        @endif
        @if(isset($arquivo) && !empty($arquivo))
            <a href="{{ $arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Imagem do produto</a>
        @endif
        @if(isset($nota_cliente) && !empty($nota_cliente))
            <a href="{{ $nota_cliente }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Nota do cliente</a>
        @endif
        @if(isset($romaneio_arquivo) && !empty($romaneio_arquivo))
            <a href="{{ $romaneio_arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Romaneio</a>
        @endif
    </div>
</div>

@if(!in_array($status,['Finalizado', 'Cancelado']))
<div class="row mt-2">
    <div class="col">
        Modelo de nota de devolução:
    </div>
</div>
<div class="row mb-2">
    <div class="col-sm-2">
        <a href="{{ route('devolucao_nota_aprovacao.pdf.gerar', ['id' => $id]) }}" target='_blank'><i class="btn-nota-pdf"></i>Venda</a>
    </div>
    
    @if(in_array($codigo_estabelecimento, ['03', '04']))
    <div class="col-sm-2">
        <a href="{{ route('devolucao_nota_aprovacao.pdf.gerar', ['id' => $id, 'tipo' => 'armazem']) }}" target='_blank'><i class="btn-nota-pdf"></i>Remessa</a>
    </div>
    @endif
</div>
@endif

@if(!empty($nota_cliente_numero))
<div class="row mb-2">
    <div class="col">
        <b>Número da Nota do cliente:</b><br>
        {!! $nota_cliente_numero !!}
    </div>
</div>
@endif

@if(!empty($documentos_diversos))
    <div class="row mt-2">
        <div class="col">
            <b>Documentos:</b>
        </div>
    </div>
    <div class="row mb-2">
        @foreach ($documentos_diversos as $documentos_diversos)
            <div class="col-sm-2">
                <a href="{{  $documentos_diversos['caminho'] }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> {{ $documentos_diversos['descricao'] }}</a>    
                @if($documentos_diversos['verifica_email'] == true)
                    {!! Form::button('Reenviar E-mail', ['id' => 'reenviar_email', 'class' => 'btn btn-primary','title' => 'Reenviar e-mail a Transportadora.', 'onclick' =>"reenviarEmailTransportadora('".$documentos_diversos['id']."')"]) !!}   
                @endif
            </div>
        @endforeach
    </div>
@endif

@if(!empty($documento_isento))
    <div class="row mt-2">
        <div class="col">
            Cliente Isento:
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-sm-2">
            <a href="{{  $documento_isento }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> Documento Cliente Isento </a>         
        </div>
    </div>
@endif

@if(!empty($observacao))
<div class="row">
    <div class="col">
        <b>Observação:</b><br>
        {{ $observacao }}
    </div>
</div>
@endif

<div class="row">
    <div class="col-sm-4">
        <b>Criado por:</b><br>
        {!! $criado_por !!} - {!! $criado_em !!}
    </div>

    @if(isset($modificado_por))
    <div class="col-sm-4">
        <b>Modificado por:</b><br>
        {!! $modificado_por !!} - {!! $modificado_em !!}
    </div>
    @endif

    @foreach($aprovadores as $aprovador)
    <div class="col-sm-4">
        <b>{{ $aprovador['status'] }}</b><br>
        {{ $aprovador['aprovador'] }} - {{ $aprovador['data'] }}
    </div>
    @endforeach

    @if(isset($excluido_por))
    <div class="col-sm-4">
        <b>Cancelado por:</b><br>
        {!! $excluido_por !!} - {!! $excluido_em !!}
    </div>
    @endif
</div>

@if(!empty($produtos))
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
        <thead>
            <tr>
                <th>Código</th>
                <th>Grupo</th>
                <th>Descrição</th>
                <th class="tb_number">Quantidade</th>
                @if($mostrar_quantidades_devolvidas)
                <th class="tb_number">Recebida</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($produtos as $produto)
            <tr>
                <td>{{ $produto['codigo_produto'] }}</td>
                <td>{{ $produto['grupo'] }}</td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                <td>{{  $produto['quantidade']  }}</td>
                @if($mostrar_quantidades_devolvidas)
                <td>{{  $produto['quantidade_recebida']  }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($titulos_descontados))
<div class="content-dialog-table">
    <br>
    <div class="row">
        <div class="col-sm-12">
            <b>Títulos com Abatimentos</b>
        </div>
    </div>  
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-titulos_descontados">
        <thead>
            <tr>
                <th>Título</th>
                <th class="tb_date">Emissão</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_number">Valor</th>
                <th class="tb_number">Valor Baixa</th>
                <th class="tb_number">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos_descontados as $titulo_descontado)
            <tr>
                <td>{{ $titulo_descontado['titulo_numero'] }}</td>
                <td>{{ $titulo_descontado['data_emissao']  }}</td>
                <td>{{ $titulo_descontado['data_vencimento']  }}</td>
                <td>{{ $titulo_descontado['titulo_valor'] }}</td>
                <td>{{ $titulo_descontado['desconto_valor'] }}</td>
                <td>{{ $titulo_descontado['saldo_valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($titulos_creditos))
<div class="content-dialog-table">
    <br>
    <div class="row">
        <div class="col-sm-12">
            <b>Título Crédito</b>
        </div>
    </div>  
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-titulo_credito">
        <thead>
            <tr>
                <th>Título</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos_creditos as $titulo_credito)
            <tr>
                <td>{{ $titulo_credito['titulo_numero'] }}</td>
                <td>{{ $titulo_credito['titulo_valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if(!empty($titulos_cancelados))
<div class="content-dialog-table">
    <br>
    <div class="row">
        <div class="col-sm-12">
            <b>Títulos Cancelados</b>
        </div>
    </div>  
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-titulos_cancelados">
        <thead>
            <tr>
                <th>Título</th>
                <th class="tb_date">Emissão</th>
                <th class="tb_date">Vencimento</th>
                <th class="tb_number">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos_cancelados as $titulo_cancelado)
            <tr>
                <td>{{ $titulo_cancelado['titulo_numero'] }}</td>
                <td>{{ $titulo_cancelado['data_emissao']  }}</td>
                <td>{{ $titulo_cancelado['data_vencimento']  }}</td>
                <td>{{ $titulo_cancelado['titulo_valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
    function showModalNotaModal($id){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var modal_class = 'modal-lg';
        var title = 'Detalhes da nota';
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_nota_nasajon', title, body, modal_class);
            }
        });
    }

    function reenviarEmailTransportadora($id){
        var url = '{{ route('devolucao_nota.visualizar.reenviar_email_transportadora') }}';

        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(dados){
                console.log(dados);
                if(dados.status == 'success'){
                    message('Atenção','<b class="text-success">E-mail enviado com sucesso.</b>');
                }else{
                    message('Atenção','<b class="text-danger">Falha ao enviar e-mail.</b>');
                }
            }
        });
    }

    @if(!empty($produtos))
    table_filters_dialog_produtos = $(document).find('#table-filters-dialog-produtos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            { "targets": 2, "width": '60vh' },
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
        ]
    });
    @endif

    @if(!empty($titulos_descontados))
    table_filters_dialog_titulos_descontados = $(document).find('#table-filters-dialog-titulos_descontados').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            { "targets": 2, "width": '60vh' },
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "tb_date"},
        ]
    });
    @endif

    @if(!empty($titulos_cancelados))
    table_filters_dialog_titulos_cancelados = $(document).find('#table-filters-dialog-titulos_cancelados').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            { "targets": 2, "width": '60vh' },
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "tb_date"},
        ]
    });
    @endif

</script>

@endsection
