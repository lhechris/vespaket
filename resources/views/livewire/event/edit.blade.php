<div class="space-y-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center">

        <div>
            @if($editingEvent)
                <div class="space-y-3 bg-white p-4 rounded-xl shadow mb-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.title') }}</label>
                        <input type="text" wire:model="eventTitle" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.date') }}</label>
                        <input type="datetime-local" wire:model="eventDate" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.location') }}</label>
                        <input type="text" wire:model="eventLocation" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.description') }}</label>
                        <input type="text" wire:model="eventDescription" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="updateEvent()" class="bg-green-600 text-white px-3 py-2 rounded font-semibold hover:bg-green-700">
                            {{ __('actions.save') }}
                        </button>
                        <button wire:click="toggleEditingEvent()" class="bg-gray-600 text-white px-3 py-2 rounded font-semibold hover:bg-gray-700">
                            {{ __('actions.cancel') }}
                        </button>
                    </div>
                </div>
            @else
                <h1 class="text-2xl font-bold text-gray-900">
                    <span>{{ $event->titre }}</span>
                </h1>

                <p class="text-gray-600"> {{ $event->formatdate() }} </p>
                <p class="text-gray-900">
                    {{ __('vespaket.location') }} : {{ $event->location }}
                </p>
                <p>
                {{ $event->description }}
                </p>

                <button wire:click="toggleEditingEvent()" class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    ✏️ {{ __('actions.edit') }}
                </button>
                <button wire:click="deleteEvent()" 
                    wire:confirm="{{ __('vespaket.confirm_delete') }}"
                    class="mt-2 text-blue-600 hover:text-blue-800 font-semibold text-sm">
                    🗑️​ {{ __('actions.delete') }}
                </button>
                
            @endif
        </div>
    </div>

    <!-- LISTE -->
    <div class="flex flex-col lg:flex-row gap-4">
        <div>
            <h2>{{ __('vespaket.list_players') }}</h2>
            <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg">
                <thead class="bg-black border-b border-default">
                    <tr>
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.first_name') }}</th>
                        <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.availability') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                    <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                        <td class="px-2 sm:px-6 py-4">{{ $member->prenom }}</td>
                        <td class="px-2 sm:px-6 py-4 text-black">
                            <button wire:click="setAvailability({{ $member->id }}, 'yes')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'yes' ? 'bg-green-500 text-white' : 'bg-gray-200' }}">
                                ✔️​
                            </button>

                            <button wire:click="setAvailability({{ $member->id }}, 'no')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'no' ? 'bg-red-500 text-white' : 'bg-gray-200' }}">
                            ❌​
                            </button>

                            <button wire:click="setAvailability({{ $member->id }}, 'maybe')"
                                class="px-1 py-1 rounded {{ $member->pivot->availability === 'maybe' ? 'bg-yellow-400' : 'bg-gray-200' }}">
                                ❓
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>



