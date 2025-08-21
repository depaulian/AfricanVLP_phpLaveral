<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class SkillImportService
{
    /**
     * Import skills from LinkedIn profile
     */
    public function importFromLinkedIn(User $user, string $accessToken): array
    {
        try {
            // Get LinkedIn profile data
            $response = Http::withToken($accessToken)
                ->get('https://api.linkedin.com/v2/people/(id~)', [
                    'projection' => '(id,firstName,lastName,skills)'
                ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch LinkedIn profile data');
            }

            $profileData = $response->json();
            $importedSkills = [];

            if (isset($profileData['skills']['values'])) {
                foreach ($profileData['skills']['values'] as $skillData) {
                    $skill = $this->createSkillFromLinkedIn($user, $skillData);
                    if ($skill) {
                        $importedSkills[] = $skill;
                    }
                }
            }

            return [
                'success' => true,
                'imported_count' => count($importedSkills),
                'skills' => $importedSkills,
                'message' => 'Successfully imported ' . count($importedSkills) . ' skills from LinkedIn'
            ];

        } catch (\Exception $e) {
            Log::error('LinkedIn skill import failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'imported_count' => 0,
                'skills' => []
            ];
        }
    }

    /**
     * Import skills from resume/CV text
     */
    public function importFromResumeText(User $user, string $resumeText): array
    {
        $extractedSkills = $this->extractSkillsFromText($resumeText);
        $importedSkills = [];

        foreach ($extractedSkills as $skillData) {
            // Check if skill already exists
            $existingSkill = $user->skills()
                ->where('skill_name', $skillData['name'])
                ->first();

            if (!$existingSkill) {
                $skill = $user->skills()->create([
                    'skill_name' => $skillData['name'],
                    'proficiency_level' => $skillData['proficiency'] ?? 'intermediate',
                    'years_experience' => $skillData['experience'] ?? null,
                    'verified' => false
                ]);

                $importedSkills[] = $skill;
            }
        }

        return [
            'success' => true,
            'imported_count' => count($importedSkills),
            'skills' => $importedSkills,
            'extracted_count' => count($extractedSkills),
            'message' => 'Successfully imported ' . count($importedSkills) . ' new skills from resume'
        ];
    }

    /**
     * Import skills from structured data (JSON/CSV)
     */
    public function importFromStructuredData(User $user, array $skillsData): array
    {
        $importedSkills = [];
        $errors = [];

        foreach ($skillsData as $index => $skillData) {
            try {
                $skill = $this->createSkillFromStructuredData($user, $skillData);
                if ($skill) {
                    $importedSkills[] = $skill;
                }
            } catch (\Exception $e) {
                $errors[] = "Row {$index}: " . $e->getMessage();
            }
        }

        return [
            'success' => count($errors) === 0,
            'imported_count' => count($importedSkills),
            'skills' => $importedSkills,
            'errors' => $errors,
            'message' => count($errors) > 0 
                ? 'Imported with ' . count($errors) . ' errors'
                : 'Successfully imported all skills'
        ];
    }

    /**
     * Suggest skills based on user's profile and interests
     */
    public function suggestSkills(User $user, int $limit = 20): array
    {
        $suggestions = [];

        // Get skills from similar users
        $similarUserSkills = $this->getSkillsFromSimilarUsers($user);
        
        // Get skills common in user's interest areas
        $interestBasedSkills = $this->getSkillsFromInterestAreas($user);
        
        // Get trending skills in user's field
        $trendingSkills = $this->getTrendingSkillsInField($user);

        // Combine and score suggestions
        $allSuggestions = collect()
            ->merge($similarUserSkills)
            ->merge($interestBasedSkills)
            ->merge($trendingSkills)
            ->groupBy('skill_name')
            ->map(function ($group, $skillName) {
                return [
                    'skill_name' => $skillName,
                    'suggestion_score' => $group->sum('score'),
                    'reasons' => $group->pluck('reason')->unique()->values()->toArray(),
                    'proficiency_suggestion' => $this->suggestProficiencyLevel($group),
                    'demand_level' => $this->calculateDemandLevel($skillName)
                ];
            })
            ->sortByDesc('suggestion_score')
            ->take($limit)
            ->values()
            ->toArray();

        return $allSuggestions;
    }

    /**
     * Create skill from LinkedIn data
     */
    private function createSkillFromLinkedIn(User $user, array $skillData): ?UserSkill
    {
        $skillName = $skillData['skill']['name'] ?? null;
        
        if (!$skillName) {
            return null;
        }

        // Check if skill already exists
        $existingSkill = $user->skills()
            ->where('skill_name', $skillName)
            ->first();

        if ($existingSkill) {
            return null; // Skip existing skills
        }

        // Determine proficiency level from endorsements count
        $endorsementsCount = $skillData['numEndorsements'] ?? 0;
        $proficiencyLevel = $this->determineProficiencyFromEndorsements($endorsementsCount);

        return $user->skills()->create([
            'skill_name' => $skillName,
            'proficiency_level' => $proficiencyLevel,
            'verified' => false,
            'years_experience' => null // LinkedIn doesn't provide this directly
        ]);
    }

    /**
     * Create skill from structured data
     */
    private function createSkillFromStructuredData(User $user, array $skillData): ?UserSkill
    {
        $requiredFields = ['skill_name'];
        foreach ($requiredFields as $field) {
            if (empty($skillData[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        // Check if skill already exists
        $existingSkill = $user->skills()
            ->where('skill_name', $skillData['skill_name'])
            ->first();

        if ($existingSkill) {
            return null; // Skip existing skills
        }

        $proficiencyLevel = $skillData['proficiency_level'] ?? 'intermediate';
        if (!in_array($proficiencyLevel, ['beginner', 'intermediate', 'advanced', 'expert'])) {
            $proficiencyLevel = 'intermediate';
        }

        return $user->skills()->create([
            'skill_name' => $skillData['skill_name'],
            'proficiency_level' => $proficiencyLevel,
            'years_experience' => $skillData['years_experience'] ?? null,
            'verified' => false
        ]);
    }

    /**
     * Extract skills from text using pattern matching
     */
    private function extractSkillsFromText(string $text): array
    {
        // Common skill patterns and keywords
        $skillPatterns = [
            // Programming languages
            '/\b(PHP|JavaScript|Python|Java|C\+\+|C#|Ruby|Go|Swift|Kotlin|TypeScript)\b/i',
            // Frameworks
            '/\b(Laravel|React|Vue\.js|Angular|Django|Spring|Express|Flask)\b/i',
            // Databases
            '/\b(MySQL|PostgreSQL|MongoDB|Redis|SQLite|Oracle)\b/i',
            // Tools
            '/\b(Git|Docker|Kubernetes|AWS|Azure|Google Cloud)\b/i',
            // Soft skills
            '/\b(Leadership|Communication|Project Management|Team Work|Problem Solving)\b/i'
        ];

        $extractedSkills = [];
        $foundSkills = [];

        foreach ($skillPatterns as $pattern) {
            preg_match_all($pattern, $text, $matches);
            foreach ($matches[0] as $match) {
                $skillName = trim($match);
                if (!in_array(strtolower($skillName), $foundSkills)) {
                    $foundSkills[] = strtolower($skillName);
                    $extractedSkills[] = [
                        'name' => $skillName,
                        'proficiency' => $this->guessProficiencyFromContext($text, $skillName),
                        'experience' => $this->extractExperienceFromContext($text, $skillName)
                    ];
                }
            }
        }

        return $extractedSkills;
    }

    /**
     * Get skills from users with similar profiles
     */
    private function getSkillsFromSimilarUsers(User $user): Collection
    {
        $userInterests = $user->volunteeringInterests()->pluck('category_id');
        
        if ($userInterests->isEmpty()) {
            return collect();
        }

        $similarUsers = User::where('id', '!=', $user->id)
            ->whereHas('volunteeringInterests', function ($query) use ($userInterests) {
                $query->whereIn('category_id', $userInterests);
            })
            ->limit(50)
            ->get();

        $userSkillNames = $user->skills()->pluck('skill_name')->toArray();
        $suggestions = collect();

        foreach ($similarUsers as $similarUser) {
            $skills = $similarUser->skills()
                ->whereNotIn('skill_name', $userSkillNames)
                ->get();

            foreach ($skills as $skill) {
                $suggestions->push([
                    'skill_name' => $skill->skill_name,
                    'score' => 10,
                    'reason' => 'Common among users with similar interests'
                ]);
            }
        }

        return $suggestions;
    }

    /**
     * Get skills common in user's interest areas
     */
    private function getSkillsFromInterestAreas(User $user): Collection
    {
        $userInterests = $user->volunteeringInterests()->pluck('category_id');
        
        if ($userInterests->isEmpty()) {
            return collect();
        }

        // This would require a more sophisticated analysis of opportunity requirements
        // For now, return a simplified version
        return collect([
            ['skill_name' => 'Communication', 'score' => 15, 'reason' => 'Essential for volunteering'],
            ['skill_name' => 'Leadership', 'score' => 12, 'reason' => 'Valuable in volunteer roles'],
            ['skill_name' => 'Project Management', 'score' => 10, 'reason' => 'Useful for organizing activities']
        ]);
    }

    /**
     * Get trending skills in user's field
     */
    private function getTrendingSkillsInField(User $user): Collection
    {
        // This would integrate with external APIs or internal analytics
        // For now, return common trending skills
        return collect([
            ['skill_name' => 'Digital Marketing', 'score' => 8, 'reason' => 'High demand skill'],
            ['skill_name' => 'Data Analysis', 'score' => 7, 'reason' => 'Growing field'],
            ['skill_name' => 'Social Media Management', 'score' => 6, 'reason' => 'Popular skill']
        ]);
    }

    /**
     * Determine proficiency level from LinkedIn endorsements
     */
    private function determineProficiencyFromEndorsements(int $endorsementsCount): string
    {
        if ($endorsementsCount >= 20) return 'expert';
        if ($endorsementsCount >= 10) return 'advanced';
        if ($endorsementsCount >= 5) return 'intermediate';
        return 'beginner';
    }

    /**
     * Guess proficiency level from text context
     */
    private function guessProficiencyFromContext(string $text, string $skillName): string
    {
        $expertKeywords = ['expert', 'senior', 'lead', 'architect', 'specialist'];
        $advancedKeywords = ['advanced', 'experienced', 'proficient', 'skilled'];
        $intermediateKeywords = ['intermediate', 'working knowledge', 'familiar'];

        $skillContext = $this->extractContextAroundSkill($text, $skillName);

        foreach ($expertKeywords as $keyword) {
            if (stripos($skillContext, $keyword) !== false) {
                return 'expert';
            }
        }

        foreach ($advancedKeywords as $keyword) {
            if (stripos($skillContext, $keyword) !== false) {
                return 'advanced';
            }
        }

        foreach ($intermediateKeywords as $keyword) {
            if (stripos($skillContext, $keyword) !== false) {
                return 'intermediate';
            }
        }

        return 'intermediate'; // Default
    }

    /**
     * Extract experience years from context
     */
    private function extractExperienceFromContext(string $text, string $skillName): ?int
    {
        $skillContext = $this->extractContextAroundSkill($text, $skillName);
        
        // Look for patterns like "5 years", "3+ years", etc.
        if (preg_match('/(\d+)\+?\s*years?/i', $skillContext, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Extract context around a skill mention
     */
    private function extractContextAroundSkill(string $text, string $skillName): string
    {
        $position = stripos($text, $skillName);
        if ($position === false) {
            return '';
        }

        $start = max(0, $position - 100);
        $length = min(200, strlen($text) - $start);
        
        return substr($text, $start, $length);
    }

    /**
     * Suggest proficiency level based on grouped suggestions
     */
    private function suggestProficiencyLevel(Collection $group): string
    {
        // Simple logic - could be enhanced
        $avgScore = $group->avg('score');
        
        if ($avgScore >= 15) return 'advanced';
        if ($avgScore >= 10) return 'intermediate';
        return 'beginner';
    }

    /**
     * Calculate demand level for a skill
     */
    private function calculateDemandLevel(string $skillName): string
    {
        // This would integrate with job market APIs or internal opportunity data
        // For now, return a placeholder
        return 'medium';
    }
}