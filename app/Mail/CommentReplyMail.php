<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommentReplyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Comment $comment;
    public $entity;

    public function __construct(Comment $comment, $entity)
    {
        $this->comment = $comment;
        $this->entity = $entity;
    }

    public function build()
    {
        $condoName = app()->bound('currentCondominium')
            ? app('currentCondominium')->name
            : config('app.name', 'Los Robles');

        $entityLabel = $this->entity instanceof \App\Models\Invoice
            ? 'Factura ' . $this->entity->number
            : 'Reporte de pago #' . $this->entity->id;

        return $this->subject('Nuevo comentario en ' . $entityLabel . ' - ' . $condoName)
            ->markdown('emails.comments.reply')
            ->with([
                'comment' => $this->comment,
                'entity' => $this->entity,
                'entityLabel' => $entityLabel,
                'url' => $this->entityUrl(),
            ]);
    }

    protected function entityUrl(): string
    {
        if ($this->entity instanceof \App\Models\Invoice) {
            return route('invoices.show', $this->entity);
        }

        if ($this->entity instanceof \App\Models\PaymentReport) {
            return route('payments.review', $this->entity);
        }

        return url('/');
    }
}
