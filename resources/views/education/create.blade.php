@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Add Education Entry</h2>

    <form action="{{ route('education.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Institution -->
        <div>
            <label for="institution" class="block text-sm font-medium text-gray-700">Institution Name</label>
            <input type="text" name="institution" id="institution" value="{{ old('institution') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Degree -->
        <div>
            <label for="degree" class="block text-sm font-medium text-gray-700">Degree / Study Program</label>
            <input type="text" name="degree" id="degree" value="{{ old('degree') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date (or expected date)</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Notes -->
        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes (GPA, awards, achievements, etc.)</label>
            <textarea name="notes" id="notes" rows="4" placeholder="GPA: 3.8/4.0&#10;Honors: Dean's List..."
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('notes') }}</textarea>
        </div>

        <!-- Submit Buttons -->
        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('education.index') }}"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Create Entry
            </button>
        </div>
    </form>
</div>
@endsection
