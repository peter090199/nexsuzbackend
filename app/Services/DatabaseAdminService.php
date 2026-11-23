<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Master-admin database tooling: backups, server info, storage usage, table maintenance.
 *
 * Deliberately limited to safe operations. There is no restore, truncate, drop or free-form SQL here.
 * Backups are written to storage/app/backups (never a public path) because they contain personal data.
 */
class DatabaseAdminService
{
    public const MAX_TABLES_PER_ACTION = 25;

    public static function backupDir(): string
    {
        $dir = storage_path('app/backups');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir;
    }

    // ----------------------------------------------------------------- server

    public function serverInfo(): array
    {
        $start = microtime(true);
        $db = DB::selectOne('select version() as version, database() as name, @@sql_mode as sql_mode');
        $latency = round((microtime(true) - $start) * 1000, 1);
        $uptime = DB::selectOne("show global status like 'Uptime'");
        $threads = DB::selectOne("show global status like 'Threads_connected'");

        return [
            'database' => [
                'name' => $db->name, 'version' => $db->version, 'connection' => config('database.default'),
                'latency_ms' => $latency, 'uptime_seconds' => (int) ($uptime->Value ?? 0), 'connections' => (int) ($threads->Value ?? 0),
            ],
            'app' => [
                'laravel' => app()->version(), 'php' => PHP_VERSION, 'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'), 'timezone' => config('app.timezone'),
                'cache_driver' => config('cache.default'), 'queue_driver' => config('queue.default'),
                'memory_limit' => ini_get('memory_limit'), 'max_execution_time' => ini_get('max_execution_time'),
            ],
            'server' => [
                'os' => PHP_OS_FAMILY, 'server_time' => now()->toDateTimeString(),
                'extensions' => ['zlib' => extension_loaded('zlib'), 'pdo_mysql' => extension_loaded('pdo_mysql'), 'gd' => extension_loaded('gd'),
                    'mbstring' => extension_loaded('mbstring'), 'openssl' => extension_loaded('openssl')],
            ],
        ];
    }

    // ---------------------------------------------------------------- storage

    public function storageInfo(): array
    {
        $root = storage_path();
        $total = @disk_total_space($root) ?: 0;
        $free = @disk_free_space($root) ?: 0;

        // folder sizes walk the disk, so cache them briefly
        $folders = Cache::remember('dbadmin_storage_folders', 60, function () {
            $map = ['Uploads (public)' => storage_path('app/public'), 'Private files' => storage_path('app/local'),
                'Backups' => self::backupDir(), 'Logs' => storage_path('logs'), 'Framework cache' => storage_path('framework')];
            $out = [];
            foreach ($map as $label => $path) {
                [$bytes, $files] = is_dir($path) ? $this->dirSize($path) : [0, 0];
                $out[] = ['label' => $label, 'bytes' => $bytes, 'files' => $files];
            }
            return $out;
        });

        $db = DB::selectOne('select coalesce(sum(data_length + index_length), 0) as bytes from information_schema.tables where table_schema = database()');

        return [
            'disk' => ['total' => (int) $total, 'free' => (int) $free, 'used' => (int) max(0, $total - $free),
                'used_percent' => $total ? round((($total - $free) / $total) * 100, 1) : 0],
            'database_bytes' => (int) $db->bytes,
            'folders' => $folders,
        ];
    }

    /** @return array{0:int,1:int} bytes, file count (capped so a huge tree cannot hang the request) */
    private function dirSize(string $path, int $limit = 200000): array
    {
        $bytes = 0; $files = 0;
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile()) { $bytes += $f->getSize(); $files++; }
            if ($files >= $limit) { break; }
        }
        return [$bytes, $files];
    }

    // ----------------------------------------------------------------- tables

    public function tables(): array
    {
        return array_map(fn ($t) => [
            'name' => $t->name, 'engine' => $t->engine, 'rows' => (int) $t->table_rows,
            'data_bytes' => (int) $t->data_length, 'index_bytes' => (int) $t->index_length, 'free_bytes' => (int) $t->data_free,
            'total_bytes' => (int) $t->data_length + (int) $t->index_length,
            'collation' => $t->table_collation,
        ], DB::select('select table_name as name, engine as engine, table_rows as table_rows, data_length as data_length,
            index_length as index_length, data_free as data_free, table_collation as table_collation
            from information_schema.tables where table_schema = database() and table_type = "BASE TABLE" order by (data_length + index_length) desc'));
    }

    /** @return string[] real base-table names, the only values ever allowed into a maintenance statement */
    public function tableNames(): array
    {
        return array_map(fn ($t) => $t->name, DB::select('select table_name as name from information_schema.tables where table_schema = database() and table_type = "BASE TABLE"'));
    }

    /** OPTIMIZE or ANALYZE the given tables. Names are checked against the real table list first. */
    public function maintain(string $action, array $tables): array
    {
        $allowed = array_flip($this->tableNames());
        $results = [];
        foreach (array_slice(array_values(array_unique($tables)), 0, self::MAX_TABLES_PER_ACTION) as $t) {
            if (!isset($allowed[$t])) { continue; }
            $sql = ($action === 'optimize' ? 'OPTIMIZE' : 'ANALYZE') . ' TABLE `' . str_replace('`', '``', $t) . '`';
            $rows = DB::select($sql);
            $last = end($rows);
            $results[] = ['table' => $t, 'status' => $last->Msg_type ?? 'status', 'message' => $last->Msg_text ?? 'OK'];
        }
        return $results;
    }

    // ---------------------------------------------------------------- backups

    public function listBackups(): array
    {
        $out = [];
        foreach (glob(self::backupDir() . '/db-*.sql.gz') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'bytes' => filesize($f), 'created_at' => date('Y-m-d H:i:s', filemtime($f))];
        }
        usort($out, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']) ?: strcmp($b['name'], $a['name']));
        return $out;
    }

    /** Backup names are generated by us; anything else (paths, dots, slashes) is refused. */
    public function backupPath(string $name): ?string
    {
        if (!preg_match('/^db-\d{8}-\d{6}(-[a-z0-9]{4})?\.sql\.gz$/', $name)) {
            return null;
        }
        $path = self::backupDir() . DIRECTORY_SEPARATOR . $name;
        return is_file($path) ? $path : null;
    }

    /** Writes a gzip'd SQL dump (schema + data, plain PHP so no mysqldump binary is needed). */
    public function createBackup(): array
    {
        @set_time_limit(300);
        $name = 'db-' . date('Ymd-His') . '-' . strtolower(\Illuminate\Support\Str::random(4)) . '.sql.gz';
        $final = self::backupDir() . DIRECTORY_SEPARATOR . $name;
        $part = $final . '.part';
        $gz = gzopen($part, 'wb6');
        if (!$gz) {
            throw new \RuntimeException('Cannot write the backup file.');
        }

        try {
            $pdo = DB::connection()->getPdo();
            $dbName = DB::selectOne('select database() as d')->d;
            gzwrite($gz, "-- Nexsuz database backup\n-- Database: {$dbName}\n-- Created: " . now()->toDateTimeString() . "\n\n"
                . "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            foreach ($this->tableNames() as $table) {
                $q = '`' . str_replace('`', '``', $table) . '`';
                $create = (array) DB::selectOne("SHOW CREATE TABLE {$q}");
                gzwrite($gz, "-- ----------------------------\n-- Table: {$table}\n-- ----------------------------\nDROP TABLE IF EXISTS {$q};\n"
                    . ($create['Create Table'] ?? '') . ";\n\n");

                $offset = 0; $size = 500;
                while (true) {
                    $rows = DB::select("SELECT * FROM {$q} LIMIT {$size} OFFSET {$offset}");
                    if (!$rows) { break; }
                    $values = [];
                    foreach ($rows as $row) {
                        $values[] = '(' . implode(',', array_map(function ($v) use ($pdo) {
                            if ($v === null) { return 'NULL'; }
                            if (is_int($v) || is_float($v)) { return (string) $v; }
                            return $pdo->quote((string) $v);
                        }, (array) $row)) . ')';
                    }
                    gzwrite($gz, "INSERT INTO {$q} VALUES\n" . implode(",\n", $values) . ";\n");
                    $offset += $size;
                    if (count($rows) < $size) { break; }
                }
                gzwrite($gz, "\n");
            }
            gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n-- End of backup\n");
            gzclose($gz);
            rename($part, $final);
        } catch (\Throwable $e) {
            @gzclose($gz);
            @unlink($part);
            throw $e;
        }

        return ['name' => $name, 'bytes' => filesize($final), 'created_at' => date('Y-m-d H:i:s', filemtime($final))];
    }
}
