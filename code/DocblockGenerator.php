<?php
class DocblockGenerator extends BuildTask {
	public $title = "Generate Docblocks";
	public $description = "Iterate through all configured files and generate missing docblocks";
	
	protected static $paths = array();
	protected static $classes = array();
	
	public static function addPath($path, $params = array()) {
		$path = realpath($path);
		if($path[strlen($path)-1] != DIRECTORY_SEPARATOR)
			$path .= DIRECTORY_SEPARATOR; // Add directory separator
		if(file_exists($path))
			self::$paths[$path] = $params;
	}
	
	public static function addClass($class, $params = array()) {
		if(class_exists($class))
			self::$classes[strtolower($class)] = $params;
	}
	
	public static function produceManifest() {
		$config_paths = self::$paths;
		$config_classes = self::$classes;
		$ss_manifest = ManifestBuilder::get_manifest_info(BASE_PATH);
		$ss_manifest = $ss_manifest["globals"]["_CLASS_MANIFEST"];
		
		$manifest = array();
		foreach($ss_manifest as $class => $path) {
			$path = realpath($path);
			foreach($config_paths as $configpath => $params)
				if(strpos($path, $configpath) === 0)
					if(!array_key_exists($path, $manifest))
						$manifest[$path] = $params;
					else
						$manifest[$path] = array_merge($manifest[$path], $params);
			
			foreach($config_classes as $configclass => $params)
				if($configclass === $class)
					if(!array_key_exists($path, $manifest))
						$manifest[$path] = $params;
					else
						$manifest[$path] = array_merge($manifest[$path], $params);
		}
		return $manifest;
	}
	
	public function run($request) {
		if(!class_exists("PHP_DocBlockGenerator")) {
?>
PHP_DocBlockGenerator PEAR package is required. Attempting to include.
<?php
			require_once("PHP/DocBlockGenerator.php");
		}
		$docblockgen = new PHP_DocBlockGenerator_Tokens();
		$manifest = self::produceManifest();
?>
<h2>Files to be processed:</h2>
<ul>
<?php
		foreach($manifest as $path => $params) {
			$path = substr($path, strpos($path, BASE_PATH)+strlen(BASE_PATH));
?>
	<li><?php echo $path; ?><?php if(!empty($params)) { ?> - <?php var_export($params); } ?></li>
<?php
		}
?>
</ul>

<h2>Processing...</h2>
<?php
		foreach($manifest as $path => $params) {
?>
<h3><?php echo $path; ?><?php if(!empty($params)) { ?> - <?php var_export($params); } ?></h3>
<?php
			if(!$inData = @file_get_contents($path)) {
?>
Failure - Cannot access file data.
<?php
				continue;
			}
			$outData = $docblockgen->process($inData, $params);
			if($outData) $writeResult = @file_put_contents($path, $outData);
			
			echo $outData&&$writeResult?"Success":"Failure";
		}
	}
}
