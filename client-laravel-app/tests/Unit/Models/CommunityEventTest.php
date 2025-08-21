<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CommunityEvent;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CommunityEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->organizer = User::factory()->create();
    }

    /** @test */
    public function it_belongs_to_an_organizer()
    {
        $event = CommunityEvent::factory()->create([
            'organizer_id' => $this->organizer->id
        ]);

        $this->assertInstanceOf(User::class, $event->organizer);
        $this->assertEquals($this->organizer->id, $event->organizer->id);
    }

    /** @test */
    public function it_has_many_registrations()
    {
        $event = CommunityEvent::factory()->create();
        $registration = EventRegistration::factory()->create([
            'event_id' => $event->id
        ]);

        $this->assertTrue($event->registrations->contains($registration));
    }

    /** @test */
    public function it_can_scope_upcoming_events()
    {
        $upcomingEvent = CommunityEvent::factory()->create([
            'start_date' => now()->addWeek()
        ]);
        
        $pastEvent = CommunityEvent::factory()->create([
            'start_date' => now()->subWeek()
        ]);

        $upcomingEvents = CommunityEvent::upcoming()->get();

        $this->assertTrue($upcomingEvents->contains($upcomingEvent));
        $this->assertFalse($upcomingEvents->contains($pastEvent));
    }

    /** @test */
    public function it_can_scope_past_events()
    {
        $pastEvent = CommunityEvent::factory()->create([
            'end_date' => now()->subWeek()
        ]);
        
        $upcomingEvent = CommunityEvent::factory()->create([
            'end_date' => now()->addWeek()
        ]);

        $pastEvents = CommunityEvent::past()->get();

        $this->assertTrue($pastEvents->contains($pastEvent));
        $this->assertFalse($pastEvents->contains($upcomingEvent));
    }

    /** @test */
    public function it_can_scope_active_events()
    {
        $activeEvent = CommunityEvent::factory()->create([
            'status' => 'active'
        ]);
        
        $cancelledEvent = CommunityEvent::factory()->create([
            'status' => 'cancelled'
        ]);

        $activeEvents = CommunityEvent::active()->get();

        $this->assertTrue($activeEvents->contains($activeEvent));
        $this->assertFalse($activeEvents->contains($cancelledEvent));
    }

    /** @test */
    public function it_can_check_if_event_is_upcoming()
    {
        $upcomingEvent = CommunityEvent::factory()->create([
            'start_date' => now()->addWeek()
        ]);

        $pastEvent = CommunityEvent::factory()->create([
            'start_date' => now()->subWeek()
        ]);

        $this->assertTrue($upcomingEvent->isUpcoming());
        $this->assertFalse($pastEvent->isUpcoming());
    }

    /** @test */
    public function it_can_check_if_event_is_past()
    {
        $pastEvent = CommunityEvent::factory()->create([
            'end_date' => now()->subWeek()
        ]);

        $upcomingEvent = CommunityEvent::factory()->create([
            'end_date' => now()->addWeek()
        ]);

        $this->assertTrue($pastEvent->isPast());
        $this->assertFalse($upcomingEvent->isPast());
    }

    /** @test */
    public function it_can_check_if_event_is_ongoing()
    {
        $ongoingEvent = CommunityEvent::factory()->create([
            'start_date' => now()->subHour(),
            'end_date' => now()->addHour()
        ]);

        $upcomingEvent = CommunityEvent::factory()->create([
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addHours(2)
        ]);

        $this->assertTrue($ongoingEvent->isOngoing());
        $this->assertFalse($upcomingEvent->isOngoing());
    }

    /** @test */
    public function it_can_check_if_registration_is_open()
    {
        $openEvent = CommunityEvent::factory()->create([
            'registration_deadline' => now()->addWeek(),
            'max_attendees' => 100,
            'current_attendees' => 50
        ]);

        $this->assertTrue($openEvent->isRegistrationOpen());
    }

    /** @test */
    public function it_can_check_if_registration_is_closed_due_to_deadline()
    {
        $closedEvent = CommunityEvent::factory()->create([
            'registration_deadline' => now()->subDay()
        ]);

        $this->assertFalse($closedEvent->isRegistrationOpen());
    }

    /** @test */
    public function it_can_check_if_event_is_full()
    {
        $fullEvent = CommunityEvent::factory()->create([
            'max_attendees' => 50,
            'current_attendees' => 50
        ]);

        $notFullEvent = CommunityEvent::factory()->create([
            'max_attendees' => 100,
            'current_attendees' => 50
        ]);

        $this->assertTrue($fullEvent->isFull());
        $this->assertFalse($notFullEvent->isFull());
    }

    /** @test */
    public function it_can_get_available_spots()
    {
        $event = CommunityEvent::factory()->create([
            'max_attendees' => 100,
            'current_attendees' => 75
        ]);

        $this->assertEquals(25, $event->getAvailableSpotsAttribute());
    }

    /** @test */
    public function it_returns_null_for_available_spots_when_no_max_attendees()
    {
        $event = CommunityEvent::factory()->create([
            'max_attendees' => null,
            'current_attendees' => 75
        ]);

        $this->assertNull($event->getAvailableSpotsAttribute());
    }

    /** @test */
    public function it_can_get_duration_display()
    {
        $event = CommunityEvent::factory()->create([
            'start_date' => Carbon::parse('2024-01-15 10:00:00'),
            'end_date' => Carbon::parse('2024-01-15 14:00:00')
        ]);

        $duration = $event->getDurationDisplayAttribute();

        $this->assertStringContainsString('4 hours', $duration);
    }

    /** @test */
    public function it_can_get_status_display()
    {
        $activeEvent = CommunityEvent::factory()->create([
            'status' => 'active'
        ]);

        $cancelledEvent = CommunityEvent::factory()->create([
            'status' => 'cancelled'
        ]);

        $this->assertEquals('Active', $activeEvent->getStatusDisplayAttribute());
        $this->assertEquals('Cancelled', $cancelledEvent->getStatusDisplayAttribute());
    }

    /** @test */
    public function it_can_check_if_user_is_registered()
    {
        $event = CommunityEvent::factory()->create();
        
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id
        ]);

        $this->assertTrue($event->isUserRegistered($this->user));
    }

    /** @test */
    public function it_can_check_if_user_is_not_registered()
    {
        $event = CommunityEvent::factory()->create();

        $this->assertFalse($event->isUserRegistered($this->user));
    }

    /** @test */
    public function it_can_get_user_registration()
    {
        $event = CommunityEvent::factory()->create();
        
        $registration = EventRegistration::factory()->create([
            'event_id' => $event->id,
            'user_id' => $this->user->id
        ]);

        $userRegistration = $event->getUserRegistration($this->user);

        $this->assertEquals($registration->id, $userRegistration->id);
    }

    /** @test */
    public function it_returns_null_when_user_has_no_registration()
    {
        $event = CommunityEvent::factory()->create();

        $this->assertNull($event->getUserRegistration($this->user));
    }

    /** @test */
    public function it_can_increment_attendee_count()
    {
        $event = CommunityEvent::factory()->create([
            'current_attendees' => 25
        ]);

        $event->incrementAttendeeCount();

        $this->assertEquals(26, $event->fresh()->current_attendees);
    }

    /** @test */
    public function it_can_decrement_attendee_count()
    {
        $event = CommunityEvent::factory()->create([
            'current_attendees' => 25
        ]);

        $event->decrementAttendeeCount();

        $this->assertEquals(24, $event->fresh()->current_attendees);
    }

    /** @test */
    public function it_can_get_registration_statistics()
    {
        $event = CommunityEvent::factory()->create();
        
        // Create registrations with different statuses
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'confirmed'
        ]);
        
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'waitlisted'
        ]);
        
        EventRegistration::factory()->create([
            'event_id' => $event->id,
            'status' => 'cancelled'
        ]);

        $stats = $event->getRegistrationStatistics();

        $this->assertEquals(3, $stats['total_registrations']);
        $this->assertEquals(1, $stats['confirmed_registrations']);
        $this->assertEquals(1, $stats['waitlisted_registrations']);
        $this->assertEquals(1, $stats['cancelled_registrations']);
    }

    /** @test */
    public function it_can_search_events_by_keyword()
    {
        $event1 = CommunityEvent::factory()->create([
            'title' => 'Volunteer Networking Event'
        ]);
        
        $event2 = CommunityEvent::factory()->create([
            'title' => 'Community Cleanup'
        ]);

        $results = CommunityEvent::search(['keyword' => 'networking'])->get();

        $this->assertTrue($results->contains($event1));
        $this->assertFalse($results->contains($event2));
    }

    /** @test */
    public function it_can_search_events_by_type()
    {
        $event1 = CommunityEvent::factory()->create([
            'type' => 'networking'
        ]);
        
        $event2 = CommunityEvent::factory()->create([
            'type' => 'training'
        ]);

        $results = CommunityEvent::search(['type' => 'networking'])->get();

        $this->assertTrue($results->contains($event1));
        $this->assertFalse($results->contains($event2));
    }

    /** @test */
    public function it_can_search_events_by_location()
    {
        $event1 = CommunityEvent::factory()->create([
            'location' => 'New York'
        ]);
        
        $event2 = CommunityEvent::factory()->create([
            'location' => 'Los Angeles'
        ]);

        $results = CommunityEvent::search(['location' => 'New York'])->get();

        $this->assertTrue($results->contains($event1));
        $this->assertFalse($results->contains($event2));
    }
}