@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Certification</h2>

    <form action="{{ route('certifications.update', $certification) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Certification Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $certification->name) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Issuer -->
        <div>
            <label for="issuer" class="block text-sm font-medium text-gray-700">Issuing Organization</label>
            <input type="text" name="issuer" id="issuer" value="{{ old('issuer', $certification->issuer) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Year -->
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700">Year Earned</label>
            <input type="text" name="year" id="year" value="{{ old('year', $certification->year) }}" required maxlength="4" pattern="\d{4}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Submit Buttons -->
        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('certifications.index') }}"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
