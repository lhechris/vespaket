<div class="mx-auto max-w-2xl space-y-6">
    <header>
        <h1 class="text-2xl font-bold text-slate-900">Modifier le match</h1>
        <p class="mt-1 text-sm text-slate-600">
            {{ $championship->name }} — {{ $gameday->label ?: 'Journée '.$gameday->number }}
        </p>
    </header>

    <form wire:submit="save" class="space-y-4 rounded-2xl bg-white p-5 shadow-sm sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="homeTeamId" value="Équipe à domicile" />
                <select wire:model="homeTeamId" id="homeTeamId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Choisir une équipe</option>
                    @foreach($teams as $teamOption)
                        <option value="{{ $teamOption->id }}">{{ $teamOption->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('homeTeamId')" />
            </div>
            <div>
                <x-input-label for="awayTeamId" value="Équipe à l’extérieur" />
                <select wire:model="awayTeamId" id="awayTeamId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Choisir une équipe</option>
                    @foreach($teams as $teamOption)
                        <option value="{{ $teamOption->id }}">{{ $teamOption->name }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('awayTeamId')" />
            </div>
        </div>

        <div>
            <x-input-label for="scheduledAt" value="Date et heure (facultatif)" />
            <x-text-input wire:model="scheduledAt" id="scheduledAt" type="datetime-local" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('scheduledAt')" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="homeScore" value="Score domicile (facultatif)" />
                <x-text-input wire:model="homeScore" id="homeScore" type="number" min="0" max="65535" class="mt-1 block w-full" />
                <x-input-error class="mt-2" :messages="$errors->get('homeScore')" />
            </div>
            <div>
                <x-input-label for="awayScore" value="Score extérieur (facultatif)" />
                <x-text-input wire:model="awayScore" id="awayScore" type="number" min="0" max="65535" class="mt-1 block w-full" />
                <x-input-error class="mt-2" :messages="$errors->get('awayScore')" />
            </div>
        </div>

        <div>
            <x-input-label for="status" value="Statut" />
            <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                <option value="scheduled">À venir</option>
                <option value="completed">Terminé</option>
                <option value="postponed">Reporté</option>
                <option value="cancelled">Annulé</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>

        <div>
            <x-input-label for="location" value="Lieu (facultatif)" />
            <x-text-input wire:model="location" id="location" type="text" maxlength="255" class="mt-1 block w-full" />
            <x-input-error class="mt-2" :messages="$errors->get('location')" />
        </div>

        <div class="flex items-center gap-3">
            <x-primary-button>Enregistrer les modifications</x-primary-button>
            <a href="{{ route('team.championship.gamedays.games.index', [$team, $championship, $gameday]) }}" class="text-sm font-semibold text-slate-700 hover:underline">
                Annuler
            </a>
        </div>
    </form>
</div>
