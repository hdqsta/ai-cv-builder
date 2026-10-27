@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-900">Certifications</h2>
        <a href="{{ route('certifications.create') }}"
           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
            + Add Certification
        </a>
    </div>

    @if ($certifications->isEmpty())
        <div class="bg-white p-6 rounded-lg border border-gray-200 text-center text-gray-500 shadow-sm">
            No certifications added yet.
        </div>
    @else
        <div class="space-y-4">
            @foreach ($certifications as $certification)
                <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">{{ $certification->name }}</h3>
                            <p class="text-sm font-medium text-indigo-600">{{ $certification->issuer }}</p>
                            <p class="text-xs text-gray-500 mt-1">Year Earned: {{ $certification->year }}</p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('certifications.edit', $certification) }}"
                               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Edit
                            </a>
                            <form action="{{ route('certifications.destroy', $certification) }}" method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this certification?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
