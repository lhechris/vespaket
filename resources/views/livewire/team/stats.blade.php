<div>
    <x-table.wrapper>
        <x-table.thead>
            <tr>
                <x-table.th>{{ __('Name') }}</x-table.th>
                <x-table.th>Matchs</x-table.th>
                <x-table.th>Entrainements</x-table.th>
                @foreach($options as $option)
                    <x-table.th>{{ $option->name }}</x-table.th>
                @endforeach
            </tr>
        </x-table.thead>
            <tbody>
                @foreach($members as $member)
                    <x-table.tr class="odd:bg-gray-800 even:bg-gray-900 border-b border-default">
                        <x-table.td scope="row" class="px-2 py-4 font-medium">
                            {{ $member->licensee->firstname }} {{ $member->licensee->name }}
                        </x-table.td>
                        <x-table.td scope="row" class="px-2 py-4 font-medium">{{ $member->nbgames }}</x-table.td>
                        <x-table.td scope="row" class="px-2 py-4 font-medium">{{ $member->nbtrainings }}</x-table.td>
                        @foreach($options as $option)
                            <x-table.td class="px-2 py-4">
                                {{ $statistics[$member->id][$option->id] ?? '' }}
                            </x-table.td>
                        @endforeach
                    </x-table.tr>
                @endforeach
            </tbody>
        </x-table.wrapper>
</div>
