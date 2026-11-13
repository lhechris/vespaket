<div class="mx-auto max-w-2xl space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-gray-900">
            {{ $championship ? 'Modifier le championnat' : 'Créer un championnat' }}
        </h1>
        <p class="mt-1 text-sm text-gray-700">Pour l’équipe {{ $team->name }}</p>
    </header>

    <form wire:submit="save" class="space-y-4 rounded-2xl bg-white p-5 shadow-sm sm:p-6">
        <div>
            <x-input-label for="name" value="Nom du championnat" />
            <x-text-input
                wire:model="name"
                id="name"
                name="name"
                type="text"
                class="mt-1 block w-full"
                required
                autofocus
            />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="season" value="Saison" />
            <x-text-input
                wire:model="season"
                id="season"
                name="season"
                type="text"
                class="mt-1 block w-full"
                placeholder="2026-2027"
                maxlength="20"
                required
            />
            <x-input-error class="mt-2" :messages="$errors->get('season')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="category" value="Catégorie (facultatif)" />
                <x-text-input
                    wire:model="category"
                    id="category"
                    name="category"
                    type="text"
                    class="mt-1 block w-full"
                />
                <x-input-error class="mt-2" :messages="$errors->get('category')" />
            </div>

            <div>
                <x-input-label for="division" value="Division (facultatif)" />
                <x-text-input
                    wire:model="division"
                    id="division"
                    name="division"
                    type="text"
                    class="mt-1 block w-full"
                />
                <x-input-error class="mt-2" :messages="$errors->get('division')" />
            </div>
        </div>

        <div>
            <x-input-label for="pool" value="Poule (facultatif)" />
            <x-text-input
                wire:model="pool"
                id="pool"
                name="pool"
                type="text"
                class="mt-1 block w-full"
            />
            <x-input-error class="mt-2" :messages="$errors->get('pool')" />
        </div>
        <div>
            <x-input-label for="source" value="Source des matchs" />
            <select wire:model.live="source" id="source" name="source" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="scorenco">Scorenco</option>
                <option value="desimone">FFBB / Desimone</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('source')" />
        </div>

        @if($source === 'scorenco')
            <div>
                <x-input-label for="scorencoteamid" value="Identifiant Scorenco de l'équipe" />
                <x-text-input
                    wire:model="scorencoteamid"
                    id="scorencoteamid"
                    name="scorencoteamid"
                    type="text"
                    class="mt-1 block w-full"
                />
                <x-input-error class="mt-2" :messages="$errors->get('scorencoteamid')" />
            </div>
        @elseif($source === 'desimone')
            <div>
                <x-input-label for="desimoneSelection" value="Championnat du club (FFBB / Desimone)" />
                <select
                    wire:model.live="desimoneSelection"
                    id="desimoneSelection"
                    name="desimoneSelection"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    required
                >
                    <option value="">Choisir un championnat</option>
                    @foreach($desimoneTeams as $clubTeam)
                        <option value="{{ $clubTeam['poule_id'] }}">
                            {{ $clubTeam['competition'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-sm text-gray-600">Liste chargée pour le club Desimone 12374.</p>
                <x-input-error class="mt-2" :messages="$errors->get('desimoneSelection')" />
            </div>
        @endif


        <div class="flex items-center gap-3">
            <x-primary-button>
                {{ $championship ? 'Enregistrer les modifications' : 'Créer le championnat' }}
            </x-primary-button>
            <a href="{{ route('team.show', $team) }}" class="text-sm font-semibold text-slate-700 hover:underline">
                Annuler
            </a>
        </div>
    </form>

    @if($championship)
        <livewire:championship.gameday.index :team="$team" :championship="$championship" />
    @endif
</div>
