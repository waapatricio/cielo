<?php

namespace Waapatricio\Cielo;

use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Waapatricio\Cielo\Responses\PaymentAuthorize; // use Lunar\Base\DataTransferObjects\PaymentAuthorize;

use Illuminate\Support\Facades\DB;
use Lunar\Models\Transaction;
use Lunar\PaymentTypes\AbstractPayment;
use GuzzleHttp\Client;
use App\Events\OrderPaid;
use Carbon\Carbon;

class CieloPaymentType extends AbstractPayment
{
    protected $policy;
    protected string $environment;
    protected string $apiUrl;
    protected string $apiQueryUrl;

    protected string $driver = 'cielo';
    protected $client;

    protected array $transactionStatus = [
        0 => 'not-finished',
        1 => 'authorized',
        2 => 'payment-confirmed',
        3 => 'denied',
        10 => 'voided',
        11 => 'refunded',
        12 => 'pending',
        13 => 'aborted',
        20 => 'scheduled'
    ];

    public function __construct()
    {   
        $this->environment = config('cielo.environment', 'sandbox');

        if ($this->environment == 'sandbox') {
            $this->apiUrl = "https://apisandbox.cieloecommerce.cielo.com.br";
            $this->apiQueryUrl = "https://apiquerysandbox.cieloecommerce.cielo.com.br";
        } else {
            $this->apiUrl = "https://api.cieloecommerce.cielo.com.br";
            $this->apiQueryUrl = "https://apiquery.cieloecommerce.cielo.com.br";
        }

        $this->policy = config('cielo.policy', 'automatic');

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'MerchantId' => config('cielo.merchant_id'),
                'MerchantKey'=> config('cielo.merchant_key')
            ],
        ]);
    }

    public function pix()
    {
        // Verifica se o pedido não esta concluído.
        if ($this->cart->completedOrder)
        {
            return new PaymentAuthorize(
                success: false,
                message: 'Este pedido já foi realizado.',
            ); 
        }

        $this->order = $this->cart->createOrder(orderIdToUpdate: $this->cart->draftOrder->id ?? null);

        $transactionPix = Transaction::whereOrderId($this->order->id)
                          ->whereDriver('cielo')
                          ->whereCardType('Pix')
                          ->whereStatus('pending')
                          ->whereType('intent')
                          ->whereAmount($this->order->total->value)
                          ->where('created_at', '>', Carbon::now()->subDay())
                          ->get();




        if($transactionPix->count())
        {
            return new PaymentAuthorize(
                success: true,
                orderId: $this->order->id,
                reference: $this->order->reference,
                message: 'Código PIX gerado com sucesso.',
                transaction: $transactionPix->first()
            );
        }

        try {

            $customer = $this->order->user->latestCustomer();

            $request = $this->client->request('POST', '1/sales/', [
                'json' => [
                    "MerchantOrderId" => $this->order->reference,
                    "Customer" => [
                        "Name" => ($customer->type === "F") ? "{$customer->first_name} {$customer->last_name}" : $customer->company_name_alias,
                        "Identity" => $customer->cpf_cnpj, 
                        "IdentityType" => ($customer->type === "F") ? 'CPF' : 'CNPJ', 
                    ],
                    "Payment" => [
                        "Type" => "PIX",
                        "Amount" => $this->order->total->value,
                    ]
                ],
            ]);

            $response = json_decode($request->getBody());

            if($response->Payment->Status === 12) // Pendente
            {
                $data = [
                    'success' => false,
                    'type' => 'intent',
                    'driver' => $this->driver,
                    'card_type' => $response->Payment->Type,
                    'amount' => $response->Payment->Amount,
                    'reference' => $response->Payment->PaymentId,
                    'status' => $this->transactionStatus[$response->Payment->Status],
                    'meta' => $response,
                    'notes' => $response->Payment->ReturnMessage
                ];
                
                return new PaymentAuthorize(
                    success: true,
                    orderId: $this->order->id,
                    reference: $this->order->reference,
                    message: 'Código PIX gerado com sucesso.',
                    transaction: $this->order->transactions()->create($data)
                );
            }

            // Caso não retorne sucesso (Pendente)
            return new PaymentAuthorize(
                success: false,
                orderId: $this->order->id,
                reference: $this->order->reference,
                message: "Erro ao gerar o código PIX: [{$response->Payment->ReturnMessage}]",
            );

        } catch (\GuzzleHttp\Exception\ClientException $e) {

            $response = json_decode($e->getResponse()->getBody(), true);

            return new PaymentAuthorize(
                success: false, 
                orderId: $this->order->id,
                reference: $this->order->reference,
                message: 'Erro interno. Tente mais tarde.'
            );


        } catch (\GuzzleHttp\Exception\ConnectException $e) {

            $response = $e;

            return new PaymentAuthorize(
                success: false,
                orderId: $this->order->id,
                reference: $this->order->reference,
                message: 'Erro interno. Tente mais tarde.'
            );

        }

    }

     public function getCardBin(string $bin): string|null
    {

        $bin = substr(preg_replace('([^0-9])', '', $bin),0,6);

        try {

            $client = new Client([
                'base_uri' => $this->apiQueryUrl,
                'headers' => [
                    'Accept'     => 'application/json',
                    'MerchantId' => config('cielo.merchant_id'),
                    'MerchantKey'=> config('cielo.merchant_key')
                ],
            ]);

            $response = $client->get("1/cardBin/{$bin}");

            if ($response->getStatusCode() == 200) {
                $result = json_decode($response->getBody(), true);

                $result['Provider'] = ucfirst(strtolower($result['Provider']));

                if ($result['Provider'] === 'Mastercard')
                    return 'Master';

                return $result['Provider'];
            }

        } catch (\Exception $e) {
            return null;
        }
    }

    public function authorize(): PaymentAuthorize
    {
        if ($this->cart->completedOrder)
        {
            return new PaymentAuthorize(
                success: false,
                message: 'Este pedido já foi realizado.',
            ); 
        }

        $this->order = $this->cart->createOrder(orderIdToUpdate: $this->cart->draftOrder->id ?? null);

        try {

            $customer = $this->order->user->latestCustomer();

            $request = $this->client->request('POST', '1/sales/', [
                'json' => [
                    "MerchantOrderId" => $this->order->reference,
                    "Customer" => [
                        "Name" => ($customer->type === "F") ? "{$customer->first_name} {$customer->last_name}" : $customer->company_name_alias,
                        "Email" => $this->order->user->email,
                        "DeliveryAddress" => [
                            "Street" => $this->order->shippingAddress->line_one,
                            "Number" => $this->order->shippingAddress->line_number,
                            "Complement" => $this->order->shippingAddress->line_two,
                            "ZipCode" => $this->order->shippingAddress->postcode,
                            "City" => $this->order->shippingAddress->city,
                            "State" => $this->order->shippingAddress->state,
                            "Country" => "BRA"
                        ]
                    ],
                    "Payment" => [
                        "Type" => "CreditCard",
                        "Amount" => $this->order->total->value,
                        "Installments" => $this->data['installments'],
                        "Capture" => true,
                        "Recurrent" => false,
                        "CreditCard" => [
                            "CardNumber" => $this->data['cardNumber'], //"4551870000841990",
                            "Holder" => $this->data['holder'],
                            "ExpirationDate" => $this->data['expirationDate'], //"06/2026",
                            "SecurityCode" => $this->data['securityCode'],
                            "Brand" => $this->data['cardProvider']
                        ]
                    ]
                ],
            ]);

            $response = json_decode($request->getBody());

            if($response->Payment->Status === 2 && $this->policy === 'automatic')// Capturado
            {
                DB::transaction(function () use ($response){

                    $transaction = $this->storeCardTransaction($response, true);

                    $this->order->update([
                        'status' => 'paid',
                        'placed_at' => now(),
                    ]);

                });

                event(new OrderPaid($this->order));

                return new PaymentAuthorize(
                    success: true,
                    message: "{$response->Payment->ReturnCode} - {$response->Payment->ReturnMessage}",
                    reference: $response->Payment->PaymentId,
                    captured: true
                );
            }

            if ($this->policy === 'manual' && $response->Payment->Status === 1) // Autorizado
            {                
                $transaction = $this->storeCardTransaction($response, true);

                return new PaymentAuthorize(
                    success: true,
                    message: "{$response->Payment->ReturnCode} - {$response->Payment->ReturnMessage}",
                    reference: $response->Payment->PaymentId,
                    captured: false
                );
            }

            $transaction = $this->storeCardTransaction($response, false);

            return new PaymentAuthorize(
                success: false,
                message: "{$response->Payment->ReturnCode} - {$response->Payment->ReturnMessage}",
            );


        } catch (\GuzzleHttp\Exception\ClientException $e) {

            $response = json_decode($e->getResponse()->getBody(), true);

            foreach ($response as $error) {
                return new PaymentAuthorize(
                    success: false,
                    message: $error['Message']
                );
            }

        } catch (\GuzzleHttp\Exception\ConnectException $e) {

            // $response = $e;
            // $error = $e->getCieloError();
            // dd($e);

            return new PaymentAuthorize(
                success: false,
                message: 'Erro interno. Tente mais tarde.',
            );

        }

    }

    protected function storeCardTransaction($transaction, bool $success = false, int|null $parent_id = null)
    {
        $data = [
            'success' => $success,
            'parent_transaction_id' => $parent_id,
            'type' => ($transaction->Payment->Capture) ? 'capture' : 'intent', //intent, capture, refund
            'driver' => $this->driver,
            'amount' => $transaction->Payment->Amount,
            'reference' => $transaction->Payment->PaymentId, //
            'status' => $transaction->Payment->Status,
            'card_type' => $transaction->Payment->CreditCard->Brand, // Bandeira
            'last_four' => substr($transaction->Payment->CreditCard->CardNumber, -1, 4), // 4 digitos
            'captured_at' => ($transaction->Payment->Capture) ? $transaction->Payment->CapturedDate : null,
            'meta' => $transaction,
            'notes' => $transaction->Payment->ReturnMessage
        ];

        return $this->order->transactions()->create($data);
    }

    public function refund(Transaction $transaction, int $amount = 0, $notes = null): PaymentRefund
    {
        // return new PaymentRefund(true);
    }

    public function capture(Transaction $transaction, $amount = 0): PaymentCapture
    {

        // $capture = $this->cielo->captureSale($paymentId, $this->cart->total->value);
        // $payment = new CieloPayment();
        // $payment->setId($transaction->reference);
        // $payment->setValue($transaction->amount);

        // $payment = app()->cielo->capturePayment($payment);

        // if($payment->getStatus() === 2)
        // {
        //     $this->storeTransaction($payment->getData(), true, $transaction->id);
        // }

        // return new PaymentCapture(
        //     success: $payment->getStatus(),
        //     message: $payment->getMessage()
        // );
    }
}
