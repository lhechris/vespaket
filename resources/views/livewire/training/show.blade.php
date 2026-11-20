<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center">

        <div>
            @if($editingTraining)
                <div class="space-y-3 bg-white p-4 rounded-xl shadow mb-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('Title') }}</label>
                        <input type="text" wire:model="trainingTitle" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('Date') }}</label>
                        <input type="date" wire:model="trainingDate" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('Location') }}</label>
                        <input type="text" wire:model="trainingLocation" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="updateTraining()" class="bg-green-600 text-white px-3 py-2 rounded font-semibold hover:bg-green-700">
                            {{ __('Save') }}
                        </button>
                        <button wire:click="toggleEditingTraining()" class="bg-gray-600 text-white px-3 py-2 rounded font-semibold hover:bg-gray-700">
                            {{ __('Cancel') }}
                        </button>
                    </div>
                </div>
            @else
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $training->titre }}
                </h1>

                <p class="text-gray-600">
                    {{ $training->formatdate() }} • {{ $training->location }}
                </p>
                <button wire:click="toggleEditingTraining()" class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    ✏️ {{ __('Edit') }}
                </button>
                <button wire:click="deleteTraining()" 
                    wire:confirm="{{ __("Are you sure you want to delete this training?") }}"
                    class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    🗑️​ {{ __('Delete') }}
                </button>
            @endif
        </div>

    </div>

    <!-- LISTE -->
    <div class="flex flex-col lg:flex-row gap-4">
        <div>
            <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg">
                <thead class="bg-black border-b border-default">
                    <tr>
                        <th scope="col" class="px-6 py-3 font-bold">{{ __('First name') }}</th>
                        <th scope="col" class="px-6 py-3 font-bold">{{ __('Present') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                        <td class="px-6 py-4">{{ $member->licensee->firstname }}</td>

                        <td class="px-6 py-4 text-black">
                            <button wire:click="setPresence({{ $member->id }}, 'yes')"
                                    wire:loading.attr="disabled"
                                    class="px-2 py-1 rounded {{ ($presence[$member->id] ?? null ) === 'yes' ? 'bg-green-500 text-white' : 'bg-gray-200' }}">
                                {{ __('Yes') }}
                            </button>

                            <button wire:click="setPresence({{ $member->id }}, 'no')"
                                    wire:loading.attr="disabled"
                                    class="px-2 py-1 rounded {{ ($presence[$member->id] ?? null) === 'no' ? 'bg-red-500 text-white' : 'bg-gray-200' }}">
                                {{ __('No') }}
                            </button>

                            <button wire:click="setPresence({{ $member->id }}, 'maybe')"
                                    wire:loading.attr="disabled"
                                    class="px-2 py-1 rounded {{ ($presence[$member->id] ?? null) === 'maybe' ? 'bg-yellow-500 text-white' : 'bg-gray-200' }}">
                                {{ __('Maybe') }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>


