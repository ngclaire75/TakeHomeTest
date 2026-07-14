CREATE TABLE IF NOT EXISTS apj_products (
    id BIGSERIAL PRIMARY KEY,
    external_id UUID NOT NULL UNIQUE,
    product_name TEXT NOT NULL,
    price NUMERIC(18, 2),
    price_with_tax NUMERIC(18, 2),
    provider_type VARCHAR(50),
    is_seller_umkk BOOLEAN,
    location TEXT,
    vendor TEXT,
    unit_sold INTEGER,
    tkdn_value NUMERIC(6, 2),
    lumen NUMERIC(12, 2),
    efikasi_lm_w NUMERIC(12, 2),
    voltase_v NUMERIC(12, 2),
    daya_watt NUMERIC(12, 2),
    category_name TEXT,
    slug TEXT,
    username TEXT,
    detail_url TEXT,
    labels JSONB,
    raw_spec_text TEXT,
    scraped_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_apj_products_product_name ON apj_products (product_name);
CREATE INDEX IF NOT EXISTS idx_apj_products_vendor ON apj_products (vendor);
