<?php
function human_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

// Returns a file size limit in bytes based on the PHP upload_max_filesize
// and post_max_size
function file_upload_max_size() {
	static $max_size = -1;

	if ($max_size < 0) {
		// Start with post_max_size.
		$max_size = parse_size(ini_get('post_max_size'));

		// If upload_max_size is less, then reduce. Except if upload_max_size is
		// zero, which indicates no limit.
		$upload_max = parse_size(ini_get('upload_max_filesize'));
		if ($upload_max > 0 && $upload_max < $max_size) {
			$max_size = $upload_max;
		}
	}
	return $max_size;
}

function parse_size($size) {
	$unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
	$size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
	if ($unit) {
		// Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
		return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
	}
	else {
		return round($size);
	}
}

$max_size = file_upload_max_size();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <title>Install Minicloud</title>
        <link rel="stylesheet" href="install.css" type="text/css" />
		<script type="text/javascript" src="install.js"></script>
    </head>
    <body>
		<div id="header"></div>
		
		<div id="progress">
			<div class="title">Installation</div>
			<div id="message"></div>
		</div>
		
		<div id="body">
			
			<div class="error" id="php_error"></div>
			
			<div class="section">
				<div class="title"><span>Database</span></div>
				<div class="error" id="db_error"></div>
				<div class="inputs">
					<div class="input">
						<label for="db_host">Host:</label>
						<input type="text" name="db_host" id="db_host" value="127.0.0.1" />
					</div>
					<div class="input">
						<label for="db_name">Database:</label>
						<input type="text" name="db_name" id="db_name" value="minicloud" />
					</div>
					<div class="input">
						<label for="db_user">User:</label>
						<input type="text" name="db_user" id="db_user" value="root" />
					</div>
					<div class="input">
						<label for="db_pass">Password:</label>
						<input type="password" name="db_pass" id="db_pass" />
					</div>
				</div>
			</div>
		
			<div class="section">
				<div class="title"><span>Storage</span></div>
				<div class="error" id="storage_error"></div>
				<div class="inputs">
					<div class="input">
						<label for="storage_folder">Path:</label>
						<input type="text" name="storage_folder" id="storage_folder" value="./files/" />
					</div>
				</div>
			</div>
			
			<div class="section">
				<div class="title"><span>Admin account</span></div>
				<div class="description">You'll be able to create additional accounts when logged into admin account.</div>
				<div class="error" id="admin_error"></div>
				<div class="inputs">
					<div class="input">
						<label for="admin_user">Username:</label>
						<input type="text" name="admin_user" id="admin_user" value="admin" />
					</div>
					<div class="input">
						<label for="admin_pass">Password:</label>
						<input type="password" name="admin_pass" id="admin_pass" />
					</div>
				</div>
			</div>
			
			<div class="section">
				<div class="title"><span>Server info</span></div>
				<div class="error" id="server_error"></div>
				<div class="inputs">
					<div class="input">
						<label for="server_name">Name:</label>
						<input type="text" name="server_name" id="server_name" value="Minicloud Server" />
					</div>
					<div class="input">
						<label for="server_desc">Description:</label>
						<textarea name="server_desc" id="server_desc"></textarea>
					</div>
					<div class="input">
						<label for="server_nice_url">Nice urls:</label>
						<select name="server_nice_url" id="server_nice_url">
							<option value="0">Disabled</option>
							<option value="1">Enabled</option>
						</select>
					</div>
					<div class="input">
						<label for="server_size">Max file size:</label>
						<input type="text" disabled="disabled" name="server_size" id="server_size" value="<?php echo human_filesize($max_size) ?>" />
					</div>
				</div>
			</div>
		
			<div class="foot">
				<div class="button" id="validate">Validate</div>
				<div class="button disabled" id="save">Install</div>
			</div>
		
		</div>
    </body>                                                   
</html>