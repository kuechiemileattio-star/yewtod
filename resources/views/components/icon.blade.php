{{-- Jeu d'icônes SVG inline (aucune requête externe, aucune police d'icônes). --}}
@props(['name', 'class' => 'size-5'])
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@switch($name)
    @case('flask')<path d="M9 3h6M10 3v6L4.5 18.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>@break
    @case('pen')<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>@break
    @case('mic')<rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5M8 22h8"/>@break
    @case('home')<path d="M3 11 12 4l9 7"/><path d="M5 10v10h14V10"/>@break
    @case('report')<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h6"/>@break
    @case('article')<path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8M8 17h5"/>@break
    @case('chart')<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>@break
    @case('stack')<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>@break
    @case('book')<path d="M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2z"/><path d="M4 19V5"/>@break
    @case('mail')<path d="M3 5h18v14H3z"/><path d="m3 6 9 7 9-7"/>@break
    @case('shield')<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6z"/><path d="m9 12 2 2 4-4"/>@break
    @case('image')<path d="M3 5h18v14H3z"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-9 7"/>@break
    @case('send')<path d="m22 2-11 11M22 2l-7 20-4-9-9-4z"/>@break
    @case('users')<circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-6 7-6s7 2.5 7 6"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14c2.5.7 4 2.8 4 6"/>@break
    @case('page')<path d="M6 3h12v18H6z"/><path d="M9 7h6M9 11h6"/>@break
    @case('cog')<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>@break
    @case('list')<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>@break
    @case('user')<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>@break
    @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
    @case('close')<path d="M6 6l12 12M18 6 6 18"/>@break
    @case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>@break
    @case('moon')<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>@break
    @case('monitor')<path d="M3 4h18v12H3zM8 20h8M12 16v4"/>@break
    @case('logout')<path d="M9 21H5V3h4M16 17l5-5-5-5M21 12H9"/>@break
    @case('plus')<path d="M12 5v14M5 12h14"/>@break
    @case('search')<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>@break
    @case('trash')<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>@break
    @case('edit')<path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>@break
    @case('eye')<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>@break
    @case('download')<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>@break
    @case('share')<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>@break
    @case('arrow-up')<path d="M12 19V5M5 12l7-7 7 7"/>@break
    @case('arrow-down')<path d="M12 5v14M5 12l7 7 7-7"/>@break
    @case('arrow-right')<path d="M5 12h14M12 5l7 7-7 7"/>@break
    @case('quote')<path d="M7 7h4v4c0 3-2 5-4 6M15 7h4v4c0 3-2 5-4 6"/>@break
    @case('external')<path d="M14 3h7v7M21 3 10 14M19 14v7H3V5h7"/>@break
    @case('check')<path d="m5 12 5 5 9-10"/>@break
    @case('upload')<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>@break
    @case('arrow-up-right')<path d="M7 17 17 7M8 7h9v9"/>@break
    @case('compass')<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5z"/>@break
    @case('network')<rect x="9" y="3" width="6" height="5" rx="1"/><rect x="3" y="16" width="6" height="5" rx="1"/><rect x="15" y="16" width="6" height="5" rx="1"/><path d="M12 8v4M6 16v-2h12v2"/>@break
    @case('lightbulb')<path d="M9 18h6M10 21h4M12 3a6 6 0 0 0-3.5 10.9c.6.5 1 1.2 1 2V16h5v-.1c0-.8.4-1.5 1-2A6 6 0 0 0 12 3z"/>@break
    @case('play')<path d="M7 5v14l12-7z"/>@break
    @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>@break
    @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
    @default<circle cx="12" cy="12" r="9"/>
@endswitch
</svg>
