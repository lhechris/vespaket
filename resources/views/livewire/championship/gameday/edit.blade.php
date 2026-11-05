<div class="mx-auto max-w-3xl space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-gray-900">Modifier la journée {{ $gameday->number }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ $championship->name }} — {{ $championship->season }}</p>
    </header>

    <form wire:submit="save" class="space-y-4 rounded-2xl bg-white p-5 shadow-sm sm:p-6">
        <div>
            <x-input-label for="number" value="Numéro de la journée" />
            <x-text-input wire:model="number" id="number" name="number" type="number" min="1" max="65535" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->get('number')" />
        </div>
        <div>
            <x-input-label for="label" value="Libellé (facultatif)" />
            <x-text-input wire:model="label" id="label" name="label" type="text" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('label')" />
        </div>
        <div>
            <x-input-label for="date" value="Date (facultatif)" />
            <x-text-input wire:model="date" id="date" name="date" type="date" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('date')" />
        </div>
        <div>
            <x-input-label for="date" value="Identifiant score'n'co de la journée (facultatif)" />
            <x-text-input wire:model="external_id" id="external_id" name="external_id" type="texte" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('external_id')" />
        </div>
        <div class="flex items-center gap-3">
            <x-primary-button>Enregistrer les modifications</x-primary-button>
            <a href="{{ route('team.championship.edit', [$team, $championship]) }}#gamedays" class="text-sm font-semibold text-slate-700 hover:underline">
                Annuler
            </a>
        </div>
    </form>
</div>
