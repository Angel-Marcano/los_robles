<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
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
    }

    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);
        // Envolver solo respuestas JSON para rutas API
        if (str_starts_with($request->path(), 'api/') && $this->shouldWrap($response)) {
            $status = $response->getStatusCode();
            $code = $this->mapExceptionToCode($e, $status);
            // Usar traducción si el mensaje está vacío (o es igual al status text genérico)
            $rawMessage = $e->getMessage();
            $message = $rawMessage ?: __("errors.$code");
            return response()->json([
                'error' => [
                    'code' => $code,
                    'message' => $message,
                ],
            ], $status, $response->headers->all());
        }
        return $response;
    }
    protected function shouldWrap($response): bool {
        $contentType = (string) $response->headers->get('Content-Type');
        $isJson = $contentType !== '' && str_contains($contentType, 'application/json');
        if (!$isJson) {
            return true;
        }
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            return !isset($data['error']);
        }
        return true;
    }
    protected function mapExceptionToCode(Throwable $e,int $status): string {
        if(method_exists($e,'getStatusCode')){ $status=$e->getStatusCode(); }
        switch($status){
            case 404: return 'NOT_FOUND';
            case 401: return 'UNAUTHORIZED';
            case 403: return 'FORBIDDEN';
            case 422: return 'VALIDATION_ERROR';
            case 429: return 'RATE_LIMIT_EXCEEDED';
            default: return 'SERVER_ERROR';
        }
    }
}
