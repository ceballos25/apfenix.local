<?php
// Verificar si el usuario está logueado
$isLoggedIn = isset($_SESSION['user_id']);
$userRole   = $_SESSION['user_role'] ?? 'administrador';
$isAdmin    = ($userRole === 'administrador');
$isVendedor = ($userRole === 'vendedor');

$dashboardLink = $isVendedor ? 'dashboard-vendedor.php' : 'dashboard.php';

// Helper para marcar activo por página
$currentPage = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

function isActive($fileName, $currentPage) {
  return $currentPage === $fileName ? 'active' : '';
}
function isOpen($files, $currentPage) {
  return in_array($currentPage, $files) ? 'in' : '';
}
?>

<aside class="left-sidebar">
  <div class="sidebar-inner">
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="<?= $dashboardLink ?>" class="text-nowrap logo-img" style="display:flex; justify-content:center; width:100%;">
        <img  style="width:80%; margin-top:10px;" class="d-flex" src="<?= ASSETS_URL ?>/images/logos/logo.jpg" alt="<?php echo SITE_NAME; ?>" />
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
      </div>
    </div>

    <nav class="sidebar-nav scroll-sidebar">
      <ul id="sidebarnav">

        <!-- PRINCIPAL -->
        <li class="nav-small-cap">
          <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
          <span class="hide-menu">Principal</span>
        </li>

        <?php if ($isVendedor): ?>
        <!-- MENÚ VENDEDOR -->
        <li class="sidebar-item <?= isActive('dashboard-vendedor.php', $currentPage); ?>">
          <a class="sidebar-link" href="dashboard-vendedor.php">
            <i class="ti ti-chart-bar"></i>
            <span class="hide-menu">Mi Dashboard</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('vender.php', $currentPage); ?>">
          <a class="sidebar-link" href="vender.php">
            <i class="ti ti-shopping-cart"></i>
            <span class="hide-menu">Vender</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('ventas.php', $currentPage); ?>">
          <a class="sidebar-link" href="ventas.php">
            <i class="ti ti-receipt"></i>
            <span class="hide-menu">Mis Ventas</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('ventas-chat.php', $currentPage); ?>">
          <a class="sidebar-link" href="ventas-chat.php">
            <i class="ti ti-brand-whatsapp"></i>
            <span class="hide-menu">Ventas Agente IA</span>
          </a>
        </li>

        <?php else: ?>
        <!-- MENÚ ADMINISTRADOR -->
        <li class="sidebar-item <?= isActive('dashboard.php', $currentPage); ?>">
          <a class="sidebar-link" href="dashboard.php">
            <i class="ti ti-home"></i>
            <span class="hide-menu">Dashboard</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('vender.php', $currentPage); ?>">
          <a class="sidebar-link" href="vender.php">
            <i class="ti ti-shopping-cart"></i>
            <span class="hide-menu">Vender</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('transferencias.php', $currentPage); ?>">
          <a class="sidebar-link" href="transferencias.php">
            <i class="ti ti-building-bank"></i>
            <span class="hide-menu">Transferencias</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('ventas-chat.php', $currentPage); ?>">
          <a class="sidebar-link" href="ventas-chat.php">
            <i class="ti ti-brand-whatsapp"></i>
            <span class="hide-menu">Ventas Agente IA</span>
          </a>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
          <span class="hide-menu">Terceros</span>
        </li>
        <li class="sidebar-item <?= isActive('clientes.php', $currentPage); ?>">
          <a class="sidebar-link" href="clientes.php">
            <i class="ti ti-users"></i>
            <span class="hide-menu">Clientes</span>
          </a>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
          <span class="hide-menu">Ventas e informes</span>
        </li>

        <?php
          $prodPages = ['ventas.php', 'numeros-vendidos.php', 'numeros.php'];
        ?>
        <li class="sidebar-item">
          <a class="sidebar-link justify-content-between has-arrow" href="javascript:void(0)" aria-expanded="false">
            <div class="d-flex align-items-center gap-3">
              <span class="d-flex"><i class="ti ti-box"></i></span>
              <span class="hide-menu">Ventas & Números</span>
            </div>
          </a>
          <ul aria-expanded="false" class="collapse first-level <?= isOpen($prodPages, $currentPage); ?>">
            <li class="sidebar-item <?= isActive('ventas.php', $currentPage); ?>">
              <a class="sidebar-link" href="ventas.php">
                <div class="round-16 d-flex align-items-center justify-content-center"><i class="ti ti-circle"></i></div>
                <span class="hide-menu">Ventas</span>
              </a>
            </li>
            <li class="sidebar-item <?= isActive('numeros-vendidos.php', $currentPage); ?>">
              <a class="sidebar-link" href="numeros-vendidos.php">
                <div class="round-16 d-flex align-items-center justify-content-center"><i class="ti ti-circle"></i></div>
                <span class="hide-menu">Números Vendidos</span>
              </a>
            </li>
            <li class="sidebar-item <?= isActive('numeros.php', $currentPage); ?>">
              <a class="sidebar-link" href="numeros.php">
                <div class="round-16 d-flex align-items-center justify-content-center"><i class="ti ti-circle"></i></div>
                <span class="hide-menu">Números</span>
              </a>
            </li>
          </ul>
        </li>

        <li><span class="sidebar-divider lg"></span></li>

        <li class="nav-small-cap">
          <iconify-icon icon="solar:menu-dots-linear" class="nav-small-cap-icon fs-4"></iconify-icon>
          <span class="hide-menu">Configuración</span>
        </li>
        <li class="sidebar-item <?= isActive('rifas.php', $currentPage); ?>">
          <a class="sidebar-link" href="rifas.php">
            <i class="ti ti-ticket"></i>
            <span class="hide-menu">Rifas</span>
          </a>
        </li>
        <li class="sidebar-item <?= isActive('vendedores.php', $currentPage); ?>">
          <a class="sidebar-link" href="vendedores.php">
            <i class="ti ti-id-badge"></i>
            <span class="hide-menu">Vendedores</span>
          </a>
        </li>
        <?php endif; ?>

        <li><span class="sidebar-divider lg"></span></li>

      </ul>
    </nav>
  </div>
</aside>
