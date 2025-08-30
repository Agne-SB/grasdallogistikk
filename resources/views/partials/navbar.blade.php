<header class="site-nav">
    <div class="navbar-inner">
        <div class="nav-left">
        <a href="{{ url('/') }}" class="navbar-brand">
            <img src="{{ asset('images/logo.png') }}" alt="Græsdal" onerror="this.style.display='none'">
            
        </a>
        </div>

        <nav class="nav-right">
        <a href="{{ url('/prosjekter') }}" class="nav-link {{ request()->is('prosjekter') ? 'active' : '' }}">Prosjekter</a>
        <a href="{{ url('/montering') }}"  class="nav-link {{ request()->is('montering')  ? 'active' : '' }}">Montering</a>
        <a href="{{ url('/henting') }}"    class="nav-link {{ request()->is('henting')    ? 'active' : '' }}">Henting</a>
        <a href="{{ url('/avvik') }}"    class="nav-link {{ request()->is('avvik')    ? 'active' : '' }}">Avvik</a>
        </nav>
    </div>
</header>
