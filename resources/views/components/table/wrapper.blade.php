    <div class="overflow-x-auto">
        <table {{ $attributes->merge(['class' =>'w-full text-sm text-left rtl:text-right text-body text-yellow-400']) }}>
            {{ $slot }}
        </table>
    </div>