<div 
    x-data="{'paymentOption': @entangle('paymentOption')}" 
    class="flex flex-col flex-wrap gap-4 p-6">

    <nav class="flex space-x-2 items-center justify-between" aria-label="Tabs" role="tablist">
        <button wire:loading.attr="disabled" wire:click="$set('paymentOption', 'cartao')" type="button" class="checkout-payment-type-button" :class="paymentOption === 'cartao' && 'active'" role="tab">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" class="w-8 h-8 ">
                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7z" />
                <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-1z" />
            </svg>
            Cartão de Crédito
        </button>

        <button wire:loading.attr="disabled" wire:click="$set('paymentOption', 'boleto')" type="button" class="checkout-payment-type-button" :class="paymentOption === 'boleto' && 'active'" role="tab">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" class="w-8 h-8 ">
                <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1h-3zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5zM.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5zm15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5zM3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-7zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0v-7z" />
            </svg>
            Boleto Bancário
        </button>
        
        <button wire:loading.attr="disabled" wire:click="$set('paymentOption', 'pix')" type="button" class="checkout-payment-type-button" :class="paymentOption === 'pix' && 'active'" role="tab">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16" class="w-8 h-8 ">
                <path d="M2 2h2v2H2V2Z" />
                <path d="M6 0v6H0V0h6ZM5 1H1v4h4V1ZM4 12H2v2h2v-2Z" />
                <path d="M6 10v6H0v-6h6Zm-5 1v4h4v-4H1Zm11-9h2v2h-2V2Z" />
                <path d="M10 0v6h6V0h-6Zm5 1v4h-4V1h4ZM8 1V0h1v2H8v2H7V1h1Zm0 5V4h1v2H8ZM6 8V7h1V6h1v2h1V7h5v1h-4v1H7V8H6Zm0 0v1H2V8H1v1H0V7h3v1h3Zm10 1h-1V7h1v2Zm-1 0h-1v2h2v-1h-1V9Zm-4 0h2v1h-1v1h-1V9Zm2 3v-1h-1v1h-1v1H9v1h3v-2h1Zm0 0h3v1h-2v1h-1v-2Zm-4-1v1h1v-2H7v1h2Z" />
                <path d="M7 12h1v3h4v1H7v-4Zm9 2v2h-3v-1h2v-1h1Z" />
            </svg>
            PIX
        </button>
    </nav>
    
    <div class="flex-1 bg-white">
        {{-- Cartão de Credito --}}
        @if($paymentOption === 'cartao')
        <div 
            x-data="{
                searchCardBin(bin) {
                    if(bin.length >= 7 && $wire.cardProvider === null) {
                        $wire.getCardBin(bin)
                    }

                    if(bin.length < 7 && $wire.cardProvider !== null) {
                        $wire.cardProvider = null;
                    }
                }
            }"
            role="tabpanel" 
        >
            <form class="max-w-lg p-6 mx-auto space-y-2" wire:submit.prevent="payment">
                @if ($errors->has('payment'))
                    <div class="py-4">
                        @foreach ($errors->get('payment') as $error)
                            <div class="p-4 font-medium text-red-800 bg-red-100 border border-red-500 rounded-md " role="alert">
                                {{ $error }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="relative">
                    <x-inputs.maskable type="text" label="Número do cartão" wire:model.lazy="cardNumber" x-on:keyup="searchCardBin($el.value)" mask="#### #### #### ####" class="py-4 text-base" />
                    
                    @if($cardProvider)
                        <div x-text="$wire.cardProvider" class="absolute mt-[24px] right-2 rounded-md inset-y-2 bg-gray-100 flex items-center px-2 text-gray-500 font-semibold"></div>
                    @endif
                </div>

                <div class="justify-between gap-3 sm:flex">
                    <div class="flex-1">
                        <x-inputs.maskable placeholder="MM/AAAA" mask="##/####" emitFormatted="true" type="text" label="Data de expiração" wire:model.defer="expirationDate" class="py-4 text-base" />
                    </div>
                    <div class="flex-1">
                        <x-input type="text" label="CVC/CVV" maxLenght="4" wire:model.defer="securityCode" class="py-4 text-base" />
                    </div>
                </div>

                <x-input type="text" label="Nome no cartão" wire:model.defer="holder" class="py-4 text-base" />

                <x-native-select label="Parcelamento" placeholder="Selecione uma parcela" :options="$this->installmentsOptions" wire:model.defer="installments" class="py-4 text-base" option-label="name" option-value="id"/>

                <x-forms.primary-button type="submit" wire:loading.attr="disabled">
                    <svg wire:loading wire:target="payment" class="flex animate-spin shrink-0 h-4 w-4 text-primary mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Efetuar Pagamento</span>
                </x-forms.primary-button>

            </form>
        </div>
        @endif

        @if($paymentOption === 'boleto')
        <div
            role="tabpanel"
        >
            <div class="max-w-lg p-6 mx-auto space-y-2">
                <div class="border-b border-slate-300">
                    <p class="p-6 text-base font-semibold text-gray-700">Pagar com Boleto Bancário</p>
                </div>
                <div class="p-4 border rounded-md border-amber-100 bg-amber-50 text-amber-700">
                    <p>O Boleto geralmente não é aprovado no dia da compra, portanto, <b>se você deseja retirar seus itens hoje, escolha um método de pagamento diferente.</b></p>
                </div>

                <div class="space-y-2">
                    <p>Imprima o Boleto e pague nas lotéricas ou em qualquer agência bancária.</p>
                    <p>Ou pague pela internet utilizando o código de barras do Boleto.</p>
                    <p>Fique atento a data de vencimento do Boleto bancário, pois, o Boleto vence em 2 dias, após este período não será possível mais o pagamento do Boleto.</p>
                </div>


                <x-forms.primary-button class="bg-gray-300 hover:bg-gray-300">Em breve</x-forms.primary-button>
            </div>
        </div>
        @endif

        @if($paymentOption === 'pix')
        <div role="tabpanel">
            <div class="max-w-lg px-4 py-2 md:p-6 mx-auto space-y-2">

                <p>Pague com Pix em qualquer dia e a qualquer hora! O pagamento é instantâneo, prático e pode ser feito em poucos segundos. É muito rápido e seguro :)</p>

                <div class="space-y-2 mt-4">
                    <p class="gap-2 flex items-center"><x-badge.circle positive label="1"/> abra o app do seu banco e entre no ambiente Pix</p>
                    <p class="gap-2 flex items-center"><x-badge.circle positive label="2"/> escolha a opção pagar com qr code e escaneie o código ao lado</p>
                    <p class="gap-2 flex items-center"><x-badge.circle positive label="3"/> confirme as informações e finalize a compra</p>
                </div>

                <div wire:poll.7s="checkOrderStatus" class="mt-4 flex flex-col items-center justify-center">
                    @if($qrCodeImagePix)
                        
                        <div class="rounded-md md:w-1/2 border border-gray-300 aspect-square flex ">
                            <img src="data:image/jpeg;base64, {{ $this->qrCodeImagePix }}" class="rounded-md" />
                        </div>
                        
                        <div
                            class="flex flex-col gap-2 w-full py-2"
                            x-data="{
                                copy(text) {
                                    if (window.clipboardData && window.clipboardData.setData) {
                                        $wire.call('notify', '{{ __('adminhub::notifications.clipboard.copied') }}')
                                        // Internet Explorer-specific code path to prevent textarea being shown while dialog is visible.
                                        return window.clipboardData.setData('Text', text);
                                    } else if (document.queryCommandSupported && document.queryCommandSupported('copy')) {
                                        var textarea = document.createElement('textarea');
                                        textarea.textContent = text;
                                        textarea.style.position = 'fixed';  // Prevent scrolling to bottom of page in Microsoft Edge.
                                        document.body.appendChild(textarea);
                                        textarea.select();
                                        
                                        try {
                                            $wire.call('notify', '{{ __('adminhub::notifications.clipboard.copied') }}')
                                            return document.execCommand('copy');  // Security exception may be thrown by some browsers.
                                        }
                                        catch (ex) {
                                            $wire.call('notify', '{{ __('adminhub::notifications.clipboard.failed_copy') }}')
                                        }
                                        finally {
                                            document.body.removeChild(textarea);
                                        }
                                    }
                                }
                            }">
                            <x-input wire:model="qrCodeStringPix" class="flex text-lg w-full" />    
                            <x-button wire:target="copy" class="rounded-md" icon="document-duplicate" label="Copiar Código PIX" primary x-on:click="copy('{{ $this->qrCodeStringPix }}')"/>
                        </div>
                        
                        <x-forms.primary-button class="bg-gray-300 hover:bg-gray-300">
                            <svg class="flex animate-spin shrink-0 h-4 w-4 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-2">Aguardando pagamento</span>
                        </x-forms.primary-button>
                    
                    @else
                        
                        @if($errors->has('pix'))
                            <div class="py-4">
                                @foreach ($errors->get('pix') as $error)
                                    <div class="p-4 font-medium text-red-800 bg-red-100 border border-red-500 rounded-md " role="alert">
                                        {{ $error }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                        <div class="aspect-square bg-gray-100 flex items-center justify-center rounded-md w-full md:w-1/2 p-4">
                            <svg class="flex animate-spin shrink-0 h-12 w-12 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @endif

                    @endif

                </div>

            </div>
        </div>
        @endif

    </div>
</div>
















