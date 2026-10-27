@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">Academic Background</h2>
        <a href="{{ route('education.create') }}"
           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            + Add Education
        </a>
    </div>

    @if ($educations->isEmpty())
        <div class="bg-white p-6 rounded-lg border border-gray-200 text-center text-gray-500">
            No education history added yet.
        </div>
    @else
        <div class="space-y-6">
            @foreach ($educations as $education)
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $education->degree }}</h3>
                            <p class="text-sm font-medium text-indigo-600">{{ $education->institution }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $education->start_date->format('M Y') }} -
                                {{ $education->end_date ? $education->end_date->format('M Y') : 'Present' }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('education.edit', $education) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('education.destroy', $education) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this education entry?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                    @if ($education->notes)
                        <div class="mt-4 border-t border-gray-100 pt-4">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Additional Notes</h4>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $education->notes }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
