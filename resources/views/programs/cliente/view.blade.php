@extends('layouts.page-dialog')

@section('content')
    <div class="content-view-cliente">
        <div class="row-table">
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Código de cadastro</div></div>
                <div class="value">{!! $dados["codcad"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CPF / CNPJ</div></div>
                <div class="value">{!! $dados["cgc_cpf"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Inscrição estadual</div></div>
                <div class="value">{!! $dados["iest"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome / Razão Social</div></div>
                <div class="value">{!! $dados["nome"] !!}</div>
            </div>
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome Fantasia / Apelido</div></div>
                <div class="value">{!! $dados["guerra"] !!}</div>
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
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (NFe)</div></div>
                <div class="value">{!! $dados["email"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (Compras; post.pedido, etc..)</div></div>
                <div class="value">{!! $dados["email_adic1"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (Cobrança)</div></div>
                <div class="value">{!! $dados["email_adic2"] !!}</div>
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
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Mensagem de Alerta</div></div>
                <div class="value">{!! $dados["alerta"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Representante</div></div>
                <div class="value">{!! $dados["vendedor"] !!}</div>                
            </div>
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cliente Desde</div></div>
                <div class="value">{!! $dados["dtdesde"] !!}</div>
            </div>
        </div>
    </div>
    <script>
        $('.content-view-cliente').find(".value, .title").find("div").off('mouseenter');
        $('.content-view-cliente').find(".value, .title").find("div").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
    </script>
@endsection