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


        <div class="flex gap-4">
            <table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400">
                <tbody>
                    @forelse($team->coaches as $coach)
                    <tr class="odd:bg-gray-800 even:bg-gray-900 border-b border-default">
                        <td class="px-2 py-4">{{ $coach->licensee->firstname }}</td>
                        <td class="px-2 py-4">{{ $coach->licensee->licence ?? '.....' }}</td>
                    </tr>
                    @empty
                    <tr class="text-gray-500">{{ __('vespaket.no_coach') }}</tr>
                    @endforelse
                </tbody>
            </table>
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

    <x-table.wrapper>
        <x-table.thead>
            <tr>
                <x-table.th>{{ __('Name') }}</x-table.th>
                <x-table.th>{{ __('vespaket.licence') }}</x-table.th>
                <x-table.th>{{ __('vespaket.number') }}</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </x-table.thead>
        <tbody>
            @forelse($players as $player)
            <x-table.tr>
                @if ($editingMemberId === $player->id)
                    <x-table.td>
                        <input type="text" wire:model="editingMember.prenom" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.prenom') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                    <x-table.td>
                        <input type="text" wire:model="editingMember.licence" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.licence') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                    <x-table.td>
                        <input type="text" wire:model="editingMember.numero" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.numero') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                @else
                    <x-table.td>{{ $player->licensee->firstname }} {{ $player->licensee->name }} </x-table.td>
                    <x-table.td>{{ $player->licensee->licence ?? '.....' }}</x-table.td>
                    <x-table.td>{{ $player->numero }}</x-table.td>
                @endif
                <x-table.td>
                    @if ($editingMemberId === $player->id)
                        <button wire:click="saveMember" type="button" class="mr-2 text-sm text-green-400 hover:underline">{{ __('actions.save') }}</button>
                        <button wire:click="cancelEdit" type="button" class="text-sm text-gray-300 hover:underline">{{ __('actions.cancel') }}</button>
                    @else
                        <button wire:click="editMember({{ $player->id }})" type="button" class="text-sm text-white hover:underline">{{ __('actions.edit') }}</button>
                    @endif
                </x-table.td>
            </x-table.tr>
            @empty
            <x-table.tr class="text-gray-500">{{ __('vespaket.no_player') }}</x-table.tr>
            @endforelse
        </tbody>
    </x-table.wrapper>

    <div class="flex gap-4 mb-2 mt-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.sub') }}</h2>
    </div>

    <x-table.wrapper>
        <x-table.thead>
            <tr>
                <x-table.th>{{ __('Name') }}</x-table.th>
                <x-table.th>{{ __('vespaket.licence') }}</x-table.th>
                <x-table.th>{{ __('vespaket.number') }}</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </x-table.thead>
        <tbody>
            @forelse($subs as $player)
            <x-table.tr>
                @if ($editingMemberId === $player->id)
                    <x-table.td>
                        <input type="text" wire:model="editingMember.prenom" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.prenom') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                    <x-table.td>
                        <input type="text" wire:model="editingMember.licence" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.licence') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                    <x-table.td>
                        <input type="text" wire:model="editingMember.numero" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.numero') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                @else
                    <x-table.td>{{ $player->licensee->firstname }} {{ $player->licensee->name }} </x-table.td>
                    <x-table.td>{{ $player->licensee->licence ?? '.....' }}</x-table.td>
                    <x-table.td>{{ $player->numero }}</x-table.td>
                @endif
                <x-table.td>
                    @if ($editingMemberId === $player->id)
                        <button wire:click="saveMember" type="button" class="mr-2 text-sm text-green-400 hover:underline">{{ __('actions.save') }}</button>
                        <button wire:click="cancelEdit" type="button" class="text-sm text-gray-300 hover:underline">{{ __('actions.cancel') }}</button>
                    @else
                        <button wire:click="editMember({{ $player->id }})" type="button" class="text-sm text-white hover:underline">{{ __('actions.edit') }}</button>
                    @endif
                </x-table.td>
            </x-table.tr>
            @empty
            <x-table.tr class="text-gray-500">{{ __('vespaket.no_player') }}</x-table.tr>
            @endforelse
        </tbody>
    </x-table.wrapper>


    <div class="flex gap-4 mb-2 mt-2">
        <h2 class="text-lg font-semibold mb-4">{{ __('vespaket.otm_full') }}</h2>
    </div>
    <x-table.wrapper>
        <x-table.thead>
            <tr>
                <x-table.th>{{ __('Name') }}</x-table.th>
                <x-table.th>{{ __('vespaket.licence') }}</x-table.th>
                <x-table.th></x-table.th>
            </tr>
        </x-table.thead>
        <tbody>
            @forelse($otms as $otm)
            <x-table.tr>
                @if ($editingMemberId === $otm->id)
                    <x-table.td>
                        <input type="text" wire:model="editingMember.prenom" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.prenom') <span class="text-red-400">{{ $message }}</span> @enderror
                        <input type="text" wire:model="editingMember.name" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.name') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                    <x-table.td>
                        <input type="text" wire:model="editingMember.licence" class="w-full rounded border-gray-700 bg-yellow-200 px-2 py-1 text-black">
                        @error('editingMember.licence') <span class="text-red-400">{{ $message }}</span> @enderror
                    </x-table.td>
                @else
                    <x-table.td>{{ $otm->licensee->firstname }} {{ $otm->licensee->name }}</x-table.td>
                    <x-table.td>{{ $otm->licensee->licence ?? '.....' }}</x-table.td>
                @endif
                <x-table.td>
                    @if ($editingMemberId === $otm->id)
                        <button wire:click="saveMember" type="button" class="mr-2 text-sm text-green-400 hover:underline">{{ __('actions.save') }}</button>
                        <button wire:click="cancelEdit" type="button" class="text-sm text-gray-300 hover:underline">{{ __('actions.cancel') }}</button>
                    @else
                        <button wire:click="editMember({{ $otm->id }})" type="button" class="text-sm text-white hover:underline">{{ __('actions.edit') }}</button>
                    @endif
                </x-table.td>
            </x-table.tr>
            @empty
            <tr class="text-gray-500"> {{ __('vespaket.no_otm') }}</tr>
            @endforelse
        </tbody>
    </x-table.wrapper>

</div>
