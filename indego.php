<?php
/* Voir description :
 * https://github.com/zazaz-de/iot-device-bosch-indego-controller/blob/master/PROTOCOL.md
 */
require_once __DIR__ . '/../../../../../core/php/core.inc.php';

class indego
{
  private $username = ''; 
  private $password = '';
  private $api = 'https://api.indego.iot.bosch-si.com/api/v1/';
  private $contextId = '';
  private $userId = '';
  private $almSn = '';

  /**
   * indego constructor.
   */
  public function __construct() {
    $credFile = __DIR__ ."/indego-credentials.txt";
    if(file_exists($credFile)) {
      $cred = file($credFile);
      $this->username = trim($cred[0]);
      $this->password = trim($cred[1]);
    }
  }



  public function getInformation() {
    $this->checkAuthentication();
    $url = $this->api . "alms/" . $this->getAlmSn() . "/state";
    $curl    = curl_init();
    $headers = array('Content-type: application/json','x-im-context-id: ' . $this->getContextId());
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $curlHttpCode == 200 ) {
      $dataJsonState = json_decode($result);
        //
      $BoschIndego_state =  $dataJsonState->state;
      echo "State: $BoschIndego_state<br/>";
      scenario::setData('BoschIndego_state', $BoschIndego_state);
        //
      $BoschIndego_mowed =  $dataJsonState->mowed;
      echo "Mowed: $BoschIndego_mowed<br/>";
      scenario::setData('BoschIndego_mowed', $BoschIndego_mowed);
        //
      $prev_mowmode = scenario::getData('BoschIndego_mowmode');
      $BoschIndego_mowmode =  $dataJsonState->mowmode;
      echo "Mowmode: $BoschIndego_mowmode<br/>";
      scenario::setData('BoschIndego_mowmode', $BoschIndego_mowmode);
        //
      $BoschIndego_statusDate =  date('d-m-Y H:i:s');
      echo "State: $BoschIndego_statusDate<br/>";
      scenario::setData('BoschIndego_statusDate', $BoschIndego_statusDate);
        //
      $BoschIndego_totalOperate =  round($dataJsonState->runtime->total->operate/60,2);
      echo "TotalOperate: $BoschIndego_totalOperate<br/>";
      scenario::setData('BoschIndego_totalOperate', $BoschIndego_totalOperate);
        //
      $BoschIndego_totalCharge =  round($dataJsonState->runtime->total->charge/60,2);
      echo "TotalCharge: $BoschIndego_totalCharge<br/>";
      scenario::setData('BoschIndego_totalCharge', $BoschIndego_totalCharge);
        //
      $BoschIndego_sessionOperate =  round($dataJsonState->runtime->session->operate/60,2);
      echo "sessionOperate: $BoschIndego_sessionOperate<br/>";
      scenario::setData('BoschIndego_sessionOperate', $BoschIndego_sessionOperate);
        //
      $BoschIndego_sessionCharge =  round($dataJsonState->runtime->session->charge/60,2);
      echo "sessionCharge: $BoschIndego_sessionCharge<br/>";
      scenario::setData('BoschIndego_sessionCharge', $BoschIndego_sessionCharge);
        //
      $BoschIndego_svg_xPos =  $dataJsonState->svg_xPos;
      echo "svg_xPos: $BoschIndego_svg_xPos<br/>";
      scenario::setData('BoschIndego_svg_xPos', $BoschIndego_svg_xPos);
        //
      $BoschIndego_svg_yPos =  $dataJsonState->svg_yPos;
      echo "svg_yPos: $BoschIndego_svg_yPos<br/>";
      scenario::setData('BoschIndego_svg_yPos', $BoschIndego_svg_yPos);

        // test carte a mettre à jour
      $Umap =  $dataJsonState->map_update_available;
      if ( $Umap ) // &&  $BoschIndego_state == 258 )
        $this->getMap(0);
      else echo "Carte à jour<br/>";

        // Recup date prochaine tonte
      $this->getNextMowingDatetime(0,$prev_mowmode,$BoschIndego_mowmode);

      $this->getAlert(0);
      /*
      $fichierJsonState = fopen(__DIR__ ."/indego_dataState.json", "w");
      if($fichierJson !== FALSE) {
        fwrite($fichierJsonState, $result);  
        fclose($fichierJsonState);
      }
       */
    }
    else {
      self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
    }
  }

  public function getNextMowingDatetime($auth=1,$prev_mowmode,$next_mowmode) {
    if ( $next_mowmode == 1 ) { // mode manu
      $BoschIndego_mowNext = "Manuel";
      scenario::setData('BoschIndego_mowNext', $BoschIndego_mowNext);
      echo "MowNext manuel<br/>";
      return;
    }
    $BoschIndego_mowNextTS = scenario::getData('BoschIndego_mowNextTS');
    if ( $next_mowmode == 2 && // mode auto
         ( time() > $BoschIndego_mowNextTS || $prev_mowmode != $next_mowmode ) ) {
      setlocale(LC_TIME,"fr_FR.utf8");
      if ( $auth == 1 ) $this->checkAuthentication();
      $url = $this->api . "alms/" . $this->getAlmSn() . "/predictive/nextcutting?last=YYYY-MM-DDTHH:MM:SS%2BHH:MM";
      $curl       = curl_init();
      $headers    = array('Content-type: application/json','x-im-context-id: ' . $this->getContextId());
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPGET, true);
      $result = curl_exec($curl);
      $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
      if ( $curlHttpCode == 200 ) {
        $dataJson = json_decode($result);
        if ( $dataJson === null ) {
          $BoschIndego_mowNext = "Manuel";
          $dateTS = 0;
        }
        else {
          $dateTS = date_create_from_format("Y-m-d\TH:i:sP", $dataJson->mow_next);
          if ( $dateTS != false ) {
            $BoschIndego_mowNext = strftime("%A %e %b %H:%M", $dateTS->getTimestamp());
            $dateTS = $dateTS->getTimestamp();
          }
          else {
            $BoschIndego_mowNext = $dataJson->mow_next;
            $dateTS = 0;
          }
        }
      }
      else {
        self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
        $BoschIndego_mowNext = "Manuel";
        $dateTS = 0;
      }
      echo "MowNext: $BoschIndego_mowNext<br/>";
      scenario::setData('BoschIndego_mowNext', $BoschIndego_mowNext);
      scenario::setData('BoschIndego_mowNextTS', $dateTS);
      /*
      $fichierJsonState = fopen(__DIR__ ."/indego_dataNextMowingDatetime.json", "w");
      if($fichierJson !== FALSE) {
        fwrite($fichierJsonState, $result);  
        fclose($fichierJsonState);
      }
       */
    }
    else echo "MowNext à jour<br/>";
  }

  public function getAlert($auth=1) {
    if ( $auth == 1 ) $this->checkAuthentication();
    $url = $this->api . "alerts";
    $curl    = curl_init();
    $headers = array('x-im-context-id: ' . $this->getContextId());
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $curlHttpCode == 200 ) {
      $dataJson = json_decode($result);
      if ( $dataJson !== null ) {
          $BoschIndego_alerts = '';
        foreach ( $dataJson as $alert ) {
          $BoschIndego_alerts .= $alert->date .'<br/>';
          $BoschIndego_alerts .= $alert->message .'<br/>';
        }
          //
        echo "Alert: $BoschIndego_alerts<br/>";
        scenario::setData('BoschIndego_alerts', $BoschIndego_alerts);
      }
      else scenario::setData('BoschIndego_alerts', '');
  /*
      $fichierJsonAlerts = fopen(__DIR__ ."/indego_dataAlert.json", "w");
      if($fichierJsonAlerts !== FALSE) {
        fwrite($fichierJsonAlerts, $result);
        fclose($fichierJsonAlerts);
      }
  */
    }
    else self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
  }

  public function getMap($auth=1) {
    if ( $auth == 1 ) $this->checkAuthentication();
    $url = $this->api . "alms/" . $this->getAlmSn() . "/map";
    $curl    = curl_init();
    $headers = array('x-im-context-id: ' . $this->getContextId());
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $curlHttpCode == 200 ) {
        //
      $BoschIndego_map = $result;
      
      $xPos = scenario::getData('BoschIndego_svg_xPos');
      $yPos = scenario::getData('BoschIndego_svg_yPos');

      $BoschIndego_map = str_replace("</svg>","<circle cx=\"$xPos\" cy=\"$yPos\" r=\"14\" stroke=\"black\" stroke_width=\"3\" fill=\"green\" />\n</svg>",$BoschIndego_map);
      echo "Map: $BoschIndego_map<br/>";
      scenario::setData('BoschIndego_map', $BoschIndego_map);
      /*
       */
      $fichierImage = fopen(__DIR__ ."/indego_dataMap".date('d-m-H-i').".svg", "w");
      if($fichierImage !== FALSE) {
        fwrite($fichierImage, $BoschIndego_map);
        fclose($fichierImage);
      }
    }
    else {
      self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
    }
  }

  public function authenticate() {
    $urlA =$this->api . 'authenticate'; 
    $requestBody = array(
          'device' => '',
          'os_type' => 'Android',
          'os_version' => '4.0',
          'dvc_manuf' => 'unknown',
          'dvc_type' => 'unknown',
      );
    $requestBody = json_encode($requestBody);
    $requestHeader = array(
        'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
        'Content-Type: application/json'
    );    

    $curl = curl_init($urlA);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeader);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
     
    self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
  
    /*
    $fichierJson = fopen(__DIR__ ."/indego_dataToken.json", "w");
    if($fichierJson !== FALSE)
    { fwrite($fichierJson, $result);
      fclose($fichierJson);
    }
    else $curlHttpCode *= -1;
*/
    echo $curlHttpCode; // retour numerique uniquement
  }

  private function checkAuthentication() {
    $urlCA =$this->api . 'authenticate/check'; 
    $requestHeader = array(
        'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password),
        'x-im-context-id: ' . $this->getContextId()
      );
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $urlCA);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeader);
    curl_setopt($curl, CURLOPT_HTTPGET, true);
    $result = curl_exec($curl);
    $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ( $curlHttpCode == 200 ) {
/*
    $fichierJson = fopen(__DIR__ ."/indego_dataToken.json", "w");
    if ( $fichierJson !== false ) {
      fwrite($fichierJson, $result);
      fclose($fichierJson);
    }
*/
      // MAJ des paramètre authentification
    $json_data = json_decode($result);
    $this->setContextId($json_data->contextId);
    $this->setUserId($json_data->userId);
    $this->setAlmSn($json_data->alm_sn); 
    }
    else self::indegoLogCurl(__FUNCTION__,$curlHttpCode,$result);
  }
   
  public function getContextId() {
    return $this->contextId;
  }
  
  public function setContextId($contextIdValue) {
      $this->contextId = $contextIdValue;
  }
  
  public function getUserId() {
      return $this->userId;
  }
  
  public function setUserId($userIdValue) {
      $this->userId = $userIdValue;
  }
  
  public function getAlmSn() {
      return $this->almSn;
  }
  
  public function setAlmSn($almSnValue) {
      $this->almSn = $almSnValue;
  }
  
  private function deauthenticate() {
  }

  public function doAction($action) {
    $action = strtolower($action);
    $this->checkAuthentication();
    
    $available_actions = array("mow", "pause", "returntodock");
    if(in_array($action, $available_actions)) {
      $data       = array("state" => $action);
      $data_json  = json_encode($data);
      $url        = $this->api . "alms/" . $this->getAlmSn() . "/state";
      $headers    = array('Content-type: application/json','x-im-context-id: ' . $this->getContextId());
      //fwrite($fichierLog, $date->format('d-m-Y|H:i:s|') . 'doAction.url : ' . $url . "\n");
      //fwrite($fichierLog, $date->format('d-m-Y|H:i:s|') . 'doAction.$this->getContextId() : ' . $this->getContextId() . "\n");
          
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($curl, CURLOPT_POSTFIELDS,$data_json);
      $result = curl_exec($curl);
      $curlHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      curl_close($curl);
      if ( $curlHttpCode != 200 )
        self::indegoLogCurl(__FUNCTION__.": $action",$curlHttpCode,$result);
        //
      $BoschIndego_actionHttpCode = (($curlHttpCode == 200) ? "200-OK" : "$curlHttpCode-ERROR");
      echo "ActionHttpCode: $BoschIndego_actionHttpCode<br/>";
      scenario::setData('BoschIndego_actionHttpCode', $BoschIndego_actionHttpCode);
        //
      $BoschIndego_actionDate = date('d-m-Y H:i:s');
      echo "ActionDate: $BoschIndego_actionDate<br/>";
      scenario::setData('BoschIndego_actionDate', $BoschIndego_actionDate);
        //
      $BoschIndego_actionLast = $action;
      echo "ActionLast: $BoschIndego_actionLast<br/>";
      scenario::setData('BoschIndego_actionLast', $BoschIndego_actionLast);
    
      /*
      $fichier = fopen(__DIR__."/indego_datadoAction.json", 'w');
      if ( $fichier !== false ) {
        $date = date('d-m-Y H:i:s');
        $json  = "{\n  \"http_code\" : \"";
        $json .= (($curlHttpCode == 200) ? "200-OK" : "$curlHttpCode-ERROR");
        $json .= "\",\n  \"date\" : \"$date\"\n}";
        fwrite($fichier, $json);
        fclose($fichier);
      }
      */
    }
    else {
      $msg = "Unsupported action : $action. Only ".implode(", ",$available_actions) ." are supported";
      self::indegoLogCurl(__FUNCTION__." $msg ","NA","NA");
      echo $msg;
    }
  }

  public function getStatusCode() {
      $status = $this->getRawStatus();
      return $status['state'];
  }

  public function indegoLogCurl($function,$curlHttpCode,$result) {
    $fichierLog = fopen(__DIR__ ."/indego_log.txt", "a");
    if ( $fichierLog !== false ) {
      $date = new DateTime('now', new DateTimeZone('Europe/Paris'));
      fwrite($fichierLog, $function.str_repeat(" ",30-strlen($function)).$date->format(' d-m-Y H:i:s ').str_repeat("-",10)."\n");
      fwrite($fichierLog, 'HTTP_CODE : ' . $curlHttpCode . "\n");
      fwrite($fichierLog, 'Result : ' . $result . "\n");
      fclose($fichierLog);
    }
  }

  public function disableCalendar() {
  }

  public function enableCalendar() {
  }

    /**
     * @return bool
     */
  public function isMowing() {
      $statuscode = $this->getStatusCode();
      return in_array($statuscode, array(513, 518));
  }

  public function isCharging() {
      $statuscode = $this->getStatusCode();
      return in_array($statuscode, array(257,260));
  }

  public function isInStation() {
      $statuscode = $this->getStatusCode();
      return in_array($statuscode, array(257, 258, 259, 260, 261, 262, 263));
  }
}
