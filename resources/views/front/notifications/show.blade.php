@extends('back.layouts.app')

@section('title', 'Détail de la notification')

@section('content')
<div style="max-width: 800px; margin: 0 auto; padding: 2rem 1.5rem;">
    
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('notifications.index') }}" style="color: #0053b3; text-decoration: none;">
            <i class="fa-solid fa-arrow-left"></i> Retour aux notifications
        </a>
    </div>

    @php
        $typeStyles = [
            'admin' => ['icon' => 'fa-solid fa-shield-halved', 'color' => '#8b5cf6', 'bg' => '#f3e8ff'],
            'info' => ['icon' => 'fa-solid fa-circle-info', 'color' => '#3b82f6', 'bg' => '#eff6ff'],
            'success' => ['icon' => 'fa-solid fa-circle-check', 'color' => '#10b981', 'bg' => '#ecfdf5'],
            'warning' => ['icon' => 'fa-solid fa-triangle-exclamation', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
            'danger' => ['icon' => 'fa-solid fa-circle-exclamation', 'color' => '#ef4444', 'bg' => '#fef2f2'],
        ];
        $style = $typeStyles[$notification->type] ?? $typeStyles['info'];
    @endphp

    <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
        <div style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; background: {{ $style['bg'] }};">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 56px; height: 56px; background: {{ $style['color'] }}20; border-radius: 16px; display: flex; align-items: center; justify-content: center;">
                    <i class="{{ $notification->icon ?? $style['icon'] }}" style="color: {{ $style['color'] }}; font-size: 1.5rem;"></i>
                </div>
                <div>
                    <h1 style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 0;">
                        {{ $notification->title }}
                    </h1>
                    <span style="font-size: 0.75rem; color: #94a3b8;">
                        {{ $notification->created_at->format('d/m/Y à H:i') }}
                    </span>
                </div>
            </div>
        </div>
        
        <div style="padding: 2rem;">
            <p style="font-size: 1rem; line-height: 1.6; color: #1e293b; white-space: pre-wrap;">
                {{ $notification->message }}
            </p>
            
            @if($notification->link)
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0;">
                    <a href="{{ $notification->link }}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; background: #0053b3; color: white; padding: 0.75rem 1.5rem; border-radius: 10px; text-decoration: none;">
                        En savoir plus <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection