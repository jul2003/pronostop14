<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneeTypeScoringProfile;
use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use App\Models\ScoringRuleTemplate;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private const JOURNEE_RULE_CODE_LABELS = [
        'home_win' => 'Résultat juste — victoire domicile / équipe 1',
        'away_win' => 'Résultat juste — victoire extérieur / équipe 2',
        'draw' => 'Résultat juste — match nul',
        'tries_exact' => 'Nombre d’essais exact',
        'tries_near' => 'Nombre d’essais à +/- 1',
        'bonus_correct' => 'Bonus pronostiqué juste',
        'bonus_wrong' => 'Bonus pronostiqué faux',
        'perfect_round' => 'Bonus journée parfaite',
    ];

    private const PRESEASON_RULE_CODE_LABELS = [
        'correct' => 'Réponse exacte',
    ];

    public function index()
    {
        $profiles = ScoringProfile::with('rules')
            ->where('category', 'journee')
            ->orderBy('position')
            ->get();

        $journeeMappings = JourneeTypeScoringProfile::with('profile')
            ->get()
            ->keyBy('journee_type');

        return view('admin.settings.index', compact(
            'profiles',
            'journeeMappings'
        ));
    }

    public function preseason()
    {
        $profiles = ScoringProfile::with('rules')
            ->where('category', 'preseason')
            ->orderBy('position')
            ->get();

        $preseasonTemplates = PreseasonPredictionTemplate::with('profile')
            ->orderBy('position')
            ->get();

        $preseasonBonusRules = PreseasonBonusRuleTemplate::with('questions')
            ->orderBy('position')
            ->get();

        return view('admin.settings.preseason', compact(
            'profiles',
            'preseasonTemplates',
            'preseasonBonusRules'
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'rules' => ['nullable', 'array'],
            'rules.*' => ['integer'],

            'journee_profiles' => ['nullable', 'array'],
            'journee_profiles.*' => ['nullable', 'exists:scoring_profiles,id'],

            'preseason' => ['nullable', 'array'],
            'preseason.*.label' => ['required', 'string', 'max:255'],
            'preseason.*.answer_type' => ['required', 'in:top14_club,prod2_club,season_club,free_text'],
            'preseason.*.scoring_profile_id' => ['required', 'exists:scoring_profiles,id'],
            'preseason.*.position' => ['nullable', 'integer'],
            'preseason.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['rules'] ?? [] as $ruleId => $points) {
            ScoringRuleTemplate::whereKey($ruleId)->update([
                'points' => $points,
            ]);
        }

        foreach ($data['journee_profiles'] ?? [] as $type => $profileId) {
            if ($profileId) {
                JourneeTypeScoringProfile::updateOrCreate(
                    ['journee_type' => $type],
                    ['scoring_profile_id' => $profileId]
                );
            }
        }

        foreach ($data['preseason'] ?? [] as $templateId => $templateData) {
            PreseasonPredictionTemplate::whereKey($templateId)->update([
                'label' => $templateData['label'],
                'answer_type' => $templateData['answer_type'],
                'scoring_profile_id' => $templateData['scoring_profile_id'],
                'position' => $templateData['position'] ?? 0,
                'is_active' => isset($templateData['is_active']),
            ]);
        }

        return back()->with('success', 'Paramètres mis à jour.');
    }

    public function storePreseasonTemplate(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'in:top14_club,prod2_club,season_club,free_text'],
            'scoring_profile_id' => ['required', 'exists:scoring_profiles,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PreseasonPredictionTemplate::create([
            'label' => $data['label'],
            'answer_type' => $data['answer_type'],
            'scoring_profile_id' => $data['scoring_profile_id'],
            'position' => (PreseasonPredictionTemplate::max('position') ?? 0) + 10,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Question avant-saison créée.');
    }

    public function destroyPreseasonTemplate(PreseasonPredictionTemplate $template)
    {
        $template->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function reorderPreseasonTemplates(Request $request)
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*' => ['integer', 'exists:preseason_prediction_templates,id'],
        ]);

        foreach ($data['templates'] as $index => $templateId) {
            PreseasonPredictionTemplate::whereKey($templateId)->update([
                'position' => ($index + 1) * 10,
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function storePreseasonBonusRuleTemplate(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'points' => ['required', 'integer', 'min:0'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', 'exists:preseason_prediction_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'stop_after_match' => ['nullable', 'boolean'],
        ]);

        $bonusRule = PreseasonBonusRuleTemplate::create([
            'label' => $data['label'],
            'points' => $data['points'],
            'position' => (PreseasonBonusRuleTemplate::max('position') ?? 0) + 10,
            'is_active' => isset($data['is_active']),
            'stop_after_match' => isset($data['stop_after_match']),
        ]);

        $bonusRule->questions()->sync($data['questions']);

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Bonus avant-saison créé.');
    }

    public function updatePreseasonBonusRuleTemplate(Request $request, PreseasonBonusRuleTemplate $bonusRule)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'points' => ['required', 'integer', 'min:0'],
            'position' => ['nullable', 'integer'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', 'exists:preseason_prediction_templates,id'],
            'is_active' => ['nullable', 'boolean'],
            'stop_after_match' => ['nullable', 'boolean'],
        ]);

        $bonusRule->update([
            'label' => $data['label'],
            'points' => $data['points'],
            'position' => $data['position'] ?? 0,
            'is_active' => isset($data['is_active']),
            'stop_after_match' => isset($data['stop_after_match']),
        ]);

        $bonusRule->questions()->sync($data['questions']);

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Bonus avant-saison mis à jour.');
    }

    public function destroyPreseasonBonusRuleTemplate(PreseasonBonusRuleTemplate $bonusRule)
    {
        $bonusRule->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function reorderPreseasonBonusRuleTemplates(Request $request)
    {
        $data = $request->validate([
            'bonus_rules' => ['required', 'array'],
            'bonus_rules.*' => ['integer', 'exists:preseason_bonus_rule_templates,id'],
        ]);

        foreach ($data['bonus_rules'] as $index => $bonusRuleId) {
            PreseasonBonusRuleTemplate::whereKey($bonusRuleId)->update([
                'position' => ($index + 1) * 10,
            ]);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function createScoringProfile(Request $request)
    {
        return view('admin.settings.scoring-profile-form', [
            'profile' => null,
            'returnTo' => $request->query('return_to'),
            'defaultCategory' => $request->query('category', 'journee'),
            'ruleCodeLabelsByCategory' => $this->ruleCodeLabelsByCategory(),
        ]);
    }

    public function storeScoringProfile(Request $request)
    {
        $category = $request->input('category', 'journee');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:scoring_profiles,code'],
            'category' => ['required', 'in:journee,preseason'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer'],
            'rules' => ['nullable', 'array'],
            'rules.*.code' => [
                'required',
                'string',
                Rule::in($this->allowedRuleCodesForCategory($category)),
                'distinct',
            ],
            'rules.*.label' => ['required', 'string', 'max:255'],
            'rules.*.points' => ['required', 'integer'],
            'rules.*.position' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'string'],
            'stop_on_wrong_result' => ['nullable', 'boolean'],
        ]);

        $profile = ScoringProfile::create([
            'code' => $data['code'],
            'category' => $data['category'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? 0,
            'stop_on_wrong_result' => $request->boolean('stop_on_wrong_result'),
        ]);

        foreach ($data['rules'] ?? [] as $rule) {
            $profile->rules()->create([
                'code' => $rule['code'],
                'label' => $rule['label'],
                'points' => $rule['points'],
                'position' => $rule['position'] ?? 0,
            ]);
        }

        $route = ($data['return_to'] ?? null) === 'preseason'
            ? 'admin.settings.preseason'
            : 'admin.settings.index';

        return redirect()
            ->route($route)
            ->with('success', 'Barème créé.');
    }

    public function editScoringProfile(Request $request, ScoringProfile $profile)
    {
        $profile->load([
            'rules' => function ($query) {
                $query->orderBy('position');
            },
        ]);

        return view('admin.settings.scoring-profile-form', [
            'profile' => $profile,
            'returnTo' => $request->query('return_to'),
            'defaultCategory' => $profile->category,
            'ruleCodeLabelsByCategory' => $this->ruleCodeLabelsByCategory(),
        ]);
    }

    public function updateScoringProfile(Request $request, ScoringProfile $profile)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer'],
            'rules' => ['nullable', 'array'],
            'rules.*.code' => [
                'required',
                'string',
                Rule::in($this->allowedRuleCodesForCategory($profile->category)),
                'distinct',
            ],
            'rules.*.label' => ['required', 'string', 'max:255'],
            'rules.*.points' => ['required', 'integer'],
            'rules.*.position' => ['nullable', 'integer'],
            'return_to' => ['nullable', 'string'],
            'stop_on_wrong_result' => ['nullable', 'boolean'],
        ]);

        $profile->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? 0,
            'stop_on_wrong_result' => $request->boolean('stop_on_wrong_result'),
        ]);

        $profile->rules()->delete();

        foreach ($data['rules'] ?? [] as $rule) {
            $profile->rules()->create([
                'code' => $rule['code'],
                'label' => $rule['label'],
                'points' => $rule['points'],
                'position' => $rule['position'] ?? 0,
            ]);
        }

        $route = ($data['return_to'] ?? null) === 'preseason'
            ? 'admin.settings.preseason'
            : 'admin.settings.index';

        return redirect()
            ->route($route)
            ->with('success', 'Barème mis à jour.');
    }

    private function allowedRuleCodesForCategory(string $category): array
    {
        return array_keys($this->ruleCodeLabelsForCategory($category));
    }

    private function ruleCodeLabelsForCategory(string $category): array
    {
        return match ($category) {
            'preseason' => self::PRESEASON_RULE_CODE_LABELS,
            default => self::JOURNEE_RULE_CODE_LABELS,
        };
    }

    private function ruleCodeLabelsByCategory(): array
    {
        return [
            'journee' => self::JOURNEE_RULE_CODE_LABELS,
            'preseason' => self::PRESEASON_RULE_CODE_LABELS,
        ];
    }
}
