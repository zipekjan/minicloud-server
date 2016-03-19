function id(id) {
	return document.getElementById(id);
}

window.onload = function() {
	
	var xml = new XMLHttpRequest();
	
	var valid = false;
	
	var inputs = {
		
		db_host: null,
		db_user: null,
		db_pass: null,
		db_name: null,
		
		storage_folder: null,
		
		admin_user: null,
		admin_pass: null,
		
		server_name: null,
		server_desc: null
		
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
	
}