<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with('bulletPoints')->get();
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tech_stack_input' => 'nullable|string',
            'link' => 'nullable|string|max:255',
            'bullets' => 'nullable|array',
            'bullets.*.content' => 'required|string',
        ]);

        $techStackInput = $request->input('tech_stack_input', '');
        $techStack = empty($techStackInput) ? [] : array_values(array_filter(array_map('trim', explode(',', $techStackInput))));

        $project = Project::create([
            'name' => $validated['name'],
            'tech_stack' => $techStack,
            'link' => $validated['link'] ?? null,
        ]);

        if ($request->has('bullets')) {
            foreach ($request->input('bullets') as $index => $bulletData) {
                if (!empty($bulletData['content'])) {
                    $project->bulletPoints()->create([
                        'content' => $bulletData['content'],
                        'order' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        $project->load('bulletPoints');
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tech_stack_input' => 'nullable|string',
            'link' => 'nullable|string|max:255',
            'bullets' => 'nullable|array',
            'bullets.*.id' => 'nullable|integer',
            'bullets.*.content' => 'required|string',
        ]);

        $techStackInput = $request->input('tech_stack_input', '');
        $techStack = empty($techStackInput) ? [] : array_values(array_filter(array_map('trim', explode(',', $techStackInput))));

        $project->update([
            'name' => $validated['name'],
            'tech_stack' => $techStack,
            'link' => $validated['link'] ?? null,
        ]);

        $submittedBulletIds = [];
        if ($request->has('bullets')) {
            foreach ($request->input('bullets') as $index => $bulletData) {
                if (empty($bulletData['content'])) {
                    continue;
                }

                if (!empty($bulletData['id'])) {
                    $bullet = $project->bulletPoints()->find($bulletData['id']);
                    if ($bullet) {
                        $bullet->update([
                            'content' => $bulletData['content'],
                            'order' => $index,
                        ]);
                        $submittedBulletIds[] = $bullet->id;
                    }
                } else {
                    $newBullet = $project->bulletPoints()->create([
                        'content' => $bulletData['content'],
                        'order' => $index,
                    ]);
                    $submittedBulletIds[] = $newBullet->id;
                }
            }
        }

        $project->bulletPoints()->whereNotIn('id', $submittedBulletIds)->delete();

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
