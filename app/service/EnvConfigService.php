<?php

namespace app\service;

class EnvConfigService
{
    /** @var string */
    private $path;

    public function __construct()
    {
        $this->path = app()->getRootPath() . '.env';
    }

    public function all(): array
    {
        if (!is_file($this->path)) {
            throw new \RuntimeException('.env 文件不存在');
        }
        $values = [];
        $section = '';
        foreach (file($this->path, FILE_IGNORE_NEW_LINES) as $line) {
            $trim = trim($line);
            if (preg_match('/^\[([^]]+)\]$/', $trim, $match)) {
                $section = strtoupper($match[1]);
                continue;
            }
            if ($trim === '' || $trim[0] === '#' || $trim[0] === ';') continue;
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_.]*)\s*=\s*(.*)$/', $line, $match)) {
                $value = trim($match[2]);
                if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
                    $value = substr($value, 1, -1);
                }
                $key = strtoupper(($section ? $section . '.' : '') . $match[1]);
                $values[$key] = $value;
            }
        }
        return $values;
    }

    public function update(array $updates): void
    {
        if (!is_file($this->path) || !is_writable($this->path)) {
            throw new \RuntimeException('.env 文件不存在或不可写');
        }
        $normalized = [];
        foreach ($updates as $key => $value) {
            $key = strtoupper((string)$key);
            $value = (string)$value;
            if (strpos($value, "\r") !== false || strpos($value, "\n") !== false) {
                throw new \InvalidArgumentException($key . ' 不能包含换行符');
            }
            $normalized[$key] = $value;
        }

        $content = (string)file_get_contents($this->path);
        $eol = strpos($content, "\r\n") !== false ? "\r\n" : "\n";
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $section = '';
        foreach ($lines as $index => $line) {
            $trim = trim($line);
            if (preg_match('/^\[([^]]+)\]$/', $trim, $match)) {
                $section = strtoupper($match[1]);
                continue;
            }
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_.]*)\s*=/', $line, $match)) {
                $fullKey = strtoupper(($section ? $section . '.' : '') . $match[1]);
                if (array_key_exists($fullKey, $normalized)) {
                    $lines[$index] = $match[1] . ' = ' . $this->formatValue($normalized[$fullKey]);
                    unset($normalized[$fullKey]);
                }
            }
        }

        foreach ($normalized as $fullKey => $value) {
            $parts = explode('.', $fullKey, 2);
            if (count($parts) === 1) {
                array_unshift($lines, $parts[0] . ' = ' . $this->formatValue($value));
                continue;
            }
            list($targetSection, $key) = $parts;
            $insertAt = count($lines);
            $found = false;
            foreach ($lines as $index => $line) {
                if (preg_match('/^\[([^]]+)\]$/', trim($line), $match)) {
                    if ($found) { $insertAt = $index; break; }
                    $found = strtoupper($match[1]) === $targetSection;
                }
            }
            if (!$found) {
                if (end($lines) !== '') $lines[] = '';
                $lines[] = '[' . $targetSection . ']';
                $lines[] = $key . ' = ' . $this->formatValue($value);
            } else {
                array_splice($lines, $insertAt, 0, [$key . ' = ' . $this->formatValue($value)]);
            }
        }

        $result = implode($eol, $lines);
        if (substr($content, -strlen($eol)) === $eol && substr($result, -strlen($eol)) !== $eol) $result .= $eol;
        if (file_put_contents($this->path, $result, LOCK_EX) === false) {
            throw new \RuntimeException('.env 文件写入失败');
        }
    }

    private function formatValue(string $value): string
    {
        if ($value === '') return '';
        if (preg_match('/^[A-Za-z0-9_:.,\/\-]+$/', $value)) return $value;
        return '"' . addcslashes($value, "\\\"") . '"';
    }
}
