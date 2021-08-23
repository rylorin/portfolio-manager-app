<?php

declare(strict_types=1);

namespace rylorin\FmtFinanceApi;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\CookieJar;
use Rylorin\FmtinanceApi\Exception\ApiException;
use Rylorin\FmtFinanceApi\Results\HistoricalData;
use Rylorin\FmtFinanceApi\Results\Quote;
use Rylorin\FmtFinanceApi\Results\SearchResult;

class ApiClient
{
    /**
     * @var string
     */
	private $apikey;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var ResultDecoder
     */
    private $resultDecoder;

    public function __construct(string $apikey, ClientInterface $guzzleClient, ResultDecoder $resultDecoder)
    {
        $this->apikey = $apikey;
        $this->client = $guzzleClient;
        $this->resultDecoder = $resultDecoder;
    }

    /**
     * Get quote for a single symbol.
     *
     * @param string $symbol
     *
     * @return Quote|null
     */
    public function getQuote($symbol)
    {
        $list = $this->fetchQuotes([$symbol]);

        return isset($list[0]) ? $list[0] : null;
    }

    /**
     * Get quotes for one or multiple symbols.
     *
     * @param array $symbols
     *
     * @return array|Quote[]
     */
    public function getQuotes(array $symbols)
    {
        return $this->fetchQuotes($symbols);
    }

    /**
     * Fetch quote data from API.
     *
     * @param array $symbols
     *
     * @return array|Quote[]
     */
    private function fetchQuotes(array $symbols)
    {
        $url = 'https://query1.finance.yahoo.com/v7/finance/quote?symbols='.urlencode(implode(',', $symbols));
        $url = sprintf('https://financialmodelingprep.com/api/v3/quote/%s?apikey=%s', urlencode(implode(',', $symbols)), $this->apikey);
        $responseBody = (string) $this->client->request('GET', $url)->getBody();
        return $this->resultDecoder->transformQuotes($responseBody);
    }

}
