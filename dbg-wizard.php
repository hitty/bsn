<?php

/*

NuSphere PHP Debugger (DBG) Helper script

Copyright (c) 2007, 2020 NuSphere Corporation

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

If you have any questions or comments, please contact:

NuSphere Corporation
http://www.nusphere.com

*/
?>
<?php
	if (!defined('E_STRICT')) {
		define('E_STRICT', 1<<11);
	}
	error_reporting(E_ALL & ~(E_NOTICE | E_STRICT));
	//    ini_set('display_errors', 'off');
	
	define('DBG_WIZARD_VERSION', '4.0.4003');
	define('DBG_VERSION', '9.2.8');
	
	define('DBGWIZ_MIN_PHP_VERSION', '4.3.0');
	define('DBGWIZ_BUNDLED_MIN_PHP_VERSION', '5.3.0');
	define('DBGWIZ_MAX_PHP_VERSION', '7.4.99');

	$expected_dbg = DBG_VERSION;
	$expected_phpunit = '';

	//Grab phpinfo

	function chk_phpinfo(&$env) {
		ob_start();
		phpinfo(INFO_GENERAL);
		$string = ob_get_contents();
		ob_end_clean();

		$env['php_ts'] = IsWindows();
		$env['debugger_enabled'] = (bool)ini_get('debugger.enabled');

		if (php_sapi_name() == 'cli') {
			if (preg_match("/Thread\s+Safety\s+=>\s+(\w+)/i", $string, $matches)) {
				$env['php_ts'] = (strcasecmp($matches[1], "enabled") == 0);
			}
			if (preg_match("/Architecture\s+=>\s+([\w,]+)/i", $string, $matches)) {
				$env['php_arch'] = trim($matches[1]);
			}
			if (preg_match("/PHP\s+Extension\s+Build\s+=>\s+([\w,]+)/i", $string, $matches)) {
				$env['php_id'] = trim($matches[1]);
			}
		} else {
			$pieces = explode("<h2", $string);
			$settings = array();
			foreach($pieces as $val) {
				preg_match("/<a name=\"module_([^<>]*)\">/", $val, $sub_key);
				preg_match_all("/<tr[^>]*>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>/Ux", $val, $sub);
				preg_match_all("/<tr[^>]*>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>/Ux", $val, $sub_ext);
				foreach($sub[0] as $key => $val) {
					switch (trim(strip_tags($sub[1][$key]))) {
						case "Thread Safety": {
							$result = trim(strip_tags($sub[2][$key]));
							$env['php_ts'] = (strcasecmp($result, "enabled") == 0);
							break;
						}
						case "Architecture": {
							$env['php_arch'] = trim(strip_tags($sub[2][$key]));
							break;
						}
						case "PHP Extension Build": {
							$env['php_id'] = trim(strip_tags($sub[2][$key]));
							break;
						}
					}
				}
			}
		}
	}

	function IsLinux() {
		return (stristr(PHP_OS, 'linux') !== false);
	}

	function  IsWindows() {
		return (stristr(PHP_OS, 'winnt')!==false || stristr(PHP_OS, 'win32')!==false);
	}

	function GetGlibcVersion() {
		$glibc = '';
		if (IsLinux()) {
			$glibc = `/lib/libc.so.6 2>/dev/null`;
			if ($glibc) {
				$pat = '/GNU.*version\s*(\\d+\\.\\d+)/i';
			}
			else {
				$glibc = `ldd --version`;
				if ($glibc) {
					$pat = '/ldd\s*\([^ \t]*\s*(?:[e]?[g]?)?libc.*\)\s*(\\d+\\.\\d+)/i';
				} else {
					$glibc = `rpm -qa|grep -i 'glibc-[0-9]'`;
					$pat = '/glibc[ \-]*(\\d+\\.\\d+)/i';
				}
			}
			$glibc = (preg_match($pat, $glibc, $glibc)) ? $glibc[1] : '';
		}
		return $glibc;
	}

	function is_32bit() {
		if (version_compare(PHP_VERSION, '4.4.0', '>=') && (PHP_INT_SIZE > 4)) {
			return false;
		}
		$a=@0x7FFFFFFFFF;
		if (($a >> 24) == 0x7FFF) {
			return false;
		} else {
			return true;
		}
	}

	function server($idx) {
		return (isset($_SERVER[$idx])) ? $_SERVER[$idx] : '';
	}

	function get_env($idx) {
		$val = getenv($idx);
		return (isset($val)) ? $val : '';
	}

	function doexplode($delim, $str) {
		$token = strtok($str, $delim);
		$r = array();
		while ($token !== false) {
			array_push($r, $token);
			$token = strtok($delim);
		}
		return array_unique($r);
	}

	function get_client_address() {
		$val = server('HTTP_X_FORWARDED_FOR');
		if (!empty($val)) {
			$val = doexplode(' ,;', $val);
			$val = $val[0];
		} else {
			$val = server('REMOTE_ADDR');
			if (empty($val)) {
				$val = get_env('SSH_CLIENT');
				if (empty($val))
					$val = get_env('SSH_CONNECTION');
				if (!empty($val)) {
					$val = doexplode(' ,;', $val);
					$val = $val[0];
				}
			}
		}
		return $val;
	}

	function is_running_srv() {
		return (stristr(server('SERVER_SOFTWARE'), "Srv") !== false);
	}

	function is_dbg_installed() {
		return extension_loaded('dbg');
	}

	function get_platform(&$env) {
		global $expected_dbg;

		$platform = '';
		$CPU = '';
		$platform_is_supported = true;
		$platform_errmsg = array();
		$dbg_module = '';
		$php_ts = '';
		$dbg_loc_instr = "";
		$dbg_arch = "";

		chk_phpinfo($env);
		$php_ts = $env['php_ts'];

		$dbg_path_prefix = "<i>&lt;PhpED install path&gt;</i>";
		$dbg_path = "\\debugger\\server\\";
		$php_version = explode('.', phpversion());

		if (!IsWindows()) {
			$CPU = php_uname('m');
			$dbg_module =  sprintf("dbg-php-%d.%d.so", $php_version[0], $php_version[1]);
		}

		if (IsWindows()) {
			$dbg_module =  sprintf("dbg-php-%d.%d.dll", $php_version[0], $php_version[1]);
			if (isset($env['php_arch'])) {
				if (stristr($env['php_arch'], 'x64')!==false||stristr($env['php_arch'], 'x86_64')!==false||stristr($env['php_arch'], 'amd64')!==false) {
					$CPU = 'x86_64';
					if (!$php_ts)
						$CPU .= "_NTS";
					if (isset($env['php_id'])&&stristr($env['php_id'],'vc15')!==false) {
						$CPU .= '_VC15';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc14')!==false) {
						$CPU .= '_VC14';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc11')!==false) {
						$CPU .= '_VC11';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc9')!==false) {
						$CPU .= '_VC9';
					} else if (empty($env['php_id'])) {
						$CPU .= '_VC9';
					} else {
						$platform_errmsg[] = "Unrecognized/unsuppoted Run-Time ({$env['php_id']})";
						$platform_is_supported = false;
					}

				} else if (stristr($env['php_arch'], 'x86')!==false||stristr($env['php_arch'], '386')!==false) {
					$CPU = 'x86';
					if (!$php_ts)
						$CPU .= "_NTS";
					if (isset($env['php_id'])&&stristr($env['php_id'],'vc15')!==false) {
						$CPU .= '_VC15';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc14')!==false) {
						$CPU .= '_VC14';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc11')!==false) {
						$CPU .= '_VC11';
					} else if (isset($env['php_id'])&&stristr($env['php_id'],'vc9')!==false) {
						$CPU .= '_VC9';
					} else if (!empty($env['php_id'])) {
						$platform_errmsg[] = "Unrecognized/unsuppoted Run-Time ({$env['php_id']})";
						$platform_is_supported = false;
					}
				}
			}
			if (empty($CPU)) {
				$CPU = php_uname('m');
				if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
					$CPU = 'x86';
					if (!@is_32bit())
						$CPU .="_64";
					if (!$php_ts)
						$CPU .= "_NTS";
				} else {
					$platform_is_supported = false;
					$platform_errmsg[] = "Windows platform under $CPU CPU is not supported";
				}
			}

			$dbg_path .= "Windows\\";
			$dbg_loc_instr = "$dbg_module is located in $dbg_path_prefix$dbg_path$CPU directory";
		}
		elseif (stristr(PHP_OS, 'darwin')!==false||stristr(PHP_OS, 'mac')!==false) {
			$platform = php_uname('r');
			if (version_compare($platform, '9.5.0', '>=')) {
				$package = "MacOSX";
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "Mac Darwin Kernel version $platform is not supported";
			}
			$CPU = php_uname('m');
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			}
			else if (stristr($CPU, 'powerpc')!==false||stristr($CPU, 'power')!==false) {    
					$CPU = 'ppc';
				} else {
					$platform_is_supported = false;
					$platform_errmsg[] = "Mac OS X platform under $CPU CPU is not supported";
			}

			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		elseif (IsLinux()) {
			$base='2.3';
			if (!function_exists('shell_exec')) {
				$platform_errmsg[] = "function shell_exec() is disabled, results may not be accurate";
				$platform = $base;
			} else if (stristr(ini_get('safe_mode'), '1') !== false || stristr(ini_get('safe_mode'), 'on') !== false){
					$platform_errmsg[] = "safe_mode is turned on, results may not be accurate";
					$platform = $base;
				} else {
					$platform=GetGlibcVersion();
			}
			if (stristr($CPU, 'arm')!==false)
				$base='2.11';
			if (version_compare($platform, $base, '>=')) {
				$package='Linux';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "Linux glibc version $platform is not supported";
			}
			if (stristr($CPU, 'unknown')!==false)
				$CPU = php_uname('m');
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			} else if (preg_match("/armv([0-9]+)/i", $CPU, $matches) && (int)$matches[1] >= 6) {
				$CPU = 'armv6';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "Linux platform under $CPU CPU is not supported";
			}
			$platform = "glibc-$platform";
			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		elseif (stristr(PHP_OS, 'freebsd')!==false) {
			$platform = (int)trim(php_uname('r'));//(int)`uname -r|sed 's,\\([0-9]*\\).*,\\1,g'`;
			if ($platform >= 6) {
				$package = 'FreeBSD';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "FreeBSD version $platform is not supported";
			}
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "FreeBSD platform under $CPU CPU is not supported";
			}

			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		elseif (stristr(PHP_OS, 'netbsd')!==false) {
			$platform = (int)trim(php_uname('r'));//(int)`uname -r|sed 's,\\([0-9]*\\).*,\\1,g'`;
			if ($platform >= 5) {
				$package = 'NetBSD';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "NetBSD version $platform is not supported";
			}
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "NetBSD platform under $CPU CPU is not supported";
			}

			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		elseif (stristr(PHP_OS, 'openbsd')!==false) {
			$platform = (int)trim(php_uname('r')); //`uname -r|sed 's,\\([0-9]*\\).*,\\1,g'`;
			if ($platform >= 5) {
				$package = 'OpenBSD';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "OpenBSD version $platform is not supported";
			}
			$CPU = php_uname('m');
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "OpenBSD platform under $CPU CPU is not supported";
			}

			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		elseif (stristr(PHP_OS, 'sunos')!==false||stristr(PHP_OS, 'solaris')!==false) {
			$platform = trim(php_uname('r'));
			$CPU = php_uname('m');
			if (version_compare($platform, '5.10', '>=')) {
				$package='SunOS';
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "Sun OS $platform is not supported";
			}
			if (stristr('i86pc', $CPU)!==false) 
				$CPU='i386';
			if (stristr($CPU, 'amd64')!==false||stristr($CPU, 'x86_64')!==false||stristr($CPU, 'i386')!==false||stristr($CPU, 'x86')!==false||stristr($CPU, 'i686')!==false||stristr($CPU, 'i586')!==false) {
				if (@is_32bit()) {
					$CPU = 'x86';
				} else {
					$CPU = 'x86_64';
				}
			} else {
				$platform_is_supported = false;
				$platform_errmsg[] = "SUN platform under $CPU CPU is not supported";
			}

			$dbg_arch = sprintf("dbg-%s-%s.tar.gz", $expected_dbg, $package);
			$dbg_loc_instr = "$dbg_module is packed into $dbg_path_prefix$dbg_path$dbg_arch archive, in $CPU subdirectory inside the archive";
		}
		else {
			$platform = PHP_OS . ' ' . php_uname('r');
			$platform_is_supported = false;
			$platform_errmsg[] = "$platform is not supported";
		}

		if (!IsWindows() && $php_ts) {
			$CPU .= '_TS';
		}

		$platform = PHP_OS . (empty($platform) ? "" : "-$platform");

		if (version_compare(PHP_VERSION, DBGWIZ_MIN_PHP_VERSION, '<')) {
			$platform_errmsg[] = "Php version " . PHP_VERSION . " is too outdated and not supported by this product. Please update to " . DBGWIZ_MIN_PHP_VERSION . " or higher";
			$platform_is_supported = false;
		}
		elseif (version_compare(PHP_VERSION, DBGWIZ_MAX_PHP_VERSION, '>')) {
			$platform_errmsg[] = "Php version " . PHP_VERSION . " is not supported by this product. Please check for the product updates at https://shop.nusphere.com/";
			$platform_is_supported = false;
		}
		elseif (version_compare(PHP_VERSION, DBGWIZ_BUNDLED_MIN_PHP_VERSION, '<')) {
			$dbg_path = "";
			$dbg_arch = "";
			$dbg_loc_instr = "$dbg_module is supported but not bundled with this product. Please contact support for the module(s) <a href='http://www.nusphere.com/contact_us'>http://www.nusphere.com/contact_us</a>";
		}

		$env['platform'] = $platform;
		$env['CPU'] = $CPU;
		$env['platform_is_supported'] = $platform_is_supported;
		$env['platform_errmsg'] = count($platform_errmsg) > 0 ? implode(', ', $platform_errmsg) : '';
		$env['dbg_module'] = $dbg_module;
		$env['dbg_arch'] = $dbg_arch;
		$env['dbg_path'] = $dbg_path;
		$env['dbg_loc_instr'] = $dbg_loc_instr;
	}

	function normalize_path($apath) {
		if (IsWindows()) {
			$apath = strtolower(str_replace("/", "\\", $apath));
		} else {
			$apath = realpath($apath);
		}
		return $apath;
	}

	function get_webserver_details(&$remote_path, &$remote_root, &$local_webserver, &$php_ini, &$extensions_dir, &$remote_url, &$server_name, &$sapi_name, &$php_version) {
		$php_ini = (string)get_cfg_var("cfg_file_path");
		$php_version = (string)phpversion();
		$server_name = server('SERVER_NAME');
		$client_addr = get_client_address();
		$local_webserver =  (($client_addr == server('SERVER_ADDR')) || $server_name == 'localhost');
		$port = server('SERVER_PORT');
		$sapi_name = php_sapi_name();

		$extensions_dir = ini_get('extension_dir');
		$remote_path = normalize_path(dirname(__FILE__) . DIRECTORY_SEPARATOR);
		$url_path = '';
		if (isset($_SERVER['DOCUMENT_ROOT'])) {
			$remote_root = normalize_path($_SERVER['DOCUMENT_ROOT']);
			if (!empty($remote_root)) {
				$ch = $remote_root[strlen($remote_root) - 1];
				if ($ch !== '/' && $ch !== "\\") {
					$remote_root = $remote_root . DIRECTORY_SEPARATOR;
				}
			}
			$url_path = @strstr( $remote_path, $remote_root);
			$outside_of_root = $url_path === false;
			if (!$outside_of_root) {
				$url_path = substr ($remote_path, strlen($remote_root));
			} else {
				$url_path = server('REQUEST_URI');
				$r = strrpos($url_path, '/');
				if ($r === false) $r = strlen($url_path);
				$url_path = substr ($url_path, 1, $r - 1);
			}
			if (!empty($url_path)) {
				$ch = $url_path[strlen($url_path) - 1];
				if ($ch !== '/' && $ch !== "\\") {
					$url_path = $url_path . '/';
				}
			}
		} else {
			$remote_root = '';
			$outside_of_root = true;
		}

		if (IsWindows()) {
			$extensions_dir = str_replace("/", "\\", $extensions_dir);
			$remote_root = str_replace("/", "\\", $remote_root);
			$remote_path = str_replace("/", "\\", $remote_path);
		}

		$url_path =  str_replace("\\", "/", $url_path);
		$is_ssl = (strtolower(server('HTTPS')) == 'on') || (server('SERVER_PORT') == 443);
		if (!empty($server_name)) {
			$remote_url = (($is_ssl) ? "https://" : "http://") . $server_name;
			if (!empty($port) && !(!$is_ssl && $port =="80") && !($is_ssl && $port == 443))
				$remote_url .=':'.$port;
			if (!$outside_of_root || !empty($url_path))
				$remote_url .='/'.$url_path;
			else if (empty($url_path))
					$remote_url .= '/';
		} else {
			$remote_url = '';
		}
	}
    
	function get_phpunit($path, &$rslt) {
		global $php_bin;
        
		if (!IsWindows() && !is_executable($path)) {
			$path = trim(`which phpunit 2>/dev/null`);
		}
		if (IsWindows() && file_exists($path)) {
			$rslt['phpunit_path'] = $path;
			$rslt['phpunit_version'] = trim(`"$php_bin" "$path" --version`);
		}
		else if (is_executable($path)) {
			$rslt['phpunit_path'] = $path;
			$rslt['phpunit_version'] = trim(`"$path" --version`);
		}
		else if (!file_exists($path)) {
			$rslt['phpunit_error'] = "phpunit ($path) is not found";
		}
		else {
			$rslt['phpunit_error'] = "phpunit ($path) is not executable";
		}
	}

	function get_results(&$rslt, &$notices, &$env, &$dbg_instructions, &$proj_settings) {
		global $expected_dbg, $expected_phpunit;

		$rslt = array();
		$notices = array();
		$dbg_instructions = array();
		$proj_settings = array();
		$env = array();

		get_platform($env);
		get_webserver_details($env['remote_path'], $env['remote_root'], $env['local_webserver'], $env['php_ini'], $env['extensions_dir'], $env['remote_url'], $env['server_name'], $env['sapi_name'], $env['php_version']);
		if (!empty($expected_phpunit)) {
			get_phpunit($expected_phpunit, $env);
		}
            

		$env['client_ip'] = get_client_address();
		$env['server_ip'] = server('SERVER_ADDR');
		if (empty($env['server_ip']) && server('SERVER_NAME')==='localhost') {
			$env['server_ip'] = '127.0.0.1';
		}
		if (empty($env['server_ip']) && server('SERVER_NAME')==='localhost6') {
			$env['server_ip'] = '::1';
		}

		if (empty($env['remote_root']) || $env['remote_root'] === DIRECTORY_SEPARATOR)
			$notices[] = 'Please make sure that you run this script under WEB server, not by invoking php in the console';
		if (empty($env['php_ini']))
			$notices[] = 'path to php.ini is not determined';
		if (substr($env['extensions_dir'], 0, 2) === './' || substr($env['extensions_dir'], 0, 2) === '.\\')
			$notices[] = 'I highly recommend you to update extension_dir in your php.ini file to contain absolute path';

		$rslt['php_version_string'] = array('caption' => '<strong>PHP Version:</strong>', 'value' => phpversion());
		$rslt['webserver']          = array('caption' => '<strong>Web Server:</strong>',  'value' => server('SERVER_SOFTWARE'));
		$rslt['server_name']        = array('caption' => '<strong>Server Name:</strong>', 'value' => $env['server_name']);
		$rslt['platform']           = array('caption' => '<strong>Platform:</strong>',    'value' => $env['platform'] . "/" . $env['CPU']);
		$rslt['client_ip']          = array('caption' => '<strong>Your Client IP Address:</strong>', 'value' => $env['client_ip']);
		$rslt['server_ip']          = array('caption' => '<strong>Your Server IP Address:</strong>', 'value' => $env['server_ip']);
		$rslt['port']               = array('caption' => '<strong>Port:</strong>',        'value' => server('SERVER_PORT'));
		$rslt['is_local']           = array('caption' => $env['local_webserver'] ?
		'<strong>Your Web Server is on the same machine with PhpED</strong>' :
		'<strong>Your Web Server and PhpED are on different machines</strong>');
		$rslt['remote_path']        = array('caption' => '<strong>Path to website files:</strong>',  'value' => $env['remote_path']);
		$rslt['remote_root']        = array('caption' => '<strong>Document Root is:</strong>',       'value' => $env['remote_root']);
		$rslt['php_ini']            = array('caption' => '<strong>Your PHP.INI file is:</strong>',   'value' => $env['php_ini']);
		$rslt['extension_dir']      = array('caption' => '<strong>PHP extensions directory is:</strong>',   'value' => $env['extensions_dir']);

		if (is_dbg_installed()) {
			$dbg_version = phpversion("dbg");
			$env['dbg_version'] = $dbg_version;
			$version_array = explode(".",$dbg_version);
			$inred = false;
			if (!version_compare($dbg_version,  $expected_dbg, '=')) {
				if (version_compare($dbg_version,  $expected_dbg, '<')) {
					$notices[] = 'Installed DBG module is of an older version than currently available from NuSphere';
					$inred = true;
				} else {
					$notices[] = 'Perhaps, DBG Wizard is outdated, please download the latest one from <a href="http://www.nusphere.com/products/dbg_wizard_download.htm">this link</a>';
				}
			}
			$rslt['dbg_installed']  = array('caption' => "<strong>DBG (PHP DEBUGGER) Version $dbg_version is </strong>", 'value' => ($inred ? '<font color="red">':'') . 'INSTALLED' . ($inred ? '</font>':''));
		}
		else {
			$notices[] = 'DBG (PHP DEBUGGER) is not installed';
			$dbg_instructions[] = "Your debugger module " . $env['dbg_loc_instr'];
			$dbg_instructions[] = "Copy {$env['dbg_module']} into {$env['extensions_dir']} on your server {$env['server_name']}.";
			$phpfilename = !empty($env['php_ini']) ? $env['php_ini'] : "php.ini";
			$lines = "Add the following lines into $phpfilename
			<div align='center'>
			<table width='90%'  align='center' cellspacing='1' cellpadding='2' border='0'>
			<tr><td align='left'><font color='#FF8000' size='2pt'>";
			if (substr($env['extensions_dir'], -1, 1) == DIRECTORY_SEPARATOR)
				$zend_extension_path =  $env['extensions_dir'] . $env['dbg_module'];
			else
				$zend_extension_path =  $env['extensions_dir'] . DIRECTORY_SEPARATOR . $env['dbg_module'];
			if ($env['php_ts'] && version_compare(PHP_VERSION, '5.3.0', '<'))
				$zend_ext_key = "zend_extension_ts";
			else
				$zend_ext_key = "zend_extension";
			$lines .= "<strong>$zend_ext_key=\"$zend_extension_path\"</strong></font><br>
			<font size='1pt'>Note: if debugger module is loaded using this way, please make sure extension={$env['dbg_module']} line is removed or commented out.</font>";

			$lines .= "</td></tr>
			<tr><td align='left'><font color='#FF8000' size='2pt'>
			<strong>[debugger]</strong><br>
			<strong>debugger.hosts_allow= {$env['client_ip']} localhost ::1 127.0.0.1</strong><br>
			<strong>debugger.hosts_deny=ALL </strong><br>
			<strong>debugger.ports=7869</strong></font></td></tr>
			</table>
			</div>";

			$dbg_instructions[] = $lines;
			$lines = 'debugger.hosts_allow has should be in format debugger.hosts_allow= host1 host2 host3, where host1, host2 and host3are host names or IP or network addresses
			allowed to start debug sessions.';
			if (!$env['local_webserver']) {
				$lines .= 'If you run debug session through
				<a href="http://forum.nusphere.com/viewtopic.php?t=580" target="_blank">an SSH tunnel</a>, you need to
				list only local IP addresses (localhost ::1 127.0.0.1)';
			}
			$dbg_instructions[] = $lines;
			$dbg_instructions[] = 'Restart web server';
			$dbg_instructions[] = 'Launch phpinfo and check its output. Make sure that one of the topmost headers contains <br>
			<span style="font-style: italic">Zend Engine vX.X.0, Copyright (c) 1998-200x Zend Technologies with DBG v ' . $expected_dbg . ', (C) 2000, 2018 by Dmitri Dmitrienko </span>';
		}

		// Project Root Directory
		$lines = $env['remote_path'];
		if (!$env['local_webserver']) {
			$lines .= "<ul class='plain'>
			<li><p>Select the location where you will store the copies of the files from
			{$env['remote_path']} from your server {$env['server_name']}<br>
			Note: if you are using Samba or some other file sharing system,
			you can simply point Root Directory to {$env['remote_path']} instead of copying it</p></li></ul>";
		}
		$proj_settings['project_root'] = array('caption' => '<strong>Project -> Root Directory:</strong>',  'value' => $lines);

		// Run Mode
		if (is_running_srv())
			$lines = 'HTTP Mode (SRV local WEB server)';
		else {
			$lines = 'HTTP Mode (3-rd party WEB server)';
		}
		$proj_settings['run_mode'] = array('caption' => '<strong>Mapping -> Run Mode:</strong>',  'value' => $lines);

		if (!is_running_srv()) {
			// Remote URL
			$proj_settings['remote_url'] = array('caption' => '<strong>Mapping -> Remote URL:</strong>', 'value' => $env['remote_url']);
			if (empty($env['remote_url']))
				$notices[] = 'Please make sure that you run this script under a WEB server, not by invoking php directly';

			// Remote Root
			$lines = $env['remote_path'];
			if ($env['local_webserver'])
				$lines .= " (same as Project -> Root Directory)";
			$proj_settings['remote_root'] = array('caption' => '<strong>Mapping -> Remote Root Directory:</strong>', 'value' => $lines);
		}
	}

	function print_value(&$avalue) {
		if (is_array($avalue)) {
			if (isset($avalue['value'])) {
				if (strlen($avalue['value']) == 0)
					return "<font color='red'>not determined</font>";
				else
					return "<font color='green'>{$avalue['value']}</font>";
			}
		} else {
			if (empty($avalue))
				return "<font color='red'>not determined</font>";
			else
				return "<font color='green'>$avalue</font>";
		}
	}

	$args = array();
	if (isset($_GET) && count($_GET) > 0) {
		foreach($_GET as $k=>$arg) {
			if (!isset($arg) || empty($arg)) {
				$args[] = $k;
			} else {
				$args[] = "$k=$arg";
			}
		}
	} else if (isset($argv)) {
			foreach ($argv as $k=>$arg) {
				if ($k != '0') {
					if (strpos($arg, '?') === 0)
						$arg = substr($arg, 1);
					$args[] = $arg;
			}
		}
	}

	if (isset($_COOKIE["DBGW-ARG"])) {
		$argcookie = explode('&', $_COOKIE["DBGW-ARG"]);
		foreach ($argcookie as $arg) {
			if (!empty($arg)) {
				$args[] = $arg;
			}
		}
	}

	if (is_array($args) && count($args) > 0) foreach ($args as $arg) {
			if (strpos($arg, '--') !== FALSE) {
				$arg = substr($arg, 2);
			}
			if (strpos($arg, '=') >= 1) {
				$arg = explode('=', $arg);
				$val = array_pop($arg);
				$arg = $arg[0];
			}

			switch($arg) {
				case "version":
					header("Content-type: text/plain");
					printf("version=%s\n", DBG_WIZARD_VERSION);
					die();
				case "phpinfo":
					phpinfo();
					die();
				case "extensions":
					$rslt = get_loaded_extensions();
					printf("extensions=%s\n", serialize($rslt));
					die();
				case "expected-dbg":
					$expected_dbg = $val;
					break;
				case "php_bin":
					$php_bin = $val;
					break;
				case "phpunit":
					$expected_phpunit = $val;
					break;
				case "ide":
					header("Content-type: text/plain");
					get_results($rslt, $notices, $env, $dbg_instructions, $proj_settings);
					if (version_compare(PHP_VERSION, '5.2.4', '>=')) {
						$zend_extensions=@get_loaded_extensions(TRUE);
					} else if (version_compare(phpversion('dbg'), '3.6.3', '>=')) {
							$zend_extensions=@dbg_get_loaded_zendextensions();
						} else {
							$zend_extensions=array();
					}
					$extensions = get_loaded_extensions();

					printf("result=%s\n", serialize($rslt));
					printf("notices=%s\n", serialize($notices));
					printf("env=%s\n", serialize($env));
					printf("zend_extensions=%s\n", serialize($zend_extensions));
					printf("extensions=%s\n", serialize($extensions));
					die();
			}
	}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>

		<meta http-equiv="Pragma" content="no-cache">
		<meta http-equiv="Cache-Control" content="no-cache">
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
		<meta http-equiv="content-language" content="en">
		<meta name="author" content="NuSphere Corporation">
		<meta http-equiv="Reply-to" content="sales@nusphere.com">
		<meta name="generator" content="PhpED 5.6">
		<meta name="description" content="NuSphere PHP Debugger (DBG) Helper script">
		<meta name="revisit-after" content="15 days">

		<style TYPE="text/css">
			<!--
			body {
				font-family: Verdana, Arial, Helvetica, sans-serif;
				font-size: 10pt;
				text-align: left;
				color: #444444;
				margin: 0px;
				background-color: #eeeeee;
			}

			div.text_desc {
				border-color: gray;
				border-style: solid;
				border-width: thin;
				padding: 4px, 8px, 4px, 8px;
				text-align: left;
				margin-top: 2px;
				background-color: #ffffff;
			}

			h1, h3, h4, h6, h7 {
				font-family: Verdana, Arial, Helvetica, sans-serif;
				font-weight: bold;
				font-size: 13pt;
				text-align: center;
				color: #444444;
			}

			h5 {
				font-family: Verdana, Arial, Helvetica, sans-serif;
				font-weight: normal;
				font-size: 8pt;
				text-align: center;
				color: #444444;
				vertical-align: top;
			}

			h2 {
				font-family: Verdana, Arial, Helvetica, sans-serif;
				font-weight: bold;
				font-size: 8pt;
				text-align: left;
				color: #444444;
			}

			p {
				font-size: 100%;
				font-family: Verdana, Arial, Helvetica, sans-serif;
				text-align: justify;
				color: #444444;
				padding-left: 1px;
				padding-right: 1px;
			}

			ul.plain {
				list-style-type: disc;
				font-family: Verdana, Arial, Helvetica, sans-serif;
				text-align: left;
				color: #444444;
				font-weight: normal;
				font-style: normal;
			}

			ul.plain li {
				list-style-type: disc;
				text-decoration:none;
			}

			strong {
				font-weight: bold;
			}

			.headline-link {
				font-size: 12pt;
				font-family: Arial Narrow, Arial, Helvetica, sans-serif;
				text-align: left;
				line-height:33px;
				font-weight:300;
				color: #FF8000;
				font-style: normal;
				font-weight: bold;
				text-decoration:none;
			}

			h1 a, a.headline-link:link {
				text-decoration:none;
			}

			-->
		</style>

		<title>NuSphere PHP Debugger (DBG) Helper</title>
	</head>
	<body>

		<h1>
			Thank you for using <a href="http://www.nusphere.com/products/dbg_wizard_download.htm">NuSphere DBG Wizard</a> (v<?php echo DBG_WIZARD_VERSION; ?>). I am your DBG (PHP Debugger) Helper Script.
		</h1>
		<div class="text_desc">
			<p>
				I will try to help you with setting up your PhpED project and installing DBG - NuSphere
				PHP Debugger. I'll do my best and suggest the ways to configure things, but if you still having problems, please don't forget:
				NuSphere's team is committed to making you successful. Here is the list of resources you can use:</p>
			<ul class="plain">
				<li> <a class="headline-link" href="http://forum.nusphere.com/viewtopic.php?t=576 "> DBG debugger installation on the server </a></li>
				<li> <a class="headline-link" href="http://forum.nusphere.com/viewtopic.php?t=2135"> Overview of DBG debugger and Project Mappings </a></li>
				<li> <a class="headline-link" href="http://forum.nusphere.com/index.php">NuSphere Forums</a></li>
				<li> and you can always ask us a question using <a class="headline-link" href="http://shop.nusphere.com/contact_us/index.php "> NuSphere Contact Us Form</a></li>
			</ul>
		</div>

		<!-- Begin System INFO -->
		<div class="text_desc">
			<h1> What did I find out about your system </h1>
			<p>
				I assume that you placed me in the root directory of your web server
				and on your PhpED machine pointed your browser to me - like this: <i>&lt;URL of your web site&gt;</i>/dbg-wizard.php
			</p>
			<p>
			I see that:
			<ul class="plain">
				<?php
					get_results($rslt, $notices, $env, $dbg_instructions, $proj_settings);
					foreach($rslt as $value) {
						$cap = $value['caption'];
						echo "<li>$cap&nbsp;&nbsp;" . print_value($value) . "</li>\n";
					}
				?>
			</ul>
			<?php
				$i=1;
				foreach($notices as $value) {
					echo "<font color='red'><strong>$i.&nbsp;</strong>$value</font><br>\n";
					$i++;
				}
				if ($env['platform_errmsg']) {
					echo "<font color='red'><strong>$i.&nbsp;</strong>{$env['platform_errmsg']}</font><br>\n";
					echo "Please consult with <a href='http://www.nusphere.com/products/debugging_php.htm'>this table</a> regarding supported platforms.";
				}
			?>
		</div>
		<!-- END System INFO -->

		<!-- Begin Srv Warning -->
		<?php
			if (is_running_srv()) { ?>
			<div class="text_desc">
				<h1> You ran this script under Srv </h1>
				<p>
					I have detected that you are running me with Srv - PhpED internal Web Server.
					Srv is perfect for local debugging and if that's what you are planning to do - you are all set!
					However, if your intention is to debug and run the scripts in your Apache or IIS Web Server
					environment you need to place me in the directory that is served by those servers and execute me again.
				</p>
			</div>
			<?php } ?>
		<!-- End   Srv Warning -->


		<!-- Begin DBG Install instructions -->
		<?php if (!is_dbg_installed()) { ?>
			<div class="text_desc">
				<h1> How to install Server side DBG module </h1>
				<p>
					I noticed that DBG (PHP DEBUGGER) is<strong> NOT INSTALLED </strong>on your server <strong><?php
echo server('SERVER_NAME') ?>.</strong>
				</P>
				<p>
				<?php
					if ($env['platform_is_supported']) {
						echo "To install it, please do the following:</p>
						<ul class='plain'>";
						foreach ($dbg_instructions as $value)
							echo "<li>$value</li>";
						echo '</ul>';
					}
				?>
			</div>
			<?php } ?>
		<!-- End    DBG Install instructions -->

		<!-- Begin Project settings instructions -->
		<div class="text_desc">
			<h1> How to setup your PhpED Project Properties </h1>
			<p>
				I can suggest the following settings for your Project to debug PHP scripts on Server <?php print server('SERVER_NAME') ?> :<br>
				You can create new Project by selecting File->New Project or by selecting  New Project in the Workspace Pop up Menu<br>
				In the Project Properties Dialog set:
			</p>
			<ul class="plain">
				<?php
					foreach($proj_settings as $value) {
						$cap = $value['caption'];
						echo "<li>$cap&nbsp;&nbsp;" . print_value($value) . "</li>\n";
					}
				?>
			</ul>
		</div>
		<!-- End of Project Settings -->
		<h5><div>
				All rights reserved. Copyright &copy; 2000-2014 NuSphere Corp <a href="http://www.nusphere.com">http://www.nusphere.com</a><br></div>
		</h5>

	</body>
</html>
