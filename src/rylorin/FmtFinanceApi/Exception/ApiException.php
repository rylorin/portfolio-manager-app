<?php

declare(strict_types=1);

namespace rylorin\FmtFinanceApi\Exception;

class ApiException extends \Exception
{
    const INVALID_RESPONSE = 1;
    const INVALID_VALUE = 2;
    const INVALID_PROPERTY = 3;
}
