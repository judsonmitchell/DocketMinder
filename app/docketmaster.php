<?php
/**
 * DocketMaster Class
 * 
 * @author Grainne O'Neill, modifications by Judson Mitchell
**/

class DocketMaster  {
	public $fileArray;
	public $iterator;// = 0;
    public $error;

	public function __construct($fileName) {
		//open the file
        try {
            $this->fileArray = file($fileName);
            if (array_search('Session Halted!', $this->fileArray)){
                throw new Exception("nocase");
            }
        }
        catch (Exception $e) {
            $this->error =  $e->getMessage();
        }

		$this->iterator = 0;
	}
	public function nextLine($num) {
		$this->iterator ++;
		return $this->fileArray[$this->iterator];
	}

	public function skipLine($num) {
		for ($i=0; $i<$num; $i++) {
			$this->iterator++;
		}
	}
	
	public function getCurrLine() {
		return $this->fileArray[$this->iterator];//$this->iterator];
	}
	
	public function printAll() {
		foreach ($this->fileArray as $currLine) {
			echo "$currLine";
		}
	}
	protected function getFileLength() {
		return count($this->fileArray);
	}
	public function printRemainder() {
		for ($i=$this->iterator; $i <= count($this->fileArray); $i++) {
			echo $this->fileArray[$i];
		}
	}
	public function parseDefendantBlock($array) {

		$linetype = 'def1';
		foreach ($array as &$line) {
			
			//check for a fake new defendant or 2 charges for 1 D
			if (substr($line, 2,1) !=' ') { // there is a number in the D place at first glance a new D
				if ($linetype == 'def1') {
					$linetype = 'def';
				} else { // have to figure out if its actually a new defendant or not
					$potentialD = new Defendant();
					$defNum = $potentialD->determineDefendantNumber($line);	// figure out the defNum and determine whether its an actual new defendant or not...

					if ($defendant->getDefendantNumber() == $defNum) { // not a new defendant
						$linetype = 'skip';
					} else { // a new defendant 
						$defendants[] = $defendant;
						$linetype = 'def';
					} 
				} 
				
			} 
			switch ($linetype) {
				case 'def': // a defendant name line
					$defendant = new Defendant();
					$defendant->setFirstName($line);
					$defendant->setLastName($line);
					$defendant->setDefendantNumber($line);
					$linetype = 'sb';
				break;
				case 'sb': // a statute and bond line
					$charge = new Charge();
					$charge->setStatute($line);
					$charge->setBond($line);
					$defendant->incrementBond($charge->getBond());
					$linetype = 'charge';
				break;
				case 'charge':
					$charge->setChargeText(trim($line));
					$defendant->setCharges($charge);					
					$linetype= 'sb';
				break;
				case 'skip':
					$linetype = 'sb';
				break;		
			} // switch
		}
		$defendants[]= $defendant;
		return $defendants;
	}
	public function getDefendantBlock() {
		
		// find the start of the defendats block...
		while (strpos($this->getCurrLine(), "DF# DEFENDANT(S):") === FALSE)  {
			$this->skipLine(1);
		}
		$this->skipLine(2);
		
		while (strpos($this->getCurrLine(), '=') === FALSE) {
			if (!(trim($this->getCurrLine())) == '') {
				$defendantblock[] = $this->getCurrLine(); 
				}
				$this->skipLine(1);

		}
		return $defendantblock;
	}

	public function getEntries() {

		$this->skipLine(2);
		$isFirst = 1;
		$line = $this->getCurrLine();
		$entry = new Entry();
		while(substr($line, 0, 1) != '=') {
			//echo "line: $line <BR>\n";
			
			//check to see if there is a new entry
			if (substr($line, 0, 1)=='0' || substr($line, 0, 1) =='1') { 
//				$currDate = trim(substr($line, 0, 10));
				if ($isFirst == 0) {
					$entries[] = $entry;   
				}
				$entry = new Entry();
				$entryDate = $entry->transformEntryDate(trim(substr($line, 0, 10)));
				$entry->setEntryDate($entryDate);
				$entry->setMinuteClerk(trim(substr($line, 15)));
				$entry->setCreated();
			} else {
				$isFirst = 0;
				$entry->concatEntry($line);
			}
			$this->skipLine(1);
			$line = $this->getCurrLine();

		}
		$entries[] = $entry;
	//	print_r($entries);
		return $entries;
	}


}

class Defendant {
	protected $firstName;
	protected $lastName;
	protected $race;
	protected $sex;
	protected $defendantNumber;
	protected $bond = 0;
	protected $charges;
	protected $dob;
	protected $folderNumber = '000000';
	protected $magistrateNumber = 0;
	const DOBSTRING = 'DOB:? ?[0-9]+/[0-9]+/[0-9]+';
	const DOBSMALLSTRING = '[0-9]+/[0-9]+/[0-9]+';
	const FNUMSTRING = 'F *[#|-]* *[0-9]+';
	const FNUMSMALLSTRING = '[0-9]+';
	const MNUMSTRING = 'M *[#|-]* *[0-9]+[/|-]?[0-9]+';
	const MNUMSMALLSTRING = '[0-9]+[/|-]?[0-9]+';
	protected $isMissing;
	
	public function getCharges() {
			return $this->charges;
	}
	public function setBond($bond){
		$this->bond = $bond;
	}	
	public function getBond() {
		return $this->bond;
	}
	public function incrementBond($num) {
		$this->setBond($this->getBond() + $num);
	}
	public function setCharges($charge) {
		$this->charges[] = $charge;
	}
	public function setDOB($dob) {
		$this->dob = $dob;
	}
	public function getDOB() {
		return $this->dob;
	}
	public function setIsMissing($isMissing) {
		$this->isMissing[] = $isMissing;
	}
	public function getIsMissing() {
		return $this->isMissing;
	}
	public function setFolderNumber($folderNumber) {
		$this->folderNumber = $folderNumber;
	}
	public function getFolderNumber() {
		return $this->folderNumber;
	}
	public function setMagistrateNumber($magistrateNumber) {
		$this->magistrateNumber = $magistrateNumber;
	}
	public function getMagistrateNumber() {
		return $this->magistrateNumber;
	}

	public function setFirstName($line) {
		$firstName = trim(substr($line, strpos($line, ',')+2)); 
		$this->firstName = $firstName;	
	}
	public function getFirstName() {
		return $this->firstName;
	}
	public function setLastName($line) {
		$line = trim(substr($line,4));
		preg_match ("@[^,]*,@", $line, $matches);
		$lastName = substr($matches[0], 0, -1);
		$this->lastName = $lastName;
	}
	
	public function getLastName() {
		return $this->lastName;
	}
	public function determineDefendantNumber($line) {
		preg_match ("@[1-9]@", $line, $matches);
		return $matches[0];
	}
	public function setDefendantNumber($line) {
		$this->defendantNumber = $this->determineDefendantNumber($line);
	}
	public function getDefendantNumber() {
		return $this->defendantNumber;
	}
		
	public function updateMultipleDefendant($entry) {
		$lastName = $this->getLastName();

		// all for the dob - clean up later
		preg_match('@'.self::DOBSTRING.'.*\n?.*'.$lastName.'@', $entry, $matches);
		if ($matches) {
			preg_match('@'.self::DOBSTRING.'@', $matches[0], $matches);
			if ($matches) {
				preg_match('@'.self::DOBSMALLSTRING.'@',$matches[0], $matches);
				$this->setDOB($matches[0]);
			} else {
				$this->setIsMissing('dob');
			}
		} else {
			$this->setIsMissing('dob');
		}
		
		// for the folder number
		preg_match('@'.self::FNUMSTRING.'.*\n?.*'.$lastName.'@', $entry, $matches);
		if ($matches) {
			preg_match('@'.self::FNUMSTRING.'@', $matches[0], $matches);
			if ($matches) {
				$this->setFolderNumber(trim(substr($matches[0], 2)));
			} else {
				$this->setIsMissing('fnum');
			}
		}	else {
			$this->setIsMissing('fnum');
		}
		// set the magistrate number
		preg_match('@'.self::MNUMSTRING.'.*\n?.*'.$lastName.'@', $entry, $matches);
		if ($matches) {
			preg_match('@'.self::MNUMSTRING.'@', $matches[0], $matches);
			if ($matches) {
			$this->setMagistrateNumber(trim(substr($matches[0], 2)));		
			} else {
				$this->setIsMissing('mnum');
			}
		} else {
			$this->setIsMissing('mnum');
		}
	
	}
	public function updateOnlyDefendant($entry) {
		//dob
		preg_match('@'.self::DOBSTRING.'@', $entry, $matches);
		if ($matches) {
			preg_match('@'.self::DOBSMALLSTRING.'@', $matches[0], $matches);
			$this->setDOB($matches[0]);
		} else {
			//might want to change this to include what is missing
			$this->setIsMissing('dob');
		}
		preg_match('@'.self::FNUMSTRING.'@', $entry, $matches);
		if ($matches) {
			preg_match('@'. self::FNUMSMALLSTRING.'@', $matches[0], $matches);
			$this->setFolderNumber($matches[0]);
		} else {
			$this->setIsMissing('fnum');
		}
		preg_match('@'.self::MNUMSTRING.'@', $entry, $matches);
		if ($matches) {
			$magistrateNumber = substr($matches[0],2);
			$this->setMagistrateNumber($magistrateNumber);
		} else {
			$this->setIsMissing('mnum');
		}
	}
}

class Charge {
	protected $statute;
	protected $chargeText;
	protected $bond;
	
	public function setStatute($line) {
		$line = $this->trimWhitespace($line);
		$end = strpos($line, 'BOND');	
		$this->statute= substr(trim(substr($line, 0, $end)),2);
	}
	public function trimWhitespace($str) {
		$str = preg_replace('/\s\s+/', ' ', $str);
		return $str;
	}
	public function getStatute() {
		return $this->statute;
	}
	public function setChargeText($line) {
		$line = $this->trimWhitespace($line);
		$this->chargeText = substr($line, 0);
	}
	public function getChargeText() {
		return $this->chargeText;
	}	
	public function setBond($line) {
		$bond = trim(substr($line, strpos($line, 'BOND')+6));
		$bond = str_replace(",", "", $bond);
		$this->bond = $bond;		
	}
	public function getBond() {
		return $this->bond;
	}	

}
class MCase {
	protected $caseNumber;
	protected $openDate;
	protected $section;
	protected $mclass;
	protected $defendants;
	protected $entries;
	
	public function setEntries($entries) {
		$this->entries = $entries;
	}
	public function getEntries() {
		return $this->entries;
	}
	public function setCaseNumber($line) {
		preg_match('@Case: [0-9]+@', $line, $matches);
		$this->caseNumber = substr($matches[0], 6);
	}
	public function getCaseNumber() {
		return $this->caseNumber;
	}
	public function setDefendants($defendants) {
		$this->defendants = $defendants;
	}
	public function setSection($line) {
		preg_match ('@Section: ([A-M]([1-6])?/)*([A-M](1-6])?)@', $line, $matches);
		$this->section = end($matches);
	}

	public function getSection() {
		return $this->section;
	}

	public function setClass($line) {
		preg_match('@Class: [1-4]@', $line, $matches);
		$this->mclass = substr($matches[0], 7);	
	}
	public function getClass() {
		return $this->mclass;
	}
	public function transformDate($date) {
		if ($date) {
			$firstSlash = strpos($date, '/');
			if ($firstSlash == 1) {
				$date = '0'. $date;
			}
			$secondSlash = strpos($date, '/', 3);
			if ($secondSlash == 4) {
				$date = substr($date, 0, 3) . '0' . substr($date, 3, 5);	
			}
			if (strlen($date) == 8) {
				$date = substr($date, 0, 6) . '19' . substr($date, 6, 2);
			}
	
			if ($date = DateTime::createFromFormat('m/d/Y', $date)) {
				return $date->format('Y-m-d');
			} else {
				return;
			}
		} else {
			return;
		}
	}
	public function insert() {
		// open up a database link
		$link = mysql_connect('localhost', 'grainne', '|a1b357kZ!');
		$openDate = date("Y-m-d");
		if (!$link) {
			die('Could not connect successfully');
		} else {
			echo 'connected successfully<BR>';
			mysql_select_db('docket_masters', $link);
			$insert = "INSERT INTO CASES (
				case_number,
				section,
				class,
				created
				) values (".
				$this->caseNumber .", " .
				"'".$this->section . "', " .
				$this->mclass .", " .
				"'".$openDate."')";
		//	echo "insert $insert <BR>\n";
			mysql_query($insert);
			$caseId = mysql_insert_id();
			
			// insert the dm entries
			foreach ($this->entries as &$entry) {
				$insert = "INSERT INTO MINUTE_ENTRIES (
					case_id,
					minute_clerk,
					entry,
					entry_date,
					created
				) VALUES (" .
					$caseId . ", " .
					"'" . $entry->getMinuteClerk() . "', " .
					"'" . $entry->getEntry() . "', " .
					"'" . $entry->getEntryDate() . "', " . 
					"'" . $openDate . "'" .
					")"; 
			//	echo "insert: $insert <BR>\n";
				mysql_query($insert);
			}
			foreach ($this->defendants as &$defendant) {
				$dob = $this->transformDate($defendant->getDOB());		
				if ($dob) {
					$insert = "INSERT INTO CLIENTS (
						folder_number,
						first_name,
						last_name,
						dob,
						opened_date
					) VALUES (" . 
						$defendant->getFolderNumber() . ", " . 
						"'". $defendant->getFirstName() . "', " . 
						"'" . $defendant->getLastName() ."', " .
						"'" . $dob. "', " .
						"'" . $openDate . "'" . 
					")";
				} else {
					$insert = "INSERT INTO CLIENTS (
						folder_number,
						first_name,
						last_name,
						created
					) VALUES (" . 
						$defendant->getFolderNumber() . ", " . 
						"'". $defendant->getFirstName() . "', " . 
						"'" . $defendant->getLastName() ."', " .
						"'" . $openDate . "'" . 
					")";
						
				}
			//	echo "insert: " . $insert . "<BR>";
				mysql_query($insert);
				$clientId = mysql_insert_id();
				// tie the client to the case
				$insert = "INSERT INTO case_clients (case_id, client_id, magistrate_number, bond, created) VALUES (" .
				$caseId . ", " .
				$clientId . ", " .
				$defendant->getMagistrateNumber() . ", " .
				$defendant->getBond() .	", " . 
					"'" . $openDate . "')";
			//	echo $insert . "<BR>";
				mysql_query($insert);
				$caseClientID = mysql_insert_id();
				if ($caseClientID == 0) {
					echo "error: " . $insert . "<BR>";
				}
				// insert the charges
				$charges = $defendant->getCharges();
				foreach ($charges as &$charge) {
					$select = "SELECT * from charges where statute like '%". $charge->getStatute() ."%'";
					$results = mysql_query($select);
					if ($row = mysql_fetch_assoc($results)) {
						$chargeId = $row['id'];
						$chargeName = $row['charge_name'];
						// if there is a new chargeText
						if ($chargeName != $charge->getChargeText()) { //put an entry in the charge_texts table
							// check to see if this charge name is already in the charge texts table
							$insert = "insert into charge_texts (charge_id, charge_text_name) values ( " .
							$chargeId . ", " .
							"'" . $charge->getChargeText() . "', " . 
							"'" . $openDate . "')";
							mysql_query($insert);							
						} else {
//							echo "the entries already match!<BR>\n";
						}
					}	else {
						// insert into charges;
						$insert = "INSERT INTO charges (charge_name, statute, created) VALUES ( " .
							"'" . $charge->getChargeText() . "', " . 
							"'" . $charge->getStatute()  . "', " .
							"'" . $openDate . "')"; 
						mysql_query($insert);
						$chargeId = mysql_insert_id();
						
					}
					$insert = "INSERT INTO case_charges (case_client_id, charge_id, bond, created)
						VALUES( " .
						$caseClientID . ", " . $chargeId . ", ". $charge->getBond().
						", '" . $openDate. "')";
				echo "insert: $insert <BR>\n";
				mysql_query($insert);
				}
			}
			echo "<BR>\n";
		}
	}
}




class Entry {
	protected $minuteClerk = '';
	protected $entry = '';
	protected $created;
	protected $entryDate;
	public function setCreated() {
		$date = date('Y-m-d');
		$this->created = $date;
	}
	public function getCreated() {
		return $this->created;
	}
	public function setMinuteClerk($line) {
		$this->minuteClerk = $line;
	}
	public function getMinuteClerk() {
		return $this->minuteClerk;
	}
	public function setEntry($entry) {
		$this->entry = $entry;
	}
	public function concatEntry($line) {
		$this->setEntry($this->getEntry() . $line);
	}
	public function getEntry() {
		return $this->entry;
	}
	public function setEntryDate($line) {
		$this->entryDate = $line;
	}
	public function getEntryDate() {
		return $this->entryDate;
	}
	public function transformEntryDate($line) {
		$entryDate = substr($line, 6, 4) . '-' . substr($line, 0, 2) . '-' .substr($line, 3, 2);
		return $entryDate;
	}
}
//
//$dir = "files";
//if (is_dir($dir)) {
//	if ($dh = opendir($dir)) {
//		while(($file = readdir($dh)) !== false) {
//			if( "." == $file || ".." == $file ){
//            continue;
//        }
//			echo "------------------------------<BR>\n";
//			echo "filename:  $file  <BR>\n";			
//			// read the docket master
//			$DM = new DocketMaster($dir."/".$file);
//			$DM->skipLine(36);
//			
//			$MCase = new MCase();
//			$MCase->setCaseNumber($DM->getCurrLine());
//						$DM->skipLine(1);
//			
//			$MCase->setSection($DM->getCurrLine());
//			
//			$DM->skipLine(1);
//			$MCase->setClass($DM->getCurrLine());
//			
//			//$defendants = new Defendants;
//			$DefendantsBlock = $DM->getDefendantBlock();
//			$defendants = $DM->parseDefendantBlock($DefendantsBlock);
//			$DM->skipLine(2);
//			$entries = $DM->getEntries();			
//			if (sizeof ($defendants)==1) {
//				$defendants[0]->updateOnlyDefendant($entries[0]->getEntry());
//			} else {
//				foreach ($defendants as &$defendant) {
//					$defendant->updateMultipleDefendant($entries[0]->getEntry()); //current($entries));
//				}
//			}
///*			foreach ($entries  as &$entry) {
////				$insert = "INSERT INTO minute_entries("
//				print_r($entry);
//			}
//*/
///*
// 			foreach ($defendants as &$defendant) {
//				$missingArray= $defendant->getIsMissing();
//				if (!(empty($missingArray))) {
//					echo "<BR>filename: " . $file . "<BR>"; 
//					echo "is missing";
//					print_r($defendant);
//				} else {
//					echo "<BR> ALL CLEAR <BR>";
//				}
//			}
//*/
//			$MCase->setEntries($entries);
//			$MCase->setDefendants($defendants);
//			$caseID = $MCase->insert();
////			print_r($MCase);
//		} //while
////		closedir($dh);
//	}
//}
//
//
