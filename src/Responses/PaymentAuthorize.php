<?php

namespace Waapatricio\Cielo\Responses;

use Lunar\Base\DataTransferObjects\PaymentAuthorize as LunarPaymentAuthorize;
use Lunar\Models\Transaction;

class PaymentAuthorize extends LunarPaymentAuthorize
{
    public function __construct(
        public bool $success = false, //
        public ?string $message = null,
        public ?int $orderId = null,
        public ?string $reference = null,
        public bool $captured = false,
        public ?Transaction $transaction = null
    ) {
        //
    }
}
