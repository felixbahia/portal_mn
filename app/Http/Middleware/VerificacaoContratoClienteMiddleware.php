<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerificacaoContratoClienteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next, $guard = null)
    {
        
        if(!empty(Auth::user())){
            //return redirect()->route('login');
            if (Auth::user()->tipo_usuario_id === 21) {
                if(Auth::user()->contrato === false){
                    $uris = ['contrato/cliente', 'contrato/cliente/aceito', 'contrato/cliente/recusado', 'contrato/fornecimento', 'logout'];
                    if(!in_array($request->path(), $uris)){
                        return redirect()->route('contrato.cliente.index');
                    }
                }
            }
        }
        
        return $next($request);
    }
}
