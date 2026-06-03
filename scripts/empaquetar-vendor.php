<?php
/**
 * Empaqueta vendor/ para subir por FTP/cPanel (hosting compartido sin Composer).
 *
 * Uso local:
 *   composer install --no-dev --optimize-autoloader
 *   php scripts/empaquetar-vendor.php
 *
 * Luego suba deploy/vendor.zip al servidor y extráigalo en public_html/vendor/
 * (debe quedar public_html/vendor/autoload.php).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$vendorDir = $root . '/vendor';
$deployDir = $root . '/deploy';
$zipPath = $deployDir . '/vendor.zip';

if (!is_dir($vendorDir) || !file_exists($vendorDir . '/autoload.php')) {
    fwrite(STDERR, "ERROR: Ejecute primero: composer install --no-dev\n");
    exit(1);
}

if (!is_dir($deployDir)) {
    mkdir($deployDir, 0755, true);
}

if (file_exists($zipPath)) {
    unlink($zipPath);
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "ERROR: No se pudo crear {$zipPath}\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$added = 0;
foreach ($iterator as $file) {
    $path = $file->getPathname();
    $relative = 'vendor/' . substr($path, strlen($vendorDir) + 1);

    if (str_contains($path, '/.git/') || str_ends_with($path, '/.git')) {
        continue;
    }

    if ($file->isDir()) {
        $zip->addEmptyDir($relative);
    } else {
        $zip->addFile($path, $relative);
        $added++;
    }
}

$zip->close();

$sizeMb = round(filesize($zipPath) / 1024 / 1024, 1);
echo "OK: {$zipPath} ({$added} archivos, {$sizeMb} MB)\n";
echo "\nSubir a producción (cPanel / FTP):\n";
echo "  1. Suba deploy/vendor.zip a public_html/\n";
echo "  2. Extraiga el ZIP (creará public_html/vendor/)\n";
echo "  3. Verifique: public_html/vendor/autoload.php existe\n";
