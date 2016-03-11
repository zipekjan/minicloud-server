<?php
require_once('src/lib/ArrayWrapper.php');
require_once('src/lib/Api.php');
require_once('src/lib/ApiException.php');
require_once('src/lib/ApiHandler.php');
require_once('src/lib/ApiHandlerAction.php');
require_once('src/lib/ApiResponse.php');
require_once('src/lib/MetaFile.php');
require_once('src/lib/MetaPath.php');
require_once('src/lib/MetaStorage.php');
require_once('src/lib/MetaUser.php');
require_once('src/lib/ContentStorage.php');
require_once('src/lib/Request.php');
require_once('src/lib/RequestFile.php');
require_once('src/lib/Response.php');
require_once('src/lib/FileResponse.php');
require_once('src/lib/DBOMetaStorage.php');
require_once('src/lib/FolderStorage.php');

(new Api())->handle(new Request())->execute();