<x-layouts.admin :title="$title" :breadcrumbs="[['label' => $title]]">
    <div class="card card-body flex flex-col items-center text-center py-16">
        <div class="w-14 h-14 rounded-full bg-[#F1F3F7] flex items-center justify-center mb-4">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="1.6">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>
            </svg>
        </div>
        <h2 class="text-[15px] font-bold mb-1.5">Halaman {{ $title }} belum tersedia</h2>
        <p class="text-[13px] text-[#6C7387] max-w-[360px]">Menu ini sedang dikerjakan pada task terpisah dan akan segera aktif.</p>
    </div>
</x-layouts.admin>