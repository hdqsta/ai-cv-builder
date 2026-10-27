@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">Work Experience</h2>
        <a href="{{ route('experiences.create') }}"
           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            + Add Experience
        </a>
    </div>

    @if ($experiences->isEmpty())
        <div class="bg-white p-6 rounded-lg border border-gray-200 text-center text-gray-500">
            No work experiences added yet.
        </div>
    @else
        <div class="space-y-6">
            @foreach ($experiences as $experience)
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $experience->position }}</h3>
                            <p class="text-sm font-medium text-indigo-600">{{ $experience->company }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $experience->start_date->format('M Y') }} -
                                {{ $experience->end_date ? $experience->end_date->format('M Y') : 'Present' }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('experiences.edit', $experience) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('experiences.destroy', $experience) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this experience?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($experience->bulletPoints->isNotEmpty())
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Key Accomplishments</h4>
                            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-600">
                                @foreach ($experience->bulletPoints as $bullet)
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
