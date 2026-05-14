@extends('back.layouts.app')

@section('title', 'Mes notifications')

@section('content')
<div style="max-width: 1000px; margin: 0 auto; padding: 2rem 1.5rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.8rem; font-weight: 800; color: #0f172a; margin: 0;">
                <i class="fa-regular fa-bell"></i> Mes notifications
            </h1>
            <p style="color: #64748b; margin-top: 0.25rem;">Consultez toutes vos notifications</p>
        </div>
        
        <div style="display: flex; gap: 0.75rem;">
            <button id="markAllReadBtn" class="btn-secondary" style="padding: 0.5rem 1rem; border-radius: 8px; background: #f1f5f9; border: none; cursor: pointer;">
                <i class="fa-regular fa-check-circle"></i> Tout marquer comme lu
            </button>
            <a href="{{ route('dashboard') }}" class="btn-secondary" style="padding: 0.5rem 1rem; border-radius: 8px; background: #f1f5f9; color: #1e293b; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem;">
            ✅ {{ session('success') }}
        </div>
    @endif

    <div style="background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden;">
        @if($notifications->isEmpty())
            <div style="text-align: center; padding: 3rem;">
                <i class="fa-regular fa-bell-slash" style="font-size: 3rem; color: #cbd5e1;"></i>
                <p style="margin-top: 1rem; color: #64748b;">Aucune notification pour le moment</p>
            </div>
        @else
            <div style="border-bottom: 1px solid #e2e8f0; padding: 0.75rem 1rem; background: #f8fafc;">
                <div style="display: flex; gap: 1rem;">
                    <a href="{{ route('notifications.index') }}" style="color: #0053b3; font-weight: 600;">📋 Toutes</a>
                    <a href="{{ route('notifications.index', ['is_read' => 0]) }}" style="color: #64748b;">🟢 Non lues</a>
                    <a href="{{ route('notifications.index', ['is_read' => 1]) }}" style="color: #64748b;">✅ Lues</a>
                </div>
            </div>

            @foreach($notifications as $notif)
                @php
                    $typeStyles = [
                        'admin' => ['icon' => 'fa-solid fa-shield-halved', 'color' => '#8b5cf6'],
                        'info' => ['icon' => 'fa-solid fa-circle-info', 'color' => '#3b82f6'],
                        'success' => ['icon' => 'fa-solid fa-circle-check', 'color' => '#10b981'],
                        'warning' => ['icon' => 'fa-solid fa-triangle-exclamation', 'color' => '#f59e0b'],
                        'danger' => ['icon' => 'fa-solid fa-circle-exclamation', 'color' => '#ef4444'],
                    ];
                    $style = $typeStyles[$notif->type] ?? $typeStyles['info'];
                @endphp
                
                <div class="notification-item {{ !$notif->is_read ? 'unread' : '' }}" data-id="{{ $notif->id }}">
                    <div style="display: flex; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                        <div style="width: 44px; height: 44px; background: {{ $style['color'] }}15; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                            <i class="{{ $notif->icon ?? $style['icon'] }}" style="color: {{ $style['color'] }}; font-size: 1.2rem;"></i>
                        </div>
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.5rem;">
                                <h3 style="font-size: 1rem; font-weight: 700; margin: 0; color: {{ !$notif->is_read ? '#0f172a' : '#64748b' }}">
                                    {{ $notif->title }}
                                </h3>
                                <span style="font-size: 0.7rem; color: #94a3b8;">
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </div>
                            <p style="margin: 0.25rem 0 0; color: #475569; font-size: 0.85rem; line-height: 1.5;">
                                {{ $notif->message }}
                            </p>
                            @if($notif->link)
                                <a href="{{ $notif->link }}" style="display: inline-block; margin-top: 0.5rem; font-size: 0.75rem; color: #0053b3; text-decoration: none;">
                                    Voir plus <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            @endif
                        </div>
                        @if(!$notif->is_read)
                            <button class="mark-read-btn" data-id="{{ $notif->id }}" style="background: none; border: none; cursor: pointer; color: #94a3b8; padding: 0.25rem;">
                                <i class="fa-regular fa-circle-check"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
            
            <div style="padding: 1rem; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .notification-item:hover {
        background: #f8fafc;
    }
    .notification-item.unread {
        background: #eff6ff;
    }
    .mark-read-btn:hover {
        color: #10b981;
    }
    .btn-secondary:hover {
        background: #e2e8f0 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Marquer une notification comme lue
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const id = this.dataset.id;
                fetch('/notifications/' + id + '/mark-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    const item = this.closest('.notification-item');
                    item.classList.remove('unread');
                    this.remove();
                    // Décrémenter le compteur si nécessaire
                }).catch(err => console.error('Erreur:', err));
            });
        });
        
        // Marquer tout comme lu
        const markAllBtn = document.getElementById('markAllReadBtn');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function() {
                fetch('/notifications/mark-all-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    window.location.reload();
                }).catch(err => console.error('Erreur:', err));
            });
        }
        
        // Clic sur une notification (ouvrir le détail)
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.mark-read-btn')) return;
                const id = this.dataset.id;
                if (id) window.location.href = '/notifications/' + id;
            });
        });
    });
</script>
@endsection