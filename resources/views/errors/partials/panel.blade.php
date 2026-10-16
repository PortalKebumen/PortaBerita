{{-- Isi halaman error di dalam layout situs (publik atau admin). Variabel: $code, $variant ('public'|'admin'), $home, $homeLabel --}}
<div class="mx-auto w-full max-w-xl rounded-card border border-[#E4E8EF] bg-white px-6 py-10 text-center shadow-sm sm:px-10">
    <div class="text-6xl font-extrabold leading-none tracking-tight text-brand-600">{{ $code }}</div>

    @if ($variant === 'public')
        {{-- Di admin judul sudah tampil sebagai judul halaman layout --}}
        <h1 class="mt-4 text-xl font-bold text-[#171B28]">@yield('title')</h1>
    @endif

    <div class="mt-3 space-y-1.5 text-[14.5px] leading-relaxed text-[#5B6378]">
        @yield('message')
    </div>

    @hasSection('search')
        @if ($variant === 'public')
            <form action="{{ url('/cari') }}" method="GET" role="search" class="mx-auto mt-6 flex max-w-md gap-2">
                <input type="search" name="q" placeholder="Cari berita..." aria-label="Cari berita"
                       class="min-w-0 flex-1 rounded-lg border border-[#CDD3DF] px-3.5 py-2.5 text-[14px] outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                <button type="submit" class="btn-primary">Cari</button>
            </form>
        @endif
    @endif

    <div class="mt-7 flex flex-wrap justify-center gap-2.5">
        @yield('extra-actions')
        <a href="{{ $home }}" class="btn-primary">{{ $homeLabel }}</a>
    </div>
</div>
