@extends('layouts.app')

@section('content-filter')
    <form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
        @csrf
        <h3>Listagem de {{ CustomView::programaName() }}</h3>
        <div class="content-fields">

            <div class="col-sm-2">Código</div>
            <div class="col-sm-2">Marca</div>
            <div class="col-sm-2">Linha</div>
            <div class="col-sm-2">Grupo</div>
            <div class="col-sm-2">Subgrupo</div>
            <div class="col-sm-2">Composição</div>
            <div class="col-sm-2">Gramatura</div>
            <div class="col-sm-2">Largura</div>
            <div class="col-sm-2">Unidade</div>
            <div class="col-sm-2">Preço</div>

        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <button type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear">Limpar busca</button>
            <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
        </div>
    </form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-produtos-preco">
        <thead>

            <tr>
                <th>Código</th>
                <th>Marca/th>
                <th>Linha</th>
                <th>Grupo</th>
                <th>Subgrupo</th>
                <th>Composição</th>
                <th>Gramatura</th>
                <th>Largura</th>
                <th>Unidade</th>
                <th>Preço</th>
                <th>Editar</th>
                <th>Deletar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

<script>
@section('script-footer')

@endsection
</script>