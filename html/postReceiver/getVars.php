<!DOCTYPE html>
<html lang="en">

<head>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
    
  <!--- Stylesheets --->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/pr-default.css" rel="stylesheet">   

  <title>Generic Post Receiver</title>        

</head>
<body>
  
  <div class="mainContainer">
    
    <!-- refEntryDiv is displayed if a reference value is passed in -->
    <div class="refBox" id="refEntryDiv" style="display:flex">
     <div>
        <div>
          <div class="internalBoxClose">
              Retrieved Parameters
          </div>
    
<?php
    
    $refID = $_POST['refID'];
    $pickupURL = $_POST['pickupURL'];
    $rAID = $_POST['rAID'];
    $adapterUser = $_POST['adapterUser'];
    $adapterPass = $_POST['adapterPass'];
    
        //Authorization is base64 of reference adapter userid + ":" + password
        $httpHeader = array(
                    "Authorization: BASIC " . base64_encode($adapterUser . ":" . $adapterPass),
                     "ping.instanceId: " . $rAID);
    
        $url = "https://" . $pickupURL . "/ext/ref/pickup?REF=" . $refID;

        $curlResource = curl_init();
        
        curl_setopt($curlResource, CURLOPT_URL, $url);
        curl_setopt($curlResource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlResource, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($curlResource, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlResource, CURLOPT_CUSTOMREQUEST, "GET");
        //Setting CURLOPT_SSL_VERIFYPEER to false allows for self-signed certs without issues
        //Note: Definitely don't use this in production
        curl_setopt($curlResource, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curlResource, CURLOPT_SSL_VERIFYHOST, false);
    
        $response = curl_exec($curlResource);
        if (curl_error($curlResource)) { die(curl_error($curlResource)); } 
        curl_close($curlResource);

        $JSONResultToArray = json_decode($response, true);
    
        foreach($JSONResultToArray as $key => $value) {
            
            if(is_array($value)) {
                $valueIsArray = true;
            } else {
                $valueIsArray = false;
            }
            
            echo "<div class='internalBoxClose'>";
            echo "  <label for='" . $key . "'>" . $key . "</label>";
            if($valueIsArray) {
                echo "  <textarea name='" . $key . "' id='" . $key . "' readonly rows='" . count($value) . "' cols='50'>";
                foreach($value as $valueValue) {
                    echo $valueValue . "\n";
                }
                echo "  </textarea>";
            } else {
                echo "  <input type='text' class='textItem' name='" . $key . "' id='" . $key . "' readonly value='" . $value . "'>";    
            }
            echo "</div>";
        } 
    
?>
      </div>
     </div>
    </div>
  </div>

</body>    
</html>