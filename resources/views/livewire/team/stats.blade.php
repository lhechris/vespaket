<div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left text-yellow-400">
            <thead class="bg-black border-b border-default">
                <tr>
                    <th scope="col" class="px-2 py-3 font-medium">{{ __('Name') }}</th>
                    <th scope="col" class="px-2 py-3 font-medium">Matchs</th>
                    <th scope="col" class="px-2 py-3 font-medium">Entrainements</th>
                    @foreach($options as $option)
                        <th scope="col" class="px-2 py-3 font-medium">{{ $option->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($members as $member)
                    <tr class="odd:bg-gray-800 even:bg-gray-900 border-b border-default">
                        <td scope="row" class="px-2 py-4 font-medium">
                            {{ $member->prenom }} {{ $member->name }}
                        </td>
                        <td scope="row" class="px-2 py-4 font-medium">{{ $member->nbgames }}</td>
                        <td scope="row" class="px-2 py-4 font-medium">{{ $member->nbtrainings }}</td>
                        @foreach($options as $option)
                            <td class="px-2 py-4">
                                {{ $statistics[$member->id][$option->id] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
