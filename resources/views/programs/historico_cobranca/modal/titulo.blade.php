@extends('layouts.page-dialog')

@section('content')
<div class="row">
    
    <div class="col-sm-6"><b>Título:</b><br>
        {{ $titulo }}
    </div>

    <div class="col-sm-6"><b>Estabelecimento:</b><br>
        {{ $estabelecimento }}
    </div>
    
    <div class="col-sm-12"><b>Cliente:</b><br>
        {{ $cliente }}
    </div>
    
    <div class="col-sm-6"><b>Parcela:</b><br>
        {{ $parcela }}
    </div>
    
    <div class="col-sm-6"><b>Vencimento:</b><br>
        {{ $vencimento }}
    </div>
    
    @if(isset($vencimento_original) && !empty($vencimento_original))
    <div class="col-sm-6"><b>Vencimento original:</b><br>
        {{ $vencimento_original }}
    </div>
    @endif
    
    @if(isset($data_pagamento) && !empty($data_pagamento))
    <div class="col-sm-6"><b>Data de Pagamento:</b><br>
        {{ $data_pagamento }}
    </div>
    @endif
    
    <div class="col-sm-6"><b>Valor do título:</b><br>
        {{ $valor }}
    </div>
    
    @if(isset($saldo) && !empty($saldo))
    <div class="col-sm-6"><b>Saldo do título:</b><br>
        {{ $saldo }}
    </div>
    @endif
    
    @if(isset($banco) && !empty($banco))
    <div class="col-sm-6"><b>Banco:</b><br>
        {{ $banco }}
    </div>
    @endif

    @if(isset($agencia) && !empty($agencia))
    <div class="col-sm-6"><b>Agência:</b><br>
        {{ $agencia }}
    </div>
    @endif
    
    @if(isset($conta) && !empty($conta))
    <div class="col-sm-6"><b>Conta:</b><br>
        {{ $conta }}
    </div>
    @endif
</div>
@endsection