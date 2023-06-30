@extends('layouts.page-dialog')

@section('content')
	<div class="row">
        <div class="col">
            <b>Pedido</b><br>
            <a href="#" data-route="{{  route('pedidos_orcamentos.modal') }}" data-id="{{ $retorno['id_pedido'] }}" data-modal="modal-lg" data-title_modal="Detalhes do pedido: {{ $retorno['pedido_nasajon'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModal(this);">{{ $retorno['pedido_nasajon'] }}</a>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <b>Cliente</b><br>
            {{ $retorno['cliente'] }}
        </div>
        <div class="col">
            <b>Emissão do Pedido</b><br>
            {{ $retorno['data_pedido_nasajon'] }}
        </div>
    </div>
    <div class="row">
        <div class="col">
            <b>Nota Fiscal</b><br>
            <a href="#" data-id="{{ $retorno['id_nota'] }}" data-modal="modal-lg" data-title_modal="Detalhes da nota: ' {{ $retorno['nota_fiscal'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNota(this);">{{ $retorno['nota_fiscal'] }}</a>
        </div>
        <div class="col">
            <b>Emissão da Nota</b><br>
            {{ $retorno['data_nota_fiscal'] }}
        </div>
    </div>
    <div class="row">
        <div class="col">
            <b>Valor do Título</b><br>
            {{ $retorno['valor_titulo'] }}
        </div>
        <div class="col">
            @if(!empty($retorno['valor_baixado']))
            <b>Valor pago</b><br>
            {{ $retorno['valor_baixado'] }}
            @endif
        </div>
    </div>
    <div class="row">
        <div class="col">
            @if(!empty($retorno['saldo']))
            <b>Saldo</b><br>
            {{ $retorno['saldo'] }}
            @endif
        </div>
        <div class="col">
            <br>
            <a href="#" data-toggle="tooltip" data-placement="top" title="Lançamentos vinculados" onclick="showModalCheques('{{ $retorno['id'] }}')"><b>Ver lançamentos vinculados</b></a>
        </div>
    </div>

    <script>
        function showModal($this){
            var url = $($this).data("route");
            var $id = $($this).data("id");
            var modal_class = $($this).data("modal");
            var title = $($this).data("title_modal");
            $.ajax({
                url: url,
                method: 'POST',
                data: {_token: "{{ csrf_token() }}", id: $id},
                success: function(body){
                    createModal('modal_message_edit', title, body, modal_class);
                }
            });
        }

        function showModalNota($this){
            var url = '{{ route('notas_nasajon.modal.exibir') }}';
            var $id = $($this).data("id");
            var modal_class = $($this).data("modal");
            var title = $($this).data("title_modal");
            $.ajax({
                url: url,
                method: 'POST',
                data: {_token: "{{ csrf_token() }}", id_nota: $id},
                success: function(body){
                    createModal('modal_message_edit', title, body, modal_class);
                }
            });
        }

        function showModalCheques($id){
            var title = "Lista de lançamentos vinculados";
            $.ajax({
                url: '{{ route('titulos_prepago.modal') }}',
                method: 'POST',
                data: {
                    _token: '{{csrf_token()}}',
                    id: $id
                },
                success: function(body){
                    $(document).find('#cliente_searsh_show').remove();
                    createModal("cheques-mdal", title, body, 'modal-lg');
                }
            });
        }
    </script>
@endsection