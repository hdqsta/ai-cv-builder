@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Add Project</h2>

    <form action="{{ route('projects.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Project Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Project Name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Tech Stack -->
        <div>
            <label for="tech_stack_input" class="block text-sm font-medium text-gray-700">Tech Stack (comma-separated)</label>
            <input type="text" name="tech_stack_input" id="tech_stack_input" value="{{ old('tech_stack_input') }}" placeholder="Laravel, Vue, Tailwind CSS"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Link -->
        <div>
            <label for="link" class="block text-sm font-medium text-gray-700">Project Link (optional)</label>
            <input type="url" name="link" id="link" value="{{ old('link') }}" placeholder="https://github.com/username/project"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Bullet Points -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Project Key Accomplishments</label>
            <div id="bullets-container" class="space-y-4">
                <!-- Initial Empty Bullet -->
                <div class="flex gap-2 items-start">
                    <textarea name="bullets[0][content]" rows="2" required placeholder="Describe what you built..."
                              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    <button type="button" onclick="this.parentNode.remove()"
                            class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
                </div>
            </div>
            <button type="button" id="add-bullet-btn"
                    class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                + Add Bullet Point
            </button>
        </div>

        <!-- Submit Buttons -->
        <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
            <a href="{{ route('projects.index') }}"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                Create Project
            </button>
        </div>
    </form>
</div>

<script>
    let bulletIndex = 1;
    document.getElementById('add-bullet-btn').addEventListener('click', function() {
        const container = document.getElementById('bullets-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start';
        div.innerHTML = `
            <textarea name="bullets[${bulletIndex}][content]" rows="2" required placeholder="Describe what you built..."
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
        bulletIndex++;
    });
</script>
@endsection
