<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyPendingInvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Invoice $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $condoName = app()->bound('currentCondominium')
            ? app('currentCondominium')->name
            : config('app.name', 'Los Robles');

        return $this->subject('Recordatorio de factura pendiente - ' . $this->invoice->period . ' - ' . $condoName)
            ->markdown('emails.invoices.monthly_pending_reminder')
            ->with([
                'invoice' => $this->invoice,
            ]);
    }
}
