{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>{{ $siteName }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ $description }}</description>
    <language>fr-fr</language>
    <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml"/>
@foreach ($items as $item)
    <item>
        <title>{{ $item->title }}</title>
        <link>{{ $item->publicUrl() }}</link>
        <guid isPermaLink="true">{{ $item->publicUrl() }}</guid>
        <pubDate>{{ $item->displayDate()->toRfc2822String() }}</pubDate>
        <category>{{ $item->type->label() }}</category>
        <dc:creator xmlns:dc="http://purl.org/dc/elements/1.1/">{{ $item->displayAuthor() }}</dc:creator>
        <description>{{ $item->excerpt(400) }}</description>
    </item>
@endforeach
</channel>
</rss>
