<div class="mx-auto max-w-4xl space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Championnats de {{ $team->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">Consultez et modifiez les championnats associés à cette équipe.</p>
        </div>
        @can('create', \App\Models\Championship::class)
            <a
                href="{{ route('team.championship.manage', $team) }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
            >
                Créer un championnat
            </a>
        @endcan
    </header>

    @if($championships->isEmpty())
        <section class="rounded-2xl bg-white p-6 text-center shadow-sm">
            <p class="text-gray-700">Aucun championnat n’est encore associé à cette équipe.</p>
            @can('create', \App\Models\Championship::class)
                <a
                    href="{{ route('team.championship.manage', $team) }}"
                    wire:navigate
                    class="mt-3 inline-block text-sm font-semibold text-indigo-600 hover:underline"
                >
                    Créer le premier championnat
                </a>
            @endcan
        </section>
    @else
        <ul class="space-y-3">
            @foreach($championships as $championship)
                <li class="flex flex-col justify-between gap-4 rounded-2xl bg-white p-5 shadow-sm sm:flex-row sm:items-center">
                    <div>
                        <h2 class="font-semibold text-gray-900">{{ $championship->name }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $championship->season }}
                            @if($championship->category || $championship->division || $championship->pool)
                                · {{ collect([$championship->category, $championship->division, $championship->pool])->filter()->implode(' · ') }}
                            @endif
                        </p>
                    </div>
                    @can('update', $championship)
                        <a
                            href="{{ route('team.championship.edit', [$team, $championship]) }}"
                            wire:navigate
                            class="text-sm font-semibold text-indigo-600 hover:underline"
                        >
                            Modifier
                        </a>
                    @endcan
                </li>
            @endforeach
        </ul>
    @endif
</div>
