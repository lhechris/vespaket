<div>
    <form wire:submit="save" class="rounded-xl border border-gray-800 bg-yellow-100 p-4 text-black shadow">
        <h2 class="text-lg font-semibold">
            {{ $licensee ? 'Modifier un licencié' : 'Nouveau licencié' }}
        </h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="licensee-firstname" class="block text-sm font-medium">Prénom</label>
                <input wire:model="firstname" id="licensee-firstname" type="text" class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200" />
                @error('firstname') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="licensee-name" class="block text-sm font-medium">Nom</label>
                <input wire:model="name" id="licensee-name" type="text" class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200" />
                @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="licensee-licence" class="block text-sm font-medium">{{ __('vespaket.licence') }}</label>
                <input wire:model="licence" id="licensee-licence" type="text" class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200" />
                @error('licence') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-base font-semibold">Équipes et rôles</h3>
                <button
                    wire:click="addTeamAssignment"
                    type="button"
                    @disabled($teams->isEmpty())
                    class="rounded-lg bg-yellow-400 px-3 py-2 font-semibold text-black hover:bg-yellow-300 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Ajouter une équipe
                </button>
            </div>

            @if($teams->isEmpty())
                <p class="mt-2 text-sm text-gray-700">Aucune équipe administrée disponible.</p>
            @endif

            @foreach($teamAssignments as $index => $assignment)
                <div wire:key="licensee-team-assignment-{{ $index }}" class="mt-3 rounded-lg border border-gray-700 p-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="flex-1">
                            <label for="licensee-team-{{ $index }}" class="block text-sm font-medium">Équipe</label>
                            <select
                                wire:model="teamAssignments.{{ $index }}.team_id"
                                id="licensee-team-{{ $index }}"
                                class="mt-1 w-full rounded-md border-gray-700 bg-yellow-200"
                            >
                                <option value="">Sélectionner une équipe</option>
                                @foreach($teams as $team)
                                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                                @endforeach
                            </select>
                            @error("teamAssignments.$index.team_id") <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap gap-4">
                            @foreach($roles as $role => $label)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input
                                        wire:model="teamAssignments.{{ $index }}.roles.{{ $role }}"
                                        type="checkbox"
                                        class="rounded border-gray-500 text-yellow-500 focus:ring-yellow-500"
                                    />
                                    {{ __($label) }}
                                </label>
                            @endforeach
                        </div>

                        <button
                            wire:click="removeTeamAssignment({{ $index }})"
                            type="button"
                            class="rounded-lg border border-red-500 px-3 py-2 font-semibold text-red-700 hover:bg-red-100"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            @endforeach

            @error('teamAssignments') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="rounded-lg bg-black px-4 py-2 font-semibold text-yellow-400 hover:bg-gray-800">
                {{ __('actions.save') }}
            </button>
        </div>
    </form>
</div>
