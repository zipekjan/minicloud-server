function id(id) {
	return document.getElementById(id);
}

window.onload = function() {
	
	var xml = new XMLHttpRequest();
	
	var valid = false;
	
	var help = {
		
		storage_folder: "Path to folder where all files will be stored. Target path must be writable.",
		server_nice_url: "Enable if your server supports mod_rewrite.",
		server_size: "Maximum size of single file uploaded to server. This size is determined by your server settings. Google 'php upload size' for more informations."
		
	}
	
	var inputs = {
		
		db_host: null,
		db_user: null,
		db_pass: null,
		db_name: null,
		
		storage_folder: null,
		
		admin_user: null,
		admin_pass: null,
		
		server_name: null,
		server_desc: null,
		server_nice_url: null
		
	}
	
	var errors = {
		
		database: 'db_error',
		admin: 'admin_error',
		storage: 'storage_error',
		php: 'php_error',
		server: 'server_error'
		
	}

	function loadInputs() {
		var data = [];
		
		for(var i in inputs) {
			data.push(i + "=" + encodeURIComponent(document.getElementById(i).value));
		}
		
		return data;
	}
	
	function validate() {

		for(var i in errors) {
			id(errors[i]).style.display = 'none';
		}
		
		xml.open("POST", "install-service.php?action=validate", true);
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		
		xml.onreadystatechange = function() {
			
			if (xml.readyState == 4 && xml.status == 200) {
				
				var info = JSON.parse(xml.responseText);
				valid = true;
				
				for(var i in errors) {
					if (typeof(info[i]) !== "undefined" && info[i] != "OK") {
						
						id(errors[i]).style.display = 'block';
						id(errors[i]).innerHTML = info[i];
						valid = false;
						
					}
				}
				
				if (valid) {
					id("save").className = "button";
				} else {
					id("save").className = "button disabled";
				}
				
			}
		};

		xml.send(loadInputs().join("&"));
		
	}
	
	function install() {
		
		if (!valid)
			return;
		
		id('body').style.display = 'none';
		id('progress').style.display = 'block';
		
		id('message').innerHTML = 'Installing database';
		
		xml.open("POST", "install-service.php?action=install", true);
		xml.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xml.onreadystatechange = function() {
			
			if (xml.readyState == 4 && xml.status == 200) {
				var data = JSON.parse(xml.responseText);
				
				if (data.install != "OK") {
					id('message').innerHTML = 'Installation failed: ' + data.install;
				} else {
					id('message').innerHTML = 'Installation completed.';
				}
			}
		
		};
		
		xml.send(loadInputs().join("&"));

	}
	
	id('validate').onclick = function() { validate(); }
	id('save').onclick = function() { install(); }
	
	for(var i in help) {
		
		var element = id(i);
		var value = help[i];
		var span = document.createElement('span');
		
		span.className = 'help';
		span.innerHTML = '<span>' + value + '</span>';
		
		element.parentNode.parentNode.insertBefore(span, element.parentNode.nextSibling);
		
	}
	
}