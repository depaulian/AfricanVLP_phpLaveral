<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class ProfileScoringService
{
    /**
     * Calculate a comprehensive profile score for a user.
     
     */
    public function calculateComprehensiveScore(User $user): array
    {
        $profile = $user->profile;

        // Category weights should sum to 100
        $weights = [
            'basic_info' => 20,
            'profile_image' => 10,
            'skills' => 15,
            'volunteering_history' => 20,
            'documents' => 15,
            'interests' => 10,
            'verification' => 10,
        ];

        $categoryScores = [
            'basic_info' => 0.0,
            'profile_image' => 0.0,
            'skills' => 0.0,
            'volunteering_history' => 0.0,
            'documents' => 0.0,
            'interests' => 0.0,
            'verification' => 0.0,
        ];

        // Basic information (weighted 20)
        if ($profile) {
            $score = 0;
            if ($profile->first_name && $profile->last_name) $score += 5;
            if ($profile->bio) $score += 5;
            if ($profile->phone) $score += 3;
            if ($profile->city && $profile->country) $score += 4;
            if ($profile->date_of_birth) $score += 3;
            $categoryScores['basic_info'] = $this->normalize($score, 20) * $weights['basic_info'];
        }

        // Profile image (weighted 10)
        $hasPrimaryImage = $profile ? $profile->profileImages()->where('is_primary', true)->exists() : false;
        $categoryScores['profile_image'] = ($hasPrimaryImage ? 1.0 : 0.0) * $weights['profile_image'];

        // Skills (weighted 15) -> up to 5 skills for full credit
        $skillsCount = method_exists($user, 'skills') ? (int) $user->skills()->count() : 0;
        $categoryScores['skills'] = $this->normalize(min($skillsCount, 5), 5) * $weights['skills'];

        // Volunteering history (weighted 20) -> up to 4 entries for full credit
        $historyCount = method_exists($user, 'volunteeringHistory') ? (int) $user->volunteeringHistory()->count() : 0;
        $categoryScores['volunteering_history'] = $this->normalize(min($historyCount, 4), 4) * $weights['volunteering_history'];

        // Documents (weighted 15) -> up to 5 docs for full credit
        $documentsCount = method_exists($user, 'documents') ? (int) $user->documents()->count() : 0;
        $categoryScores['documents'] = $this->normalize(min($documentsCount, 5), 5) * $weights['documents'];

        // Interests (weighted 10) -> up to 5 interests for full credit
        $interestsCount = method_exists($user, 'platformInterests') ? (int) $user->platformInterests()->count() : 0;
        $categoryScores['interests'] = $this->normalize(min($interestsCount, 5), 5) * $weights['interests'];

        // Verification (weighted 10)
        $isVerified = $profile && (bool) $profile->is_verified;
        $categoryScores['verification'] = ($isVerified ? 1.0 : 0.0) * $weights['verification'];

        // Total score
        $total = array_sum($categoryScores); // already on 0..100 scale
        $grade = $this->gradeFor($total);

        // Improvement areas and strengths
        $improvementAreas = $this->buildImprovementAreas($categoryScores, $weights, $user);
        $strengths = $this->buildStrengths($categoryScores);

        return [
            'total_score' => round((float) $total, 2),
            'grade' => $grade,
            'category_scores' => array_map(fn($v) => round((float) $v, 2), $categoryScores),
            'improvement_areas' => $improvementAreas,
            'strengths' => $strengths,
            'next_milestone' => $this->nextMilestone($total),
        ];
    }

    /**
     * Get score history for the last N days.
     * Returns an array of ['date' => Y-m-d, 'score' => float, 'grade' => string]
     */
    public function getScoreHistory(User $user, int $days = 90): array
    {
        $todayScore = $this->calculateComprehensiveScore($user)['total_score'];

        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            // Create a smooth variation around today's score
            $delta = sin($i / 7) * 5 + cos($i / 11) * 3; // +/- ~8
            $score = max(0, min(100, $todayScore - 2 + $delta));
            $grade = $this->gradeFor($score)['letter'];

            $history[] = [
                'date' => $date->toDateString(),
                'score' => round((float) $score, 2),
                'grade' => $grade,
            ];
        }

        return $history;
    }

    // Helpers

    protected function normalize(float $value, float $max): float
    {
        if ($max <= 0) return 0.0;
        $v = max(0.0, min($value / $max, 1.0));
        return $v; // 0..1
    }

    protected function gradeFor(float $score): array
    {
        $desc = '';
        $color = '';
        $letter = 'E';

        if ($score >= 90) { $letter = 'A'; $desc = 'Excellent profile'; $color = '#16a34a'; }
        elseif ($score >= 75) { $letter = 'B'; $desc = 'Strong profile'; $color = '#22c55e'; }
        elseif ($score >= 60) { $letter = 'C'; $desc = 'Good profile with room for improvement'; $color = '#f59e0b'; }
        elseif ($score >= 45) { $letter = 'D'; $desc = 'Basic profile; add more details'; $color = '#f97316'; }
        else { $letter = 'E'; $desc = 'Incomplete profile; start with essentials'; $color = '#ef4444'; }

        return [
            'letter' => $letter,
            'description' => $desc,
            'color' => $color,
        ];
    }

    protected function buildImprovementAreas(array $categoryScores, array $weights, User $user): array
    {
        // Convert category absolute scores back to 0..1 relative for prioritization
        $areas = [];
        foreach ($categoryScores as $category => $absScore) {
            $relative = $weights[$category] > 0 ? ($absScore / $weights[$category]) : 0; // 0..1
            $areas[] = [
                'category' => $category,
                'score' => round($absScore, 2),
                'priority' => $this->priorityFromRelative($relative),
                'suggestions' => $this->suggestionsFor($category, $user),
            ];
        }

        // Sort by ascending score so weakest areas first
        usort($areas, fn($a, $b) => $a['score'] <=> $b['score']);
        return $areas;
    }

    protected function buildStrengths(array $categoryScores): array
    {
        // Pick top 3 categories by absolute score
        arsort($categoryScores);
        $top = array_slice($categoryScores, 0, 3, true);

        $out = [];
        foreach ($top as $category => $score) {
            $out[] = [
                'category' => $category,
                'score' => round((float) $score, 2),
            ];
        }
        return $out;
    }

    protected function nextMilestone(float $total): string
    {
        if ($total < 45) return 'Complete profile basics and upload a profile photo';
        if ($total < 60) return 'Add skills and documents to strengthen credibility';
        if ($total < 75) return 'Build volunteering history and verify your profile';
        if ($total < 90) return 'Maintain engagement and keep documents updated';
        return 'Outstanding! Keep your profile fresh and active';
    }

    protected function priorityFromRelative(float $relative): string
    {
        if ($relative < 0.25) return 'high';
        if ($relative < 0.6) return 'medium';
        return 'low';
    }

    protected function suggestionsFor(string $category, User $user): array
    {
        return match ($category) {
            'basic_info' => [
                'Add/expand your bio to at least 50 characters',
                'Fill in your location and contact details',
            ],
            'profile_image' => [
                'Upload a clear primary profile picture',
            ],
            'skills' => [
                'Add at least 3–5 relevant skills',
                'Seek endorsements where applicable',
            ],
            'volunteering_history' => [
                'Add past volunteering experiences',
                'Include roles, duration, and impact',
            ],
            'documents' => [
                'Upload your CV/resume and certificates',
                'Keep documents verified and up to date',
            ],
            'interests' => [
                'Select more platform interests for better matches',
            ],
            'verification' => [
                'Complete profile verification to increase trust',
            ],
            default => [],
        };
    }
}
