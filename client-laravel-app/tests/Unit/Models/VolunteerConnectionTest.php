<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\VolunteerConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VolunteerConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
    }

    /** @test */
    public function it_belongs_to_requester()
    {
        $connection = VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $this->user2->id
        ]);

        $this->assertInstanceOf(User::class, $connection->requester);
        $this->assertEquals($this->user1->id, $connection->requester->id);
    }

    /** @test */
    public function it_belongs_to_requested_user()
    {
        $connection = VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $this->user2->id
        ]);

        $this->assertInstanceOf(User::class, $connection->requested);
        $this->assertEquals($this->user2->id, $connection->requested->id);
    }

    /** @test */
    public function it_can_scope_pending_connections()
    {
        $pendingConnection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);
        
        $acceptedConnection = VolunteerConnection::factory()->create([
            'status' => 'accepted'
        ]);

        $pendingConnections = VolunteerConnection::pending()->get();

        $this->assertTrue($pendingConnections->contains($pendingConnection));
        $this->assertFalse($pendingConnections->contains($acceptedConnection));
    }

    /** @test */
    public function it_can_scope_accepted_connections()
    {
        $acceptedConnection = VolunteerConnection::factory()->create([
            'status' => 'accepted'
        ]);
        
        $rejectedConnection = VolunteerConnection::factory()->create([
            'status' => 'rejected'
        ]);

        $acceptedConnections = VolunteerConnection::accepted()->get();

        $this->assertTrue($acceptedConnections->contains($acceptedConnection));
        $this->assertFalse($acceptedConnections->contains($rejectedConnection));
    }

    /** @test */
    public function it_can_accept_connection()
    {
        $connection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);

        $connection->accept();

        $this->assertEquals('accepted', $connection->fresh()->status);
        $this->assertNotNull($connection->fresh()->accepted_at);
    }

    /** @test */
    public function it_can_reject_connection()
    {
        $connection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);

        $connection->reject();

        $this->assertEquals('rejected', $connection->fresh()->status);
        $this->assertNotNull($connection->fresh()->rejected_at);
    }

    /** @test */
    public function it_can_check_if_connection_is_pending()
    {
        $pendingConnection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);

        $acceptedConnection = VolunteerConnection::factory()->create([
            'status' => 'accepted'
        ]);

        $this->assertTrue($pendingConnection->isPending());
        $this->assertFalse($acceptedConnection->isPending());
    }

    /** @test */
    public function it_can_check_if_connection_is_accepted()
    {
        $acceptedConnection = VolunteerConnection::factory()->create([
            'status' => 'accepted'
        ]);

        $pendingConnection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);

        $this->assertTrue($acceptedConnection->isAccepted());
        $this->assertFalse($pendingConnection->isAccepted());
    }

    /** @test */
    public function it_can_get_status_display()
    {
        $pendingConnection = VolunteerConnection::factory()->create([
            'status' => 'pending'
        ]);

        $acceptedConnection = VolunteerConnection::factory()->create([
            'status' => 'accepted'
        ]);

        $this->assertEquals('Pending', $pendingConnection->getStatusDisplayAttribute());
        $this->assertEquals('Connected', $acceptedConnection->getStatusDisplayAttribute());
    }

    /** @test */
    public function it_can_get_other_user_for_requester()
    {
        $connection = VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $this->user2->id
        ]);

        $otherUser = $connection->getOtherUser($this->user1);

        $this->assertEquals($this->user2->id, $otherUser->id);
    }

    /** @test */
    public function it_can_get_other_user_for_requested()
    {
        $connection = VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $this->user2->id
        ]);

        $otherUser = $connection->getOtherUser($this->user2);

        $this->assertEquals($this->user1->id, $otherUser->id);
    }

    /** @test */
    public function it_can_get_mutual_connections()
    {
        $user3 = User::factory()->create();
        
        // User1 connected to User3
        VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $user3->id,
            'status' => 'accepted'
        ]);
        
        // User2 connected to User3
        VolunteerConnection::factory()->create([
            'requester_id' => $this->user2->id,
            'requested_id' => $user3->id,
            'status' => 'accepted'
        ]);

        $mutualConnections = VolunteerConnection::getMutualConnections($this->user1, $this->user2);

        $this->assertEquals(1, $mutualConnections->count());
        $this->assertTrue($mutualConnections->contains('id', $user3->id));
    }

    /** @test */
    public function it_can_get_connection_suggestions()
    {
        $user3 = User::factory()->create();
        $user4 = User::factory()->create();
        
        // User1 connected to User3
        VolunteerConnection::factory()->create([
            'requester_id' => $this->user1->id,
            'requested_id' => $user3->id,
            'status' => 'accepted'
        ]);
        
        // User3 connected to User4 (potential suggestion for User1)
        VolunteerConnection::factory()->create([
            'requester_id' => $user3->id,
            'requested_id' => $user4->id,
            'status' => 'accepted'
        ]);

        $suggestions = VolunteerConnection::getConnectionSuggestions($this->user1, 5);

        $this->assertTrue($suggestions->contains('id', $user4->id));
        $this->assertFalse($suggestions->contains('id', $user3->id)); // Already connected
    }
}