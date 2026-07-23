<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\View\Compilers\BladeCompiler;
use Symfony\Component\Finder\Finder;

/**
 * Prueft alle Blade-Views auf Fehler, die sich sonst erst als 500er auf der
 * Seite zeigen.
 *
 * Zwei Fehlerklassen werden abgedeckt:
 *  1. Der BladeCompiler wirft beim Kompilieren eine Exception.
 *  2. Das Kompilat ist syntaktisch kaputtes PHP. Das passiert schleichend,
 *     z.B. wenn ein Komponenten-Tag "<x-name>" ohne schliessendes Gegenstueck
 *     im Template steht – auch in einem JS-Kommentar innerhalb eines
 *     script-Blocks, denn der ComponentTagCompiler scannt das ganze Template.
 *     Der Compiler selbst meldet das nicht, erst PHP stolpert darueber.
 *
 * "php artisan view:cache" ersetzt diesen Befehl nicht: es kompiliert zwar,
 * prueft das Ergebnis aber nie auf PHP-Syntax.
 */
class LintBladeViews extends Command
{
    protected $signature = 'blade:lint
                            {path?* : Einzelne Views oder Verzeichnisse (Standard: alle View-Pfade)}';

    protected $description = 'Kompiliert alle Blade-Views und prueft das Ergebnis auf PHP-Syntaxfehler';

    public function handle(BladeCompiler $compiler): int
    {
        $files = $this->collectViews();

        if ($files === []) {
            $this->error('Keine Blade-Views gefunden.');

            return self::FAILURE;
        }

        $this->info('Pruefe '.count($files).' Blade-Views...');

        $failures = [];

        foreach ($files as $file) {
            if ($error = $this->lint($compiler, $file)) {
                $failures[] = [$this->relative($file), $error];
            }
        }

        $this->newLine();

        if ($failures === []) {
            $this->info('Alle Views sind in Ordnung.');

            return self::SUCCESS;
        }

        foreach ($failures as [$file, $error]) {
            $this->error($file);
            $this->line('  '.$error);
        }

        $this->newLine();
        $this->error(count($failures).' von '.count($files).' Views fehlerhaft.');

        return self::FAILURE;
    }

    /**
     * Kompiliert eine View und lintet das Kompilat. Gibt die Fehlermeldung
     * zurueck oder null, wenn alles passt.
     */
    protected function lint(BladeCompiler $compiler, string $file): ?string
    {
        try {
            $php = $compiler->compileString(file_get_contents($file));
        } catch (\Throwable $e) {
            return 'Kompilierfehler: '.class_basename($e).': '.$e->getMessage();
        }

        $tmp = tempnam(sys_get_temp_dir(), 'blade-lint-').'.php';
        file_put_contents($tmp, $php);

        try {
            // short_open_tag aus: sonst liest der Linter das "<?xml" der
            // Feed-Templates als PHP-Open-Tag und meldet einen Fehler, den es
            // in der Anwendung nicht gibt.
            $command = escapeshellarg(PHP_BINARY).' -l -d short_open_tag=0 '.escapeshellarg($tmp).' 2>&1';

            $output = [];
            $status = 0;
            exec($command, $output, $status);

            if ($status === 0) {
                return null;
            }

            // Pfad der Temp-Datei ist fuer den Leser wertlos, die Zeilennummer
            // bezieht sich ohnehin auf das Kompilat.
            $message = implode(' ', array_filter(array_map('trim', $output)));

            return str_replace($tmp, 'kompiliertes Template', $message);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * @return list<string>
     */
    protected function collectViews(): array
    {
        $arguments = $this->argument('path');

        $paths = $arguments !== []
            ? $arguments
            : array_merge(config('view.paths', []), [resource_path('views')]);

        $files = [];
        $directories = [];

        foreach (array_unique($paths) as $path) {
            if (is_file($path)) {
                $files[] = realpath($path);
            } elseif (is_dir($path)) {
                $directories[] = $path;
            } else {
                $this->warn("Pfad nicht gefunden, wird uebersprungen: {$path}");
            }
        }

        if ($directories !== []) {
            $finder = Finder::create()->files()->name('*.blade.php')->in($directories);

            foreach ($finder as $file) {
                $files[] = $file->getRealPath();
            }
        }

        $files = array_values(array_unique(array_filter($files)));
        sort($files);

        return $files;
    }

    protected function relative(string $file): string
    {
        return str_starts_with($file, base_path())
            ? ltrim(substr($file, strlen(base_path())), DIRECTORY_SEPARATOR)
            : $file;
    }
}
