<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneeTypeScoringProfile;
use App\Models\PreseasonPredictionTemplate;
use App\Models\ScoringProfile;
use Illuminate\Http\Request;
use App\Models\ScoringRuleTemplate;

class SettingController extends Controller
{
    public function index()
    {
        $profiles = ScoringProfile::with('rules')
            ->orderBy('position')
            ->get();

        $journeeMappings = JourneeTypeScoringProfile::with('profile')
            ->get()
            ->keyBy('journee_type');

        $preseasonTemplates = PreseasonPredictionTemplate::with('profile')
            ->orderBy('position')
            ->get();

        return view('admin.settings.index', [
            'profiles' => $profiles,
            'journeeMappings' => $journeeMappings,
            'preseasonTemplates' => $preseasonTemplates,
        ]);
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
            \App\Models\ScoringRuleTemplate::whereKey($ruleId)->update([
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

        return back()->with('success', 'Paramètres généraux mis à jour.');
    }

    public function createScoringProfile()
    {
        return view('admin.settings.scoring-profile-form', [
            'profile' => null,
        ]);
    }

    public function storeScoringProfile(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255', 'unique:scoring_profiles,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer'],
            'rules' => ['nullable', 'array'],
            'rules.*.code' => ['required', 'string', 'max:255'],
            'rules.*.label' => ['required', 'string', 'max:255'],
            'rules.*.points' => ['required', 'integer'],
            'rules.*.position' => ['nullable', 'integer'],
        ]);

        $profile = ScoringProfile::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? 0,
        ]);

        foreach ($data['rules'] ?? [] as $rule) {
            $profile->rules()->create([
                'code' => $rule['code'],
                'label' => $rule['label'],
                'points' => $rule['points'],
                'position' => $rule['position'] ?? 0,
            ]);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Barème créé.');
    }

    public function editScoringProfile(ScoringProfile $profile)
    {
        $profile->load('rules');

        return view('admin.settings.scoring-profile-form', [
            'profile' => $profile,
        ]);
    }

    public function updateScoringProfile(Request $request, ScoringProfile $profile)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'position' => ['nullable', 'integer'],
            'rules' => ['nullable', 'array'],
            'rules.*.code' => ['required', 'string', 'max:255'],
            'rules.*.label' => ['required', 'string', 'max:255'],
            'rules.*.points' => ['required', 'integer'],
            'rules.*.position' => ['nullable', 'integer'],
        ]);

        $profile->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?? 0,
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

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Barème mis à jour.');
    }
}
