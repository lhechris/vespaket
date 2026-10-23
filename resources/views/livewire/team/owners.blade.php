<div class="p-6 rounded shadow">

    <div class="flex justify-between items-center">

        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ __('vespaket.update_admin', ["team" => $team->name]) }}
            </h1>
        </div>

        <a href="{{ route('team.show', ['team' => $team->id ]) }}" 
           class="bg-black text-white px-4 py-2 rounded-xl font-semibold hover:bg-gray-800">
            {{ __('vespaket.back_to_team') }}
        </a>

    </div>

    <!-- RECHERCHE -->
    <input 
        type="text"
        wire:model.live="search"
        placeholder="{{ __('vespaket.search_user') }}"
        class="border p-2 w-full mb-4"
    >

    <!-- RESULTATS -->
    @foreach($results as $member)
        <div class="flex justify-between border p-2 mb-2">

            <span>
                {{ $member->firstname }} ({{ $member->name }})
            </span>

            <div class="space-x-2">
                <button wire:click="addMember({{ $member->id }})"
                    class="bg-green-500 text-white px-2 py-1">
                    {{ __('actions.add') }}
                </button>
            </div>

        </div>
    @endforeach

    <!-- LISTE ACTUELLE -->
    <h2 class="mt-6 font-bold">{{ __('vespaket.headcount') }}</h2>


    <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400">
        <thead class="bg-black border-b border-default">
            <tr>
                <th scope="col" class="px-6 py-3 font-bold">{{ __('vespaket.first_name') }}</th>
                <th scope="col" class="px-6 py-3 font-bold">{{ __('Name') }}</th>
                <th scope="col" class="px-6 py-3 font-bold"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($members as $member)
            <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                <td class="px-6 py-4">{{ $member->firstname }}</td>
                <td class="px-6 py-4">{{ $member->name }}</td>
                <td class="px-6 py-4">
                    <button wire:click="removeMember({{ $member->id }})" class="text-red-600">
                    {{ __('vespaket.remove') }}
                    </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>