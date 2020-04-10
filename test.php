<?php

require __DIR__ . '/vendor/autoload.php';

require 'wp-mock.php';

require '../wpcom/wp-content/lib/jetpack-redirects/0-load.php';

$_GET['site'] = 'example.org';

$targets = jetpack_redirects_get_targets();

use SebastianBergmann\Diff\Parser;
use SebastianBergmann\Git\Git;



$git = new Git('../jetpack');

$diff = $git->getDiff(
  'master',
  'add/redirect-everything-package'
);

$parser = new Parser;

$diffs = $parser->parse($diff);

$replaced_domains = [
	'wordpress.com',
	'support.wordpress.com',
	'en.support.wordpress.com',
	'jetpack.com',
	'dashboard.vaultpress.com',
	'automattic.com',
	'help.vaultpress.com',
	'wordpress.org'
];

$replaced_domains = implode( '|', $replaced_domains );

$url_pattern = '/https?:\/\/(' . $replaced_domains . ')\/?[a-z0-9\.\-\/]*/';
//$url_pattern = '/https?:\/\/([^\/]+)\/?[a-z0-9\.\-\/]*/';
//$func_pattern = '/(Jetpack::build_redirect_url|getRedirectUrl)\( \'([a-z0-9\-]+)\'/';
$func_pattern = '/(Redirect::get_url|getRedirectUrl)\( \'([a-z0-9\-]+)\'/';

global $issues;
$issues = [];
$manual_check = [];

$files_count = 0;

function add_file_issue( $file, $issue ) {
	global $issues;
	if ( !isset( $issues[$file] ) ) {
		$issues[$file] = [];
	}
	$issues[$file][] = $issue;
	echo '-- [ISSUE] ' . $issue . "\n";
}

$urls = [];

foreach ( $diffs as $diff ) {

	$files_count ++;

	$file = $diff->getFrom();

	$urls_pile = [];
	$slugs_pile = [];

	foreach ( $diff->getChunks() as $chunk ) {

		foreach ( $chunk->getLines() as $line ) {

			if ( preg_match( $url_pattern, $line->getContent(), $matches ) ) {
				$urls_pile[] = [
					'line' => $line->getContent(),
					'match' => $matches[0]
				];
				$urls[] = parse_url($matches[0])['host'];
			}

			if ( preg_match( $func_pattern, $line->getContent(), $matches ) ) {
				$slugs_pile[] = [
					'line' => $line->getContent(),
					'match' => $matches[2]
				];
			}

		}

	}


	echo "Found in $file\n";
	
	if ( count( $slugs_pile ) === count( $urls_pile ) ) {
		echo "[OK] Diffs count matches \n";

		foreach ( $urls_pile as $i => $url) {
			
			$orig_url = untrailingslashit( $url['match'] );
			$orig_url = str_replace( 'http://', 'https://', $orig_url );
			$orig_line = $url['line'];
			$source = $slugs_pile[$i]['match'];
			$source_line = $slugs_pile[$i]['line'];
			$needs_verify = false;

			if ( strstr( $orig_line, '#' ) || strstr( $orig_line, '?' ) ) {
				$needs_verify = true;
			}

			if ( array_key_exists( $source, $targets ) ) {
				$new_url = untrailingslashit( $targets[ $source ] );
				$new_url = str_replace( '/[site]', '', $new_url);
				if ( strstr( $new_url, '[path]' ) ) {
					$needs_verify = true;
				}
				$new_url = str_replace( '/[path]', '', $new_url);
			} else {
				$new_url = 'Not found!';
				add_file_issue( $file,  'Slug ' . $source . ' not found in wpcom targets array' );
			}
			
			echo "-- ORIG:   $orig_url\n";
			echo "-- SOURCE: ", $source, "\n";
			echo "-- TARGET: $new_url\n";
			echo "-- DIFF_A:   $orig_line\n";
			echo "-- DIFF_B: ", $source_line, "\n";

			if ( $orig_url == $new_url ) {
				echo "-- [OK] Perfect Match!\n";
				if ( $needs_verify ) {
					add_file_issue( $file, 'URL looks  good, but need to check path, anchor and/or query' );
				}
			} else {
				add_file_issue( $file, 'URLs don\'t match: ' . $orig_url . ' -> ' . $new_url );
			}

			echo "\n\n";
		}


	} else {
		echo "[!!] Diffs count do not match\n";
		add_file_issue( $file, 'URLs and function calls count dont match' );
		$manual_check[] = $file;
	}

	echo "\n\n";

}

echo "\n\n";
echo "$files_count files analyzed\n";
echo  count($issues) . " files with issues to be checked\n\n";

$manual_check = array_unique( $manual_check );

echo "List of checked files with issues:\n";
foreach ( $issues as $file => $issue ) {
	if ( !in_array( $file, $manual_check ) ) {
		echo "-[ ] $file\n";
	}
}

echo "\nList of files that could not be checked:\n";
foreach ( $manual_check as $file ) {
	echo "-[ ] $file\n";
}

echo "\nYou're welcome!\n\n";

//var_dump( $issues );
//var_dump( array_unique($urls));