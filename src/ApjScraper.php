<?php

declare(strict_types=1);

namespace App;

use Throwable;

final class ApjScraper
{
    public function __construct(
        private readonly InaprocClient $client,
        private readonly ProductRepository $repository,
        private readonly ProductSpecParser $specParser,
        private readonly Config $config,
    ) {
    }

    public function run(): int
    {
        $processed = 0;
        $distinctRows = $this->repository->count();
        $page = 1;
        $lastPage = 1;

        do {
            fwrite(STDOUT, "Fetching search results page {$page}...\n");

            $result = $this->client->searchProducts($this->config->categoryId, $page, $this->config->perPage);
            $lastPage = max(1, (int) ($result['lastPage'] ?? 1));
            $items = $result['items'] ?? [];

            if ($items === []) {
                fwrite(STDOUT, "No more items returned, stopping.\n");
                break;
            }

            foreach ($items as $item) {
                try {
                    $this->processItem($item);
                    $processed++;
                } catch (Throwable $e) {
                    fwrite(STDERR, "Warning: skipped product '{$item['name']}': {$e->getMessage()}\n");
                }

                usleep($this->config->delayMs * 1000);
            }

            $distinctRows = $this->repository->count();
            fwrite(STDOUT, "Page {$page}/{$lastPage} done. Items processed: {$processed}. Distinct rows in DB: {$distinctRows}.\n");
            $page++;
        } while ($distinctRows < $this->config->minRecords && $page <= $lastPage);

        fwrite(STDOUT, "Finished. {$distinctRows} distinct products stored (from {$processed} items processed).\n");

        return $distinctRows;
    }

    private function processItem(array $item): void
    {
        $username = $item['username'];
        $slug = $item['slug'];
        $detailUrl = "{$this->config->baseUri}/{$username}/{$slug}";

        $specText = null;
        $attributes = ['lumen' => null, 'efikasi_lm_w' => null, 'voltase_v' => null, 'daya_watt' => null];

        try {
            $html = $this->client->fetchProductDetailHtml($username, $slug);
            $specText = $this->specParser->extractSpecText($html);
            $attributes = $this->specParser->extractDynamicAttributes($specText);
        } catch (Throwable $e) {
            fwrite(STDERR, "Warning: could not fetch/parse detail page {$detailUrl}: {$e->getMessage()}\n");
        }

        $this->repository->upsert([
            'external_id' => $item['id'],
            'product_name' => $item['name'],
            'price' => $item['defaultPrice'] ?? null,
            'price_with_tax' => $item['defaultPriceWithTax'] ?? null,
            'provider_type' => $this->resolveProviderType($item),
            'is_seller_umkk' => (bool) ($item['isSellerUMKK'] ?? false),
            'location' => $this->flattenLocation($item['location'] ?? null),
            'vendor' => $item['sellerName'] ?? null,
            'unit_sold' => $item['unitSold'] ?? null,
            'tkdn_value' => $item['tkdn']['value'] ?? null,
            'lumen' => $attributes['lumen'],
            'efikasi_lm_w' => $attributes['efikasi_lm_w'],
            'voltase_v' => $attributes['voltase_v'],
            'daya_watt' => $attributes['daya_watt'],
            'category_name' => $item['category']['name'] ?? null,
            'slug' => $slug,
            'username' => $username,
            'detail_url' => $detailUrl,
            'labels' => $item['labels'] ?? [],
            'raw_spec_text' => $specText,
        ]);
    }

    private function resolveProviderType(array $item): string
    {
        $labels = array_map('strtoupper', $item['labels'] ?? []);

        if (in_array('OFFICIAL VENDOR', $labels, true) || in_array('OFFICIAL_VENDOR', $labels, true)) {
            return 'Official Vendor';
        }

        return ($item['isSellerUMKK'] ?? false) ? 'UMKK' : 'Non UMKK';
    }

    private function flattenLocation(?array $location): ?string
    {
        if ($location === null || empty($location['name'])) {
            return null;
        }

        $province = $location['name'];
        $city = $location['child']['name'] ?? null;

        return $city !== null ? "{$city}, {$province}" : $province;
    }
}
