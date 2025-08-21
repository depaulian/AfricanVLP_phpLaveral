<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\VolunteerApplication;
use App\Models\VolunteeringOpportunity;
use App\Models\User;
use App\Models\Organization;
use App\Models\VolunteerApplicationStatusHistory;
use App\Models\VolunteerApplicationMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VolunteerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->organization = Organization::factory()->create();
        $this->opportunity = VolunteeringOpportunity::factory()->create([
            'organization_id' => $this->organization->id
        ]);
    }

    /** @test */
    public function it_belongs_to_a_volunteer()
    {
        $application = VolunteerApplication::factory()->create([
            'volunteer_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $application->volunteer);
        $this->assertEquals($this->user->id, $application->volunteer->id);
    }

    /** @test */
    public function it_belongs_to_an_opportunity()
    {
        $application = VolunteerApplication::factory()->create([
            'opportunity_id' => $this->opportunity->id
        ]);

        $this->assertInstanceOf(VolunteeringOpportunity::class, $application->opportunity);
        $this->assertEquals($this->opportunity->id, $application->opportunity->id);
    }

    /** @test */
    public function it_has_many_status_histories()
    {
        $application = VolunteerApplication::factory()->create();
        $statusHistory = VolunteerApplicationStatusHistory::factory()->create([
            'application_id' => $application->id
        ]);

        $this->assertTrue($application->statusHistories->contains($statusHistory));
    }

    /** @test */
    public function it_has_many_messages()
    {
        $application = VolunteerApplication::factory()->create();
        $message = VolunteerApplicationMessage::factory()->create([
            'application_id' => $application->id
        ]);

        $this->assertTrue($application->messages->contains($message));
    }

    /** @test */
    public function it_can_scope_pending_applications()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);
        
        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $pendingApplications = VolunteerApplication::pending()->get();

        $this->assertTrue($pendingApplications->contains($pendingApplication));
        $this->assertFalse($pendingApplications->contains($approvedApplication));
    }

    /** @test */
    public function it_can_scope_approved_applications()
    {
        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);
        
        $rejectedApplication = VolunteerApplication::factory()->create([
            'status' => 'rejected'
        ]);

        $approvedApplications = VolunteerApplication::approved()->get();

        $this->assertTrue($approvedApplications->contains($approvedApplication));
        $this->assertFalse($approvedApplications->contains($rejectedApplication));
    }

    /** @test */
    public function it_can_scope_rejected_applications()
    {
        $rejectedApplication = VolunteerApplication::factory()->create([
            'status' => 'rejected'
        ]);
        
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $rejectedApplications = VolunteerApplication::rejected()->get();

        $this->assertTrue($rejectedApplications->contains($rejectedApplication));
        $this->assertFalse($rejectedApplications->contains($pendingApplication));
    }

    /** @test */
    public function it_can_scope_withdrawn_applications()
    {
        $withdrawnApplication = VolunteerApplication::factory()->create([
            'status' => 'withdrawn'
        ]);
        
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $withdrawnApplications = VolunteerApplication::withdrawn()->get();

        $this->assertTrue($withdrawnApplications->contains($withdrawnApplication));
        $this->assertFalse($withdrawnApplications->contains($pendingApplication));
    }

    /** @test */
    public function it_can_approve_application()
    {
        $application = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $approver = User::factory()->create();
        $application->approve($approver, 'Great candidate');

        $this->assertEquals('approved', $application->fresh()->status);
        $this->assertEquals($approver->id, $application->fresh()->reviewed_by);
        $this->assertEquals('Great candidate', $application->fresh()->review_notes);
        $this->assertNotNull($application->fresh()->reviewed_at);
    }

    /** @test */
    public function it_can_reject_application()
    {
        $application = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $reviewer = User::factory()->create();
        $application->reject($reviewer, 'Not suitable');

        $this->assertEquals('rejected', $application->fresh()->status);
        $this->assertEquals($reviewer->id, $application->fresh()->reviewed_by);
        $this->assertEquals('Not suitable', $application->fresh()->review_notes);
        $this->assertNotNull($application->fresh()->reviewed_at);
    }

    /** @test */
    public function it_can_withdraw_application()
    {
        $application = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $application->withdraw('Changed my mind');

        $this->assertEquals('withdrawn', $application->fresh()->status);
        $this->assertEquals('Changed my mind', $application->fresh()->withdrawal_reason);
        $this->assertNotNull($application->fresh()->withdrawn_at);
    }

    /** @test */
    public function it_can_check_if_application_is_pending()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $this->assertTrue($pendingApplication->isPending());
        $this->assertFalse($approvedApplication->isPending());
    }

    /** @test */
    public function it_can_check_if_application_is_approved()
    {
        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($approvedApplication->isApproved());
        $this->assertFalse($pendingApplication->isApproved());
    }

    /** @test */
    public function it_can_check_if_application_is_rejected()
    {
        $rejectedApplication = VolunteerApplication::factory()->create([
            'status' => 'rejected'
        ]);

        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($rejectedApplication->isRejected());
        $this->assertFalse($pendingApplication->isRejected());
    }

    /** @test */
    public function it_can_check_if_application_is_withdrawn()
    {
        $withdrawnApplication = VolunteerApplication::factory()->create([
            'status' => 'withdrawn'
        ]);

        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($withdrawnApplication->isWithdrawn());
        $this->assertFalse($pendingApplication->isWithdrawn());
    }

    /** @test */
    public function it_can_check_if_application_can_be_withdrawn()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $rejectedApplication = VolunteerApplication::factory()->create([
            'status' => 'rejected'
        ]);

        $this->assertTrue($pendingApplication->canBeWithdrawn());
        $this->assertTrue($approvedApplication->canBeWithdrawn());
        $this->assertFalse($rejectedApplication->canBeWithdrawn());
    }

    /** @test */
    public function it_can_get_status_display()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $this->assertEquals('Pending Review', $pendingApplication->getStatusDisplayAttribute());
        $this->assertEquals('Approved', $approvedApplication->getStatusDisplayAttribute());
    }

    /** @test */
    public function it_can_get_status_color()
    {
        $pendingApplication = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved'
        ]);

        $rejectedApplication = VolunteerApplication::factory()->create([
            'status' => 'rejected'
        ]);

        $this->assertEquals('yellow', $pendingApplication->getStatusColorAttribute());
        $this->assertEquals('green', $approvedApplication->getStatusColorAttribute());
        $this->assertEquals('red', $rejectedApplication->getStatusColorAttribute());
    }

    /** @test */
    public function it_can_get_time_since_application()
    {
        $application = VolunteerApplication::factory()->create([
            'applied_at' => now()->subDays(3)
        ]);

        $timeSince = $application->getTimeSinceApplicationAttribute();

        $this->assertStringContainsString('3 days', $timeSince);
    }

    /** @test */
    public function it_can_get_response_time()
    {
        $application = VolunteerApplication::factory()->create([
            'applied_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(2)
        ]);

        $responseTime = $application->getResponseTimeAttribute();

        $this->assertStringContainsString('3 days', $responseTime);
    }

    /** @test */
    public function it_returns_null_response_time_when_not_reviewed()
    {
        $application = VolunteerApplication::factory()->create([
            'applied_at' => now()->subDays(5),
            'reviewed_at' => null
        ]);

        $this->assertNull($application->getResponseTimeAttribute());
    }

    /** @test */
    public function it_can_add_status_history()
    {
        $application = VolunteerApplication::factory()->create([
            'status' => 'pending'
        ]);

        $changer = User::factory()->create();
        $application->addStatusHistory('approved', $changer, 'Application approved');

        $this->assertEquals(1, $application->statusHistories()->count());
        
        $history = $application->statusHistories()->first();
        $this->assertEquals('approved', $history->new_status);
        $this->assertEquals($changer->id, $history->changed_by);
        $this->assertEquals('Application approved', $history->notes);
    }

    /** @test */
    public function it_can_add_message()
    {
        $application = VolunteerApplication::factory()->create();
        $sender = User::factory()->create();

        $application->addMessage($sender, 'Hello, when can you start?');

        $this->assertEquals(1, $application->messages()->count());
        
        $message = $application->messages()->first();
        $this->assertEquals($sender->id, $message->sender_id);
        $this->assertEquals('Hello, when can you start?', $message->message);
    }

    /** @test */
    public function it_can_get_latest_message()
    {
        $application = VolunteerApplication::factory()->create();
        $sender = User::factory()->create();

        $application->addMessage($sender, 'First message');
        sleep(1); // Ensure different timestamps
        $application->addMessage($sender, 'Latest message');

        $latestMessage = $application->getLatestMessage();

        $this->assertEquals('Latest message', $latestMessage->message);
    }

    /** @test */
    public function it_returns_null_when_no_messages()
    {
        $application = VolunteerApplication::factory()->create();

        $this->assertNull($application->getLatestMessage());
    }

    /** @test */
    public function it_can_get_unread_messages_count()
    {
        $application = VolunteerApplication::factory()->create();
        $sender = User::factory()->create();

        // Add read message
        $readMessage = VolunteerApplicationMessage::factory()->create([
            'application_id' => $application->id,
            'sender_id' => $sender->id,
            'is_read' => true
        ]);

        // Add unread message
        $unreadMessage = VolunteerApplicationMessage::factory()->create([
            'application_id' => $application->id,
            'sender_id' => $sender->id,
            'is_read' => false
        ]);

        $this->assertEquals(1, $application->getUnreadMessagesCount($this->user));
    }

    /** @test */
    public function it_can_mark_messages_as_read()
    {
        $application = VolunteerApplication::factory()->create();
        $sender = User::factory()->create();

        $message1 = VolunteerApplicationMessage::factory()->create([
            'application_id' => $application->id,
            'sender_id' => $sender->id,
            'is_read' => false
        ]);

        $message2 = VolunteerApplicationMessage::factory()->create([
            'application_id' => $application->id,
            'sender_id' => $sender->id,
            'is_read' => false
        ]);

        $application->markMessagesAsRead($this->user);

        $this->assertTrue($message1->fresh()->is_read);
        $this->assertTrue($message2->fresh()->is_read);
    }

    /** @test */
    public function it_can_get_application_statistics_for_opportunity()
    {
        $opportunity = VolunteeringOpportunity::factory()->create();

        VolunteerApplication::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'pending'
        ]);

        VolunteerApplication::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'approved'
        ]);

        VolunteerApplication::factory()->create([
            'opportunity_id' => $opportunity->id,
            'status' => 'rejected'
        ]);

        $stats = VolunteerApplication::getOpportunityStatistics($opportunity);

        $this->assertEquals(3, $stats['total_applications']);
        $this->assertEquals(1, $stats['pending_applications']);
        $this->assertEquals(1, $stats['approved_applications']);
        $this->assertEquals(1, $stats['rejected_applications']);
    }

    /** @test */
    public function it_can_get_user_application_history()
    {
        $user = User::factory()->create();

        VolunteerApplication::factory()->create([
            'volunteer_id' => $user->id,
            'status' => 'pending'
        ]);

        VolunteerApplication::factory()->create([
            'volunteer_id' => $user->id,
            'status' => 'approved'
        ]);

        VolunteerApplication::factory()->create([
            'volunteer_id' => $user->id,
            'status' => 'rejected'
        ]);

        $history = VolunteerApplication::getUserApplicationHistory($user);

        $this->assertEquals(3, $history['total_applications']);
        $this->assertEquals(1, $history['pending_applications']);
        $this->assertEquals(1, $history['approved_applications']);
        $this->assertEquals(1, $history['rejected_applications']);
    }

    /** @test */
    public function it_can_get_applications_requiring_review()
    {
        $oldApplication = VolunteerApplication::factory()->create([
            'status' => 'pending',
            'applied_at' => now()->subDays(10)
        ]);

        $newApplication = VolunteerApplication::factory()->create([
            'status' => 'pending',
            'applied_at' => now()->subDays(2)
        ]);

        $approvedApplication = VolunteerApplication::factory()->create([
            'status' => 'approved',
            'applied_at' => now()->subDays(15)
        ]);

        $requiresReview = VolunteerApplication::getApplicationsRequiringReview(7);

        $this->assertTrue($requiresReview->contains($oldApplication));
        $this->assertFalse($requiresReview->contains($newApplication));
        $this->assertFalse($requiresReview->contains($approvedApplication));
    }

    /** @test */
    public function it_can_get_application_analytics()
    {
        // Create applications with different statuses and dates
        VolunteerApplication::factory()->create([
            'status' => 'pending',
            'applied_at' => now()->subDays(5)
        ]);

        VolunteerApplication::factory()->create([
            'status' => 'approved',
            'applied_at' => now()->subDays(10),
            'reviewed_at' => now()->subDays(8)
        ]);

        VolunteerApplication::factory()->create([
            'status' => 'rejected',
            'applied_at' => now()->subDays(15),
            'reviewed_at' => now()->subDays(12)
        ]);

        $analytics = VolunteerApplication::getApplicationAnalytics();

        $this->assertEquals(3, $analytics['total_applications']);
        $this->assertEquals(1, $analytics['pending_applications']);
        $this->assertEquals(1, $analytics['approved_applications']);
        $this->assertEquals(1, $analytics['rejected_applications']);
        $this->assertEquals(50.0, $analytics['approval_rate']); // 1 approved out of 2 reviewed
        $this->assertIsFloat($analytics['average_response_time_days']);
    }
}