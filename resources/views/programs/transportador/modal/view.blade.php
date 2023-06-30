@extends('layouts.page-dialog')

@section('content')
    <div class="content-view-cliente">
        <div class="row-table">
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Código de cadastro</div></div>
                <div class="value">{!! $dados["codigo"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CNPJ</div></div>
                <div class="value">{!! $dados["cnpj"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Inscrição estadual</div></div>
                <div class="value">{!! $dados["inscricao_estadual"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome</div></div>
                <div class="value">{!! $dados["nome"] !!}</div>
            </div>
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome Fantasia / Apelido</div></div>
                <div class="value">{!! $dados["nome_fantasia"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Telefone</div></div>
                <div class="value">{!! $dados["telefone"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Fax</div></div>
                <div class="value">{!! $dados["fax"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail</div></div>
                <div class="value">{!! $dados["email"] !!}</div>                
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-1">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CEP</div></div>
                <div class="value">{!! $dados["cep"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Endereço</div></div>
                <div class="value">{!! $dados["endereco"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Bairro</div></div>
                <div class="value">{!! $dados["bairro"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cidade</div></div>
                <div class="value">{!! $dados["cidade"] !!}</div>
            </div>
            <div class="cel-table col-lg-1">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Estado</div></div>
                <div class="value">{!! $dados["estado"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Via de Transporte</div></div>
                <div class="value">{!! $dados["via_transporte"] !!}</div>
            </div>
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Faz coleta</div></div>
                <div class="value">{!! $dados["coleta"] !!}</div>
            </div>
        </div>
    </div>
@endsection