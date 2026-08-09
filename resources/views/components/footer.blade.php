<!-- Footer Component -->
<footer class="border-t border-white/10 bg-dark-950/90 backdrop-blur-md py-8 {{ View::hasSection('hide_sidebar') ? '' : 'lg:pl-64' }} text-center text-xs text-zinc-500 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6 md:gap-4">
        
        <!-- Logo & Branding -->
        <div class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="h-6 w-auto object-contain">
            <span class="text-zinc-400 font-medium">| Streaming platform for my ailll</span>
        </div>

        <!-- Navigation Links Bar -->
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors whitespace-nowrap">Home</a>
            <a href="{{ route('browse') }}" class="hover:text-white transition-colors whitespace-nowrap">Katalog Film</a>
            <a href="{{ route('changelog') }}" class="hover:text-white transition-colors flex items-center gap-1 whitespace-nowrap">
                <i data-lucide="history" class="w-3.5 h-3.5 text-amber-400"></i>
                <span>Changelog</span>
            </a>
            <a href="{{ route('privacy-policy') }}" class="hover:text-white transition-colors whitespace-nowrap">Kebijakan Privasi</a>
            <a href="{{ route('terms-of-service') }}" class="hover:text-white transition-colors whitespace-nowrap">Syarat & Ketentuan</a>
            <a href="{{ route('download.app') }}" class="text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1 transition-colors whitespace-nowrap px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                <span>App Mobile</span>
            </a>
        </div>

        <!-- Copyright -->
        <p class="text-zinc-500 text-xs font-mono whitespace-nowrap">© 2026 faiilmov. All Rights Reserved.</p>
    </div>
</footer>
