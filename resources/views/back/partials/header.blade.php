<header class="header">

    <div>
        <strong>Back Office</strong>
    </div>

    <div style="display:flex; align-items:center; gap:15px;">
        <span>{{ auth()->user()->name ?? 'Utilisateur' }}</span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" style="border:none; background:none; cursor:pointer;">
                <i class="fa-solid fa-right-from-bracket"></i>
            </button>
        </form>
    </div>

</header>