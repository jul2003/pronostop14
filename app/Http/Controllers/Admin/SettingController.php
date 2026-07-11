<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneeTypeScoringProfile;
use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonCorrectionGroupTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use App\Models\ScoringRuleTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    private const JOURNEE_RULE_CODE_LABELS = [
        'home_win' => 'Resultat juste - victoire domicile / equipe 1',
        'away_win' => 'Resultat juste - victoire exterieur / equipe 2',
        'draw' => 'Resultat juste - match nul',
        'tries_exact' => 'Nombre d essais exact',
        'tries_near' => 'Nombre d essais a +/- 1',
        'bonus_correct' => 'Bonus pronostique juste',
        'bonus_wrong' => 'Bonus pronostique faux',
        'perfect_round' => 'Bonus journee parfaite',
    ];

    private const PRESEASON_RULE_CODE_LABELS = [
        'correct' => 'Reponse exacte',
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

        $preseasonTemplates = PreseasonPredictionTemplate::with([
            'profile',
            'correctionGroups',
        ])
            ->orderBy('position')
            ->get();

        $preseasonCorrectionGroups = PreseasonCorrectionGroupTemplate::with('questions')
            ->orderBy('position')
            ->get();

        $preseasonBonusRules = PreseasonBonusRuleTemplate::with('questions')
            ->orderBy('position')
            ->get();

        return view('admin.settings.preseason', compact(
            'profiles',
            'preseasonTemplates',
            'preseasonCorrectionGroups',
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

        return back()->with('success', 'Parametres mis a jour.');
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
            ->with('success', 'Question avant-saison creee.');
    }

    public function destroyPreseasonTemplate(PreseasonPredictionTemplate $template)
    {
        DB::transaction(function () use ($template) {
            DB::table('preseason_bonus_rule_template_questions')
                ->where('preseason_prediction_template_id', $template->id)
                ->delete();

            DB::table('preseason_correction_group_template_questions')
                ->where('preseason_prediction_template_id', $template->id)
                ->delete();

            $template->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Question avant-saison supprimee.');
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

    public function storePreseasonCorrectionGroupTemplate(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'unique:preseason_correction_group_templates,code'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', 'exists:preseason_prediction_templates,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $correctionGroup = PreseasonCorrectionGroupTemplate::create([
                'label' => $data['label'],
                'code' => $this->uniquePreseasonCorrectionGroupCode($data['code'] ?? null, $data['label']),
                'position' => (PreseasonCorrectionGroupTemplate::max('position') ?? 0) + 10,
                'is_active' => isset($data['is_active']),
            ]);

            $correctionGroup->questions()->sync($data['questions']);
        });

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Groupe de correction avant-saison cree.');
    }

    public function updatePreseasonCorrectionGroupTemplate(Request $request, PreseasonCorrectionGroupTemplate $correctionGroup)
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('preseason_correction_group_templates', 'code')->ignore($correctionGroup->id),
            ],
            'position' => ['nullable', 'integer'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*' => ['integer', 'exists:preseason_prediction_templates,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($correctionGroup, $data) {
            $correctionGroup->update([
                'label' => $data['label'],
                'code' => $this->uniquePreseasonCorrectionGroupCode(
                    $data['code'] ?? null,
                    $data['label'],
                    $correctionGroup->id
                ),
                'position' => $data['position'] ?? 0,
                'is_active' => isset($data['is_active']),
            ]);

            $correctionGroup->questions()->sync($data['questions']);
        });

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Groupe de correction avant-saison mis a jour.');
    }

    public function destroyPreseasonCorrectionGroupTemplate(PreseasonCorrectionGroupTemplate $correctionGroup)
    {
        DB::transaction(function () use ($correctionGroup) {
            $correctionGroup->questions()->detach();
            $correctionGroup->delete();
        });

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Groupe de correction avant-saison supprime.');
    }

    public function reorderPreseasonCorrectionGroupTemplates(Request $request)
    {
        $data = $request->validate([
            'correction_groups' => ['required', 'array'],
            'correction_groups.*' => ['integer', 'exists:preseason_correction_group_templates,id'],
        ]);

        foreach ($data['correction_groups'] as $index => $correctionGroupId) {
            PreseasonCorrectionGroupTemplate::whereKey($correctionGroupId)->update([
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
            ->with('success', 'Bonus avant-saison cree.');
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
            ->with('success', 'Bonus avant-saison mis a jour.');
    }

    public function destroyPreseasonBonusRuleTemplate(PreseasonBonusRuleTemplate $bonusRule)
    {
        $bonusRule->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
            ]);
        }

        return redirect()
            ->route('admin.settings.preseason')
            ->with('success', 'Bonus avant-saison supprime.');
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
            ->with('success', 'Bareme cree.');
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
            ->with('success', 'Bareme mis a jour.');
    }

    private function uniquePreseasonCorrectionGroupCode(?string $requestedCode, string $label, ?int $ignoreId = null): string
    {
        $baseCode = Str::slug($requestedCode ?: $label, '_') ?: 'groupe_correction';
        $code = $baseCode;
        $suffix = 2;

        while (PreseasonCorrectionGroupTemplate::query()
            ->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $code = $baseCode.'_'.$suffix;
            $suffix++;
        }

        return $code;
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
