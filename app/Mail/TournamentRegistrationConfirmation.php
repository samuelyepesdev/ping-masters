<?php

namespace App\Mail;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, string>  $divisionNames
     */
    public function __construct(
        public readonly User $user,
        public readonly Tournament $tournament,
        public readonly array $divisionNames,
        public readonly ?string $setPasswordUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Inscripción confirmada — {$this->tournament->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.tournament-registration-confirmation',
            with: [
                'user' => $this->user,
                'tournament' => $this->tournament,
                'divisionNames' => $this->divisionNames,
                'setPasswordUrl' => $this->setPasswordUrl,
            ],
        );
    }
}
