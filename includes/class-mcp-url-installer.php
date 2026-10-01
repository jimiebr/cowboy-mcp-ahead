<?php
/** Public HTTPS ZIP installer. Never activates or replaces existing code. */
defined( 'ABSPATH' ) || exit;

class Cowboy_MCP_URL_Installer {
    const MAX_DOWNLOAD = 20971520;
    const MAX_EXPANDED = 104857600;
    const MAX_ENTRIES = 2000;

    public static function validate_input( string $url, string $sha256 ): bool|WP_Error {
        $parts = wp_parse_url( $url );
        if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https' || empty( $parts['host'] ) || isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) || ( isset( $parts['port'] ) && $parts['port'] !== 443 ) || ! wp_http_validate_url( $url ) ) {
            return new WP_Error( 'unsafe_package_url', 'Use a public HTTPS URL without credentials, fragment or a nonstandard port. Private/local hosts are refused.' );
        }
        if ( ! preg_match( '/^[a-fA-F0-9]{64}$/D', $sha256 ) ) {
            return new WP_Error( 'invalid_sha256', 'sha256 must be the expected 64-character hexadecimal SHA-256 of the ZIP.' );
        }
        return true;
    }

    private static function download( string $url, string $filename, string $sha256 ): bool|WP_Error {
        for ( $redirects = 0; $redirects <= 5; ++$redirects ) {
            $valid = self::validate_input( $url, $sha256 );
            if ( is_wp_error( $valid ) ) return $valid;
            $response = wp_safe_remote_get( $url, [
                'timeout' => 30, 'redirection' => 0, 'stream' => true,
                'filename' => $filename, 'limit_response_size' => self::MAX_DOWNLOAD + 1,
                'sslverify' => true, 'headers' => [ 'Accept' => 'application/zip, application/octet-stream' ],
            ] );
            if ( is_wp_error( $response ) ) return new WP_Error( 'package_download_failed', 'Could not download the public package. Check its accessibility and try again.' );
            $code = (int) wp_remote_retrieve_response_code( $response );
            if ( in_array( $code, [ 301, 302, 303, 307, 308 ], true ) ) {
                $location = (string) wp_remote_retrieve_header( $response, 'location' );
                // Only absolute HTTPS redirects; every destination is independently checked.
                if ( $redirects === 5 || $location === '' ) return new WP_Error( 'package_redirect_failed', 'Too many redirects or a missing redirect destination.' );
                $url = $location;
                continue;
            }
            clearstatcache( true, $filename );
            $size = is_file( $filename ) ? filesize( $filename ) : 0;
            if ( $code !== 200 || ! $size ) return new WP_Error( 'package_download_failed', 'Package download must return HTTP 200 and a nonempty file.' );
            if ( $size > self::MAX_DOWNLOAD ) return new WP_Error( 'package_too_large', 'ZIP exceeds the 20 MiB download limit.' );
            if ( ! hash_equals( strtolower( $sha256 ), hash_file( 'sha256', $filename ) ) ) return new WP_Error( 'package_hash_mismatch', 'Downloaded ZIP does not match the expected SHA-256. Nothing was installed.' );
            return true;
        }
        return new WP_Error( 'package_redirect_failed', 'Redirect limit exceeded.' );
    }

    public static function inspect_zip( string $filename ): string|WP_Error {
        if ( ! class_exists( 'ZipArchive' ) ) return new WP_Error( 'zip_unavailable', 'PHP ZipArchive is required.' );
        $zip = new ZipArchive();
        if ( $zip->open( $filename ) !== true ) return new WP_Error( 'package_invalid', 'The downloaded file is not a readable ZIP.' );
        try {
            if ( $zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES ) return new WP_Error( 'package_limits', 'ZIP must contain between 1 and 2000 entries.' );
            $folder = null;
            $expanded = 0;
            $seen = [];
            for ( $i = 0; $i < $zip->numFiles; ++$i ) {
                $entry = $zip->statIndex( $i );
                $name = $entry['name'] ?? '';
                $parts = explode( '/', rtrim( $name, '/' ) );
                if ( $name === '' || strlen( $name ) > 240 || strpbrk( $name, "\\:\0" ) !== false || in_array( '..', $parts, true ) || in_array( '.', $parts, true ) || in_array( '', $parts, true ) || ! preg_match( '/^[a-z0-9][a-z0-9._-]*$/D', $parts[0] ) || count( $parts ) < 2 && ! str_ends_with( $name, '/' ) ) return new WP_Error( 'package_unsafe_path', 'ZIP must have one plugin folder, without unsafe or root-level file paths.' );
                $key = strtolower( rtrim( $name, '/' ) );
                if ( isset( $seen[ $key ] ) ) return new WP_Error( 'package_duplicate_path', 'ZIP contains duplicate or case-colliding paths.' );
                $seen[ $key ] = true;
                if ( $folder !== null && $folder !== $parts[0] ) return new WP_Error( 'package_invalid', 'ZIP must contain exactly one top-level plugin folder.' );
                $folder = $parts[0];
                $expanded += (int) ( $entry['size'] ?? self::MAX_EXPANDED + 1 );
                if ( $expanded > self::MAX_EXPANDED ) return new WP_Error( 'package_limits', 'Expanded ZIP exceeds 100 MiB.' );
                $zip->getExternalAttributesIndex( $i, $system, $attributes );
                $mode = ( $attributes >> 16 ) & 0170000;
                if ( $system === ZipArchive::OPSYS_UNIX && ! in_array( $mode, [ 0, 0100000, 0040000 ], true ) ) return new WP_Error( 'package_unsafe_entry', 'Symlinks and special files are not permitted in plugin ZIPs.' );
            }
            if ( Cowboy_MCP_Installer::is_self( $folder ) ) return new WP_Error( 'self_target', 'Use the controlled release procedure to update Cowboy Ahead; this tool cannot replace its own integration.' );
            return $folder;
        } finally {
            $zip->close();
        }
    }

    public static function install( string $url, string $sha256 ): array|WP_Error {
        if ( ! current_user_can( 'install_plugins' ) || ( function_exists( 'wp_is_file_mod_allowed' ) && ! wp_is_file_mod_allowed( 'cowboy_install_plugin_from_url' ) ) ) return new WP_Error( 'forbidden', 'Plugin installation is not permitted for this user/site.' );
        $valid = self::validate_input( $url, $sha256 );
        if ( is_wp_error( $valid ) ) return $valid;
        $root = Cowboy_MCP_Compat::plugins_dir();
        if ( ! wp_is_writable( $root ) ) return new WP_Error( 'fs_not_writable', 'The plugins directory is not writable.' );
        // Staging is outside uploads/document root: unvalidated PHP is never web-exposed.
        $work = rtrim( sys_get_temp_dir(), '/\\' ) . '/cowboy-ahead-url-' . wp_generate_uuid4();
        if ( ! wp_mkdir_p( $work ) ) return new WP_Error( 'fs_not_writable', 'Could not create private staging directory.' );
        chmod( $work, 0700 );
        $zipfile = $work . '/package.zip';
        try {
            $downloaded = self::download( $url, $zipfile, $sha256 );
            if ( is_wp_error( $downloaded ) ) return $downloaded;
            $folder = self::inspect_zip( $zipfile );
            if ( is_wp_error( $folder ) ) return $folder;
            $dest = $root . '/' . $folder;
            if ( file_exists( $dest ) || is_link( $dest ) ) return new WP_Error( 'already_installed', 'Plugin directory already exists. This tool never overwrites installed plugins.' );
            $source = Cowboy_MCP_Installer::extract_package( $zipfile, $work . '/extract' );
            if ( is_wp_error( $source ) ) return $source;
            $headers = [];
            foreach ( glob( $source . '/*.php' ) ?: [] as $file ) {
                $data = get_file_data( $file, [ 'Name' => 'Plugin Name', 'Version' => 'Version', 'RequiresPHP' => 'Requires PHP', 'RequiresWP' => 'Requires at least' ] );
                if ( ! empty( $data['Name'] ) ) $headers[ basename( $file ) ] = $data;
            }
            if ( count( $headers ) !== 1 ) return new WP_Error( 'package_invalid', 'ZIP must contain exactly one main PHP file with a Plugin Name header in its top-level folder. Use an installable plugin ZIP, not a source archive with a nested plugin.' );
            $main = array_key_first( $headers );
            $data = $headers[ $main ];
            if ( in_array( strtolower( trim( $data['Name'] ) ), [ 'cowboy ahead', 'cowboy mcp' ], true ) ) return new WP_Error( 'self_target', 'This installer cannot install another copy of the MCP integration under a different folder.' );
            if ( ! empty( $data['RequiresPHP'] ) && version_compare( PHP_VERSION, $data['RequiresPHP'], '<' ) || ! empty( $data['RequiresWP'] ) && version_compare( get_bloginfo( 'version' ), $data['RequiresWP'], '<' ) ) return new WP_Error( 'requirements_unmet', 'Package requires a newer PHP or WordPress version.' );
            foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
                if ( strtolower( $file->getExtension() ) === 'php' ) {
                    try { token_get_all( file_get_contents( $file->getPathname() ), TOKEN_PARSE ); }
                    catch ( ParseError $e ) { return new WP_Error( 'package_php_invalid', 'A PHP file in the package failed syntax validation. Nothing was installed.' ); }
                }
            }
            $moved = self::move_validated_package( $source, $dest, $root );
            if ( is_wp_error( $moved ) ) return $moved;
            Cowboy_MCP_Compat::flush_plugins_cache();
            wp_cache_delete( 'plugins', 'plugins' );
            $change_id = Cowboy_MCP_Installer::journal_url_install( $folder, $data['Name'], $data['Version'] );
            return [ 'installed' => true, 'activated' => false, 'plugin_file' => $folder . '/' . $main, 'name' => $data['Name'], 'version' => $data['Version'], 'sha256' => strtolower( $sha256 ), 'change_id' => $change_id ];
        } finally {
            Cowboy_MCP_Installer::delete_dir( $work );
        }
    }

    /** Cross-volume staging: copy validated files, then rename on the destination volume. */
    private static function move_validated_package( string $source, string $dest, string $root ): bool|WP_Error {
        $pending = $root . '/.cowboy-ahead-install-' . wp_generate_uuid4();
        if ( ! mkdir( $pending, 0700 ) ) return new WP_Error( 'package_move_failed', 'Could not create destination staging directory.' );
        try {
            $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::SELF_FIRST );
            foreach ( $iterator as $file ) {
                $relative = substr( $file->getPathname(), strlen( $source ) + 1 );
                $target = $pending . '/' . $relative;
                if ( $file->isDir() ) {
                    if ( ! mkdir( $target, 0755 ) ) return new WP_Error( 'package_move_failed', 'Could not copy plugin directory.' );
                } elseif ( ! copy( $file->getPathname(), $target ) || ! chmod( $target, 0644 ) ) {
                    return new WP_Error( 'package_move_failed', 'Could not copy validated plugin files.' );
                }
            }
            if ( file_exists( $dest ) || is_link( $dest ) ) return new WP_Error( 'already_installed', 'Plugin directory already exists. Nothing was overwritten.' );
            if ( ! chmod( $pending, 0755 ) || ! @rename( $pending, $dest ) ) return new WP_Error( 'package_move_failed', 'Could not finish installation on the plugins volume.' );
            return true;
        } finally {
            Cowboy_MCP_Installer::delete_dir( $pending );
        }
    }
}
