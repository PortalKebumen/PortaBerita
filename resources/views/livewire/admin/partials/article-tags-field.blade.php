<div class="mb-5">
    <label class="form-label">Tag</label>
    <input type="text" wire:model.blur="tagInput" list="article-tag-suggestions" placeholder="Contoh: Prameks, Kereta Api, Stasiun Kebumen"
        class="form-input @error('tagInput') is-error @enderror">
    <datalist id="article-tag-suggestions">
        @foreach ($this->tagSuggestions as $name)
            <option value="{{ $name }}"></option>
        @endforeach
    </datalist>
    <p class="text-[11px] text-[#848CA3] mt-1">Pisahkan dengan koma, maksimal 10. Tag yang belum ada dibuat otomatis. Pakai nama topik yang konsisten agar beritanya terkumpul di satu halaman tag.</p>
    @error('tagInput') <p class="form-error-text">{{ $message }}</p> @enderror
</div>
