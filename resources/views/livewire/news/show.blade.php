<section class="space-y-3" aria-label="Actualités">
    <div class="bg-gray-900 p-5 rounded-2xl border border-gray-800">

        <h2 class="text-white font-bold mb-4">
            📰​ {{ __('vespaket.news') }}
        </h2>

        <div class="grid grids-row gap-3">
            @forelse($news as $item)
                @php
                    $description = str_replace(["\r\n", "\r", '\\n', '\\r'], ["\n", "\n", "\n", "\n"], $item->description);
                    $description = str_replace('\\t', "\t", $description);
                @endphp
                <article x-data="{ expanded: false }" class="rounded-xl border border-yellow-300 text-white bg-gray-900 p-4 shadow">
                    <h2 class="text-lg font-bold text-yellow-400">{{ $item->title }}</h2>
                    <p
                        class="mt-1 whitespace-pre-wrap text-sm text-gray-200"
                        :style="expanded ? 'max-height: none; overflow: visible; tab-size: 4;' : 'max-height: 2rem; overflow: hidden; tab-size: 4;'"
                    >{!! str_replace("\n", '<br>', e($description)) !!}</p>
                    <button
                        type="button"
                        class="mt-3 text-sm font-semibold text-yellow-400 underline hover:text-yellow-300"
                        @click="expanded = !expanded"
                        x-text="expanded ? 'Voir moins' : 'Voir plus'"
                    >{{ __('actions.show_all') }}</button>
                </article>
            @empty
                <p class="rounded-xl border border-gray-300 bg-white p-4 text-sm text-gray-600">
                    {{ __('vespaket.no_news') }}
                </p>
            @endforelse

        </div>
    </div>


</section>
