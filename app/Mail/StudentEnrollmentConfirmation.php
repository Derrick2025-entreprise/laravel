<?php

namespace App\Mail;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class StudentEnrollmentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student;
    public $enrollmentCardPath;

    /**
     * Create a new message instance.
     */
    public function __construct(Student $student, $enrollmentCardPath = null)
    {
        $this->student = $student;
        $this->enrollmentCardPath = $enrollmentCardPath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎓 Confirmation d\'inscription - SGEE Cameroun',
            from: config('mail.from.address', 'noreply@sgee-cameroun.cm'),
            replyTo: 'support@sgee-cameroun.cm'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.student.enrollment-confirmation',
            with: [
                'student' => $this->student,
                'school' => $this->student->school,
                'department' => $this->student->department,
                'filiere' => $this->student->filiere,
                'enrollment_date' => $this->student->enrollment_date->format('d/m/Y'),
                'academic_year' => $this->student->academic_year,
                'student_number' => $this->student->student_number,
                'total_fees' => $this->student->filiere->enrollment_fee + $this->student->filiere->tuition_fee
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->enrollmentCardPath && file_exists(storage_path('app/' . $this->enrollmentCardPath))) {
            $attachments[] = Attachment::fromPath(storage_path('app/' . $this->enrollmentCardPath))
                ->as('Fiche_Inscription_' . $this->student->student_number . '.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}