{{-- Pastille de statut, couleurs par jeton sémantique, lisible en clair et en sombre (cahier § 9.5). --}}
@props(['status', 'label' => null])
@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $text = $label ?? ($status instanceof \BackedEnum && method_exists($status, 'label') ? $status->label() : $value);
    $classes = match ($value) {
        'published', 'approved', 'active', 'accepted', 'confirmed', 'archived_ok' => 'bg-success/15 text-success',
        'scheduled', 'in_progress', 'pending', 'review' => 'bg-warning/20 text-warning-ink',
        'rejected', 'suspended', 'revoked', 'failed', 'unsubscribed' => 'bg-danger/15 text-danger',
        'new' => 'bg-accent-2/15 text-accent-2-ink',
        default => 'bg-surface-2 text-muted',
    };
@endphp
<span {{ $attributes->merge(['class' => 'badge '.$classes]) }}>{{ $text }}</span>
