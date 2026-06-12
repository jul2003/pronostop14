<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function index()
    {
        $clubs = Club::orderBy('name')->get();

        return view('admin.clubs.index', compact('clubs'));
    }

    public function create()
    {
        return view('admin.clubs.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:clubs,name'],
            'short_name' => ['required', 'string', 'max:20', 'unique:clubs,short_name'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        Club::create([
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'slug' => Str::slug($data['name']),
        ]);

        if ($request->filled('redirect_to')) {
            return redirect()
                ->to($request->input('redirect_to'))
                ->with('success', 'Club créé.');
        }

        return redirect()
            ->route('admin.clubs.index')
            ->with('success', 'Club créé.');
    }

    public function edit(Club $club)
    {
        return view('admin.clubs.edit', compact('club'));
    }

    public function update(Request $request, Club $Club)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('Clubs', 'name')->ignore($Club->id),
            ],
            'short_name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('Clubs', 'short_name')->ignore($Club->id),
            ],
        ]);

        $Club->update([
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'slug' => Str::slug($data['name']),
        ]);

        return redirect()
            ->route('admin.clubs.index')
            ->with('success', 'Équipe modifiée.');
    }

    public function destroy(Club $Club)
    {
        $Club->delete();

        return redirect()
            ->route('admin.clubs.index')
            ->with('success', 'Équipe supprimée.');
    }
}
