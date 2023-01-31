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

		if(!empty($config["data"])){
			foreach($config["data"] as $data){
				if(!empty($data) && is_array($data)){
					$this->FreePBX->Missedcall->addMissedcallRow($data);
				}
			}
		}

		$this->importFeatureCodes($configs['features']);
	}

	public function processLegacy($pdo, $data, $tables, $unknownTables) {
        // Nothing to do here.
	}
}
