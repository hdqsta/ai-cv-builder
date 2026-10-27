@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">Projects Portfolio</h2>
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            + Add Project
        </a>
    </div>

    @if ($projects->isEmpty())
        <div class="bg-white p-6 rounded-lg border border-gray-200 text-center text-gray-500">
            No projects added yet.
        </div>
    @else
        <div class="space-y-6">
            @foreach ($projects as $project)
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $project->name }}</h3>
                            @if ($project->link)
                                <a href="{{ $project->link }}" target="_blank" class="text-sm text-indigo-600 hover:underline mt-1 inline-block">
                                    {{ $project->link }}
                                </a>
                            @endif

                            @if (!empty($project->tech_stack))
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach ($project->tech_stack as $tech)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                            {{ $tech }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('projects.edit', $project) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('projects.destroy', $project) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this project?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($project->bulletPoints->isNotEmpty())
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Project Accomplishments</h4>
                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600">
                                @foreach ($project->bulletPoints as $bullet)
                                    <li>{{ $bullet->content }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
