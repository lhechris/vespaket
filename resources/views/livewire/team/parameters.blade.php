<div class="gap-3">

    <div class="mb-3 flex flex-col gap-3">
        <div>
            <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.team_name') }}</label>
            <input type="text" wire:model="teamName" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.link_whatsapp') }}</label>
            <input type="text" wire:model="teamWhatsapp" class="w-full rounded border border-gray-300 px-3 py-2 text-black">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700">{{ __('vespaket.message_convocation') }}</label>
            <textarea type="text" rows="8" wire:model="teamMsgConvocation" class="w-full rounded border border-gray-300 px-3 py-2 text-black"> </textarea>
        </div>
    </div>

    <div>
        <label >{{ __('vespaket.list_options_matchs') }} </label>
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-black text-yellow-400">
                    <th class="p-2">{{ __('Name') }}</th>
                    <th class="p-2">{{ __('vespaket.type') }}</th>
                    <th class="p-2">{{ __('vespaket.order') }}</th>
                    <th class="p-2">{{ __('vespaket.display') }}</th>
                    <th class="p-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($gameoptions as $key => $option)
                    <tr wire:key="game-option-{{ $key }}" class="border-b">
                        <td class="p-1">
                            <input type="text" wire:model="gameoptions.{{ $key }}.name"
                                class="w-full text-sm rounded border-gray-300 focus:border-yellow-400 focus:ring-yellow-400" />
                            @error("gameoptions.{$key}.name") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="p-1">
                            <select wire:model="gameoptions.{{ $key }}.type"
                                class="w-full rounded border-gray-300 focus:border-yellow-400 focus:ring-yellow-400 text-sm">
                                <option value="">{{ __('actions.select') }}</option>
                                @foreach($typeOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error("gameoptions.{$key}.type") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="p-1">
                            <input type="number" wire:model="gameoptions.{{ $key }}.order"
                                class="w-20 rounded border-gray-300 focus:border-yellow-400 focus:ring-yellow-400 text-sm" />
                            @error("gameoptions.{$key}.order") <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </td>
                        <td class="p-1">
                            <select wire:model="gameoptions.{{ $key }}.display"
                                class="w-full rounded border-gray-300 focus:border-yellow-400 focus:ring-yellow-400 text-sm">
                                <option value="">{{ __('actions.select') }}</option>
                                @foreach($displayOptions as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-1">
                            <button wire:click="delete('{{ $key }}')" type="button"
                                wire:confirm="{{ __('vespaket.confirm_delete') }}"
                                class="hover:scale-110 transition">
                                🗑️
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3 flex gap-2">
        <button wire:click="addRow" type="button"
            class="bg-black text-yellow-400 font-semibold px-4 py-2 rounded hover:bg-gray-800">
            + {{ __('actions.add') }}
        </button>
    </div>
    <div class="mt-3 flex gap-2">

        <button wire:click="saveAll" type="button"
            class="bg-yellow-400 text-black font-semibold px-4 py-2 rounded hover:bg-yellow-500"
            wire:loading.attr="disabled" wire:target="saveAll">
            💾 {{ __('actions.save') }}
        </button>

        <button wire:click="deleteTeam()" 
            wire:confirm="{{ __('vespaket.confirm_delete_team') }}"
            class="bg-red-800 text-yellow-400 font-semibold px-4 py-2 rounded hover:bg-red-900">
            🗑️​ {{ __('actions.delete') }}
        </button>


    </div>

    <div x-data="{ show: false }"
        x-on:saved.window="show = true; setTimeout(() => show = false, 2000)"
        x-show="show" x-transition
        class="mt-2 text-sm text-green-600">
        {{ __('vespaket.successfully_saved') }}
    </div>
</div>