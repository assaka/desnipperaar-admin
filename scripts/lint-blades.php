<?php

/**
 * Compileert elke blade en haalt php -l over het resultaat.
 *
 * `artisan view:cache` zegt niets over de juistheid: het schrijft de
 * gecompileerde PHP weg zonder hem te lezen. Een blade die naar kapotte PHP
 * compileert komt daar vrolijk doorheen en valt pas om als iemand de pagina
 * opvraagt of de mail verstuurt. Precies dat gebeurde met @php(...), dat hier
 * niet betrouwbaar compileert.
 *
 *   php scripts/lint-blades.php
 *
 * Bewust zonder de applicatie op te starten. Alleen de Blade-compiler is nodig,
 * en zo draait dit ook op een machine zonder database.
 *
 * Exitcode 1 zodra er iets kapot is, zodat een hook of CI erop kan afgaan.
 */

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

$views = __DIR__ . '/../resources/views';
$compiler = new BladeCompiler(new Filesystem, sys_get_temp_dir());

$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($views));

$stuk = [];
$aantal = 0;

foreach ($iter as $bestand) {
    if (! $bestand->isFile() || ! str_ends_with($bestand->getFilename(), '.blade.php')) {
        continue;
    }

    $aantal++;
    $php = $compiler->compileString(file_get_contents($bestand->getPathname()));

    $tmp = tempnam(sys_get_temp_dir(), 'blade') . '.php';
    file_put_contents($tmp, $php);

    $uit = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $uit, $code);
    @unlink($tmp);

    if ($code !== 0) {
        $melding = preg_replace('/ in .*$/', '', (string) ($uit[0] ?? 'parse error'));
        $pad = str_replace([realpath($views) . DIRECTORY_SEPARATOR, '\\'], ['', '/'], $bestand->getPathname());
        $stuk[] = $pad . '  ' . trim($melding);
    }
}

echo $aantal . " blades gecontroleerd\n";

if ($stuk === []) {
    echo "alles compileert naar geldige PHP\n";
    exit(0);
}

echo count($stuk) . " kapot:\n";
foreach ($stuk as $regel) {
    echo '  ' . $regel . "\n";
}
exit(1);
