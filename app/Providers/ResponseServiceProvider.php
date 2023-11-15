<?php

namespace App\Providers;

use Illuminate\Support\Facades\Response;
use App\Http\Resources\ApiResource;
use Illuminate\Support\ServiceProvider;

class ResponseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->descriptiveResponseMethod();
    }

    protected function descriptiveResponseMethod()
    {
        $instance = $this;
        Response::macro('ok', function ($data = [], $message = 'Success') {
            // return new ApiResource(200, $message, $data);
            return [
                'status' => 200,
                'message' => $message,
                'data' => $data,
            ];
        });

        Response::macro('created', function ($data = [], $message = 'Created successfully') {
            if (count($data)) {
                // return new ApiResource(201, $message, $data);
                return [
                    'status' => 201,
                    'message' => $message,
                    'data' => $data,
                ];
            }

            // return new ApiResource(201, $message);
            return [
                'status' => 201,
                'message' => $message,
            ];
        });

        Response::macro('noContentResponse', function ($data = [], $message = 'Success') {
            // return new ApiResource(204, $message, null);
            return [
                'status' => 204,
                'message' => $message,
                'data' => $data
            ];
        });

        Response::macro('invalidInput', function ($data = [], $message = 'Invalid Input') {
            // return new ApiResource(409, $message, null);
            return [
                'status' => 409,
                'message' => $message,
                'data' => $data,
            ];
        });

        Response::macro('unauthorized', function ($message = 'User unauthorized', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 401);
        });

        Response::macro('forbidden', function ($message = 'Access denied', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 403);
        });

        Response::macro('notFound', function ($message = 'Resource not found.', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 404);
        });

        Response::macro('internalServerError', function ($message = 'Internal Server Error.', $errors = []) use ($instance) {
            return $instance->handleErrorResponse($message, $errors, 500);
        });
    }

    public function handleErrorResponse($message, $errors, $status)
    {
        $response = [
            'message' => $message,
        ];

        // if (!empty($errors)) {
        //     $response['errors'] = $errors;
        // }

        return new ApiResource($status, $response);
    }
}
