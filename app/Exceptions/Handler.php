<?php

namespace App\Exceptions;

use Exception;
use Request;
use Illuminate\Auth\AuthenticationException;
use Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
Use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception){
        header('Access-Control-Allow-Origin: *');
        return $request->expectsJson()
                ? response()->json([
                        "error" => [
                            "error" => true,
                            "msg" => [
                                "dev" => "Ocorreu um erro no manuseio do token",
                                "user" => "Credencial expirada, favor efetue o login novamente."
                            ]
                        ],
                        "request" => new \stdClass(),
                        "response" => new \stdClass()
                    ], 403)
                : redirect()->guest(route('login'));
    }
}

