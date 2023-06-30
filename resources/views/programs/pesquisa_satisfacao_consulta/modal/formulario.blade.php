@extends('layouts.page-dialog')

@section('content')
<div class="tab-content">
    <div class="tab-pane show active" role="tabpanel" aria-labelledby="dados-tab">
        <div class="content-tab">
            @foreach ($retorno as $item)
                <div class="row-tab">
                    <div class="field-tab w-100">
                        <div class="title">{{ $item['pergunta'] }}</div>
                        <div>
                            <p>{{ $item['resposta'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection