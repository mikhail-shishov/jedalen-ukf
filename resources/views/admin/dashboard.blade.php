<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.84.0">
    <title>Dashboard</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.0/examples/dashboard/">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="{{ asset('admin-assets/styles.css') }}" rel="stylesheet">
</head>

<body>

    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">UKF</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse"
            data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="{{ route('logout') }}">Odhlasiť sa</a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-2 col-lg-1 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    @php
                        $currentUser = auth()->user();
                        $isAdminByFlag = (bool) ($currentUser?->is_admin ?? false);
                        $isAdminByRole = (int) ($currentUser?->role_id ?? 0) === 4;
                        $isAdmin = $isAdminByFlag || $isAdminByRole;
                        $isCook = (int) ($currentUser?->role_id ?? 0) === 3;
                    @endphp
                    <ul class="nav flex-column">
                        @if($isAdmin)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.dashboard') }}">
                                    Admin
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/orders') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.orders') }}">
                                    Objednavky
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/meals') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.meals') }}">
                                    Jedlá
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/menu') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.menu') }}">
                                    Denné menu
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/canteens') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.canteens') }}">
                                    Jedalne
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/articles') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.articles') }}">
                                    Blog
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/users') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.users') }}">
                                    Použivatelia
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/import') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.import') }}">
                                    Import CSV
                                </a>
                            </li>
                        @endif

                        @if($isAdmin || $isCook)
                            <li class="nav-item">
                                <a class="nav-link {{ request()->is('admin/cook') ? 'active fw-bold' : '' }}"
                                    href="{{ route('admin.cook') }}">
                                    Kuchyňa
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <main class="col-md-10 ms-sm-auto col-lg-11 px-md-4">
                @yield('admin_content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/admin/menu.js"></script>
</body>

</html>