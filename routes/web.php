<?php

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;

    // Route::get('notifications/cielo', function () {
    //     echo 'teste';
    //     // return view('lunar::opayo.threed-secure-iframe');
    // })->name('cielo.webhook');

    Route::webhooks('notifications/cielo', 'distripack-cielo');


    // Route::post('notifications/cielo', function (Request $request, WebhookConfig $config) {
        
    //     $signature = $request->header($config->signatureHeaderName);
    //     $signingSecret = $config->signingSecret;


    //     $computedSignature = hash_hmac('sha256', $request->getContent(), $signingSecret);

    //     dd($signature, $computedSignature);
    // //     dd($request->all());


    // //     return view('lunar::opayo.threed-secure-response', [
    // //         'cres' => $request->cres,
    // //         'PaRes' => $request->PaRes,
    // //         'md' => $request->md,
    // //         'mdx' => $request->mdx,
    // //     ]);
    // })->name('distripack-cielo');