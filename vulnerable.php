<?php
// File: poc/vulnerable.php
class VulnerableFileCache {
    private $cacheDir = __DIR__ . '/cache/';
    
    public function doGet($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        echo "[+] Tentando carregar: $file\n";
        if (file_exists($file)) {
            $data = file_get_contents($file);
            echo "[+] Desserializando...\n";
            return unserialize($data, ['allowed_classes' => true]);
        }
        return null;
    }
}

// Trigger
echo "=== FileCache Object Injection PoC ===\n";
$cache = new VulnerableFileCache();
$result = $cache->doGet('victim_key');
if ($result) {
    echo "[+] Objeto injetado com sucesso!\n";
}
?>