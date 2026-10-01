<?php
// Isolated regression tests: exercise real tool callbacks without loading WP admin.
define('ABSPATH', __DIR__ . '/');
class WP_Error {
    public function __construct(public string $code, public string $message) {}
}
function is_wp_error($v) { return $v instanceof WP_Error; }
function current_user_can($cap) { return true; }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function sanitize_file_name($v) { return basename($v); }
function wp_delete_file($p) { if (is_file($p)) unlink($p); }
function wp_get_attachment_url($id) { return 'https://example.test/image.png'; }
function get_attached_file($id) { return '/uploads/image.png'; }
function esc_url_raw($url) { return $url; }
function wp_safe_remote_get($url, $args) { return ['code'=>200, 'body'=>'fixture']; }
function wp_remote_retrieve_response_code($r) { return $r['code']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
class Cowboy_MCP_Tools { static function tool(...$args) { return []; } }
class Cowboy_MCP_Security {
    static function power_mode_enabled() { return false; }
    static function is_protected_storage_path($p) { return false; }
    static function validate_url_ssrf($url) { return true; }
}
class Cowboy_MCP_Compat {
    static string $base;
    static function content_dir() { return self::$base; }
    static function handle_sideload($file, $parent) {
        if (file_get_contents($file['tmp_name']) !== 'fixture') throw new RuntimeException('Temporary payload mismatch');
        wp_delete_file($file['tmp_name']);
        return 10;
    }
}
function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: $message\n";
}
$media = require __DIR__ . '/../includes/tools/core/media.php';
$files = require __DIR__ . '/../includes/tools/core/files.php';
check(!function_exists('wp_tempnam'), 'No wp_tempnam available in isolated REST-like environment');
$upload = $media['handlers']['wp_upload_media'];
foreach (['base64','url'] as $type) {
    $result = $upload(['source_type'=>$type, 'data'=>base64_encode('fixture'), 'filename'=>'image.png', 'url'=>'https://example.test/image.png']);
    check(is_array($result) && $result['attachment_id'] === 10, "$type upload reaches sideload without admin includes");
}
check($upload(['source_type'=>'base64','data'=>'!invalid!','filename'=>'image.png'])->code === 'invalid_base64', 'Invalid base64 returns explicit error');
Cowboy_MCP_Compat::$base = sys_get_temp_dir() . '/ahead-test-' . bin2hex(random_bytes(8));
mkdir(Cowboy_MCP_Compat::$base . '/nested', 0700, true);
file_put_contents(Cowboy_MCP_Compat::$base . '/nested/example.txt', 'fixture');
try {
    $list = $files['handlers']['wp_list_directory'];
    $flat = $list(['recursive'=>false]);
    $recursive = $list(['recursive'=>true]);
    check(count($flat) === 1, 'Non-recursive listing skips dot entries');
    check(count($recursive) === 2, 'Recursive listing returns nested file without SplFileInfo fatal');
    check(is_wp_error($list(['path'=>'../escape'])), 'Path traversal remains rejected');
} finally {
    unlink(Cowboy_MCP_Compat::$base . '/nested/example.txt');
    rmdir(Cowboy_MCP_Compat::$base . '/nested');
    rmdir(Cowboy_MCP_Compat::$base);
}
