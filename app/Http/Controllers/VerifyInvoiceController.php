<?php

namespace App\Http\Controllers;

use App\Services\InvoiceVerificationService;

class VerifyInvoiceController extends Controller
{
    public function show(string $token, InvoiceVerificationService $verification)
    {
        $result = $verification->verifyToken($token);

        return response()->view('verify.invoice', [
            'status' => $result['status'] ?? 'no-existe',
            'invoice' => $result['invoice'] ?? null,
        ]);
    }
}
