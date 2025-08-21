<?php

namespace App\Events;

use App\Models\VolunteeringOpportunity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VolunteeringOpportunityCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $opportunity;

    /**
     * Create a new event instance.
     */
    public function __construct(VolunteeringOpportunity $opportunity)
    {
        $this->opportunity = $opportunity;
    }
}