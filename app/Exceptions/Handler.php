<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Exception $e, $request) {
            return $this->handleException($request, $e);
        });
    }

    public function handleException($request, Exception $exception)
    {
        if ($exception instanceof RouteNotFoundException) {
            return response('The specified URL cannot be  found.', 404);
        }
    }

    // public function render($request, Throwable $e)
    // {
    //     if ($e instanceof ModelNotFoundException) {
    //         return response()->json(['message' => 'Data Not Found'], 404);
    //     } elseif ($this->isHttpException($e)) {
    //         return $this->renderHttpException($e);
    //     }
    //     return parent::render($request, $e);
    // }
}
