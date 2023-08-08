<?php
namespace FreePBX\modules\Missedcall;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
	public function runBackup($id,$transaction){
		/**
		 * Backup missecall data
         */
		$config = [];
		$config["data"]   = $this->FreePBX->Missedcall->fetchAllUserAndSettings();;
		$config['features']     = $this->dumpFeatureCodes();

		$this->addConfigs($config);
	}
}