<x-layouts.admin title="Manajemen Kategori dan Tag">
    {{-- Notifikasi Berhasil --}}
    @if (session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    {{-- Notifikasi Gagal --}}
    @if (session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    {{-- Notifikasi Error Validasi Input --}}
    @if ($errors->any())
        <div class="mb-5 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Grid Pembungkus Utama --}}
    <div class="grid grid-cols-12 gap-5 sm:gap-6">

        {{-- Kolom Kiri: Form Input (Lebar 4 dari 12 Kolom) --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">

            {{-- Kartu Form Tambah Kategori --}}
            <div class="bg-white border border-[#E4E8EF] rounded-2xl p-5 shadow-sm">
                <h2 class="text-[15px] font-bold text-gray-800 mb-1">Tambah Kategori</h2>
                <p class="text-[12px] text-[#848CA3] mb-4">Buat kategori utama atau sub-kategori turunan.</p>

                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf

                    <div class="mb-3.5">
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Kategori</label>
                        <input type="text" name="name" required placeholder="Misal: Wisata, Berita Desa"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>

                    <div class="mb-4">
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Kategori Induk (Parent)</label>
                        <select name="parent_id" class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2 bg-white focus:outline-none focus:border-brand-600">
                            <option value="">-- Tanpa Induk (Kategori Utama) --</option>
                            @foreach ($parentCategories as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-[#848CA3] mt-1 block">Pilih kategori utama jika ini merupakan sub-kategori.</span>
                    </div>

                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white text-[13px] font-semibold py-2.5 rounded-xl transition shadow-sm">
                        Simpan Kategori
                    </button>
                </form>
            </div>

            {{-- Kartu Form Tambah Tag --}}
            <div class="bg-white border border-[#E4E8EF] rounded-2xl p-5 shadow-sm">
                <h2 class="text-[15px] font-bold text-gray-800 mb-1">Tambah Tag</h2>
                <p class="text-[12px] text-[#848CA3] mb-4">Gunakan kata kunci pencarian topik artikel.</p>

                <form action="{{ route('admin.tags.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-[12px] font-bold text-gray-700 mb-1.5">Nama Tag</label>
                        <input type="text" name="name" required placeholder="Misal: Kuliner Kebumen"
                            class="w-full text-[13px] border border-[#E4E8EF] rounded-xl px-3.5 py-2 focus:outline-none focus:border-brand-600 focus:ring-1 focus:ring-brand-600">
                    </div>

                    <button type="submit" class="w-full bg-gray-800 hover:bg-gray-900 text-white text-[13px] font-semibold py-2.5 rounded-xl transition shadow-sm">
                        Simpan Tag
                    </button>
                </form>
            </div>

        </div>
        {{-- Akhir Kolom Kiri --}}

        {{-- Kolom Kanan: Tabel Data (Lebar 8 dari 12 Kolom) --}}
        <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">

            {{-- Kartu Tabel Kategori --}}
            <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-[#E4E8EF] flex justify-between items-center">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-800">Daftar Kategori</h2>
                        <p class="text-[12px] text-[#848CA3]">Termasuk hierarki sub-kategori 1 level.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] border-collapse">
                        <thead>
                            <tr class="bg-gray-50/50">
                                <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-5 py-3 border-b border-[#E4E8EF]">Nama Kategori</th>
                                <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-3 border-b border-[#E4E8EF]">Slug</th>
                                <th class="text-center text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-3 border-b border-[#E4E8EF]">Artikel</th>
                                <th class="text-right text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-5 py-3 border-b border-[#E4E8EF]">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categories as $cat)
                                {{-- Baris Kategori Induk --}}
                                <tr class="border-b border-[#E4E8EF] hover:bg-gray-50/50">
                                    <td class="px-5 py-3 text-[13px] font-bold text-gray-800 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-brand-600"></span>
                                        {{ $cat->name }}
                                    </td>
                                    <td class="px-3 py-3 text-[12px] font-mono text-[#848CA3]">{{ $cat->slug }}</td>
                                    <td class="px-3 py-3 text-[12px] text-center font-medium">{{ $cat->articles_count ?? 0 }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus kategori ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-[12px] font-semibold">Hapus</button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Baris Sub-kategori --}}
                                @foreach ($cat->children as $child)
                                    <tr class="border-b border-[#E4E8EF] bg-gray-50/30 hover:bg-gray-50/80">
                                        <td class="pl-10 pr-5 py-2.5 text-[12.5px] text-gray-700 flex items-center gap-2">
                                            <span class="text-[#848CA3]">—</span>
                                            {{ $child->name }}
                                        </td>
                                        <td class="px-3 py-2.5 text-[11.5px] font-mono text-[#848CA3]">{{ $child->slug }}</td>
                                        <td class="px-3 py-2.5 text-[12px] text-center font-medium">{{ $child->articles_count ?? 0 }}</td>
                                        <td class="px-5 py-2.5 text-right">
                                            <form action="{{ route('admin.categories.destroy', $child->id) }}" method="POST" onsubmit="return confirm('Hapus sub-kategori ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 hover:text-rose-800 text-[12px] font-semibold">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-8 text-center text-[13px] text-[#848CA3]">
                                        Belum ada kategori yang ditambahkan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kartu List Tag --}}
            <div class="bg-white border border-[#E4E8EF] rounded-2xl overflow-hidden shadow-sm">
                <div class="px-5 py-4 border-b border-[#E4E8EF]">
                    <h2 class="text-[15px] font-bold text-gray-800">Daftar Tag</h2>
                    <p class="text-[12px] text-[#848CA3]">Label topik bebas untuk artikel berita.</p>
                </div>

                <div class="p-5 flex flex-wrap gap-2.5">
                    @forelse ($tags as $t)
                        <div class="inline-flex items-center gap-2 bg-[#F6F8FB] border border-[#E4E8EF] px-3 py-1.5 rounded-lg text-[12px]">
                            <span class="font-medium text-gray-700">#{{ $t->name }}</span>
                            <span class="text-[11px] text-[#848CA3]">({{ $t->articles_count ?? 0 }})</span>
                            <form action="{{ route('admin.tags.destroy', $t->id) }}" method="POST" onsubmit="return confirm('Hapus tag ini?');" class="inline ml-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[#848CA3] hover:text-rose-600 font-bold leading-none">&times;</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-[13px] text-[#848CA3]">Belum ada tag yang dibuat.</div>
                    @endforelse
                </div>
            </div>

        </div>
        {{-- Akhir Kolom Kanan --}}
    </div>
    {{-- Akhir Grid Utama --}}
</x-layouts.admin>