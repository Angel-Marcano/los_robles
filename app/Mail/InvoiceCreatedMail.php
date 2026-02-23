<?php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceCreatedMail extends Mailable {
	use Queueable, SerializesModels;

	public $invoice;

	public function __construct(Invoice $invoice)
	{
		$this->invoice = $invoice;
	}

	public function build()
	{
		$html = view('invoices.pdf',[ 'invoice' => $this->invoice ])->render();
		$dompdf = new Dompdf((new Options())->set('defaultFont','DejaVu Sans'));
		$dompdf->loadHtml($html);
		$dompdf->render();
		$pdf = $dompdf->output();
		return $this->subject('Nueva factura '.$this->invoice->period)
			->view('emails.invoice_created')
			->attachData($pdf,'factura_'.$this->invoice->id.'.pdf');
	}
}
