<footer class="border-t border-secondary/5 bg-white dark:border-white/10 dark:bg-secondary">
    <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 py-8 sm:px-6 md:flex-row md:items-center lg:px-8">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-14 items-center justify-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-secondary/10 dark:ring-white/10">
                <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="Logo Gugus Depan" class="h-full w-full object-contain">
            </div>
            <div>
                <p class="text-sm font-semibold">Perkap Pramuka</p>
                <p class="text-xs text-secondary/50 dark:text-white/40">SMK Negeri 5 Surakarta</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="https://wa.me/6285877708909" target="_blank" rel="noopener"
               class="flex h-10 w-10 items-center justify-center rounded-full bg-[#25D366]/10 text-[#25D366] transition hover:bg-[#25D366] hover:text-white" title="WhatsApp">
                <x-icon name="whatsapp" class="h-5 w-5"/>
            </a>
            <a href="https://instagram.com/pramuka.smkn5surakarta" target="_blank" rel="noopener"
               class="flex h-10 w-10 items-center justify-center rounded-full bg-rose/10 text-rose transition hover:bg-gradient-to-br hover:from-amber-400 hover:via-rose hover:to-accent hover:text-white" title="Instagram">
                <x-icon name="instagram" class="h-5 w-5"/>
            </a>
            <a href="https://tiktok.com/@Prajamudalimasala" target="_blank" rel="noopener"
               class="flex h-10 w-10 items-center justify-center rounded-full bg-secondary/10 text-secondary transition hover:bg-secondary hover:text-white dark:bg-white/10 dark:text-white" title="TikTok">
                <x-icon name="tiktok" class="h-5 w-5"/>
            </a>
        </div>

        <p class="text-xs text-secondary/40 dark:text-white/30">© {{ date('Y') }} Dewan Ambalan Perkap Pramuka.</p>
    </div>
</footer>
