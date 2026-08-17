@php
    $footerSetting = \App\Models\SiteSetting::current();
    $socials = is_array($footerSetting->social_links) ? array_filter($footerSetting->social_links) : [];
@endphp

<!-- Footer Component -->
<footer class="border-t border-white/10 bg-dark-950/90 backdrop-blur-md py-8 {{ View::hasSection('hide_sidebar') ? '' : 'lg:pl-64' }} text-center text-xs text-zinc-500 relative z-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 md:gap-4">
            <!-- Logo & Branding -->
            <div class="flex items-center gap-2.5">
                <img src="{{ $footerSetting->logo_url }}" alt="{{ $footerSetting->site_name }}" class="h-6 w-auto object-contain">
                <span class="text-zinc-400 font-medium">| {{ $footerSetting->site_tagline ?: 'Streaming platform for movie lovers' }}</span>
            </div>

            <!-- Navigation Links Bar -->
            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors whitespace-nowrap">Home</a>
                <a href="{{ route('browse') }}" class="hover:text-white transition-colors whitespace-nowrap">Katalog Film</a>
                <a href="{{ route('collections.index') }}" class="hover:text-white transition-colors whitespace-nowrap">Koleksi</a>
                <a href="{{ route('changelog') }}" class="hover:text-white transition-colors whitespace-nowrap">Changelog</a>
                <a href="{{ route('privacy-policy') }}" class="hover:text-white transition-colors whitespace-nowrap">Kebijakan Privasi</a>
                <a href="{{ route('terms-of-service') }}" class="hover:text-white transition-colors whitespace-nowrap">Syarat & Ketentuan</a>
                <a href="{{ route('download.app') }}" class="text-amber-400 hover:text-amber-300 font-bold flex items-center gap-1 transition-colors whitespace-nowrap px-2.5 py-1 rounded-lg bg-amber-500/10 border border-amber-500/20">
                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                    <span>App Mobile</span>
                </a>
            </div>
        </div>

        <!-- Social Media Icons Bar (If defined in CMS) -->
        @if(!empty($socials))
            <div class="pt-4 border-t border-white/5 flex flex-wrap items-center justify-center gap-4">
                @if(!empty($socials['instagram']))
                    <a href="{{ $socials['instagram'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="Instagram">
                        <i data-lucide="instagram" class="w-4 h-4"></i>
                    </a>
                @endif
                @if(!empty($socials['twitter']))
                    <a href="{{ $socials['twitter'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="Twitter / X">
                        <i data-lucide="twitter" class="w-4 h-4"></i>
                    </a>
                @endif
                @if(!empty($socials['telegram']))
                    <a href="{{ $socials['telegram'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="Telegram">
                        <i data-lucide="send" class="w-4 h-4"></i>
                    </a>
                @endif
                @if(!empty($socials['discord']))
                    <a href="{{ $socials['discord'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="Discord">
                        <i data-lucide="message-square" class="w-4 h-4"></i>
                    </a>
                @endif
                @if(!empty($socials['youtube']))
                    <a href="{{ $socials['youtube'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="YouTube">
                        <i data-lucide="youtube" class="w-4 h-4"></i>
                    </a>
                @endif
                @if(!empty($socials['tiktok']))
                    <a href="{{ $socials['tiktok'] }}" target="_blank" rel="noopener noreferrer" class="text-zinc-400 hover:text-white transition-colors" title="TikTok">
                        <i data-lucide="video" class="w-4 h-4"></i>
                    </a>
                @endif
            </div>
        @endif

        <!-- Copyright -->
        <div class="pt-2 text-zinc-500 text-xs font-mono">
            <p>{{ $footerSetting->footer_text ?: ('© ' . date('Y') . ' ' . $footerSetting->site_name . '. All Rights Reserved.') }}</p>
        </div>

    </div>
</footer>
