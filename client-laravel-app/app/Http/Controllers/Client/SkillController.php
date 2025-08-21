<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\UserProfileService;
use App\Models\UserSkill;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Exception;

class SkillController extends Controller
{
    public function __construct(
        private UserProfileService $profileService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display user's skills.
     */
    public function index(): View
    {
        $user = Auth::user();
        $skills = $user->skills()
            ->orderBy('verified', 'desc')
            ->orderBy('proficiency_level', 'desc')
            ->orderBy('skill_name')
            ->get();

        $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];

        return view('client.skills.index', compact('skills', 'proficiencyLevels'));
    }

    /**
     * Show the form for creating a new skill.
     */
    public function create(): View
    {
        $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
        
        return view('client.skills.create', compact('proficiencyLevels'));
    }

    /**
     * Store a newly created skill.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $this->profileService->addSkill(Auth::user(), $request->validated());

            return redirect()->route('skills.index')
                ->with('success', 'Skill added successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to add skill. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified skill.
     */
    public function edit(UserSkill $skill): View
    {
        $this->authorize('update', $skill);

        $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
        
        return view('client.skills.edit', compact('skill', 'proficiencyLevels'));
    }

    /**
     * Update the specified skill.
     */
    public function update(Request $request, UserSkill $skill): RedirectResponse
    {
        $this->authorize('update', $skill);

        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $this->profileService->updateSkill($skill, $request->validated());

            return redirect()->route('skills.index')
                ->with('success', 'Skill updated successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update skill. Please try again.');
        }
    }

    /**
     * Remove the specified skill.
     */
    public function destroy(UserSkill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);

        try {
            $this->profileService->removeSkill($skill);

            return redirect()->route('skills.index')
                ->with('success', 'Skill removed successfully!');
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to remove skill. Please try again.');
        }
    }

    /**
     * Add skill via AJAX.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $skill = $this->profileService->addSkill(Auth::user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Skill added successfully!',
                'skill' => [
                    'id' => $skill->id,
                    'skill_name' => $skill->skill_name,
                    'proficiency_level' => $skill->proficiency_level,
                    'proficiency_label' => $skill->proficiency_label,
                    'years_experience' => $skill->years_experience,
                    'experience_description' => $skill->experience_description,
                    'verified' => $skill->verified,
                    'proficiency_color' => $skill->proficiency_level_color,
                    'proficiency_weight' => $skill->proficiency_weight,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add skill. Please try again.'
            ], 400);
        }
    }

    /**
     * Update skill via AJAX.
     */
    public function updateAjax(Request $request, UserSkill $skill): JsonResponse
    {
        $this->authorize('update', $skill);

        $request->validate([
            'skill_name' => 'required|string|max:255',
            'proficiency_level' => 'required|in:beginner,intermediate,advanced,expert',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $skill = $this->profileService->updateSkill($skill, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Skill updated successfully!',
                'skill' => [
                    'id' => $skill->id,
                    'skill_name' => $skill->skill_name,
                    'proficiency_level' => $skill->proficiency_level,
                    'proficiency_label' => $skill->proficiency_label,
                    'years_experience' => $skill->years_experience,
                    'experience_description' => $skill->experience_description,
                    'verified' => $skill->verified,
                    'proficiency_color' => $skill->proficiency_level_color,
                    'proficiency_weight' => $skill->proficiency_weight,
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update skill. Please try again.'
            ], 400);
        }
    }

    /**
     * Remove skill via AJAX.
     */
    public function remove(UserSkill $skill): JsonResponse
    {
        $this->authorize('delete', $skill);

        try {
            $this->profileService->removeSkill($skill);

            return response()->json([
                'success' => true,
                'message' => 'Skill removed successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove skill. Please try again.'
            ], 400);
        }
    }

    /**
     * Get skills list via AJAX.
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = $user->skills();

            // Filter by proficiency level
            if ($request->has('proficiency') && $request->proficiency !== 'all') {
                $query->where('proficiency_level', $request->proficiency);
            }

            // Filter by verification status
            if ($request->has('verified')) {
                $query->where('verified', $request->boolean('verified'));
            }

            // Search by skill name
            if ($request->has('search') && !empty($request->search)) {
                $query->where('skill_name', 'like', '%' . $request->search . '%');
            }

            $skills = $query->orderBy('verified', 'desc')
                ->orderBy('proficiency_level', 'desc')
                ->orderBy('skill_name')
                ->get();

            return response()->json([
                'success' => true,
                'skills' => $skills->map(function ($skill) {
                    return [
                        'id' => $skill->id,
                        'skill_name' => $skill->skill_name,
                        'proficiency_level' => $skill->proficiency_level,
                        'proficiency_label' => $skill->proficiency_label,
                        'years_experience' => $skill->years_experience,
                        'experience_description' => $skill->experience_description,
                        'verified' => $skill->verified,
                        'proficiency_color' => $skill->proficiency_level_color,
                        'proficiency_weight' => $skill->proficiency_weight,
                    ];
                })
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load skills'
            ], 500);
        }
    }

    /**
     * Get skill statistics via AJAX.
     */
    public function statistics(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total' => $user->skills()->count(),
                'verified' => $user->skills()->where('verified', true)->count(),
                'by_proficiency' => [],
                'average_experience' => $user->skills()->whereNotNull('years_experience')->avg('years_experience'),
            ];

            // Get counts by proficiency level
            $proficiencyLevels = ['beginner', 'intermediate', 'advanced', 'expert'];
            foreach ($proficiencyLevels as $level) {
                $stats['by_proficiency'][$level] = [
                    'label' => ucfirst($level),
                    'count' => $user->skills()->where('proficiency_level', $level)->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Get skill suggestions based on user's existing skills.
     */
    public function suggestions(): JsonResponse
    {
        try {
            $user = Auth::user();
            $existingSkills = $user->skills()->pluck('skill_name')->toArray();
            
            // This would typically come from a skills database or API
            // For now, we'll return some common related skills
            $suggestions = $this->getSkillSuggestions($existingSkills);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load suggestions'
            ], 500);
        }
    }

    /**
     * Get skill suggestions based on existing skills.
     */
    private function getSkillSuggestions(array $existingSkills): array
    {
        // This is a simplified implementation
        // In a real application, you might use ML or a skills database
        $skillCategories = [
            'programming' => ['PHP', 'JavaScript', 'Python', 'Java', 'C#', 'Ruby', 'Go'],
            'web_development' => ['HTML', 'CSS', 'React', 'Vue.js', 'Angular', 'Laravel', 'Django'],
            'database' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'SQLite'],
            'devops' => ['Docker', 'Kubernetes', 'AWS', 'Azure', 'Jenkins', 'Git'],
            'design' => ['Photoshop', 'Illustrator', 'Figma', 'Sketch', 'InDesign'],
            'project_management' => ['Agile', 'Scrum', 'Kanban', 'JIRA', 'Trello'],
        ];

        $suggestions = [];
        
        // Find related skills based on existing ones
        foreach ($skillCategories as $category => $skills) {
            $hasSkillInCategory = !empty(array_intersect($existingSkills, $skills));
            if ($hasSkillInCategory) {
                $relatedSkills = array_diff($skills, $existingSkills);
                $suggestions = array_merge($suggestions, array_slice($relatedSkills, 0, 3));
            }
        }

        return array_unique($suggestions);
    }
}