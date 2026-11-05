<div class="mx-auto max-w-3xl space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-gray-900">Ajouter une journée</h1>
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
        <div class="flex items-center gap-3">
            <x-primary-button>Créer la journée</x-primary-button>
            <a href="{{ route('team.championship.edit', [$team, $championship]) }}#gamedays" class="text-sm font-semibold text-slate-700 hover:underline">
                Annuler
            </a>
        </div>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Importer une journée depuis Scorenco</h2>
        <p class="mt-1 text-sm text-slate-600">
            Entrez l’identifiant Scorenco de la journée pour importer les matchs et les équipes rencontrées.
        </p>

        <form wire:submit="refresh" class="mt-4 space-y-3">
            <div>
                <label for="scorenco-team-id" class="block text-sm font-medium text-slate-700">
                    Identifiant Scorenco de la journée
                </label>
                <input
                    id="scorenco-team-id"
                    type="number"
                    min="1"
                    wire:model="scorencoTeamId"
                    class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                />
                @error('scorencoTeamId')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="scorenco-daynumber" class="block text-sm font-medium text-slate-700">Numéro de journée (facultatif)</label>
                    <input id="scorenco-daynumber" type="number" min="1" wire:model="scorencoDayNumber" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" />
                </div>
                <div>
                    <label for="scorenco-daylabel" class="block text-sm font-medium text-slate-700">Libellé (facultatif)</label>
                    <input id="scorenco-daylabel" type="text" wire:model="scorencoDayLabel" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm" />
                </div>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="refresh" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:cursor-wait disabled:opacity-60">
                <span wire:loading.remove wire:target="refresh">Importer</span>
                <span wire:loading wire:target="refresh">Synchronisation…</span>
            </button>
        </form>

        @if (session()->has('championship-refresh-success'))
            <p role="status" class="mt-3 text-sm text-green-700">{{ session('championship-refresh-success') }}</p>
        @endif
    </section>
</div>
