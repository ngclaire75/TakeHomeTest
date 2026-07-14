<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'external_id' => (string) Str::uuid(),
            'name' => 'Lampu PJU Tenaga Surya 60 Watt',
            'price' => 5000000,
            'provider_type' => 'UMKK',
            'location' => 'KOTA SURABAYA, JAWA TIMUR',
            'vendor' => 'VENDOR UJI COBA',
            'unit_sold' => 10,
            'tkdn_value' => 45.50,
            'dynamic_attributes' => [
                'daya_watt' => 60,
                'lumen' => 9600,
                'efikasi_lm_w' => 160,
                'voltase_v' => 220,
            ],
            'detail_url' => 'https://katalog.inaproc.id/vendor-uji/lampu-pju-60-watt',
            'image_url' => 'https://asset.inaproc.id/upload/contoh.png',
        ], $overrides));
    }

    public function test_product_list_endpoint_returns_200_with_correct_json_structure(): void
    {
        $this->createProduct();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'current_page',
            'data' => [
                '*' => [
                    'id',
                    'external_id',
                    'name',
                    'price',
                    'provider_type',
                    'location',
                    'vendor',
                    'unit_sold',
                    'tkdn_value',
                    'dynamic_attributes',
                    'detail_url',
                    'image_url',
                ],
            ],
            'first_page_url',
            'from',
            'last_page',
            'last_page_url',
            'next_page_url',
            'path',
            'per_page',
            'prev_page_url',
            'to',
            'total',
        ]);
    }

    public function test_product_list_is_paginated_at_15_items_per_page(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->createProduct(['name' => "Produk Uji {$i}"]);
        }

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('total', 20)
            ->assertJsonPath('last_page', 2)
            ->assertJsonCount(15, 'data');
    }

    public function test_product_list_filters_by_price_range(): void
    {
        $this->createProduct(['name' => 'Produk Murah', 'price' => 1000000]);
        $this->createProduct(['name' => 'Produk Sedang', 'price' => 7000000]);
        $this->createProduct(['name' => 'Produk Mahal', 'price' => 50000000]);

        $response = $this->getJson('/api/products?min_price=5000000&max_price=10000000');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Produk Sedang');
    }

    public function test_product_list_supports_full_text_search_by_name(): void
    {
        $this->createProduct(['name' => 'Lampu Jalan Tenaga Surya 90 Watt']);
        $this->createProduct(['name' => 'Tiang Oktagonal 9 Meter']);

        $response = $this->getJson('/api/products?q=surya');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Lampu Jalan Tenaga Surya 90 Watt');
    }

    public function test_product_list_rejects_invalid_price_input(): void
    {
        $response = $this->getJson('/api/products?min_price=abc');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['min_price']);
    }
}
