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
						<input type="text" name="db_name" id="db_name" value="mc-test" />
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
				</div>
			</div>
		
			<div class="foot">
				<div class="button" id="validate">Validate</div>
				<div class="button disabled" id="save">Install</div>
			</div>
		
		</div>
    </body>                                                   
</html>