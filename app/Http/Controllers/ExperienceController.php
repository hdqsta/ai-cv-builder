<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    public function index()
    {
        $experiences = Experience::with('bulletPoints')->orderBy('start_date', 'desc')->get();
        return view('experiences.index', compact('experiences'));
    }

    public function create()
    {
        return view('experiences.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bullets' => 'nullable|array',
            'bullets.*.content' => 'required|string',
        ]);

        $experience = Experience::create($validated);

        if ($request->has('bullets')) {
            foreach ($request->input('bullets') as $index => $bulletData) {
                if (!empty($bulletData['content'])) {
                    $experience->bulletPoints()->create([
                        'content' => $bulletData['content'],
                        'order' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('experiences.index')->with('success', 'Experience created successfully.');
    }

    public function edit(Experience $experience)
    {
        $experience->load('bulletPoints');
        return view('experiences.edit', compact('experience'));
    }

    public function update(Request $request, Experience $experience)
    {
        $validated = $request->validate([
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'bullets' => 'nullable|array',
            'bullets.*.id' => 'nullable|integer',
            'bullets.*.content' => 'required|string',
        ]);

        $experience->update($validated);

        $submittedBulletIds = [];
        if ($request->has('bullets')) {
            foreach ($request->input('bullets') as $index => $bulletData) {
                if (empty($bulletData['content'])) {
                    continue;
                }

                if (!empty($bulletData['id'])) {
                    $bullet = $experience->bulletPoints()->find($bulletData['id']);
                    if ($bullet) {
                        $bullet->update([
                            'content' => $bulletData['content'],
                            'order' => $index,
                        ]);
                        $submittedBulletIds[] = $bullet->id;
                    }
                } else {
                    $newBullet = $experience->bulletPoints()->create([
                        'content' => $bulletData['content'],
                        'order' => $index,
                    ]);
                    $submittedBulletIds[] = $newBullet->id;
                }
            }
        }

        $experience->bulletPoints()->whereNotIn('id', $submittedBulletIds)->delete();

        return redirect()->route('experiences.index')->with('success', 'Experience updated successfully.');
    }

    public function destroy(Experience $experience)
    {
        $experience->delete();
        return redirect()->route('experiences.index')->with('success', 'Experience deleted successfully.');
    }
}
