<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
        $this->renderable(function (Throwable $exception, $request) {
            if ($this->isHttpException($exception)) {
                $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
                $titles = [
                    403 => 'Forbidden',
                    404 => 'Not Found',
                    500 => 'Internal Server Error',
                    431 => 'Error'
                ];
                $errorTitle = $titles[$statusCode] ?? 'Error';
                $errorMessage = $exception->getMessage() ?: 'Sorry, something went wrong.';

                return response()->view('errors.error', [
                    'errorCode' => $statusCode,
                    'errorTitle' => $errorTitle,
                    'errorMessage' => $errorMessage,
                ], $statusCode);
            }
        });
    }
}
