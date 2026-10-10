@props(['article'])
<script type="application/ld+json">{!! json_encode(
    \App\Services\Seo\NewsArticleSchema::make($article),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PRETTY_PRINT
) !!}</script>