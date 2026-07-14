<?php

declare(strict_types=1);

namespace App;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function upsert(array $product): void
    {
        $sql = <<<'SQL'
        INSERT INTO apj_products (
            external_id, product_name, price, price_with_tax, provider_type, is_seller_umkk,
            location, vendor, unit_sold, tkdn_value, lumen, efikasi_lm_w, voltase_v, daya_watt,
            category_name, slug, username, detail_url, labels, raw_spec_text, scraped_at, updated_at
        ) VALUES (
            :external_id, :product_name, :price, :price_with_tax, :provider_type, :is_seller_umkk,
            :location, :vendor, :unit_sold, :tkdn_value, :lumen, :efikasi_lm_w, :voltase_v, :daya_watt,
            :category_name, :slug, :username, :detail_url, :labels, :raw_spec_text, now(), now()
        )
        ON CONFLICT (external_id) DO UPDATE SET
            product_name = EXCLUDED.product_name,
            price = EXCLUDED.price,
            price_with_tax = EXCLUDED.price_with_tax,
            provider_type = EXCLUDED.provider_type,
            is_seller_umkk = EXCLUDED.is_seller_umkk,
            location = EXCLUDED.location,
            vendor = EXCLUDED.vendor,
            unit_sold = EXCLUDED.unit_sold,
            tkdn_value = EXCLUDED.tkdn_value,
            lumen = EXCLUDED.lumen,
            efikasi_lm_w = EXCLUDED.efikasi_lm_w,
            voltase_v = EXCLUDED.voltase_v,
            daya_watt = EXCLUDED.daya_watt,
            category_name = EXCLUDED.category_name,
            slug = EXCLUDED.slug,
            username = EXCLUDED.username,
            detail_url = EXCLUDED.detail_url,
            labels = EXCLUDED.labels,
            raw_spec_text = EXCLUDED.raw_spec_text,
            scraped_at = now(),
            updated_at = now()
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'external_id' => $product['external_id'],
            'product_name' => $product['product_name'],
            'price' => $product['price'],
            'price_with_tax' => $product['price_with_tax'],
            'provider_type' => $product['provider_type'],
            'is_seller_umkk' => $product['is_seller_umkk'] ? 'true' : 'false',
            'location' => $product['location'],
            'vendor' => $product['vendor'],
            'unit_sold' => $product['unit_sold'],
            'tkdn_value' => $product['tkdn_value'],
            'lumen' => $product['lumen'],
            'efikasi_lm_w' => $product['efikasi_lm_w'],
            'voltase_v' => $product['voltase_v'],
            'daya_watt' => $product['daya_watt'],
            'category_name' => $product['category_name'],
            'slug' => $product['slug'],
            'username' => $product['username'],
            'detail_url' => $product['detail_url'],
            'labels' => json_encode($product['labels'] ?? []),
            'raw_spec_text' => $product['raw_spec_text'],
        ]);
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM apj_products')->fetchColumn();
    }
}
