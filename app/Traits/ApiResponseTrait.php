<?php

namespace App\Traits;

trait ApiResponseTrait
{
    /**
     * Return a success JSON response.
     *
     * @param  array|string  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data, string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array|string|null  $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(string $message = null, int $code = 500, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $data
        ], $code);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $items
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function paginated($items, string $message = null)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'items' => $items->items(),
                'pagination' => [
                    'total' => $items->total(),
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'last_page' => $items->lastPage()
                ]
            ]
        ]);
    }
}
