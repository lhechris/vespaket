<div class="flex flex-col gap-4">
    <div class="flex justify-end">
        <button wire:click="create" class="bg-black text-yellow-400 px-4 py-2 rounded-lg font-semibold">
            {{ __('actions.new')}}
        </button>
    </div>

    <livewire:gymnase.form :place="$editingPlace" :key="'gymnase-form-' . ($editingPlace?->id ?? 'new')" />

    <table class="w-full text-left rtl:text-right text-body text-yellow-400 ">
        <thead  class="bg-black border-b border-default">
            <th>{{ __('Name') }}</th>
            <th>{{ __('vespaket.address') }}</th>
            <th>{{ __('vespaket.latlong') }}</th>
            <th></th>
            
        </thead>
        <tbody>
            @foreach($places as $place) 
            <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                <td>{{ $place->name }}</td>
                <td>{{ $place->address }}</td>
                <td>{{ $place->lat }} / {{ $place->lng }}</td>
                <td>
                        <button wire:click="edit({{ $place->id }})" class="text-blue-600">
                            📝​
                        </button>

                        <button wire:click="delete({{ $place->id }})" class="text-red-600 ml-2">
                            ​🗑️​
                        </button>                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
