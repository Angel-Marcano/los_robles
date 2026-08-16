<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\PaymentReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentApprovedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PaymentReport $paymentReport;
    public Invoice $invoice;

    public function __construct(PaymentReport $paymentReport, Invoice $invoice)
    {
        $this->paymentReport = $paymentReport;
        $this->invoice = $invoice;
    }

    public function build()
    {
        $condoName = app()->bound('currentCondominium')
            ? app('currentCondominium')->name
            : config('app.name', 'Los Robles');

        return $this->subject('Pago aprobado - Factura ' . $this->invoice->number . ' - ' . $condoName)
            ->markdown('emails.payments.approved')
            ->with([
                'paymentReport' => $this->paymentReport,
                'invoice' => $this->invoice,
                'remainingUsd' => $this->invoice->remainingUsdEquivalent(),
            ]);
    }
}
