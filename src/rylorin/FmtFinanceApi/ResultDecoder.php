<?php

declare(strict_types=1);

namespace rylorin\FmtFinanceApi;

use Rylorin\FmtFinanceApi\Exception\ApiException;
use Rylorin\FmtFinanceApi\Results\HistoricalData;
use Rylorin\FmtFinanceApi\Results\Quote;
use Rylorin\FmtFinanceApi\Results\SearchResult;

class ResultDecoder
{
    const HISTORICAL_DATA_HEADER_LINE = ['Date', 'Open', 'High', 'Low', 'Close', 'Adj Close', 'Volume'];
    const SEARCH_RESULT_FIELDS = ['symbol', 'name', 'exch', 'type', 'exchDisp', 'typeDisp'];
    const EXCHANGE_RATE_FIELDS = ['Name', 'Rate', 'Date', 'Time', 'Ask', 'Bid'];
    const QUOTE_FIELDS_MAP = [
    	'symbol' => 'string',
    	'price' => 'float',
    	'changesPercentage' => 'float',
   		'change' => 'float',
    	'dayLow' => 'float',
    	'dayHigh' => 'float',
    	'yearHigh' => 'float',
    	'yearLow' => 'float',
    	'marketCap' => 'float',
    	'priceAvg50' => 'float',
    	'priceAvg200' => 'float',
    	'volume' => 'int',
    	'avgVolume' => 'float',
    	'exchange' => 'string',
    ];

    /**
     * @var array
     */
    private $quoteFields;

    public function __construct()
    {
        $this->quoteFields = array_keys(self::QUOTE_FIELDS_MAP);
    }

    public function transformSearchResult($responseBody)
    {
        $decoded = json_decode($responseBody, true);
        if (!isset($decoded['data']['items']) || !is_array($decoded['data']['items'])) {
            throw new ApiException('Yahoo Search API returned an invalid response', ApiException::INVALID_RESPONSE);
        }

        return array_map(function ($item) {
            return $this->createSearchResultFromJson($item);
        }, $decoded['data']['items']);
    }

    private function createSearchResultFromJson(array $json)
    {
        $missingFields = array_diff(self::SEARCH_RESULT_FIELDS, array_keys($json));
        if ($missingFields) {
            throw new ApiException('Search result is missing fields: '.implode(', ', $missingFields), ApiException::INVALID_RESPONSE);
        }

        return new SearchResult(
            $json['symbol'],
            $json['name'],
            $json['exch'],
            $json['type'],
            $json['exchDisp'],
            $json['typeDisp']
        );
    }

    public function transformHistoricalDataResult($responseBody)
    {
        $lines = array_map('trim', explode("\n", trim($responseBody)));
        $headerLine = array_shift($lines);
        $expectedHeaderLine = implode(',', self::HISTORICAL_DATA_HEADER_LINE);
        if ($headerLine !== $expectedHeaderLine) {
            throw new ApiException('CSV header line did not match expected header line, given: '.$headerLine.', expected: '.$expectedHeaderLine, ApiException::INVALID_RESPONSE);
        }

        return array_map(function ($line) {
            return $this->createHistoricalData(explode(',', $line));
        }, $lines);
    }

    private function createHistoricalData(array $columns)
    {
        if (7 !== count($columns)) {
            throw new ApiException('CSV did not contain correct number of columns', ApiException::INVALID_RESPONSE);
        }

        try {
            $date = new \DateTime($columns[0], new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            throw new ApiException('Not a date in column "Date":'.$columns[0], ApiException::INVALID_VALUE);
        }

        for ($i = 1; $i <= 6; ++$i) {
            if (!is_numeric($columns[$i]) && 'null' != $columns[$i]) {
                throw new ApiException('Not a number in column "'.self::HISTORICAL_DATA_HEADER_LINE[$i].'": '.$columns[$i], ApiException::INVALID_VALUE);
            }
        }

        $open = (float) $columns[1];
        $high = (float) $columns[2];
        $low = (float) $columns[3];
        $close = (float) $columns[4];
        $adjClose = (float) $columns[5];
        $volume = (int) $columns[6];

        return new HistoricalData($date, $open, $high, $low, $close, $adjClose, $volume);
    }

    public function transformQuotes($responseBody)
    {
    	$results = json_decode($responseBody, true);
    	if (!is_array($results) || !is_array($results[array_key_first($results)])) {
    		printf("FMT quote API returned an invalid result: %s\n", $results[array_key_first($results)]);
    		return null;
//    		throw new ApiException('FMT quote API returned an invalid result.', ApiException::INVALID_RESPONSE);
    	}
    	// Single element is returned directly in "quote"
        return array_map(function ($item) {
            return $this->createQuote($item);
        }, $results);
    }

    private function createQuote(array $json)
    {
        $mappedValues = [];
        foreach ($json as $field => $value) {
            if (array_key_exists($field, self::QUOTE_FIELDS_MAP)) {
                $type = self::QUOTE_FIELDS_MAP[$field];
                $mappedValues[$field] = $this->mapValue($field, $value, $type);
            }
        }

        return new Quote($mappedValues);
    }

    private function mapValue($field, $rawValue, $type)
    {
        if (null === $rawValue) {
            return null;
        }

        switch ($type) {
            case 'float':
                return $this->mapFloatValue($field, $rawValue);
            case 'percent':
                return $this->mapPercentValue($field, $rawValue);
            case 'int':
                return $this->mapIntValue($field, $rawValue);
            case 'date':
                return $this->mapDateValue($field, $rawValue);
            case 'string':
                return (string) $rawValue;
            case 'bool':
                return $this->mapBoolValue($rawValue);
            default:
                throw new \InvalidArgumentException('Invalid data type '.$type.' for field '.$field);
        }
    }

    private function mapFloatValue($field, $rawValue)
    {
        if (!is_numeric($rawValue)) {
            throw new ApiException('Not a number in field "'.$field.'": '.$rawValue, ApiException::INVALID_VALUE);
        }

        return (float) $rawValue;
    }

    private function mapPercentValue($field, $rawValue)
    {
        if ('%' !== substr($rawValue, -1, 1)) {
            throw new ApiException('Not a percent in field "'.$field.'": '.$rawValue, ApiException::INVALID_VALUE);
        }

        $numericPart = substr($rawValue, 0, strlen($rawValue) - 1);
        if (!is_numeric($numericPart)) {
            throw new ApiException('Not a percent in field "'.$field.'": '.$rawValue, ApiException::INVALID_VALUE);
        }

        return (float) $numericPart;
    }

    private function mapIntValue($field, $rawValue)
    {
        if (!is_numeric($rawValue)) {
            throw new ApiException('Not a number in field "'.$field.'": '.$rawValue, ApiException::INVALID_VALUE);
        }

        return (int) $rawValue;
    }

    private function mapBoolValue($rawValue)
    {
        return (bool) $rawValue;
    }

    private function mapDateValue($field, $rawValue)
    {
        try {
            return new \DateTime('@'.$rawValue);
        } catch (\Exception $e) {
            throw new ApiException('Not a date in field "'.$field.'": '.$rawValue, ApiException::INVALID_VALUE);
        }
    }
}
