<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ProfileGamificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateProfileScores extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profile:calculate-scores 
                            {--user= : Calculate score for specific user ID}
                            {--batch-size=100 : Number of users to process in each batch}
                            {--force : Force recalculation even if recently calculated}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate profile scores and achievements for users';

    /**
     * Execute the console command.
     */
    public function handle(ProfileGamificationService $gamificationService): int
    {
        $this->info('Starting profile score calculation...');

        if ($userId = $this->option('user')) {
            return $this->calculateForUser($userId, $gamificationService);
        }

        return $this->calculateForAllUsers($gamificationService);
    }

    /**
     * Calculate score for a specific user.
     */
    private function calculateForUser(int $userId, ProfileGamificationService $gamificationService): int
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return 1;
        }

        $this->info("Calculating score for user: {$user->full_name} (ID: {$userId})");

        try {
            $profileScore = $gamificationService->calculateProfileScore($user);
            
            $this->info("Score calculated successfully:");
            $this->line("  Total Score: {$profileScore->total_score}");
            $this->line("  Completion: {$profileScore->completion_score}");
            $this->line("  Quality: {$profileScore->quality_score}");
            $this->line("  Engagement: {$profileScore->engagement_score}");
            $this->line("  Verification: {$profileScore->verification_score}");
            $this->line("  Rank: #{$profileScore->rank_position}");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to calculate score for user {$userId}: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calculate scores for all users.
     */
    private function calculateForAllUsers(ProfileGamificationService $gamificationService): int
    {
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        // Get users that need score calculation
        $query = User::query()
            ->with(['profile', 'profileScore'])
            ->where('status', 'active');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereDoesntHave('profileScore')
                  ->orWhereHas('profileScore', function ($sq) {
                      $sq->where('last_calculated_at', '<', now()->subHours(24));
                  });
            });
        }

        $totalUsers = $query->count();
        
        if ($totalUsers === 0) {
            $this->info('No users need score calculation.');
            return 0;
        }

        $this->info("Found {$totalUsers} users to process.");
        
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $processed = 0;
        $errors = 0;

        $query->chunk($batchSize, function ($users) use ($gamificationService, $progressBar, &$processed, &$errors) {
            foreach ($users as $user) {
                try {
                    $gamificationService->calculateProfileScore($user);
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error processing user {$user->id}: " . $e->getMessage());
                }
                
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Profile score calculation completed:");
        $this->line("  Processed: {$processed} users");
        
        if ($errors > 0) {
            $this->warn("  Errors: {$errors} users");
        }

        // Update all rank positions
        $this->info("Updating rank positions...");
        $this->updateRankPositions();
        $this->info("Rank positions updated successfully.");

        return $errors > 0 ? 1 : 0;
    }

    /**
     * Update rank positions for all users.
     */
    private function updateRankPositions(): void
    {
        DB::statement("
            UPDATE profile_scores ps1
            SET rank_position = (
                SELECT COUNT(*) + 1
                FROM profile_scores ps2
                WHERE ps2.total_score > ps1.total_score
            )
        ");
    }
}