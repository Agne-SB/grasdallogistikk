<header class="admin-topbar" role="banner">
    <div class="admin-topbar__inner">
        <a href="{{ route('admin.users.index') }}" class="admin-brand">Admin</a>

        <nav class=" admin-topbar__center admin-nav" aria-label="Admin">
        
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                Brukere
            </a>
            <a href="{{ route('admin.closed.index') }}" class="{{ request()->is('admin/closed-projects*') ? 'active' : '' }}">
                Lukkede prosjekter
            </a>
            <a href="{{ url('/') }}">Forside</a>
        </nav>

        <div class="admin-topbar__right">

            <span>Logget som: {{ auth()->user()->name }}</span>

            <form method="POST" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button type="submit" class="linklike">Logg ut</button>
            </form>
            </div>
        </div>
    </div>
</header>
