<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Vespaket') }}</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;900&family=Barlow:wght@400;500&display=swap" rel="stylesheet">


    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="bg-black overflow-hidden">
 
<section class="relative w-full h-screen flex flex-col items-center justify-center text-center">
 
    <!-- Image de fond -->
    <img src="{{ asset('images/baniere.jpg') }}" alt="AS Labarthaise Basket"
         class="absolute inset-0 w-full h-full object-cover object-center">
 
    <!-- Overlay dégradé -->
    <div class="absolute inset-0 bg-gradient-to-b from-black/35 via-black/60 to-black/85"></div>
 
    <!-- Contenu -->
    <div class="relative z-10 flex flex-col items-center gap-8 px-6">
 
        <p class="font-['Barlow_Condensed'] text-xs font-bold tracking-[0.25em] uppercase text-yellow-400">
            
        </p>
 
        <h1 class="font-['Barlow_Condensed'] text-[clamp(3rem,10vw,7rem)] font-black uppercase leading-none text-white">
            {{ __('vespaket.welcome') }}
        </h1>
 
        <div class="w-12 h-[3px] bg-yellow-400"></div>
 
        @auth
            <a href="/dashboard"
               class="font-['Barlow_Condensed'] text-lg font-bold tracking-widest uppercase text-black bg-yellow-400 border-2 border-yellow-400 px-11 py-4 transition-all duration-200 hover:bg-transparent hover:text-yellow-400 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow-400">
                {{ __('vespaket.my_area') }}
            </a>
        @else
            <a href="/login"
               class="font-['Barlow_Condensed'] text-lg font-bold tracking-widest uppercase text-black bg-yellow-400 border-2 border-yellow-400 px-11 py-4 transition-all duration-200 hover:bg-transparent hover:text-yellow-400 hover:-translate-y-0.5 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow-400">
                {{ __('actions.log_in') }}
            </a>
        @endauth
    </div>
 
    <!-- Footer -->
    <footer class="absolute bottom-0 left-0 right-0 text-center py-5 text-xs tracking-widest text-white/30">
        © {{ date('Y') }} AS Labarthaise Basket
    </footer>
 
</section>
 
</body>

</html>
