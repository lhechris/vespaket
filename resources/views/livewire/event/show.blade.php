<div class="space-y-6 mb-4">

    <!-- HEADER -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ $event->titre }}</h1>
            <p class="mt-2 text-sm text-gray-600">
                {{ $event->formatdate() }}
            </p>
            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-slate-800 p-4 rounded-2xl shadow-sm border border-slate-700 text-yellow-300">
                    <p class="text-xs uppercase tracking-[0.16em] font-semibold text-yellow-200">{{ __('vespaket.description') }}</p>
                    <p class="mt-2 text-base font-semibold">{{ $event->description }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($members as $member)
            <div class="bg-slate-800 p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col gap-4 text-yellow-300">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-lg font-semibold text-yellow-200">{{ $member->prenom }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button wire:click="setAvailability({{ $member->id }}, 'yes')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'yes' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{__('vespaket.present')}}
                    </button>
                    <button wire:click="setAvailability({{ $member->id }}, 'no')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'no' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('vespaket.absent') }}
                    </button>
                    <button wire:click="setAvailability({{ $member->id }}, 'maybe')"
                        class="px-3 py-2 rounded-xl text-sm font-medium transition-colors {{ $member->pivot->availability === 'maybe' ? 'bg-yellow-400 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('vespaket.maybe') }}
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div>
        <h2>{{ __('vespaket.list_players') }}</h2>
        <table class="text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg ">
            <thead class="bg-black border-b border-default">
                <tr>
                    <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('vespaket.first_name') }}</th>
                    <th>{{ __('vespaket.availability') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($players as $player)
                    <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
                        <td class="px-2 sm:px-6 py-4">{{ $player->prenom }}</td>
                        <td>
                            @if($player->pivot->availability=='yes')
                            ✅​
                            @elseif($player->pivot->availability=='no')
                            ❌​
                            @else
                            ❓​
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>



</div>