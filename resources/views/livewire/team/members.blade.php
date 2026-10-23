<div>

    <div class="flex gap-4">
        <a href="{{ route('team.owners', ['team' => $team->id ]) }}" wire:navigate>
            <x-button>⚙️ {{ __('vespaket.manage_admin') }}</x-button>
        </a>

        <a href="{{ route('team.members', ['team' => $team->id ]) }}" wire:navigate>
            <x-button>⚙️ {{ __('vespaket.manage_members') }}</x-button>
        </a>

    </div>
    <!-- COACHS -->
    <div>
        <h2 class="text-lg font-semibold mb-2">{{ __('vespaket.coaches') }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

            @forelse($team->coaches as $coach)
                <x-card title="{{ $coach->prenom }}" tag=" {{ $coach->licence ?? '.....' }}" />
            @empty
                <p class="text-gray-500">{{ __('vespaket.no_coach') }}</p>
            @endforelse

        </div>
    </div>


    <div class="flex gap-4 mb-2 mt-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('Admin') }}</h2>
        @forelse($team->owners as $owner)
        <p class="text-gray-500 p-1 ">{{ $owner->firstname }} </p>
        @empty
            <p class="text-gray-500">{{ __('vespaket.no_admin') }}</p>
        @endforelse

    </div>


    <div class="flex gap-4 mb-2 mt-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.players') }}</h2>
    </div>

    <div class="flex gap-4">
        <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400">
            <thead class="bg-black border-b border-default">
                <tr>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('vespaket.first_name') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('vespaket.licence') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('vespaket.number') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('vespaket.matchs') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('vespaket.trainings') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $player)
                <tr class="odd:bg-gray-800 even:bg-gray-900 border-b border-default">
                    @if ($editingMemberId === $player->id)
                        <td class="px-2 py-4">
                            <input type="text" wire:model="editingMember.prenom" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                            @error('editingMember.prenom') <span class="text-red-400">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-2 py-4">
                            <input type="text" wire:model="editingMember.licence" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                            @error('editingMember.licence') <span class="text-red-400">{{ $message }}</span> @enderror
                        </td>
                        <td class="px-2 py-4">
                            <input type="text" wire:model="editingMember.numero" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                            @error('editingMember.numero') <span class="text-red-400">{{ $message }}</span> @enderror
                        </td>
                    @else
                        <td class="px-2 py-4">{{ $player->prenom }}</td>
                        <td class="px-2 py-4">{{ $player->licence ?? '.....' }}</td>
                        <td class="px-2 py-4">{{ $player->numero }}</td>
                    @endif
                    <td class="px-2 py-4">{{ $player->games_count }}</td>
                    <td class="px-2 py-4">{{ $player->trainings_count }}</td>
                    <td class="px-2 py-4">
                        @if ($editingMemberId === $player->id)
                            <button wire:click="saveMember" type="button" class="mr-2 text-sm text-green-400 hover:underline">{{ __('actions.save') }}</button>
                            <button wire:click="cancelEdit" type="button" class="text-sm text-gray-300 hover:underline">{{ __('actions.cancel') }}</button>
                        @else
                            <button wire:click="editMember({{ $player->id }})" type="button" class="text-sm text-white hover:underline">{{ __('actions.edit') }}</button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="text-gray-500">{{ __('vespaket.no_player') }}</tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
