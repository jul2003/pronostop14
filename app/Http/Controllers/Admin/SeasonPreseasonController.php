<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use App\Models\Season;
use App\Models\SeasonPreseasonBonusRule;
use App\Models\SeasonPreseasonQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SeasonPreseasonController extends Controller
{
    public function edit(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $season->load([
            'preseasonQuestions',
            'preseasonBonusRules.questions',
        ]);

        $scoringProfiles = ScoringProfile::where('category', 'preseason')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin.seasons.preseason', [
            'season' => $season,
            'questions' => $season->preseasonQuestions,
            'bonusRules' => $season->preseasonBonusRules,
            'scoringProfiles' => $scoringProfiles,
        ]);
    }

    public function updateQuestions(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'questions' => ['nullable', 'array'],
            'questions.*.label' => ['required', 'string', 'max:255'],
            'questions.*.answer_type' => ['required', 'string', 'max:50'],
            'questions.*.correction_group' => ['nullable', 'string', 'max:100'],
            'questions.*.correction_mode' => ['nullable', Rule::in(['unordered'])],
            'questions.*.scoring_profile_id' => ['nullable', 'integer', 'exists:scoring_profiles,id'],
            'questions.*.points' => ['required', 'integer'],
            'questions.*.position' => ['required', 'integer', 'min:0'],
            'questions.*.is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($season, $data) {
            foreach ($data['questions'] ?? [] as $questionId => $questionData) {
                $question = $season->preseasonQuestions()
                    ->whereKey($questionId)
                    ->first();

                if (! $question) {
                    continue;
                }

                $correctionGroup = $this->normalizeCorrectionGroup($questionData['correction_group'] ?? null);

                $question->update([
                    'label' => $questionData['label'],
                    'answer_type' => $questionData['answer_type'],
                    'correction_group' => $correctionGroup,
                    'correction_mode' => $this->correctionModeForGroup(
                        $correctionGroup,
                        $questionData['correction_mode'] ?? null
                    ),
                    'scoring_profile_id' => $questionData['scoring_profile_id'] ?? null,
                    'points' => $questionData['points'],
                    'position' => $questionData['position'],
                    'is_active' => (bool) ($questionData['is_active'] ?? false),
                ]);
            }
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Questions avant-saison enregistrées.');
    }

    public function storeQuestion(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'string', 'max:50'],
            'correction_group' => ['nullable', 'string', 'max:100'],
            'correction_mode' => ['nullable', Rule::in(['unordered'])],
            'scoring_profile_id' => ['nullable', 'integer', 'exists:scoring_profiles,id'],
            'points' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $correctionGroup = $this->normalizeCorrectionGroup($data['correction_group'] ?? null);

        $season->preseasonQuestions()->create([
            'source_template_id' => null,
            'scoring_profile_id' => $data['scoring_profile_id'] ?? null,
            'label' => $data['label'],
            'answer_type' => $data['answer_type'],
            'correction_group' => $correctionGroup,
            'correction_mode' => $this->correctionModeForGroup(
                $correctionGroup,
                $data['correction_mode'] ?? null
            ),
            'points' => $data['points'],
            'position' => $data['position'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Question avant-saison ajoutée.');
    }

    public function destroyQuestion(Season $season, SeasonPreseasonQuestion $question)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $question = $season->preseasonQuestions()
            ->whereKey($question->id)
            ->firstOrFail();

        DB::transaction(function () use ($question) {
            DB::table('season_preseason_bonus_rule_questions')
                ->where('season_preseason_question_id', $question->id)
                ->delete();

            $question->delete();
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Question avant-saison supprimée.');
    }

    public function updateBonusRules(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'bonus_rules' => ['nullable', 'array'],
            'bonus_rules.*.label' => ['required', 'string', 'max:255'],
            'bonus_rules.*.points' => ['required', 'integer'],
            'bonus_rules.*.position' => ['required', 'integer', 'min:0'],
            'bonus_rules.*.is_active' => ['nullable', 'boolean'],
            'bonus_rules.*.stop_after_match' => ['nullable', 'boolean'],
            'bonus_rules.*.question_ids' => ['nullable', 'array'],
            'bonus_rules.*.question_ids.*' => ['integer', 'exists:season_preseason_questions,id'],
        ]);

        DB::transaction(function () use ($season, $data) {
            $seasonQuestionIds = $season->preseasonQuestions()
                ->pluck('id')
                ->toArray();

            foreach ($data['bonus_rules'] ?? [] as $bonusRuleId => $bonusRuleData) {
                $bonusRule = $season->preseasonBonusRules()
                    ->whereKey($bonusRuleId)
                    ->first();

                if (! $bonusRule) {
                    continue;
                }

                $bonusRule->update([
                    'label' => $bonusRuleData['label'],
                    'points' => $bonusRuleData['points'],
                    'position' => $bonusRuleData['position'],
                    'is_active' => (bool) ($bonusRuleData['is_active'] ?? false),
                    'stop_after_match' => (bool) ($bonusRuleData['stop_after_match'] ?? false),
                ]);

                $questionIds = collect($bonusRuleData['question_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => in_array($id, $seasonQuestionIds, true))
                    ->values()
                    ->all();

                $bonusRule->questions()->sync($questionIds);
            }
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Bonus avant-saison enregistrés.');
    }

    public function storeBonusRule(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'points' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'stop_after_match' => ['nullable', 'boolean'],
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'exists:season_preseason_questions,id'],
        ]);

        DB::transaction(function () use ($request, $season, $data) {
            $bonusRule = $season->preseasonBonusRules()->create([
                'source_template_id' => null,
                'label' => $data['label'],
                'points' => $data['points'],
                'position' => $data['position'],
                'is_active' => $request->boolean('is_active'),
                'stop_after_match' => $request->boolean('stop_after_match'),
            ]);

            $seasonQuestionIds = $season->preseasonQuestions()
                ->pluck('id')
                ->toArray();

            $questionIds = collect($data['question_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $seasonQuestionIds, true))
                ->values()
                ->all();

            $bonusRule->questions()->sync($questionIds);
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Bonus avant-saison ajouté.');
    }

    public function destroyBonusRule(Season $season, SeasonPreseasonBonusRule $bonusRule)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $bonusRule = $season->preseasonBonusRules()
            ->whereKey($bonusRule->id)
            ->firstOrFail();

        DB::transaction(function () use ($bonusRule) {
            $bonusRule->questions()->detach();
            $bonusRule->delete();
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Bonus avant-saison supprimé.');
    }

    public function syncToGlobal(Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        DB::transaction(function () use ($season) {
            $season->load([
                'preseasonQuestions',
                'preseasonBonusRules.questions',
            ]);

            $seasonQuestionToGlobalTemplateIds = [];

            foreach ($season->preseasonQuestions()->orderBy('position')->get() as $question) {
                if ($question->scoring_profile_id) {
                    $profile = ScoringProfile::with('rules')
                        ->where('category', 'preseason')
                        ->whereKey($question->scoring_profile_id)
                        ->first();

                    if ($profile) {
                        $rule = $profile->rules()
                            ->where('code', 'correct')
                            ->first();

                        if ($rule) {
                            $rule->update([
                                'points' => $question->points,
                            ]);
                        }
                    }
                }

                if ($question->source_template_id) {
                    $template = PreseasonPredictionTemplate::find($question->source_template_id);
                } else {
                    $template = null;
                }

                if (! $template) {
                    $template = new PreseasonPredictionTemplate();
                }

                $template->fill([
                    'label' => $question->label,
                    'answer_type' => $question->answer_type,
                    'correction_group' => $question->correction_group,
                    'correction_mode' => $question->correction_mode,
                    'scoring_profile_id' => $question->scoring_profile_id,
                    'position' => $question->position,
                    'is_active' => $question->is_active,
                ]);

                $template->save();

                if (! $question->source_template_id) {
                    $question->update([
                        'source_template_id' => $template->id,
                    ]);
                }

                $seasonQuestionToGlobalTemplateIds[$question->id] = $template->id;
            }

            $globalTemplateIdsToKeep = array_values($seasonQuestionToGlobalTemplateIds);

            PreseasonPredictionTemplate::query()
                ->whereNotIn('id', $globalTemplateIdsToKeep ?: [0])
                ->delete();

            $seasonBonusToGlobalTemplateIds = [];

            foreach ($season->preseasonBonusRules()->with('questions')->orderBy('position')->get() as $bonusRule) {
                if ($bonusRule->source_template_id) {
                    $bonusTemplate = PreseasonBonusRuleTemplate::find($bonusRule->source_template_id);
                } else {
                    $bonusTemplate = null;
                }

                if (! $bonusTemplate) {
                    $bonusTemplate = new PreseasonBonusRuleTemplate();
                }

                $bonusTemplate->fill([
                    'label' => $bonusRule->label,
                    'points' => $bonusRule->points,
                    'position' => $bonusRule->position,
                    'is_active' => $bonusRule->is_active,
                    'stop_after_match' => $bonusRule->stop_after_match,
                ]);

                $bonusTemplate->save();

                if (! $bonusRule->source_template_id) {
                    $bonusRule->update([
                        'source_template_id' => $bonusTemplate->id,
                    ]);
                }

                $globalQuestionIds = $bonusRule->questions
                    ->pluck('id')
                    ->map(fn ($seasonQuestionId) => $seasonQuestionToGlobalTemplateIds[$seasonQuestionId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $bonusTemplate->questions()->sync($globalQuestionIds);

                $seasonBonusToGlobalTemplateIds[] = $bonusTemplate->id;
            }

            PreseasonBonusRuleTemplate::query()
                ->whereNotIn('id', $seasonBonusToGlobalTemplateIds ?: [0])
                ->delete();
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'La configuration avant-saison de cette saison a été appliquée aux paramètres globaux.');
    }

    private function lockedRedirect(Season $season)
    {
        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('error', 'Cette saison est verrouillée : la configuration avant-saison ne peut plus être modifiée.');
    }

    private function normalizeCorrectionGroup(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function correctionModeForGroup(?string $correctionGroup, ?string $correctionMode): ?string
    {
        if (! filled($correctionGroup)) {
            return null;
        }

        return $correctionMode ?: null;
    }

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
