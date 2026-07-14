<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('products:import')]
class ImportProductsFromScraper extends Command
{
    public function handle(): int
    {
        $rows = DB::connection('scraper_source')->table('apj_products')->get();

        $this->info("Fetched {$rows->count()} rows from apj_products.");

        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $dynamicAttributes = array_filter([
                'lumen' => $row->lumen !== null ? (float) $row->lumen : null,
                'efikasi_lm_w' => $row->efikasi_lm_w !== null ? (float) $row->efikasi_lm_w : null,
                'voltase_v' => $row->voltase_v !== null ? (float) $row->voltase_v : null,
                'daya_watt' => $row->daya_watt !== null ? (float) $row->daya_watt : null,
            ], static fn ($value) => $value !== null);

            Product::updateOrCreate(
                ['external_id' => $row->external_id],
                [
                    'name' => trim($row->product_name),
                    'price' => $row->price,
                    'provider_type' => $row->provider_type,
                    'location' => $row->location,
                    'vendor' => $row->vendor,
                    'unit_sold' => $row->unit_sold,
                    'tkdn_value' => $row->tkdn_value,
                    'dynamic_attributes' => $dynamicAttributes === [] ? null : $dynamicAttributes,
                    'detail_url' => $row->detail_url,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Import complete. Total products in catalog: ' . Product::count());

        return self::SUCCESS;
    }
}
