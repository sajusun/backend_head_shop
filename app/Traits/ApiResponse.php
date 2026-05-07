<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * JSON Success Response
     */
    public function success(string $message, mixed $data = null, bool $paginate = false, mixed $paginateData = null, $status=true, $code=200): JsonResponse
    {

        $response = [
            'status' => $status,
            'message' => $message,
            'code' => $code,
        ];

        if ($paginate && !empty($paginateData)) {

            $response['data'] = $data;

            $response['pagination'] = [
                'current_page'   => $paginateData->currentPage(),
                'last_page'      => $paginateData->lastPage(),
                'per_page'       => $paginateData->perPage(),
                'total'          => $paginateData->total(),
                'first_page_url' => $paginateData->url(1),
                'last_page_url'  => $paginateData->url($paginateData->lastPage()),
                'next_page_url'  => $paginateData->nextPageUrl(),
                'prev_page_url'  => $paginateData->previousPageUrl(),
                'from'           => $paginateData->firstItem(),
                'to'             => $paginateData->lastItem(),
                'path'           => $paginateData->path(),
            ];
        } elseif ($paginate && !empty($data)) {

            $response['data'] = $data->items();

            $response['pagination'] = [
                'current_page'   => $data->currentPage(),
                'last_page'      => $data->lastPage(),
                'per_page'       => $data->perPage(),
                'total'          => $data->total(),
                'first_page_url' => $data->url(1),
                'last_page_url'  => $data->url($data->lastPage()),
                'next_page_url'  => $data->nextPageUrl(),
                'prev_page_url'  => $data->previousPageUrl(),
                'from'           => $data->firstItem(),
                'to'             => $data->lastItem(),
                'path'           => $data->path(),
            ];
        } elseif ($data !== null) {

            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * JSON Error Response
     */
    public function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {

        return response()->json([
            'status' => false,
            'message' => $message,
            'code' => $code,
            'errors' => $errors,
        ], $code);
    }
}
