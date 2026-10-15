<?php

declare(strict_types=1);

namespace App\Support\System;

final class MasterDataCodes {

    public const IDENTITY_DNI = "dni";

    public const IDENTITY_RUC = "ruc";

    public const IDENTITY_FOREIGN = "ce";

    public const DOCUMENT_RECEIPT = "BV";

    public const DOCUMENT_INVOICE = "FA";

    public const CURRENCY_PEN = "PEN";

    private function __construct() {

    }

}
