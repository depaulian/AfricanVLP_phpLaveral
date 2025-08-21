<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\VolunteerTimeLog;
use App\Models\VolunteerAssignment;
use App\Models\User;
use App\Models\VolunteeringOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VolunteerTimeLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->opportunity = VolunteeringOpportunity::factory()->create();
        $this->assignment = VolunteerAssignment::factory()->create([
            'volunteer_id' => $this->user->id,
            'opportunity_id' => $this->opportunity->id
        ]);
    }

    /** @test */
    public function it_belongs_to_a_volunteer()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'volunteer_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $timeLog->volunteer);
        $this->assertEquals($this->user->id, $timeLog->volunteer->id);
    }

    /** @test */
    public function it_belongs_to_an_assignment()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'assignment_id' => $this->assignment->id
        ]);

        $this->assertInstanceOf(VolunteerAssignment::class, $timeLog->assignment);
        $this->assertEquals($this->assignment->id, $timeLog->assignment->id);
    }

    /** @test */
    public function it_can_scope_pending_logs()
    {
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);
        
        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $pendingLogs = VolunteerTimeLog::pending()->get();

        $this->assertTrue($pendingLogs->contains($pendingLog));
        $this->assertFalse($pendingLogs->contains($approvedLog));
    }

    /** @test */
    public function it_can_scope_approved_logs()
    {
        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);
        
        $rejectedLog = VolunteerTimeLog::factory()->create([
            'status' => 'rejected'
        ]);

        $approvedLogs = VolunteerTimeLog::approved()->get();

        $this->assertTrue($approvedLogs->contains($approvedLog));
        $this->assertFalse($approvedLogs->contains($rejectedLog));
    }

    /** @test */
    public function it_can_scope_rejected_logs()
    {
        $rejectedLog = VolunteerTimeLog::factory()->create([
            'status' => 'rejected'
        ]);
        
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $rejectedLogs = VolunteerTimeLog::rejected()->get();

        $this->assertTrue($rejectedLogs->contains($rejectedLog));
        $this->assertFalse($rejectedLogs->contains($pendingLog));
    }

    /** @test */
    public function it_can_scope_logs_for_volunteer()
    {
        $userLog = VolunteerTimeLog::factory()->create([
            'volunteer_id' => $this->user->id
        ]);
        
        $otherUser = User::factory()->create();
        $otherLog = VolunteerTimeLog::factory()->create([
            'volunteer_id' => $otherUser->id
        ]);

        $userLogs = VolunteerTimeLog::forVolunteer($this->user->id)->get();

        $this->assertTrue($userLogs->contains($userLog));
        $this->assertFalse($userLogs->contains($otherLog));
    }

    /** @test */
    public function it_can_scope_logs_for_assignment()
    {
        $assignmentLog = VolunteerTimeLog::factory()->create([
            'assignment_id' => $this->assignment->id
        ]);
        
        $otherAssignment = VolunteerAssignment::factory()->create();
        $otherLog = VolunteerTimeLog::factory()->create([
            'assignment_id' => $otherAssignment->id
        ]);

        $assignmentLogs = VolunteerTimeLog::forAssignment($this->assignment->id)->get();

        $this->assertTrue($assignmentLogs->contains($assignmentLog));
        $this->assertFalse($assignmentLogs->contains($otherLog));
    }

    /** @test */
    public function it_can_scope_logs_in_date_range()
    {
        $startDate = Carbon::parse('2024-01-01');
        $endDate = Carbon::parse('2024-01-31');
        
        $logInRange = VolunteerTimeLog::factory()->create([
            'log_date' => Carbon::parse('2024-01-15')
        ]);
        
        $logOutOfRange = VolunteerTimeLog::factory()->create([
            'log_date' => Carbon::parse('2024-02-15')
        ]);

        $logsInRange = VolunteerTimeLog::inDateRange($startDate, $endDate)->get();

        $this->assertTrue($logsInRange->contains($logInRange));
        $this->assertFalse($logsInRange->contains($logOutOfRange));
    }

    /** @test */
    public function it_can_approve_time_log()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $approver = User::factory()->create();
        $timeLog->approve($approver, 'Looks good');

        $this->assertEquals('approved', $timeLog->fresh()->status);
        $this->assertEquals($approver->id, $timeLog->fresh()->approved_by);
        $this->assertEquals('Looks good', $timeLog->fresh()->approval_notes);
        $this->assertNotNull($timeLog->fresh()->approved_at);
    }

    /** @test */
    public function it_can_reject_time_log()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $reviewer = User::factory()->create();
        $timeLog->reject($reviewer, 'Hours seem excessive');

        $this->assertEquals('rejected', $timeLog->fresh()->status);
        $this->assertEquals($reviewer->id, $timeLog->fresh()->approved_by);
        $this->assertEquals('Hours seem excessive', $timeLog->fresh()->approval_notes);
        $this->assertNotNull($timeLog->fresh()->approved_at);
    }

    /** @test */
    public function it_can_check_if_log_is_pending()
    {
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $this->assertTrue($pendingLog->isPending());
        $this->assertFalse($approvedLog->isPending());
    }

    /** @test */
    public function it_can_check_if_log_is_approved()
    {
        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($approvedLog->isApproved());
        $this->assertFalse($pendingLog->isApproved());
    }

    /** @test */
    public function it_can_check_if_log_is_rejected()
    {
        $rejectedLog = VolunteerTimeLog::factory()->create([
            'status' => 'rejected'
        ]);

        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($rejectedLog->isRejected());
        $this->assertFalse($pendingLog->isRejected());
    }

    /** @test */
    public function it_can_check_if_log_can_be_edited()
    {
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $this->assertTrue($pendingLog->canBeEdited());
        $this->assertFalse($approvedLog->canBeEdited());
    }

    /** @test */
    public function it_can_get_status_display()
    {
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $this->assertEquals('Pending Approval', $pendingLog->getStatusDisplayAttribute());
        $this->assertEquals('Approved', $approvedLog->getStatusDisplayAttribute());
    }

    /** @test */
    public function it_can_get_status_color()
    {
        $pendingLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending'
        ]);

        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved'
        ]);

        $rejectedLog = VolunteerTimeLog::factory()->create([
            'status' => 'rejected'
        ]);

        $this->assertEquals('yellow', $pendingLog->getStatusColorAttribute());
        $this->assertEquals('green', $approvedLog->getStatusColorAttribute());
        $this->assertEquals('red', $rejectedLog->getStatusColorAttribute());
    }

    /** @test */
    public function it_can_get_hours_display()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'hours_logged' => 2.5
        ]);

        $this->assertEquals('2.5 hours', $timeLog->getHoursDisplayAttribute());
    }

    /** @test */
    public function it_can_get_date_display()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'log_date' => Carbon::parse('2024-01-15')
        ]);

        $this->assertEquals('Jan 15, 2024', $timeLog->getDateDisplayAttribute());
    }

    /** @test */
    public function it_can_get_time_since_logged()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'created_at' => now()->subDays(3)
        ]);

        $timeSince = $timeLog->getTimeSinceLoggedAttribute();

        $this->assertStringContainsString('3 days', $timeSince);
    }

    /** @test */
    public function it_can_calculate_total_hours_for_volunteer()
    {
        $volunteer = User::factory()->create();

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 3.0,
            'status' => 'approved'
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 2.5,
            'status' => 'approved'
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 1.0,
            'status' => 'pending' // Should not be counted
        ]);

        $totalHours = VolunteerTimeLog::getTotalHoursForVolunteer($volunteer);

        $this->assertEquals(5.5, $totalHours);
    }

    /** @test */
    public function it_can_calculate_total_hours_for_assignment()
    {
        $assignment = VolunteerAssignment::factory()->create();

        VolunteerTimeLog::factory()->create([
            'assignment_id' => $assignment->id,
            'hours_logged' => 4.0,
            'status' => 'approved'
        ]);

        VolunteerTimeLog::factory()->create([
            'assignment_id' => $assignment->id,
            'hours_logged' => 3.5,
            'status' => 'approved'
        ]);

        VolunteerTimeLog::factory()->create([
            'assignment_id' => $assignment->id,
            'hours_logged' => 2.0,
            'status' => 'rejected' // Should not be counted
        ]);

        $totalHours = VolunteerTimeLog::getTotalHoursForAssignment($assignment);

        $this->assertEquals(7.5, $totalHours);
    }

    /** @test */
    public function it_can_get_volunteer_time_statistics()
    {
        $volunteer = User::factory()->create();

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 3.0,
            'status' => 'approved'
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 2.0,
            'status' => 'pending'
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 1.0,
            'status' => 'rejected'
        ]);

        $stats = VolunteerTimeLog::getVolunteerTimeStatistics($volunteer);

        $this->assertEquals(6.0, $stats['total_hours_logged']);
        $this->assertEquals(3.0, $stats['approved_hours']);
        $this->assertEquals(2.0, $stats['pending_hours']);
        $this->assertEquals(1.0, $stats['rejected_hours']);
        $this->assertEquals(3, $stats['total_entries']);
        $this->assertEquals(1, $stats['approved_entries']);
        $this->assertEquals(1, $stats['pending_entries']);
        $this->assertEquals(1, $stats['rejected_entries']);
    }

    /** @test */
    public function it_can_get_logs_requiring_approval()
    {
        $oldLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(10)
        ]);

        $newLog = VolunteerTimeLog::factory()->create([
            'status' => 'pending',
            'created_at' => now()->subDays(2)
        ]);

        $approvedLog = VolunteerTimeLog::factory()->create([
            'status' => 'approved',
            'created_at' => now()->subDays(15)
        ]);

        $requiresApproval = VolunteerTimeLog::getLogsRequiringApproval(7);

        $this->assertTrue($requiresApproval->contains($oldLog));
        $this->assertFalse($requiresApproval->contains($newLog));
        $this->assertFalse($requiresApproval->contains($approvedLog));
    }

    /** @test */
    public function it_can_get_time_log_analytics()
    {
        // Create logs with different statuses and dates
        VolunteerTimeLog::factory()->create([
            'status' => 'pending',
            'hours_logged' => 2.0,
            'created_at' => now()->subDays(5)
        ]);

        VolunteerTimeLog::factory()->create([
            'status' => 'approved',
            'hours_logged' => 3.0,
            'created_at' => now()->subDays(10),
            'approved_at' => now()->subDays(8)
        ]);

        VolunteerTimeLog::factory()->create([
            'status' => 'rejected',
            'hours_logged' => 1.0,
            'created_at' => now()->subDays(15),
            'approved_at' => now()->subDays(12)
        ]);

        $analytics = VolunteerTimeLog::getTimeLogAnalytics();

        $this->assertEquals(3, $analytics['total_logs']);
        $this->assertEquals(1, $analytics['pending_logs']);
        $this->assertEquals(1, $analytics['approved_logs']);
        $this->assertEquals(1, $analytics['rejected_logs']);
        $this->assertEquals(6.0, $analytics['total_hours_logged']);
        $this->assertEquals(3.0, $analytics['approved_hours']);
        $this->assertEquals(50.0, $analytics['approval_rate']); // 1 approved out of 2 reviewed
        $this->assertIsFloat($analytics['average_approval_time_days']);
    }

    /** @test */
    public function it_can_validate_hours_logged()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'hours_logged' => 2.5
        ]);

        $this->assertTrue($timeLog->validateHours());
    }

    /** @test */
    public function it_can_detect_invalid_hours()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'hours_logged' => 25.0 // More than 24 hours in a day
        ]);

        $this->assertFalse($timeLog->validateHours());
    }

    /** @test */
    public function it_can_detect_negative_hours()
    {
        $timeLog = VolunteerTimeLog::factory()->create([
            'hours_logged' => -1.0
        ]);

        $this->assertFalse($timeLog->validateHours());
    }

    /** @test */
    public function it_can_get_monthly_hours_for_volunteer()
    {
        $volunteer = User::factory()->create();
        $month = Carbon::parse('2024-01-01');

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 3.0,
            'status' => 'approved',
            'log_date' => Carbon::parse('2024-01-15')
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 2.0,
            'status' => 'approved',
            'log_date' => Carbon::parse('2024-01-20')
        ]);

        VolunteerTimeLog::factory()->create([
            'volunteer_id' => $volunteer->id,
            'hours_logged' => 1.0,
            'status' => 'approved',
            'log_date' => Carbon::parse('2024-02-15') // Different month
        ]);

        $monthlyHours = VolunteerTimeLog::getMonthlyHoursForVolunteer($volunteer, $month);

        $this->assertEquals(5.0, $monthlyHours);
    }
}