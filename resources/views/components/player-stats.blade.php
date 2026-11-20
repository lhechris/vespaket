@props(['players','options'])


<table class="w-full text-sm text-left rtl:text-right text-body text-yellow-400 max-w-lg">
    <thead class="bg-black border-b border-default">
        <tr>
            <th scope="col" class="px-2 sm:px-6 py-3 font-bold">{{ __('Name') }}
            @foreach($options as $option) 
            @if($option->isDisplayStat())
            <th>{{ $option->name }}</th>        
            @endif
            @endforeach   
        </tr>
    </thead >
    <tbody>
    @foreach($players as $player)
    @if($player->isPlayer())
        <tr class="odd:bg-gray-700 even:bg-gray-800 border-b border-default">
            <td class="px-2 sm:px-6 py-4">{{$player->licensee->firstname}}</td>
            @foreach($options as $option)
            @if($option->isDisplayStat())
                @php
                    $memberOption = $player->gameOptions->firstWhere('game_option_id', $option->id);
                @endphp
                <td class="px-2 sm:px-6 py-4"><x-game-member-option-cell :member="$player" :option="$option" :value="$memberOption?->value" /></td>
            @endif
            @endforeach
        </tr>
    @endif
    @endforeach
    </tbody>
</table>

