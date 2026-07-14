<?php

declare(strict_types=1);

namespace App;

final class ProductSpecParser
{
    private const WINDOW_SIZE = 20000;

    public function extractSpecText(string $html): ?string
    {
        $marker = 'Spesifikasi Produk';
        $pos = strpos($html, $marker);
        if ($pos === false) {
            return null;
        }

        $window = substr($html, $pos, self::WINDOW_SIZE);
        $window = str_replace(['\\\\', '\\"'], ['\\', '"'], $window);

        if (!preg_match('/"value"\s*:\s*"(.*?)"\s*[,}]/su', $window, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * @return array{lumen: ?float, efikasi_lm_w: ?float, voltase_v: ?float, daya_watt: ?float}
     */
    public function extractDynamicAttributes(?string $specText): array
    {
        $attributes = [
            'lumen' => null,
            'efikasi_lm_w' => null,
            'voltase_v' => null,
            'daya_watt' => null,
        ];

        if ($specText === null || $specText === '') {
            return $attributes;
        }

        $patterns = [
            'daya_watt' => '/(?:Daya(?:\s+\w+)?|LED\s*Power|Rated\s*Power)\s*:?\s*([\d.,]+)\s*Watt/iu',
            'voltase_v' => '/(?:Tegangan(?:\s*Kerja)?|Voltage)\s*:?\s*(?:AC|DC)?\s*(?:[\d.,]+\s*[-~]\s*)?([\d.,]+)\s*V(?:AC|DC)?\b/iu',
            'lumen' => '/(?:Lumen\s*Output|Luminous\s*Flux)\s*:?\s*([\d.,]+)\s*(?:Lumen|lm)?/iu',
            'efikasi_lm_w' => '/(?:Efikasi(?:\s*Luminer)?|Light\s*Efficiency|Efficiency|Luminous\s*Efficacy)\s*:?\s*([\d.,]+)\s*(?:Lumen\s*\/\s*Watt|lm\s*\/\s*Watt|lm\/W)/iu',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $specText, $matches)) {
                $attributes[$key] = $this->normalizeNumber($matches[1]);
            }
        }

        return $attributes;
    }

    private function normalizeNumber(string $raw): float
    {
        $raw = trim($raw);
        $raw = str_replace(',', '.', $raw);

        if (substr_count($raw, '.') > 1) {
            $lastDot = strrpos($raw, '.');
            $raw = str_replace('.', '', substr($raw, 0, $lastDot)) . substr($raw, $lastDot);
        }

        return (float) $raw;
    }
}
