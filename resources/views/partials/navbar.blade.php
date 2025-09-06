<header class="site-nav">
    <div class="navbar-inner">
        <div class="nav-left">
        <a href="{{ url('/') }}" class="navbar-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Græsdal" onerror="this.style.display='none'">
        </a>
        </div>

        <nav class="nav-center" aria-label="Hovedmeny">
        <a href="{{ url('/prosjekter') }}"  class="nav-link {{ request()->is('prosjekter*')  ? 'active' : '' }}">Prosjekter</a>
        <a href="{{ url('/montering') }}"   class="nav-link {{ request()->is('montering*')   ? 'active' : '' }}">Montering</a>
        <a href="{{ url('/henting') }}"     class="nav-link {{ request()->is('henting*')     ? 'active' : '' }}">Henting</a>
        <a href="{{ url('/planlegging') }}" class="nav-link {{ request()->is('planlegging*') ? 'active' : '' }}">Planlegging</a>
        <a href="{{ url('/avvik') }}"       class="nav-link {{ request()->is('avvik*')       ? 'active' : '' }}">Avvik</a>
        </nav>

        <div class="nav-right">
        @auth
            @if (auth()->user()->role === 'admin')
            <a href="{{ route('admin.dashboard') }}"
                class="nav-link {{ request()->is('admin*') ? 'active' : '' }}">
                Admin
            </a>
            @endif

            <span>Logget som: {{ auth()->user()->name }}</span>

            <form method="POST" action="{{ route('logout') }}" class="inline" style="display:inline;">
            @csrf
            <button type="submit" class="nav-link as-button">Logg ut</button>
            </form>
        @endauth

        @guest
            <a href="{{ route('login') }}" class="nav-link">Logg inn</a>
        @endguest
        </div>
    </div>
</header>
