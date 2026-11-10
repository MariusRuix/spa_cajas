<?php
/**
 * Genera los paquetes de despliegue para Hostinger en deploy/:
 *
 *   deploy/public_html.zip  → extraer DENTRO de public_html/
 *   deploy/royal-vault.zip  → extraer DENTRO de royal-vault/ (al lado de public_html)
 *
 * Solo incluye archivos que se pueden reemplazar sin riesgo. NUNCA incluye:
 *   - app/config.local.php           (claves y ajustes de cada servidor)
 *   - storage/*.sqlite, *.log         (base de datos y registros)
 *   - public/uploads/* (salvo .htaccess)  (imágenes subidas desde el panel)
 *
 * Uso (en Laragon):  php tools/build-deploy.php      o doble clic en deploy.bat
 */
declare(strict_types=1);

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "Falta la extensión zip de PHP (actívala en php.ini: extension=zip).\n");
    exit(1);
}

$root = dirname(__DIR__);
$out  = $root . '/deploy';
if (!is_dir($out)) {
    mkdir($out, 0775, true);
}

/** Archivos que nunca se deben desplegar (rutas relativas al ZIP, con /). */
$neverDeploy = function (string $zipPath, string $package): bool {
    $name = basename($zipPath);
    if ($package === 'royal-vault') {
        if ($zipPath === 'app/config.local.php') return true;
        if (str_starts_with($zipPath, 'storage/') && !in_array($name, ['.htaccess', '.gitignore'], true)) return true;
    }
    if ($package === 'public_html') {
        if (str_starts_with($zipPath, 'uploads/') && $zipPath !== 'uploads/.htaccess') return true;
    }
    return in_array($name, ['.DS_Store', 'Thumbs.db', 'desktop.ini'], true);
};

$build = function (string $zipFile, string $base, array $folders, string $package) use ($neverDeploy): void {
    $tmp = $zipFile . '.tmp';
    @unlink($tmp);
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException("No se pudo crear $tmp");
    }
    $count = 0;
    foreach ($folders as $folder) {
        $dir = $folder === '.' ? $base : $base . '/' . $folder;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if (!$file->isFile()) continue;
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            if ($neverDeploy($rel, $package)) continue;
            $zip->addFile($file->getPathname(), $rel);
            $count++;
        }
    }
    $zip->close();
    @unlink($zipFile);
    rename($tmp, $zipFile);
    printf("  %-22s %3d archivos  %6.1f KB\n", basename($zipFile), $count, filesize($zipFile) / 1024);
};

echo "Generando paquetes de despliegue…\n";
$build($out . '/public_html.zip', $root . '/public', ['.'], 'public_html');
$build($out . '/royal-vault.zip', $root, ['app', 'lib', 'storage'], 'royal-vault');
echo "\nListo. Sube y extrae (reemplazando) en Hostinger:\n";
echo "  public_html.zip → dentro de public_html/\n";
echo "  royal-vault.zip → dentro de royal-vault/\n";
echo "Tu config.local.php, la base de datos y las imágenes del servidor no se tocan.\n";
