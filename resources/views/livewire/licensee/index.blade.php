<div>
    <div class="space-y-6 p-0 sm:p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des licenciés</h1>
            <button wire:click="create" type="button" class="rounded-lg bg-yellow-400 px-3 py-2 font-semibold text-black hover:bg-yellow-300">
                Nouveau licencié
            </button>
        </div>

        <input
            wire:model.live.debounce.300ms="search"
            type="search"
            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
            placeholder="Rechercher par nom, prénom ou licence"
            aria-label="Rechercher un licencié"
        />

        @if (session('success'))
            <p class="rounded-lg bg-green-100 px-4 py-3 text-sm text-green-800" role="status">
                {{ session('success') }}
            </p>
        @endif

        <livewire:licensee.form :licensee="$editingLicensee" :key="'licensee-form-' . ($editingLicensee?->id ?? 'new')" />

        @if($licenseesLastPage > 1)
            <div class="flex items-center justify-between gap-4 text-sm text-gray-700">
                <button wire:click="previousLicenseesPage" type="button" @disabled($licenseesPage === 1) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                    {!! __('pagination.previous') !!}
                </button>
                <span>{{ $licenseesPage }} / {{ $licenseesLastPage }}</span>
                <button wire:click="nextLicenseesPage" type="button" @disabled($licenseesPage === $licenseesLastPage) class="rounded-lg border border-gray-700 px-3 py-2 font-semibold disabled:cursor-not-allowed disabled:opacity-40">
                    {!! __('pagination.next') !!}
                </button>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-800 bg-gray-900 shadow">
            <table class="w-full text-left text-sm text-white">
                <thead class="bg-black text-xs uppercase tracking-wide text-yellow-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('vespaket.licensee') }}</th>
                        <th class="px-4 py-3">{{ __('vespaket.licence') }}</th>
                        <th class="px-4 py-3">{{ __('vespaket.teams') }}</th> 
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($licensees as $licensee)
                        <tr wire:key="licensee-{{ $licensee->id }}" class="border-b border-gray-800 last:border-0">
                            <td class="px-4 py-4">{{ $licensee->firstname }} {{ $licensee->name }}</td>
                            <td class="px-4 py-4">{{ $licensee->licence ?: '—' }}</td>
                            <td class="px-4 py-4">
                                @forelse($licensee->members as $member)
                                <span class="block">
                                    {{ $member->team?->name."(".__('vespaket.' . $member->type).")  " }}
                                </span>
                                @empty
                                {{ __('vespaket.no_teams') }}
                                @endforelse
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-right">
                                <button wire:click="edit({{ $licensee->id }})" type="button" class="font-semibold text-yellow-400 hover:text-yellow-300">
                                    Modifier
                                </button>
                                <button wire:click="delete({{ $licensee->id }})" wire:confirm="Confirmer la suppression de ce licencié ?" type="button" class="ml-3 font-semibold text-red-400 hover:text-red-300">
                                    Supprimer
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-gray-400">Aucun licencié.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
