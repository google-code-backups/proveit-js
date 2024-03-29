#!/usr/bin/php
<?php
error_reporting(-1); // All errors

chdir(dirname (__FILE__)); // Change to directory of script (should be repo root)
define('REPO', 'https://proveit-js.googlecode.com/hg/');
define('IMPORT_HEADER', <<<'EOH'
/*
 * Imported from Mercurial commit %s as of %s from http://code.google.com/p/proveit-js/
 * Changes should be made through that Google Code project.
 */
EOH
);
define('USER_AGENT', 'ProveIt deploy script (http://code.google.com/p/proveit-js/)');
define('MW_API', 'http://en.wikipedia.org/w/api.php');
define('REV_SHORT', 'r');
define('REV_LONG', 'rev');
define('TYPE_SHORT', 't');
define('TYPE_LONG', 'type');
define('SSH_DEFAULT_PORT', 22);
$options = getopt(REV_SHORT . ':' . TYPE_SHORT . ':', array(REV_LONG . ':', TYPE_LONG . ':'));

function get_option_value($options, $short, $long, $meaning, $err_code)
{
	if(isset($options[$short])) {
		return $options[$short];
	}
	else if(isset($options[$long])) {
		return $options[$long];
	}
	else {
		fwrite(STDERR, "You must specify the $meaning.  Use -$short or --$long.\n");
		exit($err_code);
	}
}

function sftp_walk($con, $sftp, $local_dir, $remote_dir)
{
	$dir = opendir($local_dir);
	ssh2_sftp_mkdir($sftp, $remote_dir, 0755, true);
	while (($file = readdir($dir)) !== false) {
		$local_file = $local_dir . '/' . $file;
		$remote_file = $remote_dir . '/' . $file;
		if(!is_dir($local_file)) {
			echo "Transferring $local_file to $remote_file\n";
			$scp_ret = ssh2_scp_send($con, $local_file, $remote_file, 0755);
			if(!$scp_ret) {
				fwrite(STDERR, "Failed to transfer $local_file.\n");
				exit(8);
			}
		}
		else if($file != "." && $file != "..") {
			sftp_walk($con, $sftp, $local_file, $remote_file);
		}
	}
}

function sync_yui($configuration) {
	system('./yuidoc.sh', $yui_exit);
	if($yui_exit != 0) {
		fwrite(STDERR, "Failed to run yuidoc.  Please check that it and its dependencies are installed.\n");
		exit(11);
	}
	$port = isset($configuration->ssh->port) ? $configuration->ssh->port : DEFAULT_SSH_PORT;
	echo "Connecting to {$configuration->ssh->host} on port $port\n";

	$con = ssh2_connect($configuration->ssh->host, $port);
	if(!$con) {
		fwrite(STDERR, "Failed to connect to {$configuration->ssh->host}\n");
		exit(10);
	}

	if(isset($configuration->ssh->password)) {
		$auth_ret = ssh2_auth_password($con, $configuration->ssh->username, $configuration->ssh->password);
		if(!$auth_ret) {
			fwrite(STDERR, 'SSH password authentication failed.\n');
			exit(6);
		}
	}
	else {
		$passphrase = isset($configuration->ssh->passphrase) ? $configuration->ssh->passphrase : NULL;
		$auth_ret = ssh2_auth_pubkey_file($con, $configuration->ssh->username, $configuration->ssh->publicKeyFileName, $configuration->ssh->privateKeyFileName, $passphrase);
		if(!$auth_ret) {
			fwrite(STDERR, 'SSH public/private key authentication failed.\n');
			exit(15);
		}

	}

	$sftp = ssh2_sftp($con);
	if(!$sftp) {
		fwrite(STDERR, "Failed to open SFTP subsystem.\n");
		exit(7);
	}

	sftp_walk($con, $sftp, 'yui_docs/html', $configuration->ssh->path);
	chdir(dirname(__FILE__));
	echo "You have succesfully deployed the ProveIt API documentation.\n";
}

$opt_deploy_type = get_option_value($options, TYPE_SHORT, TYPE_LONG, 'deployment type (proveitgt, prod, etc.)', 12);
$configuration_filename = "./deploy_configuration.{$opt_deploy_type}.json";
if(!file_exists($configuration_filename)) {
	fwrite(STDERR, "$configuration_filename does not exist.  Ensure that '$opt_deploy_type' is the correct type and the file exists.\n");
	exit(13);
}

$configuration = json_decode(file_get_contents($configuration_filename));
# Must have SSH configuration and at least one page.
if(!isset($configuration->users[0]->username, $configuration->users[0]->password, $configuration->ssh->host, $configuration->ssh->username, $configuration->ssh->path)) {
	fwrite(STDERR, <<< 'EOM'
		You must provide a JSON file, $configuration_filename, in the repository root (but not committed).
		It must have username, password, and header fields for at least one page.
		There must also be ssh configuration fields set.

EOM
	);
	exit(1);
}

if(!isset($configuration->ssh->password) && !isset($configuration->ssh->publicKeyFileName, $configuration->ssh->privateKeyFileName)) {
	fwrite(STDERR, "In the SSH section you must set either password or both publicKeyFileName and privateKeyFileName\n");
	exit(14);
}

$_ = NULL; // unused, needed because only variables can be passed by reference.
exec('hg outgoing ' . REPO, $_, $out_ret);
// 0 unpushed changes, 1 otherwise
if($out_ret === 0) {
	fwrite(STDERR, "Push your changes to the main repository, " . REPO . ", before running $argv[0].\n");
	exit(2);
}

$opt_revid = get_option_value($options, REV_SHORT, REV_LONG, 'revision to deploy', 4);

$revid = exec('hg identify -i -r ' . $opt_revid, $_, $id_ret);
if($id_ret != 0) {
	fwrite(STDERR, "Invalid revision id: " . $opt_revid . "\n");
	exit(5);
}
$date_line = exec("hg log --template '{date}' -r $revid");
$date_stamp = substr($date_line, 0, strpos($date_line, '.'));
$datetime = new DateTime(NULL, new DateTimeZone('UTC'));
$datetime->setTimestamp($date_stamp);
$date = $datetime->format('Y-m-d');

$temp_dir = tempnam("/tmp", "proveit_deploy_r{$revid}_");
if(!$temp_dir) {
	fwrite(STDERR, "Failed to create temporary file.\n");
	exit(9);
}
unlink($temp_dir); // We need a directory, rather than file.
shell_exec("hg archive -r $revid $temp_dir");
chdir($temp_dir);

$users = $configuration->users;
foreach($users as $user) {
	if(isset($user->header)) {
		$header = implode("\n", $user->header) . "\n"; // Having header be an array makes the JSON file more readable
	}
	else {
		$header = '';
	}
	$subbed_import_header = sprintf(IMPORT_HEADER, $revid, $date);
	$deploy_cookies = tempnam("/tmp", "deploy_cookie");
	$login_ch = curl_init(MW_API);
	$login_data = array(
		'action' => 'login',
		'lgname' => $user->username,
		'lgpassword' => $user->password,
		'format' => 'json'
	);
	curl_setopt($login_ch, CURLOPT_POSTFIELDS, http_build_query($login_data));
	curl_setopt($login_ch, CURLOPT_USERAGENT, USER_AGENT);
	curl_setopt($login_ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($login_ch, CURLOPT_COOKIEJAR, $deploy_cookies);
	$login_resp = json_decode(curl_exec($login_ch));
	curl_close($login_ch);

	$token_ch = curl_init(MW_API);
	$login_data['lgtoken'] = $login_resp->login->token;
	curl_setopt($token_ch, CURLOPT_POSTFIELDS, http_build_query($login_data));
	curl_setopt($token_ch, CURLOPT_USERAGENT, USER_AGENT);
	curl_setopt($token_ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($token_ch, CURLOPT_COOKIEFILE, $deploy_cookies);
	curl_setopt($token_ch, CURLOPT_COOKIEJAR, $deploy_cookies);
	$token_resp = json_decode(curl_exec($token_ch));
	curl_close($token_ch);

	foreach($user->files as $filename => $title) {
		if(!isset($edit_token)) {
			$edit_token_ch = curl_init(MW_API);
			$edit_token_params = http_build_query(array(
				'action' => 'query',
				'prop' => 'info|revisions',
				'intoken' => 'edit',
				'titles' => $title,
				'format' => 'json'
			));
			curl_setopt($edit_token_ch, CURLOPT_POSTFIELDS, $edit_token_params);
			curl_setopt($edit_token_ch, CURLOPT_USERAGENT, USER_AGENT);
			curl_setopt($edit_token_ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($edit_token_ch, CURLOPT_COOKIEFILE, $deploy_cookies);
			curl_setopt($edit_token_ch, CURLOPT_COOKIEJAR, $deploy_cookies);
			$edit_token_resp = json_decode(curl_exec($edit_token_ch), TRUE);
			curl_close($edit_token_ch);

			$resp_page = array_pop($edit_token_resp['query']['pages']);
			$edit_token = $resp_page['edittoken'];
		}

		echo "Attempting to deploy ProveIt file $filename to $title\n";
		if(!file_exists($filename)) {
			echo realpath(dirname('.'));
			fwrite(STDERR, "Code file " . realpath($filename) . " does not exist.\n");
			exit(12);
		}
		$code = file_get_contents($filename);
		$full_code = $header . $subbed_import_header . "\n" . $code;

		$edit_ch = curl_init(MW_API);
		$edit_params = array(
			'action' => 'edit',
			'title' => $title,
			'text' => $full_code,
			'summary' => "Deploy commit $revid of ProveIt.",
			'notminor' => 1,
			'token' => $edit_token,
			'format' => 'json'
		);

		curl_setopt($edit_ch, CURLOPT_POSTFIELDS, http_build_query($edit_params));
		curl_setopt($edit_ch, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($edit_ch, CURLOPT_USERAGENT, USER_AGENT);
		curl_setopt($edit_ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($edit_ch, CURLOPT_COOKIEFILE, $deploy_cookies);
		$edit_resp_str = curl_exec($edit_ch);
		$edit_resp = json_decode($edit_resp_str);
		$success_msg = "You have successfully deployed commit $revid of ProveIt file $filename to $title\n";
		$success = $edit_resp->edit->result == 'Success';

		if($success) {
			echo $success_msg;
		}
		else if(isset($edit_resp->edit->captcha)) {
			fwrite(STDERR, "Solve CAPTCHA at " . "http://en.wikipedia.org" . $edit_resp->edit->captcha->url . ", then enter it and press return:\n");
			$answer = trim(fgets(STDIN));
			$edit_params['captchaid'] = $edit_resp->edit->captcha->id;
			$edit_params['captchaword'] = $answer;
			curl_setopt($edit_ch, CURLOPT_POSTFIELDS, http_build_query($edit_params));
			$edit_resp = json_decode(curl_exec($edit_ch));
			$success = $edit_resp->edit->result == 'Success';
			if($success) {
				echo "CAPTCHA successful. $success_msg";
			}
			else {
				fwrite(STDERR, "CAPTCHA retry failed.");
			}
		}
		curl_close($edit_ch);
		if(!$success) {
			fwrite(STDERR, "Failed to deploy $filename to $title. Exiting. Final response was:\n");
			fwrite(STDERR, $edit_resp_str);
			exit(3);
		}
	}

	unset($edit_token);
	unlink($deploy_cookies);
}

// sync_yui($configuration);
system("rm -r $temp_dir");
