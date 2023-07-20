<?php

namespace Waapatricio\Cielo\Components;

use Livewire\Component;
use Livewire\ComponentConcerns\PerformsRedirects;
use Lunar\Models\Cart;
use Lunar\Facades\Payments;
use Lunar\Facades\CartSession;
use Lunar\Hub\Http\Livewire\Traits\Notifies;
use App\Events\OrderPaid;

class PaymentForm extends Component
{
    use Notifies, PerformsRedirects;

    public string $paymentOption = 'cartao';

    public Cart $cart;

    public ?string $qrCodeImagePix = null;
    public ?string $qrCodeStringPix = null;

    public ?string $cardNumber = '';
    public ?string $cardProvider = null;
    public ?string $expirationDate = '';
    public ?string $securityCode = '';
    public ?string $holder = '';
    public ?int $installments = 1;
    public string $returnUrl;

    public bool $statusPaid = false;

    protected $listeners = [
        // 'cardDetailsSubmitted',
        'generatePixCode' => 'generatePixCode',
        'cartUpdated' => 'refreshOrder',
        'selectedShippingOption' => 'refreshOrder',
    ];

    public function rules()
    {
        if ($this->paymentOption === 'cartao')
        {
            return [
                'cardNumber' => 'required',
                'expirationDate' => 'required',
                'securityCode' => 'required',
                'holder' => 'required',
                'installments' => 'required'
            ];
        }
    }

    public function mount($returnUrl) 
    {
        $this->returnUrl = $returnUrl;
        $this->cart = CartSession::current();
    }

    public function hydrate()
    {
        $this->cart = CartSession::current();
    }

    public function updatedPaymentOption()
    {
        if ($this->paymentOption === 'pix' && $this->qrCodeImagePix === null) 
        {
            $this->emit('generatePixCode');
        }
    }

    public function checkOrderStatus()
    {
        if ($this->cart->completedOrder) 
        {
            $this->statusPaid = true;
            return redirect()->route($this->returnUrl, ['slug' => $this->cart->completedOrder->reference]);
        }
    }

    public function generatePixCode() 
    {   
        $this->qrCodeImagePix = null;
        $this->qrCodeStringPix = null;
        
        $payment = Payments::driver('cielo')->cart($this->cart)->pix();
        
        if($payment->success === true)
        {
            $this->qrCodeImagePix = $payment->transaction->meta->Payment->QrCodeBase64Image;
            $this->qrCodeStringPix = $payment->transaction->meta->Payment->QrCodeString;
        
            return;
        }

        $this->addError('pix', $payment->message);
    }

    public function getInstallmentsOptionsProperty() 
    {
        $maxNumberInstallments = 6;
        $minValueInstallments = 30;
        $installments[] = [
            'id' => 1, 
            'name' => "1x de {$this->cart->total->formatted} sem juros"
        ];

        for ($i = 2; $i <= $maxNumberInstallments; $i++)
        {
            $valueInstallments = $this->cart->total->decimal/$i;

            if ($valueInstallments < $minValueInstallments)
            {
                break;
            }

            $valueInstallments = number_format($valueInstallments, 2, ',', '.');
            
            $installments[] = [
                'id' => $i, 
                'name' => "{$i}x de R$ {$valueInstallments} sem juros"
            ];
        }

        return $installments;
    }

    public function updatedCardNumber()
    {
        $this->cardProvider = null;

        if (strlen($this->cardNumber) >= 6)
        {
            $this->getCardBin($this->cardNumber);
        }
    }

    public function getCardBin($bin)
    {
        $payment = Payments::driver('cielo')->getCardBin($bin);

        if ($payment)  {
            $this->cardProvider = $payment;
            return;
        }

        $this->addError('cardNumber', 'Cartão inválido ou não aceito.');
    }

    public function payment()
    {
        $this->validate();

        $payment = Payments::driver('cielo')->cart($this->cart)->withData([
            'cardNumber' => $this->cardNumber,
            'cardProvider' => $this->cardProvider,
            'expirationDate' => $this->expirationDate,
            'securityCode' => $this->securityCode,
            'holder' => $this->holder,
            'installments' => $this->installments
        ])->authorize();

        if ($payment->success) {
            return redirect()->route($this->returnUrl);
        }

        $this->addError('payment', $payment->message);
    }

    public function render()
    {
        return view('cielo::cielo.components.payment-form');
    }

}
