@props(['title', 'href','description', 'tag','id'])

<a href="{{$href?? ''}}" id="{{$id?? ''}}">
    <div class="bg-gray-900 text-yellow-400 p-4 rounded-2xl shadow hover:shadow-lg transition hover:-translate-y-1 text-sm">

        <div class="flex justify-between items-center mb-1">
            @isset($description)
            <span class="text-yellow-400 rounded-lg">
                {{ $description }}
            </span>
            @endisset
            @isset($tag)
            <span class="bg-yellow-400 text-black text-xs px-2 py-1 rounded-lg">
                {{ $tag }}
            </span>
            @endisset
        </div>
        <div class="flex justify-between items-center mb-2 h-8">

            <p class="font-semibold">{{ $title }}</p>
        </div>            

        <!-- ACTION -->
        <div class="mt-3 text-gray-400">
             {{ $slot }}
        </div>
    </div>
</a>
