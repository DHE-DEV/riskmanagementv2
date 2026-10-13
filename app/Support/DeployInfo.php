<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Stand des laufenden Servers: Branch, Commit und Deploy-Zeit – fuer
 * GET /v1/app-info, damit Apps zeigen koennen, gegen welchen Stand sie laufen.
 *
 * Gelesen aus .git (ohne Shell); auf dem Server koennen APP_DEPLOY_COMMIT,
 * APP_DEPLOY_BRANCH und APP_DEPLOYED_AT gesetzt werden (config app.deploy).
 * Deploy-Zeit sonst: Aenderungszeit des Release-Verzeichnisses bzw. des
 * Konfigurations-Caches. Je Release eine Stunde zwischengespeichert.
 */
class DeployInfo
{
    /**
     * @return array{branch: ?string, commit: ?string, commit_short: ?string, deployed_at: ?string, environment: string}
     */
    public static function get(): array
    {
        return Cache::remember('deploy-info:'.md5((string) realpath(base_path())), now()->addHour(), fn () => self::read());
    }

    /**
     * @return array{branch: ?string, commit: ?string, commit_short: ?string, deployed_at: ?string, environment: string}
     */
    public static function read(): array
    {
        [$branch, $commit] = self::fromGit(base_path('.git'));

        $branch = config('app.deploy.branch') ?: $branch;
        $commit = config('app.deploy.commit') ?: $commit;
        $commit = is_string($commit) && preg_match('/^[0-9a-f]{7,40}$/i', $commit) ? strtolower($commit) : null;

        return [
            'branch' => $branch ?: null,
            'commit' => $commit,
            'commit_short' => $commit ? substr($commit, 0, 8) : null,
            'deployed_at' => self::deployedAt()?->toIso8601String(),
            'environment' => (string) app()->environment(),
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string} Branch und Commit
     */
    protected static function fromGit(string $git): array
    {
        // Worktree o. Ae.: .git ist eine Datei mit "gitdir: …".
        if (is_file($git) && preg_match('/^gitdir:\s*(.+)$/m', (string) @file_get_contents($git), $match)) {
            $git = str_starts_with(trim($match[1]), '/') ? trim($match[1]) : dirname($git).'/'.trim($match[1]);
        }

        $head = trim((string) @file_get_contents($git.'/HEAD'));

        if ($head === '') {
            return [null, null];
        }

        if (! str_starts_with($head, 'ref: ')) {
            return [null, $head];
        }

        $ref = substr($head, 5);
        $branch = str_starts_with($ref, 'refs/heads/') ? substr($ref, 11) : $ref;
        $commit = trim((string) @file_get_contents($git.'/'.$ref));

        if ($commit === '' && is_file($git.'/packed-refs')) {
            foreach (file($git.'/packed-refs', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (str_ends_with($line, ' '.$ref)) {
                    $commit = strtok($line, ' ');
                    break;
                }
            }
        }

        return [$branch, $commit ?: null];
    }

    protected static function deployedAt(): ?Carbon
    {
        if ($at = config('app.deploy.deployed_at')) {
            return rescue(fn () => Carbon::parse($at), null, false);
        }

        $times = array_filter([
            @filemtime(base_path('bootstrap/cache/config.php')),
            @filemtime(base_path('vendor/autoload.php')),
        ]);

        return $times ? Carbon::createFromTimestamp(max($times)) : null;
    }
}
