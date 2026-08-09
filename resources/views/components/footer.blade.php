<!-- Footer Component -->
<footer class="border-t border-white/10 bg-dark-950/90 backdrop-blur-md py-8 {{ View::hasSection('hide_sidebar') ? '' : 'lg:pl-64' }} text-center text-xs text-zinc-500 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <img src="{{ asset('images/logo.png') }}" alt="faiilmov" class="h-6 w-auto object-contain">
            <span class="hidden sm:inline">| Streaming platform for my ailll</span>
        </div>
        <div class="flex items-center gap-5 text-xs">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
            <a href="{{ route('browse') }}" class="hover:text-white transition-colors">Katalog Film</a>
            <a href="{{ route('changelog') }}" class="hover:text-white transition-colors flex items-center gap-1"><i data-lucide="history" class="w-3 h-3 text-amber-400"></i><span>Changelog</span></a>
            <a href="{{ route('privacy-policy') }}" class="hover:text-white transition-colors">Kebijakan Privasi</a>
            <a href="{{ route('terms-of-service') }}" class="hover:text-white transition-colors">Syarat & Ketentuan</a>
            <a href="{{ route('download.app') }}" class="text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1 transition-colors">
                <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                <span>App Mobile</span>
            </a>
        </div>
        <p>© 2026 faiilmov. All Rights Reserved.</p>
    </div>
</footer>
