<?php
namespace Core;

use PDO;

// PDO สำหรับฐาน MySQL ในเครื่อง (dev) — Models ทั้งหมดเขียนเป็น T-SQL (MSSQL) ตัวนี้แปล SQL เป็น MySQL ตอน
// prepare/query/exec ให้ ไม่ต้องแก้ Models สองชุด ครอบคลุมเฉพาะ syntax ที่โค้ดนี้ใช้จริง
// (dbo., SYSDATETIME/GETDATE, TOP n, ISNULL, DATEADD, DATEDIFF, CONVERT(VARCHAR..), N'..', hrtime.dbo.)
class LocalMysqlPdo extends PDO
{
    public function prepare(string $query, array $options = []): \PDOStatement|false
    {
        return parent::prepare(self::translate($query), $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$args): \PDOStatement|false
    {
        $query = self::translate($query);
        return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$args);
    }

    public function exec(string $statement): int|false
    {
        return parent::exec(self::translate($statement));
    }

    public static function translate(string $sql): string
    {
        $sql = preg_replace('/\bhrtime\.dbo\./i', '', $sql);
        $sql = preg_replace('/\bdbo\./i', '', $sql);
        $sql = preg_replace('/\b(SYSDATETIME|GETDATE)\(\)/i', 'NOW()', $sql);
        $sql = preg_replace("/\\bN'/", "'", $sql);
        $sql = preg_replace('/CAST\(\s*\?\s+AS\s+INT\s*\)/i', 'CAST(? AS SIGNED)', $sql);

        $sql = self::rewriteCalls($sql, 'ISNULL', fn(array $a) => 'IFNULL(' . implode(', ', $a) . ')');
        $sql = self::rewriteCalls($sql, 'DATEDIFF', fn(array $a) => "TIMESTAMPDIFF({$a[0]}, {$a[1]}, {$a[2]})");
        $sql = self::rewriteCalls($sql, 'DATEADD', fn(array $a) => "DATE_ADD({$a[2]}, INTERVAL {$a[1]} {$a[0]})");
        $sql = self::rewriteCalls($sql, 'CONVERT', function (array $a) {
            // CONVERT(VARCHAR(10), col, 120|23) → 'YYYY-MM-DD' (สไตล์เดียวที่โค้ดนี้ใช้)
            return "DATE_FORMAT({$a[1]}, '%Y-%m-%d')";
        });

        // SELECT TOP n / TOP (n) → LIMIT n ท้ายคำสั่ง
        if (preg_match('/\bSELECT\s+TOP\s*\(?\s*(\d+)\s*\)?/i', $sql, $m)) {
            $sql = preg_replace('/\bSELECT\s+TOP\s*\(?\s*\d+\s*\)?\s*/i', 'SELECT ', $sql, 1);
            $sql = rtrim($sql, " \t\r\n;") . ' LIMIT ' . $m[1];
        }
        return $sql;
    }

    // แทนที่ NAME(arg, arg, ...) โดยแยก argument ตามระดับวงเล็บ (รองรับ nested เช่น CAST(? AS DATE))
    private static function rewriteCalls(string $sql, string $name, callable $fn): string
    {
        $offset = 0;
        while (preg_match('/\b' . $name . '\s*\(/i', $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $m[0][1];
            $i = $start + strlen($m[0][0]);
            $depth = 1;
            $args = [];
            $cur = '';
            $quote = false;
            for ($len = strlen($sql); $i < $len; $i++) {
                $c = $sql[$i];
                if ($c === "'") $quote = !$quote;
                if (!$quote) {
                    if ($c === '(') $depth++;
                    elseif ($c === ')') {
                        $depth--;
                        if ($depth === 0) break;
                    } elseif ($c === ',' && $depth === 1) {
                        $args[] = trim($cur);
                        $cur = '';
                        continue;
                    }
                }
                $cur .= $c;
            }
            $args[] = trim($cur);
            $replacement = $fn($args);
            $sql = substr($sql, 0, $start) . $replacement . substr($sql, $i + 1);
            $offset = $start + strlen($replacement);
        }
        return $sql;
    }
}
