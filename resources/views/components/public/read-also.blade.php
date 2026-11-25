@props(['articles'])

{{-- Disisipkan di tengah isi artikel (setelah paragraf ke-3), gaya sederhana seperti portal berita pada umumnya --}}
@foreach ($articles as $item)
    <p class="read-also"><strong>Baca juga:</strong> <a href="{{ $item->publicUrl() }}">{{ $item->title }}</a></p>
@endforeach
