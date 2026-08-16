<?php
namespace App\Mail;

use App\Models\Invoice;
use App\Services\InvoiceVerificationService;
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
		$condoName = app()->bound('currentCondominium')
			? app('currentCondominium')->name
			: config('app.name', 'Los Robles');

		$verification = app(InvoiceVerificationService::class);
		$verifyUrl    = $verification->verificationUrl($this->invoice);
		$invoiceQrSvg = $verification->qrSvgForInvoice($this->invoice, 130);

		$html = view('invoices.pdf', [
			'invoice'      => $this->invoice,
			'verifyUrl'    => $verifyUrl,
			'invoiceQrSvg' => $invoiceQrSvg,
		])->render();
		$dompdf = new Dompdf((new Options())->set('defaultFont','DejaVu Sans'));
		$dompdf->loadHtml($html);
		$dompdf->render();
		$pdf = $dompdf->output();
		return $this->subject('Nueva factura ' . $this->invoice->period . ' - ' . $condoName)
			->view('emails.invoice_created')
			->attachData($pdf,'factura_' . $this->invoice->id . '.pdf');
	}
}
