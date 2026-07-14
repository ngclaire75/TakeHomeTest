<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ProductController extends Controller
{
    private const PER_PAGE = 15;
    private const CACHE_TTL_SECONDS = 300;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $cacheKey = $this->buildCacheKey($validated, $page);
        $fetch = fn () => $this->fetchProducts($validated);

        try {
            $wasCached = Cache::has($cacheKey);
            $result = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, $fetch);
            $cacheStatus = $wasCached ? 'HIT' : 'MISS';
        } catch (Throwable $e) {
            $result = $fetch();
            $cacheStatus = 'BYPASS';
        }

        return response()->json($result)->header('X-Cache', $cacheStatus);
    }

    private function fetchProducts(array $validated): array
    {
        $query = Product::query();

        if (! empty($validated['q'])) {
            $query->whereRaw("search_vector @@ plainto_tsquery('simple', ?)", [$validated['q']])
                ->orderByRaw("ts_rank(search_vector, plainto_tsquery('simple', ?)) DESC", [$validated['q']]);
        } else {
            $query->orderBy('id');
        }

        if (isset($validated['min_price'])) {
            $query->where('price', '>=', $validated['min_price']);
        }

        if (isset($validated['max_price'])) {
            $query->where('price', '<=', $validated['max_price']);
        }

        return $query->paginate(self::PER_PAGE)->toArray();
    }

    private function buildCacheKey(array $filters, int $page): string
    {
        return 'products:index:'.md5(json_encode([
            'q' => $filters['q'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'page' => $page,
        ]));
    }
}
