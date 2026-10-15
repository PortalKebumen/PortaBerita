{{-- Iklan sidebar kedua yang ikut menempel saat digulir, mengisi ruang kosong di bawah sidebar
     ketika kolom utama lebih panjang. Hanya desktop; tidak tampil bila iklan sidebar kurang dari dua. --}}
<div class="hidden lg:sticky lg:top-32 lg:block">
    <x-public.ad-slot placement="sidebar" :start="1" :placeholder="false" />
</div>
