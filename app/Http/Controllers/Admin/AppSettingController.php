<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function index()
    {
        $settings = AppSetting::orderBy('position')
            ->orderBy('label')
            ->get();

        return view('admin.app-settings.index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $settings = AppSetting::orderBy('position')
            ->orderBy('label')
            ->get();

        $rules = [];

        foreach ($settings as $setting) {
            $rules["settings.{$setting->id}"] = $this->validationRulesForSetting($setting);
        }

        $data = $request->validate($rules);

        foreach ($settings as $setting) {
            $value = $data['settings'][$setting->id] ?? null;

            $setting->update([
                'value' => $this->normalizeValue($setting, $value),
            ]);
        }

        return redirect()
            ->route('admin.app-settings.index')
            ->with('success', 'Paramètres de l’application enregistrés.');
    }

    private function validationRulesForSetting(AppSetting $setting): array
    {
        return match ($setting->type) {
            'integer' => ['required', 'integer', 'min:1'],
            'boolean' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
            'datetime' => ['nullable', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            default => ['nullable', 'string'],
        };
    }

    private function normalizeValue(AppSetting $setting, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($setting->type) {
            'integer' => (string) max(1, (int) $value),
            'boolean' => $value ? '1' : '0',
            'date' => Carbon::parse((string) $value)->format('Y-m-d'),
            'datetime' => Carbon::parse((string) $value)->format('Y-m-d H:i:s'),
            'time' => Carbon::createFromFormat('H:i', (string) $value)->format('H:i'),
            'color' => strtoupper((string) $value),
            default => (string) $value,
        };
    }
}
