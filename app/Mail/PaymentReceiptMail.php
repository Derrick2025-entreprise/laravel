<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $payment;
    public $receiptPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Payment $payment, $receiptPath = null)
    {
        $this->payment = $payment;
        $this->receiptPath = $receiptPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '💳 Quitus de paiement validé - SGEE Cameroun',
            from: config('mail.from.address', 'noreply@sgee-cameroun.cm'),
            replyTo: 'comptabilite@sgee-cameroun.cm'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.receipt-validated',
            with: [
                'payment' => $this->payment,
                'student' => $this->payment->student,
                'school' => $this->payment->student->school,
                'filiere' => $this->payment->student->filiere,
                'amount' => number_format($this->payment->amount, 0, ',', ' '),
                'payment_date' => $this->payment->payment_date->format('d/m/Y H:i'),
                'validated_date' => $this->payment->validated_at->format('d/m/Y H:i'),
                'reference' => $this->payment->reference_number,
                'remaining_amount' => $this->payment->student->remaining_amount
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->receiptPath && file_exists(storage_path('app/' . $this->receiptPath))) {
            $attachments[] = Attachment::fromPath(storage_path('app/' . $this->receiptPath))
                ->as('Quitus_' . $this->payment->reference_number . '.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}