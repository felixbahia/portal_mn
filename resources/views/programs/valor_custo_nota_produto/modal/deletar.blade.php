@extends('layouts.page-dialog')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <b>Deseja realmente excluir os custos deste pedido?</b>
    </div>
</div>
<br>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Número do Pedido</b></div>
    <div class='col-sm-6'>{{ $numero_pedido }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Estabelecimento</b></div>
    <div class='col-sm-6'>{{ $estabelecimento }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Fornecedor</b></div>
    <div class='col-sm-6'>{{ $fornecedor }}</div>
</div>
<div class='row border-bottom'>
    <div class='col-sm-6'><b>Data da compra</b></div>
    <div class='col-sm-6'>{{ $data_compra }}</div>
</div>

<div class="content-buttons">
    <button name="excluir" id="btn-excluir" class="btn btn-danger float-right mt-2 mr-2">Excluir</button>
</div>

<script>
    $(document).ready( function(){
        $(document).find("#btn-excluir").on('click', function(){
            $.ajax({
                url: '{{ route('valor_custo_nota_produto.excluir_custo')}}',
                dataType: 'json',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'id': '{{ $id }}'
                },
                method: 'POST',
                success: function(){
                    filterAjax($("#form_filter").serialize());
                    $(document).find('#modal_novo').modal('hide');
                }
            });
        });
    });
</script>

@endsection