<?php
/**
 * 文件作用：异地备份加密工具：用一次性密钥分块加密备份，再用团队公钥封装密钥；也负责解密。
 * 作者：蒋林瀞_2413507
 */
/**
 * WorldCup Universe: public-key encryption for off-site backups (PHP sodium; same tool on the server and on Windows).
 *
 *   php scripts/backup-crypt.php keygen  <secret-key-out> <public-key-out>
 *   php scripts/backup-crypt.php encrypt <public-key> <plain-in> <cipher-out>
 *   php scripts/backup-crypt.php decrypt <secret-key> <cipher-out> <cipher-part> [<cipher-part> ...]
 *
 * The server only ever holds the PUBLIC key, so it can write backups but never read them; the SECRET key stays with the
 * team (e.g. %USERPROFILE%\.worldcup-universe\backup-secret.key). Format "WCUBK1":
 *   magic | crypto_box_seal(random 32-byte stream key, public key) | secretstream header | chunks
 * Each chunk is a 4-byte big-endian length plus XChaCha20-Poly1305 ciphertext of up to 1 MiB; the last chunk carries
 * TAG_FINAL. Any modified, missing, reordered or appended byte makes decryption fail instead of yielding bad data.
 * Split parts (git hosts limit file sizes) are decrypted by listing them in order.
 */
declare(strict_types=1);

const MAGIC = "WCUBK1\n";
const CHUNK = 1048576;

function fail(string $m): never { fwrite(STDERR, "ERROR: $m\n"); exit(1); }
function readKey(string $file, int $len, string $what): string {
    if (!is_file($file)) { fail("$what not found: $file"); }
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if ($line[0] === '#') { continue; }
        $raw = base64_decode(trim($line), true);
        if ($raw !== false && strlen($raw) === $len) { return $raw; }
    }
    fail("$what in $file is not a valid key");
}
/** Reads exactly $n bytes from a sequence of part files as if they were one stream. */
final class Parts {
    private int $i = 0; private $h = null;
    public function __construct(private array $files) { foreach ($files as $f) { if (!is_file($f)) { fail("missing part $f"); } } }
    public function read(int $n): string {
        $buf = '';
        while (strlen($buf) < $n) {
            if ($this->h === null) { if ($this->i >= count($this->files)) { break; } $this->h = fopen($this->files[$this->i++], 'rb'); }
            $chunk = fread($this->h, $n - strlen($buf));
            if ($chunk === '' || $chunk === false) { fclose($this->h); $this->h = null; continue; }
            $buf .= $chunk;
        }
        return $buf;
    }
}

if (!extension_loaded('sodium')) { fail('PHP extension sodium is required (enable extension=sodium in php.ini)'); }
[$cmd, $a, $b] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? ''];

if ($cmd === 'keygen') {
    if ($a === '' || $b === '') { fail('usage: keygen <secret-key-out> <public-key-out>'); }
    if (file_exists($a)) { fail("$a already exists; refusing to overwrite a secret key"); }
    $kp = sodium_crypto_box_keypair();
    $umask = umask(0077);
    file_put_contents($a, "# WorldCup Universe backup SECRET key. Anyone holding it can read every backup. Keep it private and keep a copy.\n" . base64_encode($kp) . "\n");
    umask($umask);
    file_put_contents($b, "# WorldCup Universe backup public key (safe to share; it can only encrypt).\n" . base64_encode(sodium_crypto_box_publickey($kp)) . "\n");
    echo "Secret key: $a\nPublic key: $b\n";
    exit(0);
}

if ($cmd === 'encrypt') {
    $c = $argv[4] ?? '';
    if ($c === '') { fail('usage: encrypt <public-key> <plain-in> <cipher-out>'); }
    $pk = readKey($a, SODIUM_CRYPTO_BOX_PUBLICKEYBYTES, 'public key');
    $in = fopen($b, 'rb') ?: fail("cannot read $b");
    $out = fopen($c, 'wb') ?: fail("cannot write $c");
    $key = sodium_crypto_secretstream_xchacha20poly1305_keygen();
    [$state, $header] = sodium_crypto_secretstream_xchacha20poly1305_init_push($key);
    fwrite($out, MAGIC . sodium_crypto_box_seal($key, $pk) . $header);
    sodium_memzero($key);
    $next = fread($in, CHUNK);
    do {
        $cur = $next === false ? '' : $next;
        $next = feof($in) ? '' : fread($in, CHUNK);
        $last = $next === '' || $next === false;
        $ct = sodium_crypto_secretstream_xchacha20poly1305_push($state, $cur, '', $last ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE);
        fwrite($out, pack('N', strlen($ct)) . $ct);
    } while (!$last);
    fclose($in); fclose($out);
    exit(0);
}

if ($cmd === 'decrypt') {
    $parts = array_slice($argv, 4);
    if ($b === '' || !$parts) { fail('usage: decrypt <secret-key> <plain-out> <cipher-part> [<cipher-part> ...]'); }
    $kp = readKey($a, SODIUM_CRYPTO_BOX_KEYPAIRBYTES, 'secret key');
    $src = new Parts($parts);
    if ($src->read(strlen(MAGIC)) !== MAGIC) { fail('not a WorldCup Universe encrypted backup (bad header)'); }
    $sealed = $src->read(SODIUM_CRYPTO_BOX_SEALBYTES + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES);
    $key = sodium_crypto_box_seal_open($sealed, $kp);
    if ($key === false) { fail('this backup was encrypted for a different key'); }
    $state = sodium_crypto_secretstream_xchacha20poly1305_init_pull($src->read(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES), $key);
    $tmp = $b . '.part';
    $out = fopen($tmp, 'wb') ?: fail("cannot write $b");
    $final = false;
    while (true) {
        $len = $src->read(4);
        if ($len === '') { break; }
        if (strlen($len) !== 4) { fail('truncated backup'); }
        $n = unpack('N', $len)[1];
        if ($final || $n > CHUNK + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES) { fail('corrupt backup (data after the end, or bad chunk size)'); }
        $ct = $src->read($n);
        $r = strlen($ct) === $n ? sodium_crypto_secretstream_xchacha20poly1305_pull($state, $ct) : false;
        if ($r === false) { fclose($out); unlink($tmp); fail('backup is damaged or was modified (authentication failed)'); }
        fwrite($out, $r[0]);
        $final = $r[1] === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
    }
    fclose($out);
    if (!$final) { unlink($tmp); fail('truncated backup (final chunk missing; are all parts present?)'); }
    rename($tmp, $b);
    exit(0);
}

fail('usage: keygen | encrypt | decrypt (see the header of this file)');
