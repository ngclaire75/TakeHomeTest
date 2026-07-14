<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

final class InaprocClient
{
    private const SEARCH_QUERY = <<<'GRAPHQL'
    query SearchProducts($input: SearchProductInput!) {
      searchProducts(input: $input) {
        ... on ListSearchProductResponse {
          total
          perPage
          currentPage
          lastPage
          items {
            id
            name
            slug
            username
            sellerName
            isSellerUMKK
            labels
            defaultPrice
            defaultPriceWithTax
            unitSold
            tkdn {
              value
            }
            location {
              name
              child {
                name
              }
            }
            category {
              id
              name
            }
          }
        }
        ... on GenericError {
          __typename
          code
          message
          reqId
        }
      }
    }
    GRAPHQL;

    private Client $http;

    public function __construct(private readonly Config $config)
    {
        $this->http = new Client([
            'base_uri' => $this->config->baseUri,
            'timeout' => 20,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                    . '(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Origin' => $this->config->baseUri,
                'Referer' => $this->config->baseUri . '/search',
            ],
        ]);
    }

    /**
     * @return array{total:int,lastPage:int,currentPage:int,items:array<int,array<string,mixed>>}
     */
    public function searchProducts(string $categoryId, int $page, int $perPage): array
    {
        $payload = [
            'query' => self::SEARCH_QUERY,
            'operationName' => 'SearchProducts',
            'variables' => [
                'input' => [
                    'sort' => [['field' => 'RELEVANCE', 'order' => 'DESC']],
                    'filter' => [
                        'strategy' => 'SRP',
                        'keyword' => null,
                        'labels' => [],
                        'sellerTypes' => [],
                        'sellerRegionCodes' => [''],
                        'minPrice' => null,
                        'maxPrice' => null,
                        'rateTypes' => [],
                        'productTypes' => [],
                        'categoryIds' => [$categoryId],
                        'ratingAvgGte' => null,
                    ],
                    'pagination' => [
                        'page' => $page,
                        'perPage' => $perPage,
                    ],
                ],
            ],
        ];

        $response = $this->requestWithRetry('POST', '/graphql', [
            'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            'json' => $payload,
        ]);

        $body = json_decode((string) $response->getBody(), true);
        if (!is_array($body)) {
            throw new RuntimeException('Malformed JSON response from /graphql');
        }

        if (!empty($body['errors'])) {
            throw new RuntimeException('GraphQL error: ' . json_encode($body['errors']));
        }

        $result = $body['data']['searchProducts'] ?? null;
        if (!is_array($result) || isset($result['code'])) {
            throw new RuntimeException('Unexpected searchProducts response: ' . json_encode($body));
        }

        return $result;
    }

    public function fetchProductDetailHtml(string $username, string $slug): string
    {
        $response = $this->requestWithRetry('GET', "/{$username}/{$slug}", [
            'headers' => ['Accept' => 'text/html'],
        ]);

        return (string) $response->getBody();
    }

    private function requestWithRetry(string $method, string $uri, array $options, int $maxAttempts = 3): ResponseInterface
    {
        $lastError = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return $this->http->request($method, $uri, $options);
            } catch (GuzzleException $e) {
                $lastError = $e;
                if ($attempt < $maxAttempts) {
                    usleep(500_000 * $attempt);
                }
            }
        }

        throw new RuntimeException(
            "Request {$method} {$uri} failed after {$maxAttempts} attempts: " . ($lastError instanceof Throwable ? $lastError->getMessage() : 'unknown error')
        );
    }
}
