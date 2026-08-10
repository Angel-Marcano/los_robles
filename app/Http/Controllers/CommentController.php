<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Invoice;
use App\Models\PaymentReport;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class CommentController extends Controller
{
    public function storeInvoiceComment(Request $request, Invoice $invoice)
    {
        $this->authorize('comment', $invoice);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $comment = $invoice->comments()->create([
            'user_id' => auth()->id(),
            'message' => $data['message'],
            'is_internal' => $request->boolean('is_internal', false) && auth()->user()->hasAnyRole(['super_admin', 'condo_admin', 'tower_admin']),
        ]);

        app(AuditService::class)->log('comment_created', 'Comment', $comment->id, [
            'commentable_type' => 'Invoice',
            'commentable_id' => $invoice->id,
        ]);

        $this->sendNotification($comment, $invoice);

        return back()->with('status', 'Comentario agregado.');
    }

    public function storePaymentReportComment(Request $request, PaymentReport $paymentReport)
    {
        $this->authorize('comment', $paymentReport);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $comment = $paymentReport->comments()->create([
            'user_id' => auth()->id(),
            'message' => $data['message'],
            'is_internal' => $request->boolean('is_internal', false) && auth()->user()->hasAnyRole(['super_admin', 'condo_admin', 'tower_admin']),
        ]);

        app(AuditService::class)->log('comment_created', 'Comment', $comment->id, [
            'commentable_type' => 'PaymentReport',
            'commentable_id' => $paymentReport->id,
        ]);

        $this->sendNotification($comment, $paymentReport);

        return back()->with('status', 'Comentario agregado.');
    }

    /**
     * Notifica al otro participante (propietario o admin) cuando se agrega un comentario.
     */
    protected function sendNotification(Comment $comment, $entity): void
    {
        $author = auth()->user();
        $isAdmin = $author->hasAnyRole(['super_admin', 'condo_admin', 'tower_admin']);

        if ($isAdmin) {
            $this->notifyOwner($comment, $entity);
        } else {
            $this->notifyAdmins($comment, $entity);
        }
    }

    /**
     * Notificar al propietario del apartamento cuando un admin comenta.
     */
    protected function notifyOwner(Comment $comment, $entity): void
    {
        $ownerEmail = null;
        $apartmentId = null;

        if ($entity instanceof Invoice) {
            $ownerEmail = $entity->owner_email;
            $apartmentId = $entity->apartment_id;
        } elseif ($entity instanceof PaymentReport) {
            $apartmentId = $entity->apartment_id;
        }

        if (!$ownerEmail && $apartmentId) {
            $ownership = \App\Models\Ownership::where('apartment_id', $apartmentId)
                ->where('active', true)
                ->where('role', 'owner')
                ->with('user')
                ->first();
            $ownerEmail = optional($ownership->user)->email;
        }

        if ($ownerEmail) {
            try {
                Mail::to($ownerEmail)->queue(new \App\Mail\CommentReplyMail($comment, $entity));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Comment reply mail to owner failed', [
                    'comment_id' => $comment->id,
                    'email' => $ownerEmail,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Notificar a los administradores cuando un propietario comenta.
     */
    protected function notifyAdmins(Comment $comment, $entity): void
    {
        $admins = \App\Models\User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['super_admin', 'condo_admin', 'tower_admin']);
        })->where('active', true)
            ->pluck('email')
            ->filter();

        if ($admins->isEmpty()) {
            return;
        }

        try {
            foreach ($admins as $adminEmail) {
                Mail::to($adminEmail)->queue(new \App\Mail\CommentReplyMail($comment, $entity));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Comment reply mail to admins failed', [
                'comment_id' => $comment->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
