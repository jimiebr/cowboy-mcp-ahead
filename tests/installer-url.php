<?php
// Real installer and ZIP handling, WordPress/HTTP simulated; no live-site writes.
define('ABSPATH', __DIR__ . '/');
define('COWBOY_MCP_PATH', '/plugins/cowboy-mcp-ahead/');
class WP_Error {
    public function __construct(public string $code, public string $message) {}
    function get_error_code() { return $this->code; }
    function get_error_message() { return $this->message; }
}
function is_wp_error($v) { return $v instanceof WP_Error; }
function wp_parse_url($url) { return parse_url($url); }
function wp_http_validate_url($url) { return ! in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1','localhost','10.0.0.1'], true); }
function current_user_can($cap) { return $GLOBALS['permission']; }
function wp_is_file_mod_allowed($context) { return $GLOBALS['file_mod']; }
function wp_is_writable($path) { return is_writable($path); }
function wp_mkdir_p($path) { return is_dir($path) || mkdir($path, 0700, true); }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function wp_delete_file($path) { if (is_file($path)) unlink($path); }
function wp_cache_delete(...$args) {}
function get_bloginfo($key) { return '7.1.2'; }
function get_file_data($file, $headers) {
    $content=file_get_contents($file); $data=[];
    foreach ($headers as $key=>$label) { preg_match('/^\s*\*?\s*'.preg_quote($label,'/').':\s*(.*)$/m',$content,$m); $data[$key]=trim($m[1]??''); }
    return $data;
}
function wp_safe_remote_get($url, $args) {
    $GLOBALS['requests'][]=$url;
    if (isset($GLOBALS['redirect'])) { return ['code'=>302,'location'=>$GLOBALS['redirect']]; }
    copy($GLOBALS['fixture'], $args['filename']); return ['code'=>200];
}
function wp_remote_retrieve_response_code($response) { return $response['code']; }
function wp_remote_retrieve_header($response,$name) { return $response[$name]??''; }
class Cowboy_MCP_Compat {
    static string $base;
    static function plugins_dir() { return self::$base; }
    static function flush_plugins_cache() {}
}
class Cowboy_MCP_Tools { static function get_settings() { return ['undo_enabled'=>true]; } }
class Cowboy_MCP_Rollback {
    static function snapshot(...$args) { return []; }
    static function state_hash($state) { return 'fixture'; }
    static function insert_row($row) { $GLOBALS['journal']=$row; return 42; }
}
require __DIR__.'/../includes/class-mcp-installer.php';
require __DIR__.'/../includes/class-mcp-url-installer.php';
function check($condition,$message) { if (!$condition) throw new RuntimeException($message); echo "PASS: $message\n"; }
function fixture($entries,$symlink=false) {
    $file=$GLOBALS['base'].'/fixture-'.bin2hex(random_bytes(4)).'.zip';
    $z=new ZipArchive(); $z->open($file,ZipArchive::CREATE);
    foreach($entries as $name=>$content) $z->addFromString($name,$content);
    if($symlink) $z->setExternalAttributesName(array_key_first($entries),ZipArchive::OPSYS_UNIX,0120777<<16);
    $z->close(); $GLOBALS['fixture']=$file; return $file;
}
function install_fixture($entries) { $zip=fixture($entries); return Cowboy_MCP_URL_Installer::install('https://github.com/example/release.zip',hash_file('sha256',$zip)); }
$GLOBALS['base']=sys_get_temp_dir().'/ahead-url-test-'.bin2hex(random_bytes(8));
mkdir($GLOBALS['base'],0700); Cowboy_MCP_Compat::$base=$GLOBALS['base'].'/plugins'; mkdir(Cowboy_MCP_Compat::$base,0700);
$GLOBALS['permission']=true; $GLOBALS['file_mod']=true; $GLOBALS['requests']=[];
$plugin="<?php\n/*\nPlugin Name: Ahead Test\nVersion: 1.0.0\nRequires PHP: 8.0\n*/\n";
try {
    foreach(['http://example.com/a.zip','https://127.0.0.1/a.zip','https://user:pass@example.com/a.zip','https://example.com:444/a.zip'] as $url)
        check(Cowboy_MCP_URL_Installer::validate_input($url,str_repeat('a',64))->code==='unsafe_package_url','Reject unsafe URL '.$url);
    check(Cowboy_MCP_URL_Installer::validate_input('https://github.com/a.zip','123')->code==='invalid_sha256','Reject malformed hash');
    $GLOBALS['permission']=false;
    check(Cowboy_MCP_URL_Installer::install('https://github.com/a.zip',str_repeat('a',64))->code==='forbidden','Enforce install_plugins before HTTP');
    $GLOBALS['permission']=true; $GLOBALS['file_mod']=false;
    check(Cowboy_MCP_URL_Installer::install('https://github.com/a.zip',str_repeat('a',64))->code==='forbidden','Enforce file modification policy'); $GLOBALS['file_mod']=true;
    $zip=fixture(['fixture/plugin.php'=>$plugin]);
    check(Cowboy_MCP_URL_Installer::install('https://github.com/a.zip',str_repeat('0',64))->code==='package_hash_mismatch','Wrong digest installs nothing');
    $GLOBALS['redirect']='https://127.0.0.1/internal'; $GLOBALS['requests']=[];
    check(Cowboy_MCP_URL_Installer::install('https://github.com/a.zip',hash_file('sha256',$zip))->code==='unsafe_package_url' && count($GLOBALS['requests'])===1,'Reject private redirect before second HTTP request'); unset($GLOBALS['redirect']);
    foreach([
        ['fixture/../outside.php'=>$plugin], ['fixture/link'=>$plugin], ['fixture/a.php'=>$plugin,'other/b.php'=>$plugin],
        ['fixture/A.php'=>$plugin,'fixture/a.php'=>$plugin], ['plugin.php'=>$plugin], ['cowboy-mcp-ahead/main.php'=>$plugin],
    ] as $i=>$entries) {
        $zip=fixture($entries,$i===1); check(is_wp_error(Cowboy_MCP_URL_Installer::inspect_zip($zip)), 'Reject unsafe ZIP fixture '.$i);
    }
    check(install_fixture(['bad/main.php'=>'<?php echo "no header";'])->code==='package_invalid','Reject package without plugin header');
    check(install_fixture(['bad/main.php'=>$plugin.'syntax invalid'])->code==='package_php_invalid','Reject PHP syntax error before installation');
    check(install_fixture(['future/main.php'=>str_replace('Requires PHP: 8.0','Requires PHP: 99.0',$plugin)])->code==='requirements_unmet','Reject incompatible PHP requirements');
    $result=install_fixture(['fixture/plugin.php'=>$plugin]);
    check(is_array($result) && $result['installed'] && !$result['activated'] && $result['plugin_file']==='fixture/plugin.php' && $result['change_id']===42 && file_get_contents(Cowboy_MCP_Compat::$base.'/fixture/plugin.php')===$plugin,'Install verified ZIP inactive and record undo journal');
    check($GLOBALS['journal']['tool']==='wp_install_plugin_from_url' && $GLOBALS['journal']['object_id']==='fixture','Journal identifies URL installation');
    check(install_fixture(['fixture/plugin.php'=>$plugin])->code==='already_installed','Refuse to overwrite existing plugin');
    check(Cowboy_MCP_Installer::is_self('cowboy-mcp-ahead/cowboy-mcp.php') && Cowboy_MCP_Installer::is_self('cowboy-mcp/cowboy-mcp.php'),'Protect fork and upstream integration');
    $plan=Cowboy_MCP_Installer::dry_run_plan('wp_install_plugin_from_url',['url'=>'https://github.com/a.zip','sha256'=>str_repeat('a',64)]);
    check($plan['overwrite']===false && $plan['activate']===false,'URL dry run has no download or activation');
} finally { Cowboy_MCP_Installer::delete_dir($GLOBALS['base']); }
