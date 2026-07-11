<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreseasonBonusRuleTemplate;
use App\Models\PreseasonCorrectionGroupTemplate;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use App\Models\Season;
use App\Models\SeasonPreseasonBonusRule;
use App\Models\SeasonPreseasonCorrectionGroup;
use App\Models\SeasonPreseasonQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeasonPreseasonController extends Controller
{
    public function edit(?Season $season = null)
    {
        $season = $this->resolveSeason($season);

        $season->load([
            'preseasonQuestions.scoringProfile',
            'preseasonQuestions.correctionGroups',
            'preseasonCorrectionGroups.questions',
            'preseasonBonusRules.questions',
        ]);

        $scoringProfiles = ScoringProfile::where('category', 'preseason')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view('admin.seasons.preseason', [
            'season' => $season,
            'questions' => $season->preseasonQuestions,
            'correctionGroups' => $season->preseasonCorrectionGroups,
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

                $question->update([
                    'label' => $questionData['label'],
                    'answer_type' => $questionData['answer_type'],
                    'scoring_profile_id' => $questionData['scoring_profile_id'] ?? null,
                    'points' => $questionData['points'],
                    'position' => $questionData['position'],
                    'is_active' => (bool) ($questionData['is_active'] ?? false),
                ]);
            }
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Questions avant-saison enregistrees.');
    }

    public function storeQuestion(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'string', 'max:50'],
            'scoring_profile_id' => ['nullable', 'integer', 'exists:scoring_profiles,id'],
            'points' => ['required', 'integer'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $season->preseasonQuestions()->create([
            'source_template_id' => null,
            'scoring_profile_id' => $data['scoring_profile_id'] ?? null,
            'label' => $data['label'],
            'answer_type' => $data['answer_type'],
            'points' => $data['points'],
            'position' => $data['position'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Question avant-saison ajoutee.');
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

            DB::table('season_preseason_correction_group_questions')
                ->where('season_preseason_question_id', $question->id)
                ->delete();

            $question->delete();
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Question avant-saison supprimee.');
    }

    public function updateCorrectionGroups(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'correction_groups' => ['nullable', 'array'],
            'correction_groups.*.label' => ['required', 'string', 'max:255'],
            'correction_groups.*.code' => ['nullable', 'string', 'max:100'],
            'correction_groups.*.position' => ['required', 'integer', 'min:0'],
            'correction_groups.*.is_active' => ['nullable', 'boolean'],
            'correction_groups.*.question_ids' => ['required', 'array', 'min:1'],
            'correction_groups.*.question_ids.*' => ['integer', 'exists:season_preseason_questions,id'],
        ]);

        DB::transaction(function () use ($season, $data) {
            $seasonQuestionIds = $season->preseasonQuestions()
                ->pluck('id')
                ->toArray();

            foreach ($data['correction_groups'] ?? [] as $correctionGroupId => $correctionGroupData) {
                $correctionGroup = $season->preseasonCorrectionGroups()
                    ->whereKey($correctionGroupId)
                    ->first();

                if (! $correctionGroup) {
                    continue;
                }

                $correctionGroup->update([
                    'label' => $correctionGroupData['label'],
                    'code' => $this->uniqueSeasonCorrectionGroupCode(
                        $season,
                        $correctionGroupData['code'] ?? null,
                        $correctionGroupData['label'],
                        $correctionGroup->id
                    ),
                    'position' => $correctionGroupData['position'],
                    'is_active' => (bool) ($correctionGroupData['is_active'] ?? false),
                ]);

                $questionIds = collect($correctionGroupData['question_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => in_array($id, $seasonQuestionIds, true))
                    ->values()
                    ->all();

                $correctionGroup->questions()->sync($questionIds);
            }
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Groupes de correction enregistres.');
    }

    public function storeCorrectionGroup(Request $request, Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'question_ids' => ['required', 'array', 'min:1'],
            'question_ids.*' => ['integer', 'exists:season_preseason_questions,id'],
        ]);

        DB::transaction(function () use ($request, $season, $data) {
            $correctionGroup = $season->preseasonCorrectionGroups()->create([
                'source_template_id' => null,
                'label' => $data['label'],
                'code' => $this->uniqueSeasonCorrectionGroupCode($season, $data['code'] ?? null, $data['label']),
                'position' => $data['position'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $seasonQuestionIds = $season->preseasonQuestions()
                ->pluck('id')
                ->toArray();

            $questionIds = collect($data['question_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => in_array($id, $seasonQuestionIds, true))
                ->values()
                ->all();

            $correctionGroup->questions()->sync($questionIds);
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Groupe de correction ajoute.');
    }

    public function destroyCorrectionGroup(Season $season, SeasonPreseasonCorrectionGroup $correctionGroup)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        $correctionGroup = $season->preseasonCorrectionGroups()
            ->whereKey($correctionGroup->id)
            ->firstOrFail();

        DB::transaction(function () use ($correctionGroup) {
            $correctionGroup->questions()->detach();
            $correctionGroup->delete();
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'Groupe de correction supprime.');
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
            ->with('success', 'Bonus avant-saison enregistres.');
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
            ->with('success', 'Bonus avant-saison ajoute.');
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
            ->with('success', 'Bonus avant-saison supprime.');
    }

    public function syncToGlobal(Season $season)
    {
        if ($season->is_locked) {
            return $this->lockedRedirect($season);
        }

        DB::transaction(function () use ($season) {
            $season->load([
                'preseasonQuestions',
                'preseasonCorrectionGroups.questions',
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

                $template = $question->source_template_id
                    ? PreseasonPredictionTemplate::find($question->source_template_id)
                    : null;

                if (! $template) {
                    $template = new PreseasonPredictionTemplate();
                }

                $template->fill([
                    'label' => $question->label,
                    'answer_type' => $question->answer_type,
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

            $obsoleteGlobalTemplateIds = PreseasonPredictionTemplate::query()
                ->whereNotIn('id', $globalTemplateIdsToKeep ?: [0])
                ->pluck('id');

            if ($obsoleteGlobalTemplateIds->isNotEmpty()) {
                DB::table('preseason_bonus_rule_template_questions')
                    ->whereIn('preseason_prediction_template_id', $obsoleteGlobalTemplateIds)
                    ->delete();

                DB::table('preseason_correction_group_template_questions')
                    ->whereIn('preseason_prediction_template_id', $obsoleteGlobalTemplateIds)
                    ->delete();

                PreseasonPredictionTemplate::query()
                    ->whereIn('id', $obsoleteGlobalTemplateIds)
                    ->delete();
            }

            $seasonCorrectionGroupToGlobalTemplateIds = [];

            foreach ($season->preseasonCorrectionGroups()->with('questions')->orderBy('position')->get() as $correctionGroup) {
                $correctionGroupTemplate = $correctionGroup->source_template_id
                    ? PreseasonCorrectionGroupTemplate::find($correctionGroup->source_template_id)
                    : null;

                if (! $correctionGroupTemplate) {
                    $correctionGroupTemplate = new PreseasonCorrectionGroupTemplate();
                }

                $correctionGroupTemplate->fill([
                    'label' => $correctionGroup->label,
                    'code' => $this->uniqueGlobalCorrectionGroupCode(
                        $correctionGroup->code,
                        $correctionGroup->label,
                        $correctionGroupTemplate->exists ? $correctionGroupTemplate->id : null
                    ),
                    'position' => $correctionGroup->position,
                    'is_active' => $correctionGroup->is_active,
                ]);
                $correctionGroupTemplate->save();

                if (! $correctionGroup->source_template_id) {
                    $correctionGroup->update([
                        'source_template_id' => $correctionGroupTemplate->id,
                    ]);
                }

                $globalQuestionIds = $correctionGroup->questions
                    ->pluck('id')
                    ->map(fn ($seasonQuestionId) => $seasonQuestionToGlobalTemplateIds[$seasonQuestionId] ?? null)
                    ->filter()
                    ->values()
                    ->all();

                $correctionGroupTemplate->questions()->sync($globalQuestionIds);
                $seasonCorrectionGroupToGlobalTemplateIds[] = $correctionGroupTemplate->id;
            }

            PreseasonCorrectionGroupTemplate::query()
                ->whereNotIn('id', $seasonCorrectionGroupToGlobalTemplateIds ?: [0])
                ->get()
                ->each(function (PreseasonCorrectionGroupTemplate $correctionGroupTemplate) {
                    $correctionGroupTemplate->questions()->detach();
                    $correctionGroupTemplate->delete();
                });

            $seasonBonusToGlobalTemplateIds = [];

            foreach ($season->preseasonBonusRules()->with('questions')->orderBy('position')->get() as $bonusRule) {
                $bonusTemplate = $bonusRule->source_template_id
                    ? PreseasonBonusRuleTemplate::find($bonusRule->source_template_id)
                    : null;

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
                ->get()
                ->each(function (PreseasonBonusRuleTemplate $bonusTemplate) {
                    $bonusTemplate->questions()->detach();
                    $bonusTemplate->delete();
                });
        });

        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('success', 'La configuration avant-saison de cette saison a ete appliquee aux parametres globaux.');
    }

    private function lockedRedirect(Season $season)
    {
        return redirect()
            ->route('admin.seasons.preseason.edit', $season)
            ->with('error', 'Cette saison est verrouillee : la configuration avant-saison ne peut plus etre modifiee.');
    }

    private function uniqueSeasonCorrectionGroupCode(Season $season, ?string $requestedCode, string $label, ?int $ignoreId = null): string
    {
        $baseCode = Str::slug($requestedCode ?: $label, '_') ?: 'groupe_correction';
        $code = $baseCode;
        $suffix = 2;

        while (SeasonPreseasonCorrectionGroup::query()
            ->where('season_id', $season->id)
            ->where('code', $code)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $code = $baseCode.'_'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function uniqueGlobalCorrectionGroupCode(?string $requestedCode, string $label, ?int $ignoreId = null): string
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

    private function resolveSeason(?Season $season = null): Season
    {
        if ($season) {
            return $season;
        }

        return Season::where('is_active', true)->firstOrFail();
    }
}
