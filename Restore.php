<?php
namespace FreePBX\modules\Missedcall;
use FreePBX\modules\Backup as Base;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
class Restore Extends Base\RestoreBase{

	public function runRestore(){
		$config 	= $this->getConfigs();
		$db 		= $this->FreePBX->Database;

		/**
         * Restoring missedcall data 
         */
        $db 		= $this->FreePBX->Database;
		$sql		= "TRUNCATE TABLE missedcall;";
		$db->prepare($sql)->execute();

		if(!empty($config["missedcall"])){
			foreach($config["missedcall"] as $data){
				if(!empty($data) && is_array($data)){
                    unset($data["followme"]);
					$data[":extension"] = $data["extension"];   unset($data["extension"]);
					$data[":queue"]     = $data["queue"]; 	    unset($data["queue"]);
					$data[":ringgroup"] = $data["ringgroup"]; 	unset($data["ringgroup"]);
					$data[":internal"]  = $data["internal"];	unset($data["internal"]);
                    $data[":external"]  = $data["external"];	unset($data["external"]);
					$sql  			    = "INSERT INTO missedcall (`extension`, `queue`, `ringgroup` ,`internal` ,`external`) VALUES (:extension, :queue, :ringgroup, :internal, :external ) ";
					$db->prepare($sql)->execute($data);
				}
			}
		}

		$this->importFeatureCodes($configs['features']);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables) {
        // Nothing to do here.
	}
}
