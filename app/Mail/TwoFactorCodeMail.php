<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TwoFactorCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public string $userName;

    public function __construct(string $code, string $userName = '')
    {
        $this->code = $code;
        $this->userName = $userName;
    }

    public function build()
    {
        $condoName = app()->bound('currentCondominium')
            ? app('currentCondominium')->name
            : config('app.name', 'Los Robles');

        return $this->subject('Tu código de verificación - ' . $condoName)
            ->view('emails.two_factor_code');
    }
}
