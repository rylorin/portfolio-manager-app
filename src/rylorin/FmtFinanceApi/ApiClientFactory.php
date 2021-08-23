<?php

declare(strict_types=1);

namespace rylorin\FmtFinanceApi;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class ApiClientFactory
{
    /**
     * @param ClientInterface|null $guzzleClient
     *
     * @return ApiClient
     */
    public static function createApiClient(string $apikey, ClientInterface $guzzleClient = null)
    {
        $guzzleClient = $guzzleClient ? $guzzleClient : new Client();
        $resultDecoder = new ResultDecoder();

        return new ApiClient($apikey, $guzzleClient, $resultDecoder);
    }
}
