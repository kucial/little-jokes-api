<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Arr;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;

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
     * @param  \Throwable  $exception
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
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $request->expectsJson()
            ? response()->json([
                'code' => 'NOT_AUTHENTICATED',
                'message' => $exception->getMessage()
            ], 401)
            : redirect()->guest($exception->redirectTo() ?? route('login'));
    }


    /**
     * Custom invalid json with
     *
     * @param \Illuminate\Http\Request $request
     * @param ValidationException $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'code' => 'VALIDATION_FAILED',
            'message' => $exception->getMessage(),
            'data' => $exception->errors(),
        ], $exception->status);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        return config('app.debug') ? [
            'code' => $this->getErrorCode($e),
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => collect($e->getTrace())->map(function ($trace) {
                return Arr::except($trace, ['args']);
            })->all(),
        ] : [
            'code' => $this->getErrorCode($e),
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error',
        ];
    }

    protected function getErrorCode(Throwable $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return 'RESOURCE_NOT_FOUND';
        }

        return 'ERROR';
    }
}
