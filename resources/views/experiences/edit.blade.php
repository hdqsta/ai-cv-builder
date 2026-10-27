@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Work Experience</h2>

    <form action="{{ route('experiences.update', $experience) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Company -->
        <div>
            <label for="company" class="block text-sm font-medium text-gray-700">Company Name</label>
            <input type="text" name="company" id="company" value="{{ old('company', $experience->company) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Position -->
        <div>
            <label for="position" class="block text-sm font-medium text-gray-700">Job Title / Position</label>
            <input type="text" name="position" id="position" value="{{ old('position', $experience->position) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Dates -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $experience->start_date->format('Y-m-d')) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date (Leave blank if present)</label>
                <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $experience->end_date ? $experience->end_date->format('Y-m-d') : '') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        <!-- Bullet Points -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Key Responsibilities & Accomplishments</label>
            <div id="bullets-container" class="space-y-4">
                @foreach ($experience->bulletPoints as $index => $bullet)
                    <div class="flex gap-2 items-start">
                        <input type="hidden" name="bullets[{{ $index }}][id]" value="{{ $bullet->id }}">
                        <textarea name="bullets[{{ $index }}][content]" rows="2" required
                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $bullet->content }}</textarea>
                        <button type="button" onclick="this.parentNode.remove()"
                                class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
                    </div>
                @endforeach
            </div>
            <button type="button" id="add-bullet-btn"
                    class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                + Add Bullet Point
            </button>
        </div>

        <!-- Submit Buttons -->
        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('experiences.index') }}"
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

<script>
    let bulletIndex = {{ $experience->bulletPoints->count() }};
    document.getElementById('add-bullet-btn').addEventListener('click', function() {
        const container = document.getElementById('bullets-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start';
        div.innerHTML = `
            <textarea name="bullets[${bulletIndex}][content]" rows="2" required placeholder="Describe your achievement..."
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
        bulletIndex++;
    });
</script>
@endsection
