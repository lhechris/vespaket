<div class="flex flex-col gap-4">
    <div class="flex justify-end">
        <button wire:click="create" class="bg-black text-yellow-400 px-4 py-2 rounded-lg font-semibold">
            {{ __('actions.new')}}
        </button>
    </div>

    <livewire:gymnase.form :place="$editingPlace" :key="'gymnase-form-' . ($editingPlace?->id ?? 'new')" />

    <table class="w-full text-left rtl:text-right text-body text-yellow-400 ">
        <thead  class="bg-black border-b border-default">
            <tr>
                <th>{{ __('vespaket.city') }} </th>
                <th>{{ __('vespaket.label') }}</th>
                <th class="hidden md:table-cell">{{ __('vespaket.address') }}</th>
                <th class="hidden md:table-cell">{{ __('vespaket.postal_code') }}</th>
                <th class="hidden md:table-cell">{{ __('vespaket.latlong') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($places as $place)
            <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                <td>{{ $place->city }}</td>
                <td>{{ $place->label }}</td>
                <td class="hidden md:table-cell">{{ $place->address }}</td>
                <td class="hidden md:table-cell">{{ $place->cp }}</td>
                <td class="hidden md:table-cell">{{ $place->lat }} / {{ $place->lng }}</td>
                <td>
                        <button wire:click="edit({{ $place->id }})" class="text-blue-600">
                            📝​
                        </button>

                        <button wire:click="delete({{ $place->id }})" class="text-red-600 ml-2">
                            ​🗑️​
                        </button>
                </td>
            </tr>
            @empty
                <tr>
                    <td colspan="6" class="py-4 text-center text-gray-400">Aucun gymnase.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($placesLastPage > 1)
        <div class="flex items-center justify-between gap-4 text-sm text-gray-700">
            <button
                wire:click="previousPlacesPage"
                type="button"
                @disabled($placesPage === 1)
                class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40"
            >
                {!! __('pagination.previous') !!}
            </button>
            <span>{{ $placesPage }} / {{ $placesLastPage }}</span>
            <button
                wire:click="nextPlacesPage"
                type="button"
                @disabled($placesPage === $placesLastPage)
                class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40"
            >
                {!! __('pagination.next') !!}
            </button>
        </div>
    @endif
</div>
