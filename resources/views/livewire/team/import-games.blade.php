<div>
    <x-button wire:click="toggleForm">{{ __('vespaket.import_match') }}</x-button>

    @if($showForm)
        <div class="mt-4 max-w-xl space-y-4 rounded-xl bg-white p-4 shadow">
            <p class="text-sm text-gray-700">
                Importez dans cette équipe les matchs du championnat sélectionné. Les matchs déjà présents
                à la même date contre le même adversaire ne seront pas ajoutés une seconde fois.
            </p>

            @if($championships->isEmpty())
                <p class="text-sm text-gray-700">
                    Aucun championnat n’est associé à cette équipe.
                </p>
            @else
                <div>
                    <label for="import-championship" class="mb-1 block text-sm font-semibold text-gray-700">
                        Championnat
                    </label>
                    <select
                        id="import-championship"
                        wire:model="selectedChampionshipId"
                        class="w-full rounded border border-gray-300 px-3 py-2 text-black"
                    >
                        <option value="">Sélectionnez un championnat</option>
                        @foreach($championships as $championship)
                            <option value="{{ $championship->id }}">
                                {{ $championship->name }}{{ $championship->season ? ' — '.$championship->season : '' }}
                            </option>
                        @endforeach
                    </select>
                    @error('selectedChampionshipId')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        wire:model="setTimeToMidnight"
                        class="mt-1 rounded border-gray-300"
                    >
                    <span>Initialiser l’heure des matchs importés à 00:00</span>
                </label>

                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        wire:model="importLocation"
                        class="mt-1 rounded border-gray-300"
                    >
                    <span>Importer le lieu depuis le championnat</span>
                </label>

                <div class="flex gap-2">
                    <button
                        type="button"
                        wire:click="importGames"
                        wire:loading.attr="disabled"
                        class="rounded bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        Importer
                    </button>
                    <button
                        type="button"
                        wire:click="toggleForm"
                        class="rounded bg-gray-200 px-4 py-2 font-semibold text-gray-800 hover:bg-gray-300"
                    >
                        Annuler
                    </button>
                </div>
            @endif

            @if($feedback)
                <p role="status" class="text-sm font-semibold text-green-700">{{ $feedback }}</p>
            @endif
        </div>
    @endif
</div>
