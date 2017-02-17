<?
require_once("Api.php");
require_once("Client.php");
require_once("Robot.php");

class NeatoBotvacClient {
	protected $baseUrl = "https://vorwerk-beehive-production.herokuapp.com";
	public $token;

	public function __construct($token = false) {
		$this->token = $token;
	}

	public function authorize($email, $password, $force = false) {
		if($this->token === false || $force === true) {
			$result = NeatoBotvacApi::request($this->baseUrl."/sessions",
				array(
					"platform" 	=> "ios",
					"email" 		=> $email,
					"token" 		=> bin2hex(openssl_random_pseudo_bytes(32)),
					"password" 	=> $password
				)
			);

			if(isset($result["access_token"])) {
				$this->token = $result["access_token"];
			}
		}

		return $this->token;
	}

	  public function Create() {
    parent::Create();
    $this->RegisterPropertyString("email", "");
    $this->RegisterPropertyString("password", "");


    $this->UpdateScenesProfile();
    $this->UpdateInputsProfile();
  }
	 public function ApplyChanges() {
    parent::ApplyChanges();
    $this->UpdateScenesProfile();
    $this->UpdateInputsProfile();
	
	
	$sceneId = $this->RegisterVariableFloat("Status", "Status", "email");
 //   $sceneId = $this->RegisterVariableFloat("Status", "Status", "YAVR.Scenes{$this->InstanceID}", 7);
 //   $sceneId = $this->RegisterVariableFloat("Batterie", "Batterie", "YAVR.Scenes{$this->InstanceID}", 6);
 //   $inputId = $this->RegisterVariableFloat("Version", "Version", "YAVR.Inputs{$this->InstanceID}", 8);


  }
	
	public function reauthorize($email, $password) {
		return $this->authorize($email, $password, true);
	}

	public function getRobots($token = false) {
		$result = array("message" => "no token");

		if($token !== false) {
			$this->token = $token;
		}
		if($this->token !== false) {
			$result = NeatoBotvacApi::request($this->baseUrl."/dashboard", null, "GET", array("Authorization: Token token=".$this->token));
		}

		return $result;
	}
}


class IHAVR200 extends IPSModule {
  public $InputMapping = array(
    'HDMI1' => 1,
    'HDMI2' => 2,
    'HDMI3' => 3,
    'HDMI4' => 4,
    'HDMI5' => 5,
    'HDMI6' => 6,
    'HDMI7' => 7,
    'HDMI8' => 8,
    'HDMI9' => 9,
    'AV1' => 11,
    'AV2' => 12,
    'AV3' => 13,
    'AV4' => 14,
    'AV5' => 15,
    'AV6' => 16,
    'AV7' => 17,
    'AV8' => 18,
    'AV9' => 10,
    'AUDIO' => 20,
    'AUDIO1' => 21,
    'AUDIO2' => 22,
    'AUDIO3' => 23,
    'AUDIO4' => 24,
    'AUDIO5' => 25,
    'AUDIO6' => 26,
    'AUDIO7' => 27,
    'AUDIO8' => 28,
    'AUDIO9' => 29,
    'Napster' => 101,
    'NET RADIO' => 102,
    'PC' => 103,
    'iPod' => 104,
    'Bluetooth' => 105,
    'UAW' => 106,
    'USB' => 107,
    'iPod (USB)' => 108,
    'TUNER' => 109,
    'PHONO' => 110,
    'V-AUX' => 111,
    'Spotify' => 112,
    'SERVER' => 113,
    'AirPlay' => 114,
    'JUKE' => 115,
    'MusicCast Link' => 116,
    'Main Zone Sync' => 200
  );

 
  

  
  

 

  protected function UpdateScenesProfile($scenes = array()) {
    if (!IPS_VariableProfileExists("YAVR.Scenes{$this->InstanceID}")) IPS_CreateVariableProfile("YAVR.Scenes{$this->InstanceID}", 1);
    IPS_SetVariableProfileAssociation("YAVR.Scenes{$this->InstanceID}", 0, "Auswahl", '', 0x000000);
    if (count($scenes) > 0) {
      foreach ($scenes as $key => $name) {
        IPS_SetVariableProfileAssociation("YAVR.Scenes{$this->InstanceID}", $key, $name, '', 0x000000);
      }
    }
  }

  protected function UpdateInputsProfile($inputs = array()) {
    if (!IPS_VariableProfileExists("YAVR.Inputs{$this->InstanceID}")) IPS_CreateVariableProfile("YAVR.Inputs{$this->InstanceID}", 1);
    IPS_SetVariableProfileAssociation("YAVR.Inputs{$this->InstanceID}", 0, "Auswahl", '', 0x000000);
    if (count($inputs) > 0) {
      foreach ($inputs as $key => $name) {
        IPS_SetVariableProfileAssociation("YAVR.Inputs{$this->InstanceID}", $key, $name, '', 0x000000);
      }
    }
  }

  public function RequestAction($ident, $value) {
    switch ($ident) {
      case 'STATE':
         $value = $value == 1;
         $this->SetState($value);
         break;
      case 'SCENE':
         if($value > 0) {
           $value = "Scene $value";
           $this->SetScene($value);
         }
         break;
      case 'INPUT':
         if($value > 0) {
           $value = $this->GetInputKey($value);
           $this->SetInput($value);
         }
         break;
      case 'MUTE':
         $value = $value == 1;
         $this->SetMute($value);
         break;
      case 'VOLUME':
         $this->SetVolume($value);
         break;
    }
  }

  protected function RegisterTimer($ident, $interval, $script) {
    $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

    if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
      IPS_DeleteEvent($id);
      $id = 0;
    }

    if (!$id) {
      $id = IPS_CreateEvent(1);
      IPS_SetParent($id, $this->InstanceID);
      IPS_SetIdent($id, $ident);
    }

    IPS_SetName($id, $ident);
    IPS_SetHidden($id, true);
    IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

    if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

    if (!($interval > 0)) {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
      IPS_SetEventActive($id, false);
    } else {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
      IPS_SetEventActive($id, true);
    }
  }

  public function RequestData() {
    $data = $this->Request("<Basic_Status>GetParam</Basic_Status>", 'GET');
    if($data === false) return false;
    $data = $data->Basic_Status;
    $power = $data->Power_Control->Power == 'On';
    SetValueBoolean($this->GetIDForIdent('STATE'), $power);
    $input = (string)$data->Input->Input_Sel;
    SetValueInteger($this->GetIDForIdent('INPUT'), $this->GetInputId($input));
    $volume = round($data->Volume->Lvl->Val / 10, 1);
    SetValueFloat($this->GetIDForIdent('VOLUME'), $volume);
    $mute = $data->Volume->Mute == 'On';
    SetValueBoolean($this->GetIDForIdent('MUTE'), $mute);
    return $data;
  }

  public function Request($partial, $cmd = 'GET') {
    $Benutzername = $this->ReadPropertyString('Benutzername');
    $zone = $this->ReadPropertyString('Zone');
    $cmd = strtoupper($cmd);
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
    $xml .= "<YAMAHA_AV cmd=\"{$cmd}\">";
    $xml .= "<{$zone}>{$partial}</{$zone}>";
    $xml .= "</YAMAHA_AV>";
    $client = curl_init();
    curl_setopt($client, CURLOPT_URL, "http://$host:80/YamahaRemoteControl/ctrl");
    curl_setopt($client, CURLOPT_USERAGENT, "SymconYAVR");
    curl_setopt($client, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($client, CURLOPT_TIMEOUT, 5);
    curl_setopt($client, CURLOPT_POST, true);
    curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($client, CURLOPT_POSTFIELDS, $xml);
    $result = curl_exec($client);
    $status = curl_getinfo($client, CURLINFO_HTTP_CODE);
    curl_close($client);

    if ($status == '0') {
      $this->SetStatus(201);
      return false;
    } elseif ($status != '200') {
      $this->SetStatus(202);
      return false;
    } else {
      $this->SetStatus(102);
      if($cmd == 'PUT') return true;
      return simplexml_load_string($result)->$zone;
    }
  }

  public function GetValue($key) {
    return GetValue($this->GetIDForIdent($key));
  }

  public function SetState($state) {
    SetValueBoolean($this->GetIDForIdent('STATE'), $state);
    $state = $state ? 'On' : 'Standby';
    return $this->Request("<Power_Control><Power>{$state}</Power></Power_Control>", 'PUT');
  }

  public function SetMute($state) {
    SetValueBoolean($this->GetIDForIdent('MUTE'), $state);
    $state = $state ? 'On' : 'Off';
    return $this->Request("<Volume><Mute>{$state}</Mute></Volume>", 'PUT');
  }

  public function SetScene($scene) {
    return $this->Request("<Scene><Scene_Sel>{$scene}</Scene_Sel></Scene>", 'PUT');
  }

  public function SetInput($input) {
    SetValueInteger($this->GetIDForIdent('INPUT'), $this->GetInputId($input));
    return $this->Request("<Input><Input_Sel>{$input}</Input_Sel></Input>", 'PUT');
  }

  public function SetVolume($volume) {
    if ($volume < -80) $volume = -80;
    if ($volume > 16) $volume = -20; // dont use maximum 16 - if wrong parameter it will not be to loud
    SetValueFloat($this->GetIDForIdent('VOLUME'), $volume);
    $volume = $volume * 10;
    return $this->Request("<Volume><Lvl><Val>{$volume}</Val><Exp>1</Exp><Unit>dB</Unit></Lvl></Volume>", 'PUT');
  }

  public function ListScenes() {
    $result = array();
    $data = $this->Request("<Scene><Scene_Sel_Item>GetParam</Scene_Sel_Item></Scene>", 'GET');
    if($data === false) return false;
    $data = (array)$data->Scene->Scene_Sel_Item;
    foreach ($data as $id => $item) {
      $item = (array)$item;
      if ($item['RW'] == 'W') $result[str_replace('Scene ', '', $item['Param'])] = $item['Title'];
    }
    $this->UpdateScenesProfile($result);
    return $result;
  }


  public function ListInputs() {
    $result = array();
    $data = $this->Request("<Input><Input_Sel_Item>GetParam</Input_Sel_Item></Input>", 'GET');
    if($data === false) return false;
    $data = (array)$data->Input->Input_Sel_Item;
    foreach ($data as $id => $item) {
      $item = (array)$item;
      $result[$this->GetInputId($item['Param'])] = $item['Title'];
    }
    $this->UpdateInputsProfile($result);
    return $result;
  }
}
?>
