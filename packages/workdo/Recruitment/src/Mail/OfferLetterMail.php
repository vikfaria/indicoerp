<?php

namespace Workdo\Recruitment\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Workdo\Recruitment\Models\Offer;

class OfferLetterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $offer;

    public function __construct(Offer $offer)
    {
        $this->offer = $offer;
    }

    public function build()
    {
        $candidateName = $this->offer->candidate 
            ? $this->offer->candidate->first_name . ' ' . $this->offer->candidate->last_name 
            : 'Candidate';

        return $this->subject('Job Offer Letter - ' . $this->offer->position)
                    ->view('recruitment::emails.offer-letter')
                    ->with([
                        'offer' => $this->offer,
                        'candidateName' => $candidateName,
                    ]);
    }
}