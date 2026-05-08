<?php
if (!isset($adminActivePage)) {
    $adminActivePage = '';
}
?>
<header class="cyber-header">
    <div class="container">
        <nav class="cyber-nav">
            <a href="/" class="cyber-logo">
                <div class="logo-text">
                    <span class="logo-gold">LARK</span>
                    <span class="logo-light">FREELANCE</span>
                </div>
            </a>

            <div class="nav-hologram">
                <a href="/" class="nav-link">ГЛАВНАЯ</a>
                <a href="/admin/dashboard.php" class="<?= $adminActivePage === 'dashboard' ? 'nav-link active' : 'nav-link' ?>">ДАШБОРД</a>
                <a href="/admin/projects/index.php" class="<?= $adminActivePage === 'projects' ? 'nav-link active' : 'nav-link' ?>">ПРОЕКТЫ</a>
                <a href="/admin/cases.php" class="<?= $adminActivePage === 'cases' ? 'nav-link active' : 'nav-link' ?>">КЕЙСЫ</a>
                <a href="/admin/requests/developers.php" class="<?= $adminActivePage === 'developers' ? 'nav-link active' : 'nav-link' ?>">РАЗРАБОТЧИКИ</a>
                <a href="/admin/requests/clients.php" class="<?= $adminActivePage === 'clients' ? 'nav-link active' : 'nav-link' ?>">КЛИЕНТЫ</a>
                <a href="/admin/logout.php" class="nav-link admin-portal">
                    <i class="fas fa-sign-out-alt"></i> ВЫЙТИ
                </a>
            </div>

            <button class="cyber-menu-btn" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </div>
</header>
