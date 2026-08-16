<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public float $lateUsd;
    public float $lateVes;

    public function __construct(Invoice $invoice, float $lateUsd, float $lateVes)
    {
        $this->invoice = $invoice;
        $this->lateUsd = $lateUsd;
        $this->lateVes = $lateVes;
    }

    public function build()
    {
        $condoName = app()->bound('currentCondominium')
            ? app('currentCondominium')->name
            : config('app.name', 'Los Robles');

        return $this->subject('Recordatorio de pago - Factura ' . $this->invoice->number . ' - ' . $condoName)
            ->markdown('emails.invoices.reminder')
            ->with([
                'invoice' => $this->invoice,
                'lateUsd' => $this->lateUsd,
                'lateVes' => $this->lateVes,
            ]);
    }
}
