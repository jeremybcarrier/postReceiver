<?php

function formatSAML($unformattedSAML) {
    
    $stringLength = strlen($unformattedSAML);
    $tagOpen = false;
    $arrayRow = 0;
    $prevChar = "";
    $explodedArray[0] = "";
    $formattedSAML = null;
    $indentLevel = 0;

    /* Step through the characters of the string */
    for ($currentPos = 0; $currentPos < $stringLength; $currentPos++) {
        $currentChar = substr($unformattedSAML, $currentPos, 1);
        if($currentChar == "<") {$tagOpen = true;}
        if($currentChar == ">") {$tagOpen = false;}
        if($prevChar <> "") {
            /* Break lines on tag opening */
            if($currentChar == "<") {$arrayRow++; $explodedArray[$arrayRow] = "";}
            /* Break lines at the end of values */
            if(($prevChar <> ">") && ($currentChar == "<")) {$arrayRow++; $explodedArray[$arrayRow] = "";}
            /* Break lines if a tag closes but a new tag doesn't start immediately */
            if(($prevChar == ">") && ($currentChar <> "<")) {$arrayRow++; $explodedArray[$arrayRow] = "";}
        }
        
        $explodedArray[$arrayRow] .= $currentChar;
        $prevChar = $currentChar;
    }

    $indentLevel = 0;
//    for($deletedIndex = 0; $deletedIndex < 7; $deletedIndex++) {
//        $line = $explodedArray[$deletedIndex];
    foreach($explodedArray as $lineIndex => $line) {
        if (($line <> "") && ($line <> chr(10)) && ($line <> chr(13))) {
            /* Replace tag open and close with HTML-friendly characters for display */
            $updatedLine = "";
            //$line = str_replace('>', '&gt;', $line);
            //$line = str_replace('<', '&lt;', $line);
            $tagOpen = false;
            $spaceAfterOpenFound = false;
            $spanOpen = false;
            $openQuotes = false;
                for($lineIndex = 0; $lineIndex < strlen($line); $lineIndex++) {
                    /* Tag Opening */
                    if ($line[$lineIndex] == "<") {
                        $tagOpen = true;
                        $updatedLine .= '<span style="color:blue">&lt;';
                        $spanOpen = true;
                    } else {
                        /* Tag Closing */
                        if ($line[$lineIndex] == ">") {
                            if ($spanOpen == true) {    
                                $tagOpen = false;
                                $updatedLine .= '&gt;</span>';
                                $spanOpen = false;
                            } else {
                                $tagOpen = false;
                                $updatedLine .= '<span style="color:blue">&gt;</span>';
                            }
                        } else {
                            if (($line[$lineIndex] == " ") && ($tagOpen == true) && ($spaceAfterOpenFound == false)) {
                                $spaceAfterOpenFound = true;
                                $updatedLine .= '</span> ';
                                $spanOpen = false;
                            } else {
                                /* Everything Else */
                                if($line[$lineIndex -1] == " ") {
                                    $updatedLine .= '<span style="color:green;">' . $line[$lineIndex];
                                } else {
                                    if($line[$lineIndex] == "=") {
                                        if($tagOpen == false) {
                                            $updatedLine .= '=';
                                        } else {
                                            $updatedLine .= '=</span>';
                                        }
                                    } else {
                                        if($line[$lineIndex] == "\"") {
                                            if($openQuotes == false) {
                                                $openQuotes = true;
                                                $updatedLine .= '<span style="color:orange">"';
                                            } else {
                                                $openQuotes = false;
                                                $updatedLine .= '"</span>';
                                            }
                                        } else {
                                            if (($line[$lineIndex] == chr(13)) || $line[$lineIndex] == chr(10)) {
                                                /* Do Nothing - we shoudln't have line feeds or carriage returns */
                                            } else {
                                                $updatedLine .= $line[$lineIndex];                                            
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            
            //$line = str_replace('&gt;', '<span style="color:blue;">&gt;</span>', $line);
            //$line = str_replace('&lt;', '<span style="color:blue;">&lt;</span>', $line);
            $line = $updatedLine;

            /* Reduce indent when a tag closes */
            if((substr($line, 0, 30)) == "<span style=\"color:blue\">&lt;/") {
                $indentLevel--;
            }            
            
            /* Add appropriate level of indention to string */
            for ($doIndent = 1; $doIndent <= $indentLevel; $doIndent++){
                $formattedSAML .= "&#09;";
            }
            
            /* Add indent after a tag opens but doesn't close inside of the tag opening */
            if(((substr($line, -11)) == "&gt;</span>") && (substr($line, 0, 29) == "<span style=\"color:blue\">&lt;")) {
                if((substr($line, -37)) != "/<span style=\"color:blue\">&gt;</span>") {
                    if((substr($line, 0, 30) != "<span style=\"color:blue\">&lt;/")) {
                        $indentLevel++;
                    }
                }
            }
            
            $formattedSAML .= $line . "<br>";
        }
    }
    
   /* $breakmeup = $formattedSAML;
    $formattedSAML = "";
    for($i = 0; $i < strlen($breakmeup); $i++)
    {
        if(ord($breakmeup[$i]) < 32) {
            $formattedSAML .= '"' . ord($breakmeup[$i]) . '"';
        }
    }*/
        
    return $formattedSAML;
}

function listSAMLDetails() {
    
    if(isset($_POST["SAMLResponse"])) {
        $rawSAML = base64_decode($_POST["SAMLResponse"]);
        
        $xmlDoc = new DOMDocument();
        $xmlDoc->loadXML($rawSAML);

        $rootXPath = new DOMXPath($xmlDoc);
        $rootXPath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $rootXPath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $rootXPath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        
        //$assertionXPath = new DOMXPath($xmlDoc);
        //$assertionXPath->registerNamespace('samlAssertion', 'urn:oasis:names:tc:SAML:2.0:assertion');
        
        //Get response attributes
        // ---------------------------------------------------------------------
        //Get SAML Version
        $query = ".//samlp:Response/@Version";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        $attributeArray["response"]["samlVersion"] = stripLineFeeds($nodeset->item(0)->value);

        //Get Response ID
        $query = ".//samlp:Response/@ID";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        $attributeArray["response"]["id"] = stripLineFeeds($nodeset->item(0)->value);
        
        //Get Issue Time
        $query = ".//samlp:Response/@IssueInstant";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        $attributeArray["response"]["issueInstant"] = stripLineFeeds($nodeset->item(0)->value);
        
        //Get EntityID
        $query = ".//samlp:Response/saml:Issuer";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        $attributeArray["response"]["issuer"] = stripLineFeeds($nodeset->item(0)->nodeValue);
        
        //----------------------------------------------------------------------
        
        //Get signature attributes
        //----------------------------------------------------------------------
        //Get canonicalization method algorithm
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:CanonicalizationMethod/@Algorithm";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["signature"]["canonicalizationMethodAlgorithm"] = stripLineFeeds($nodeset->item(0)->value);
        }
        
        //Get signature method algorithm
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:SignatureMethod/@Algorithm";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["signature"]["signatureMethodAlgorithm"] = stripLineFeeds($nodeset->item(0)->value);       
        }
        
        //Get signature reference URI
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference/@URI";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["signature"]["referenceUri"] = stripLineFeeds($nodeset->item(0)->value);
        }
            
        //Get signature transforms
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference/ds:Transforms/ds:Transform/@Algorithm";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            foreach($nodeset as $key=>$node) {
                $attributeArray["signature"]["transform"][$key] = stripLineFeeds($node->value);
            }
        }
        
        //Get signature digest method algorithm
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestMethod/@Algorithm";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["signature"]["digestMethodAlgorithm"] = stripLineFeeds($nodeset->item(0)->value);   
        }
        
        //Get signature digest value
        $query = ".//samlp:Response/ds:Signature/ds:SignedInfo/ds:Reference/ds:DigestValue";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {        
            $attributeArray["signature"]["digest"] = stripLineFeeds($nodeset->item(0)->nodeValue);   
        }
        
        //Get signature value
        $query = ".//samlp:Response/ds:Signature/ds:SignatureValue";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["signature"]["signature"] = stripLineFeeds($nodeset->item(0)->nodeValue);
        }
        //----------------------------------------------------------------------

           
            
        //Get status attributes
        //----------------------------------------------------------------------
        //Get status code
        $query = ".//samlp:Response/samlp:Status/samlp:StatusCode/@Value";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["status"]["value"] = stripLineFeeds($nodeset->item(0)->value); 
        }
        //----------------------------------------------------------------------
        
        //Get assertion attributes
        //----------------------------------------------------------------------
        $query = ".//samlp:Response/saml:Assertion/@Version";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["samlVersion"] = stripLineFeeds($nodeset->item(0)->value);
        }
            
        //Get Response ID
        $query = ".//samlp:Response/saml:Assertion/@ID";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["id"] = stripLineFeeds($nodeset->item(0)->value);
        }
        
        //Get Issue Time
        $query = ".//samlp:Response/saml:Assertion/@IssueInstant";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["issueInstant"] = stripLineFeeds($nodeset->item(0)->value);
        }
        
        //Get EntityID
        $query = ".//samlp:Response/saml:Assertion/saml:Issuer";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count()) {
            $attributeArray["assertion"]["issuer"] = stripLineFeeds($nodeset->item(0)->nodeValue);
        }
        
        //Get Subject NameID Format
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:NameID/@Format";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["subjectNameIdFormat"] = stripLineFeeds($nodeset->item(0)->value);        
        }
        
        //Get Subject NameID
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:NameID";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["subjectNameId"] = stripLineFeeds($nodeset->item(0)->nodeValue);          
        }
        
        //Get Subject Confirmation Method
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:SubjectConfirmation/@Method";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["subjectConfirmationMethod"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get Subject Confirmation Recipient
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:SubjectConfirmation/saml:SubjectConfirmationData/@Recipient";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["subjectConfirmationRecipient"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get Subject Confirmation NotOnOrAfter
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:SubjectConfirmation/saml:SubjectConfirmationData/@NotOnOrAfter";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["subjectNotOnOrAfter"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get Conditions NotBefore
        $query = ".//samlp:Response/saml:Assertion/saml:Conditions/@NotBefore";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["conditionsNotBefore"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get Conditions NotOnOrAfter
        $query = ".//samlp:Response/saml:Assertion/saml:Conditions/@NotOnOrAfter";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["conditionsNotOnOrAfter"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get Conditions Audience
        $query = ".//samlp:Response/saml:Assertion/saml:Conditions/saml:AudienceRestriction/saml:Audience";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["conditionsAudience"] = stripLineFeeds($nodeset->item(0)->nodeValue);          
        }
        
        //Get AuthN Session Index
        $query = ".//samlp:Response/saml:Assertion/saml:AuthnStatement/@SessionIndex";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["authnSessionIndex"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get AuthN Instant
        $query = ".//samlp:Response/saml:Assertion/saml:AuthnStatement/@AuthnInstant";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["authnInstant"] = stripLineFeeds($nodeset->item(0)->value);          
        }
        
        //Get AuthN Context Class
        $query = ".//samlp:Response/saml:Assertion/saml:AuthnStatement/saml:AuthnContext/saml:AuthnContextClassRef";
        $nodeset = $rootXPath->query($query,$xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["assertion"]["authnContextClass"] = stripLineFeeds($nodeset->item(0)->nodeValue);          
        }
        
        //Get Assertion Attributes
        $query = ".//samlp:Response/saml:Assertion/saml:AttributeStatement/saml:Attribute";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            foreach($nodeset as $key=>$node) {
                $attributeArray["assertion"]["attribute"][$key]['name'] = stripLineFeeds($node->getAttribute('Name'));
                $attributeArray["assertion"]["attribute"][$key]['nameFormat'] = stripLineFeeds($node->getAttribute('NameFormat'));
                $attributeValueIndex = 0;
                foreach ($node->childNodes as $valueIndex=>$attributeValue) {
                    $attributeArray["assertion"]["attribute"][$key]['childNode'][$valueIndex] = stripLineFeeds($attributeValue->nodeValue);
                }         
            }
        }
        
        //----------------------------------------------------------------------
        
        //Get encrypted assertion, encrypted subject, and encrypted attribute status
        //----------------------------------------------------------------------
        //Assertion
        $query = ".//samlp:Response/saml:EncryptedAssertion";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["encryptedAssertion"]["isEncrypted"] = "true";
        } else {
            $attributeArray["encryptedAssertion"]["isEncrypted"] = "false";
        }

        //Subject
        $query = ".//samlp:Response/saml:Assertion/saml:Subject/saml:EncryptedID";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["encryptedSubject"]["isEncrypted"] = "true";
        } else {
            $attributeArray["encryptedSubject"]["isEncrypted"] = "false";
        }

        //Attributes
        $query = ".//samlp:Response/saml:Assertion/saml:AttributeStatement/saml:EncryptedAttribute";
        $nodeset = $rootXPath->query($query, $xmlDoc);
        if($nodeset->count() > 0) {
            $attributeArray["encryptedAttribute"]["isEncrypted"] = "true";
        } else {
            $attributeArray["encryptedAttribute"]["isEncrypted"] = "false";
        }
        

            
        return $attributeArray;
        
    } else {
        
        return "Not a SAML response.";  
        
    }
    
}

function getSAMLResponseFields() {
    
    $samlDetails = listSAMLDetails();
    //print_r( $samlDetails );
    //die();
    $samlFields = "";
    
    /* Response Attributes */
        
    //SAML Version
    if(isset($samlDetails["response"]["samlVersion"])) {
        $samlFields .= '<div class="marginAbove5px">SAML Version:&nbsp;<span class="boldUl">' . $samlDetails['response']['samlVersion'] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">SAML Version:&nbsp;<span class="boldUl" style="color:red">Not Provided</span></div><br>';
    }
    
    //SAML Response ID
    if(isset($samlDetails["response"]["id"])) {
        $samlFields .= '<div class="marginAbove5px">Response ID:&nbsp;<span class="boldUl">' . $samlDetails['response']['id'] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Response ID:&nbsp;<span class="boldUl"> style="color:red">Not provided</span></div><br>';
}

    //SAML Issuer Instant
    if(isset($samlDetails["response"]["issueInstant"])) {
        $samlFields .= '<div class="marginAbove5px">Issued At:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["response"]["issueInstant"]) . 'GMT</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Issued At:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //SAML Issuer
    if(isset($samlDetails["response"]["issuer"])) {
        $samlFields .= '<div class="marginAbove5px">Issued By:&nbsp;<span class="boldUl">' . $samlDetails['response']['issuer'] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Issued By:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    /* End Response Attributes */
    
    return $samlFields;
}

function getSAMLSignatureFields() {
    
    $samlDetails = listSAMLDetails();
    $samlFields = "";
    
    /* Signature Attributes */
        
    //Canonicalized algorithm
    if(isset($samlDetails["signature"]["canonicalizationMethodAlgorithm"])) {
        $samlFields .= '<div class="marginAbove5px">Canonicalization Method:&nbsp;<span class="boldUl">' . $samlDetails['signature']['canonicalizationMethodAlgorithm'] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Canonicalization Method:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Signature algorithm
    if(isset($samlDetails["signature"]["signatureMethodAlgorithm"])) {
        $samlFields .= '<div class="marginAbove5px">Signature Method:&nbsp;<span class="boldUl">' . $samlDetails["signature"]["signatureMethodAlgorithm"] . '<span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Method:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Signature reference URI
    if(isset($samlDetails["signature"]["referenceUri"])) {
        $samlFields .= '<div class="marginAbove5px">Signature Reference URI:&nbsp;<span class="boldUl">' . $samlDetails["signature"]["referenceUri"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Reference URI:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Signature transform(s)
    if(isset($samlDetails["signature"]["transform"])) {
        
        foreach($samlDetails["signature"]["transform"] as $index => $value){
            $samlFields .= '<div class="marginAbove5px">Signature Transform (' . $index . '):&nbsp;<span class="boldUl">' . $value . '</span></div><br>';    
        }
        //$samlFields .= '<div>Signature Reference URI: <input type="text" name="samlST" id="samlST" value="' . $samlDetails["signature"]["referenceUri"] . '"></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Transform:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Signature digest method algorithm
    if(isset($samlDetails["signature"]["digestMethodAlgorithm"])) {
        $samlFields .= '<div class="marginAbove5px">Digest Method Algorithm:&nbsp;<span class="boldUl">' . $samlDetails["signature"]["digestMethodAlgorithm"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Digest Method Algorithm:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Signature digest
    if(isset($samlDetails["signature"]["digest"])) {
        $samlFields .= '<div class="marginAbove5px">Signature Digest:&nbsp;<span class="boldUl">' . $samlDetails["signature"]["digest"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Digest:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Signature value
    if(isset($samlDetails["signature"]["signature"])) {
        $samlFields .= '<div class="marginAbove5px">Signature Value:&nbsp;<span class="boldUl"><pre class="signaturePre">' . $samlDetails["signature"]["signature"] . '</pre></span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Value:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }   

/*    //Signature value
    if(isset($samlDetails["signature"]["signature"])) {
        $samlFields .= '<div class="marginAbove5px">Signature Value:&nbsp;<span class="boldUl"><pre class="signaturePre">';
        for($i = 0; $i < strlen($samlDetails["signature"]["signature"]); $i++) {
            $samlFields .= '"' . ord($samlDetails["signature"]["signature"][$i]) . '"';
        }
        $samlFields .= '</pre></span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Signature Value:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }*/   
    
    
    /* Signature Attributes */
    
    return $samlFields;
}

function getSAMLStatusFields() {
    
    $samlDetails = listSAMLDetails();
    $samlFields = "";
    
    /* Status Attributes */
        
    //Assertion Status
    if(isset($samlDetails["status"]["value"])) {
        
        if($samlDetails["status"]["value"] == "urn:oasis:names:tc:SAML:2.0:status:Success") {
            $samlFields .= '<div class="marginAbove5px">Assertion Status:&nbsp;<span class="boldUl" style="color:green;">Success</span></div><br>';
        } else {
            $samlFields .= '<div class="marginAbove5px">Assertion Status:&nbsp;<span class="boldUl" style="color:red">Not Success</span></div><br>';
        }
            
    } else {
        $samlFields .= '<div class="marginAbove5px">Assertion Status:&nbsp;<span style="color:red">Not provided</span></div><br>';
    }

    /* Status Attributes */

    return $samlFields;
    
}

function getSAMLAssertionFields() {
    
    $samlDetails = listSAMLDetails();
    $samlFields = "";
    
    /* Assertion Attributes */
    
    if($samlDetails["encryptedAssertion"]["isEncrypted"] == "true") {

        $samlFields .= '<div class="marginAbove5px"><span class="boldUl" style="color:red">No assertion information is available as the entire assertion was encrypted.</span></div><br>';
        
    } else {

    //Assertion SAML Version
    if(isset($samlDetails["assertion"]["samlVersion"])) {
        $samlFields .= '<div class="marginAbove5px">SAML Version:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["samlVersion"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">SAML Version:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Assertion ID
    if(isset($samlDetails["assertion"]["id"])) {
        $samlFields .= '<div class="marginAbove5px">Assertion ID:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["id"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Assertion ID:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Assertion Issue Instant
    if(isset($samlDetails["assertion"]["issueInstant"])) {
        $samlFields .= '<div class="marginAbove5px">Issue Instant:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["issueInstant"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Issue Instant:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Assertion Issuer
    if(isset($samlDetails["assertion"]["issuer"])) {
        $samlFields .= '<div class="marginAbove5px">Assertion Issuer:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["issuer"] . '</spa></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Assertion Issuer:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Subject Name ID Format
    if($samlDetails["encryptedSubject"]["isEncrypted"] == "true") {
        $samlFields .= '<div class="marginAbove5px">Subject Name ID Format:&nbsp;<span class="boldUl" style="color:red">Value Encrypted</span></div><br>';        
    } else {
        if(isset($samlDetails["assertion"]["subjectNameIdFormat"])) {
            $samlFields .= '<div class="marginAbove5px">Subject Name ID Format:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["subjectNameIdFormat"] . '</span></div><br>';
        } else {
            $samlFields .= '<div class="marginAbove5px">Subject Name ID Format:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
        }        
    }
    
    //Subject Name ID
    if($samlDetails["encryptedSubject"]["isEncrypted"] == "true") {
        $samlFields .= '<div class="marginAbove5px">Subject Name ID:&nbsp;<span class="boldUl" style="color:red">Value Encrypted</span></div><br>';        
    } else {
        if(isset($samlDetails["assertion"]["subjectNameId"])) {
            $samlFields .= '<div class="marginAbove5px">Subject Name ID:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["subjectNameId"] . '</span></div><br>';
        } else {
            $samlFields .= '<div class="marginAbove5px">Subject Name ID:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
        }        
    }
    
    //Subject Confirmation Method
    if(isset($samlDetails["assertion"]["subjectConfirmationMethod"])) {
        $samlFields .= '<div class="marginAbove5px">Subject Confirmation Method:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["subjectConfirmationMethod"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Subject Confirmation Method:&nbsp;<span class="boldUl style="color:red">Not provided</span></div><br>';
    }
    
    //Subject Confirmation Recipient
    if(isset($samlDetails["assertion"]["subjectConfirmationRecipient"])) {
        $samlFields .= '<div class="marginAbove5px">Subject Confirmation Recipient:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["subjectConfirmationRecipient"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Subject Confirmation Recipient:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Subject Not On Or After
    if(isset($samlDetails["assertion"]["subjectNotOnOrAfter"])) {
        $samlFields .= '<div class="marginAbove5px">Valid Until:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["subjectNotOnOrAfter"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Valid Until:&nbsp;<span class="bolsUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Subject Not On Or After
    if(isset($samlDetails["assertion"]["subjectNotOnOrAfter"])) {
        $samlFields .= '<div class="marginAbove5px">Subject Valid Until:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["subjectNotOnOrAfter"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Subject Valid Until:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Conditions - Not Before
    if(isset($samlDetails["assertion"]["conditionsNotBefore"])) {
        $samlFields .= '<div class="marginAbove5px">Audience Valid After:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["conditionsNotBefore"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Audience Valid After:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Conditions - Not On Or After
    if(isset($samlDetails["assertion"]["conditionsNotOnOrAfter"])) {
        $samlFields .= '<div class="marginAbove5px">Audience Valid Until:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["conditionsNotOnOrAfter"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Audience Valid Until:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
    //Is the assertion valid with current system time?
/*        if(isset($samlDetails["assertion"]["conditionsNotBefore"]) && isset($samlDetails["assertion"]["conditionsNotOnOrAfter"])) {

            $samlFields .= '.&nbsp;&nbsp;This assertion is&nbsp;';

            $now = time();

            if(compareTime($samlDetails["assertion"]["conditionsNotBefore"], $samlDetails["assertion"]["conditionsNotOnOrAfter"], $now) == "valid") {
                $samlFields .= '<span class="boldUl">Valid</span>';
            } else {
                $samlFields .= '<span class="boldUl" style="color:red">Invalid</span>';
            }

            $samlFields .= '&nbsp;since the system time is currently&nbsp;<span class="boldUl">' . date('l, M d, Y', $now) . ' at ' . date('H:i:s', $now) . 'GMT</span>.</div>';

        } else {
            $samlFields .= '.</div>';
        }*/
    if(isset($samlDetails["assertion"]["conditionsNotBefore"]) && isset($samlDetails["assertion"]["conditionsNotOnOrAfter"])) {
        $now = time();
        if(compareTime($samlDetails["assertion"]["conditionsNotBefore"], $samlDetails["assertion"]["conditionsNotOnOrAfter"], $now) == "valid") {
            $samlFields .= '<div class="marginAbove5px">Audience Time Validity:&nbsp;<span class="boldUl">Valid since system time is currently ' . date('l, M d, Y', $now) . ' at ' . date('H:i:s', $now) . '</span></div><br>';
        } else {
            $samlFields .= '<div class="marginAbove5px">Audience Time Validity:&nbsp;<span class="boldUl" style="color:red;">Invalid since system time is currently ' . date('l, M d, Y', $now) . ' at ' . date('H:i:s', $now) . '</span></div><br>';            
        }
    }
    
    //Conditions - Audience
    if(isset($samlDetails["assertion"]["conditionsAudience"])) {
        $samlFields .= '<div class="marginAbove5px">Audience/Intended Entity ID:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["conditionsAudience"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Audience/Intended Entity ID:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Authentication Session Index
    if(isset($samlDetails["assertion"]["authnSessionIndex"])) {
        $samlFields .= '<div class="marginAbove5px">Auth Session Index:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["authnSessionIndex"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Auth Session Index:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Authentication Instant
    if(isset($samlDetails["assertion"]["authnInstant"])) {
        $samlFields .= '<div class="marginAbove5px">Auth Instant:&nbsp;<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["authnInstant"]) . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Auth Instant:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }

    //Authentication Context Class
    if(isset($samlDetails["assertion"]["authnContextClass"])) {
        $samlFields .= '<div class="marginAbove5px">Auth Context Class:&nbsp;<span class="boldUl">' . $samlDetails["assertion"]["authnContextClass"] . '</span></div><br>';
    } else {
        $samlFields .= '<div class="marginAbove5px">Auth Context Class:&nbsp;<span class="boldUl" style="color:red">Not provided</span></div><br>';
    }
    
/*    //Assertion Attributes
    if(isset($samlDetails["assertion"]["attribute"])) {
        
        foreach($samlDetails["assertion"]["attribute"] as $key => $attributeDetails) {
            $samlFields .= '<div>Subject Attribute' . $key . '<br>';
            $samlFields .= '&emsp;&emsp;Attribute Name: <input type="text" name="samlAAN' . $key . '" id="samlAAN' . $key . '" value="' . $attributeDetails["name"] . '"><br>';
            $samlFields .= '&emsp;&emsp;Attribute Name Format: <input type="text" name="samlAANF' . $key . '" id="samlAANF' . $key . '" value="' . $attributeDetails["nameFormat"] . '"><br>';
            $samlFields .= '&emsp;&emsp;Attribute Value(s)<br>';
            
            foreach($attributeDetails["childNode"] as $valueIndex => $attributeValue) {
              $samlFields .= '&emsp;&emsp;&emsp;&emsp;Value' . $valueIndex . ': <input type="text" name="samlAAV' . $key . '-' . $valueIndex . '" id="samlAAV' . $key . '-' . $valueIndex . '" value="' . $attributeValue . '"><br>';
            }
        
            $samlFields .= '</div><br>';
        }
        
    } else {
        $samlFields .= '<div>Subject Attributes: <span style="color:red"><input type="text" name="samlAACC" id="samlAACC" value="Not provided"></span></div><br>';
    }*/

    //Assertion Attributes
    if(isset($samlDetails["assertion"]["attribute"])) {

            $samlFields .= '<div class="marginAbove10px">Additional attributes were provided as part of the assertion.  They are: </div>';        
        
        foreach($samlDetails["assertion"]["attribute"] as $key => $attributeDetails) {
            $samlFields .= '<div class="attributeBox">&#8226;&nbsp;&nbsp;Attribute <span class="boldUl">' . $attributeDetails["name"] . '</span>';
            $samlFields .= ' has a format of&nbsp;<span class="boldUl">' . $attributeDetails["nameFormat"] . '</span>';

            if(count($attributeDetails["childNode"]) == 1) {
                $samlFields .= ' and a value of&nbsp;<span class="boldUl">' . $attributeDetails["childNode"][0] . '</span>.</div>';
            } else {
                $samlFields .= '&nbsp;and has multiple values:<br>';                    
                //go through values
                foreach($attributeDetails["childNode"] as $valueIndex => $attributeValue) {
                    $samlFields .= '&emsp;&emsp;[' . $valueIndex . ']&emsp;<span class="boldUl">' . $attributeValue . '</span><br>';
                }
                $samlFields .= '</div>';
            }
        }
        
    } else {
        $samlFields .= '<div class="marginAbove10px">Subject Attributes: <span style="color:red">Not provided</span></div><br>';
    }

        //Encrypted Attributes
        if($samlDetails["encryptedAttribute"]["isEncrypted"] == "true") {
            $samlFields .= '<div class="marginAbove10px">There are additional attributes which cannot be displayed because they are&nbsp;<span class="boldUl">encrypted</span>.</div>';
        }
    
    
    
/*      //Attributes
        if(isset($samlDetails["assertion"]["attribute"])) {

            $samlFields .= '<div class="marginAbove10px">Additional attributes were provided as part of the assertion.  They are: </div>';

            foreach($samlDetails["assertion"]["attribute"] as $key => $attributeDetails) {
                $samlFields .= '<div class="attributeBox">&#8226;&nbsp;&nbsp;Attribute <span class="boldUl">' . $attributeDetails["name"] . '</span>';
                $samlFields .= ' has a format of&nbsp;<span class="boldUl">' . $attributeDetails["nameFormat"] . '</span>';

                if(count($attributeDetails["childNode"]) == 1) {
                    $samlFields .= ' and a value of&nbsp;<span class="boldUl">' . $attributeDetails["childNode"][0] . '</span>.</div>';
                } else {
                    $samlFields .= '&nbsp;and has multiple values:<br>';
                    //go through values
                    foreach($attributeDetails["childNode"] as $valueIndex => $attributeValue) {
                        $samlFields .= '&emsp;&emsp;[' . $valueIndex . ']&emsp;<span class="boldUl">' . $attributeValue . '</span><br>';
                    }
                    $samlFields .= '</div>';
                }
            }

        } else {
            $samlFields .= '<div class="marginAbove10px"><span class="boldUl">No addtional attribtes were provided with this assertion</span></div>';
        }
        
        //Encrypted Attributes
        if($samlDetails["encryptedAttribute"]["isEncrypted"] == "true") {
            $samlFields .= '<div class="marginAbove10px">There are additional attributes which cannot be displayed because they are&nbsp;<span class="boldUl">encrypted</span>.</div>';
        }*/

    
    
    
        //Get Assertion Attributes
        //$query = ".//samlp:Response/saml:Assertion/saml:AttributeStatement/saml:Attribute";
        //$nodeset = $rootXPath->query($query, $xmlDoc);
        //foreach($nodeset as $key=>$node) {
        //    $attributeArray["assertion"]["attribute"][$key]['name'] = $node->getAttribute('Name');
        //    $attributeArray["assertion"]["attribute"][$key]['nameFormat'] = $node->getAttribute('NameFormat');
        //    $attributeValueIndex = 0;
        //    foreach ($node->childNodes as $valueIndex=>$attributeValue) {
        //        $attributeArray["assertion"]["attribute"][$key]['childNode'][$valueIndex] = $attributeValue->nodeValue;
        //    }
            //*********do for each for multiple attribute values********
            //$attributeArray["assertion"]["attribute"][$key]['childNode'] = $node->childNodes->item(0);            
        //}     
    
    
    /* Assertion Attributes */        
        
    }
        


    return $samlFields;
    
}

function getSimpleFields() {

    $samlDetails = listSAMLDetails();
    $samlFields = "";
    
    /*//SAML Version
    if(isset($samlDetails["response"]["samlVersion"])) {
        $samlFields .= '<div>SAML Version: <input type="text" name="samlVersion" id="samlVersion" value="' . $samlDetails['response']['samlVersion'] . '"></div><br>';
    } else {
        $samlFields .= '<div>SAML Version: <span style="color:red"><input type="text" name="samlVersion" id="samlVersion" value="Not provided"></span></div><br>';
    }*/

    //SAML Version
    if(isset($samlDetails["response"]["samlVersion"])) {
        $samlFields .= '<div class="marginAbove10px">SAML Version&nbsp;<span class="boldUl">' . $samlDetails['response']['samlVersion'] . '</span>';
    } else {
        $samlFields .= '<div class="marginAbove10px">SAML Version&nbsp;<span class="boldUl" style="color:red">Not Provided</span>';
    }
    
    $samlFields .= '&nbsp;assertion created at&nbsp;';
    
    /*//Issued At
    if(isset($samlDetails["response"]["issueInstant"])) {
        $samlFields .= '<div>Issued At: <input type="text" size="100" name="samlIssuerInstant" id="samlIssuerInstant" value="' . formatSamlTime($samlDetails["response"]["issueInstant"]) . 'GMT"></div><br>';
    } else {
        $samlFields .= '<div>Issued At: <span style="color:red"><input type="text" name="samlIssuerInstant" id="samlIssuerInstant" value="Not provided"></span></div><br>';
    }*/

    //Issued At
    if(isset($samlDetails["response"]["issueInstant"])) {
        $samlFields .= '<span class="boldUl">' . formatSamlTime($samlDetails["response"]["issueInstant"]) . 'GMT</span>';
    } else {
        $samlFields .= '<span class="boldUl" style="color:red">Not provided</span>';
    }
 
    /*//Issued By
    if(isset($samlDetails["response"]["issuer"])) {
        $samlFields .= '<div>Issued By: <input type="text" name="samlIssuer" id="samlIssuer" value="' . $samlDetails['response']['issuer'] . '"></div><br>';
    } else {
        $samlFields .= '<div>Issued By: <span style="color:red"><input type="text" name="samlIssuer" id="samlIssuer" value="Not provided"></span></div><br>';
    } */
    
    $samlFields .= '&nbsp;by an IdP identifying itself as&nbsp;';
    
    //Issued By
    if(isset($samlDetails["response"]["issuer"])) {
        $samlFields .= '<span class="boldUl">' . $samlDetails['response']['issuer'] . '</span>.</div>';
    } else {
        $samlFields .= '<span class="boldUl" style="color:red">Not provided</span>.</div>';
    }
    
    /*//Assertion Status
    if(isset($samlDetails["status"]["value"])) {
        
        if($samlDetails["status"]["value"] == "urn:oasis:names:tc:SAML:2.0:status:Success") {
            $samlFields .= '<div>Assertion Status: <input type="text" name="samlSSV" id="samlSSV" value="Success"></div><br>';
        } else {
            $samlFields .= '<div>Assertion Status: <span style="color:red"><input type="text" name="samlSSV" id="samlSSV" value="Not Success"></span></div><br>';
        }
            
    } else {
        $samlFields .= '<div>Assertion Status: <span style="color:red"><input type="text" name="samlSSV" id="samlSSV" value="Not provided"></span></div><br>';
    }
    
    //Subject
    if(isset($samlDetails["assertion"]["subjectNameId"])) {
        $samlFields .= '<div>Subject: <input type="text" name="samlASNId" id="samlASNId" value="' . $samlDetails["assertion"]["subjectNameId"] . '"></div><br>';
    } else {
        $samlFields .= '<div>Subject: <span style="color:red"><input type="text" name="samlASNId" id="samlASNId" value="Not provided"></span></div><br>';
    }*/

    //Assertion Status
    if(isset($samlDetails["status"]["value"])) {
        
        if($samlDetails["status"]["value"] == "urn:oasis:names:tc:SAML:2.0:status:Success") {
            $samlFields .= '<div class="marginAbove10px">The assertion indicates an authentication status of&nbsp;<span class="boldUl" style="color:green;">Success</span>';
        } else {
            $samlFields .= '<div class="marginAbove10px">The assertion indicates an authentication status of&nbsp;<span class="boldUl" style="color:red">Not Success</span>';
        }
            
    } else {
        $samlFields .= '<div><span style="color:red">The assertion did not provide an authentication status</span></div>';
    }

    if($samlDetails["encryptedAssertion"]["isEncrypted"] == "true") {
        
        $samlFields .= '.</div>';
        $samlFields .= '<div class="marginAbove10px">No additional information is available as the entire assertion is&nbsp;<span class="boldUl">encrypted</span>.</div>';
        
    } else {
        
        if($samlDetails["encryptedSubject"]["isEncrypted"] == "true") {
            
            $samlFields .= '.</div>';
            $samlFields .= '<div class="marginAbove10px">Details on the subject are unavailable because they are&nbsp;<span class="boldUl">encrypted</span>.</div>';
                        
        } else {
            
            if(isset($samlDetails["status"]["value"])) {

                $samlFields .= '&nbsp;for the subject&nbsp;';
                //Subject
                if(isset($samlDetails["assertion"]["subjectNameId"])) {
                    $samlFields .= '<span class="boldUl">' . $samlDetails["assertion"]["subjectNameId"] . '</span></div>';
                } else {
                    $samlFields .= '<span class="boldUl" style="color:red">Not provided</span></div>';
                }
            }   

        }
        

        $samlFields .= '<div class="marginAbove10px">The IdP indicates that the assertion is valid between&nbsp;';

        /*//Not Before
        if(isset($samlDetails["assertion"]["conditionsNotBefore"])) {
            $samlFields .= '<div>Audience Valid After: <input type="text" name="samlACNB" id="samlACNB" value="' . formatSamlTime($samlDetails["assertion"]["conditionsNotBefore"]) . '"></div><br>';
        } else {
            $samlFields .= '<div>Audience Valid After: <span style="color:red"><input type="text" name="samlACNB" id="samlACNB" value="Not provided"></span></div><br>';
        }*/

        //Not Before
        if(isset($samlDetails["assertion"]["conditionsNotBefore"])) {
            $samlFields .= '<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["conditionsNotBefore"]) . 'GMT</span>';
        } else {
            $samlFields .= '<span class="boldUl" style="color:red">Not provided</span>';
        }

        $samlFields .= "&nbsp;and&nbsp;";


        //Not On Or After
        if(isset($samlDetails["assertion"]["conditionsNotOnOrAfter"])) {
            $samlFields .= '<span class="boldUl">' . formatSamlTime($samlDetails["assertion"]["conditionsNotOnOrAfter"]) . 'GMT</span>';
        } else {
            $samlFields .= '<span class="boldUl" style="color:red;">Not provided</span>';
        }

        if(isset($samlDetails["assertion"]["conditionsNotBefore"]) && isset($samlDetails["assertion"]["conditionsNotOnOrAfter"])) {

            $samlFields .= '.&nbsp;&nbsp;This assertion is&nbsp;';

            $now = time();

            if(compareTime($samlDetails["assertion"]["conditionsNotBefore"], $samlDetails["assertion"]["conditionsNotOnOrAfter"], $now) == "valid") {
                $samlFields .= '<span class="boldUl">Valid</span>';
            } else {
                $samlFields .= '<span class="boldUl" style="color:red">Invalid</span>';
            }

            $samlFields .= '&nbsp;since the system time is currently&nbsp;<span class="boldUl">' . date('l, M d, Y', $now) . ' at ' . date('H:i:s', $now) . 'GMT</span>.</div>';

        } else {
            $samlFields .= '.</div>';
        }

        /*//Attributes
        if(isset($samlDetails["assertion"]["attribute"])) {

            foreach($samlDetails["assertion"]["attribute"] as $key => $attributeDetails) {
                $samlFields .= '<div>Subject Attribute' . $key . '<br>';
                $samlFields .= '&emsp;&emsp;Attribute Name: <input type="text" name="samlAAN' . $key . '" id="samlAAN' . $key . '" value="' . $attributeDetails["name"] . '"><br>';
                $samlFields .= '&emsp;&emsp;Attribute Name Format: <input type="text" name="samlAANF' . $key . '" id="samlAANF' . $key . '" value="' . $attributeDetails["nameFormat"] . '"><br>';
                $samlFields .= '&emsp;&emsp;Attribute Value(s)<br>';

                foreach($attributeDetails["childNode"] as $valueIndex => $attributeValue) {
                  $samlFields .= '&emsp;&emsp;&emsp;&emsp;Value' . $valueIndex . ': <input type="text" name="samlAAV' . $key . '-' . $valueIndex . '" id="samlAAV' . $key . '-' . $valueIndex . '" value="' . $attributeValue . '"><br>';
                }

                $samlFields .= '</div><br>';
            }

        } else {
            $samlFields .= '<div>Subject Attributes: <span style="color:red"><input type="text" name="samlAACC" id="samlAACC" value="Not provided"></span></div><br>';
        }*/

        //Attributes
        if(isset($samlDetails["assertion"]["attribute"])) {

            $samlFields .= '<div class="marginAbove10px">Additional attributes were provided as part of the assertion.  They are: </div>';

            foreach($samlDetails["assertion"]["attribute"] as $key => $attributeDetails) {
                $samlFields .= '<div class="attributeBox">&#8226;&nbsp;&nbsp;Attribute <span class="boldUl">' . $attributeDetails["name"] . '</span>';
                $samlFields .= ' has a format of&nbsp;<span class="boldUl">' . $attributeDetails["nameFormat"] . '</span>';

                if(count($attributeDetails["childNode"]) == 1) {
                    $samlFields .= ' and a value of&nbsp;<span class="boldUl">' . $attributeDetails["childNode"][0] . '</span>.</div>';
                } else {
                    $samlFields .= '&nbsp;and has multiple values:<br>';
                    //go through values
                    foreach($attributeDetails["childNode"] as $valueIndex => $attributeValue) {
                        $samlFields .= '&emsp;&emsp;[' . $valueIndex . ']&emsp;<span class="boldUl">' . $attributeValue . '</span><br>';
                    }
                    $samlFields .= '</div>';
                }
            }

        } else {
            $samlFields .= '<div class="marginAbove10px"><span class="boldUl">No addtional attribtes were provided with this assertion</span></div>';
        }
        
        //Encrypted Attributes
        if($samlDetails["encryptedAttribute"]["isEncrypted"] == "true") {
            $samlFields .= '<div class="marginAbove10px">There are additional attributes which cannot be displayed because they are&nbsp;<span class="boldUl">encrypted</span>.</div>';
        }

    }
    
    
    
    return $samlFields;
    
}

function formatSamlTime($samlTimeString) {
    
    $datepart = date("l, M d, Y", strtotime(substr($samlTimeString, 0, strpos($samlTimeString, "T"))));
    $timepart = date("H:i:s", strtotime(substr($samlTimeString, strpos($samlTimeString, "T") + 1, -1)));
    
    $formattedTime = $datepart . " at " . $timepart;
    
    return $formattedTime;
}

function compareTime($notBefore, $notOnOrAfter, $now) {
    $notBeforeTime = strtotime($notBefore);
    $notOnOrAfterTime = strtotime($notOnOrAfter);
    
    if(($now >= $notBeforeTime) && ($now < $notOnOrAfterTime)) {
        return 'valid';
    } else {
        return 'invalid';
    }
}

function stripLineFeeds($value) {
    $tempvalue = str_replace(chr(10), "", $value);
    $tempvalue = str_replace(chr(13), "", $tempvalue);
    return $tempvalue;
}

?>

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
    
  <nav class="navbar">
    <div class="navbar-container">
      <div id="navbar-left" name="navbar-left" class="navbar-left">
          <span class="navtextlarge">Assertion Validator</span>
      </div>
      <div id="navbar-right" name="navbar-right" class="navbar-right">
      </div>
    </div>
  </nav>
    
  
  <div class="mainContainer">
    
    <!-- refEntryDiv is displayed if a reference value is passed in -->
    <div class="refBox" id="refEntryDiv">
     <div>
      <form method="POST" action="getVars.php">
        <div>
          <div class="internalBoxClose">
              Reference Adapter Receiver
          </div>
          <div class="internalBoxClose">
            <label for="refID">Reference ID</label>
            <input type="text" class="textItem" name="refID" id="refID" readonly onkeyup="javascript:buildURL();">
          </div>
          <div class="internalBoxClose">
            <label for="pickupURL">PF Server IP:Port</label>
            <input type="text" class="textItem" name="pickupURL" id="pickupURL" required onkeyup="javascript:buildURL();">
          </div>            
          <div class="internalBoxClose">
            <label for="rAID">Ref Adapter ID</label>
            <input type="text" class="textItem" name="rAID" id="rAID" required onkeyup="javascript:buildInstanceHeader();">
          </div>     
          <div class="internalBoxClose">
            <label for="adapterUser">Adapter User</label>
            <input type="text" class="textItem" name="adapterUser" id="adapterUser" required onkeyup="javascript:buildAuthHeader();">
          </div>
          <div class="internalBoxClose">
            <label for="adapterPass">Adapter Password</label>
            <input type="text" class="textItem" name="adapterPass" id="adapterPass" required onkeyup="javascript:buildAuthHeader();">
          </div>
        </div>
        <div class="internalBoxSpaced">  
            <input type="submit" value="Retrieve Parameters">
            <!---<a href="#" class="btn btn-default" onclick="javascript:retrieveParameters();">Retrieve Parameters</a>--->
        </div>
      </form>
     </div>
    </div>
      
    <div class="refPostBox" id="refCall">
     <div>
        <div>
          <div class="internalBoxClose">
              Reference Callback
          </div>
          <div class="internalBoxClose">
              URL<br>
              <div id="urlDiv"><pre><code id="apiURL"></code></pre></div>
          </div>
          <div class="internalBoxClose">
              POST Headers<br>
              <div id="authHeaderDiv"><pre><code id="authHeader"></code></pre></div>
              <div id="instanceDiv"><pre><code id="instanceHeader"></code></pre></div>
          </div>
        </div>
     </div>
    </div>      
      
  </div>
    
    <!-- Raw SAML -->
    <div class="samlBox" name="decodedSamlDiv" id="decodedSamlDiv">
        <div class="topBar">
            SAML Assertion - Raw
        </div>
        <div class="internalBoxClose">
            <pre class="rawPreBox" style="overflow:scroll" name="decodedSAML" id="decodedSAML" ></pre>
        </div>
    </div>

    <!-- Detailed SAML Information -->
    <div class="samlBox" name="detailedSamlDiv" id="detailedSamlDiv">
        <div class="topBar">
            SAML Assertion - Verbose Detail
        </div>
        <div class="internalBoxClose">
            <div class="verboseDetailHeader">
                Response Information
            </div>
            <div class="sRABox" name="samlResponseAttributes" id="samlResponseAttributes">
            <!--Will be replaced with assertion details--->
            </div>
            <div class="verboseDetailHeader">
                Signature Information
            </div>
            <div class="sSigABox" name="samlSignatureAttributes" id="samlSignatureAttributes">
            <!--Will be replaced with assertion details--->
            </div>
            <div class="verboseDetailHeader">
                Status Information
            </div>
            <div class="sStatusABox" name="samlStatusAttributes" id="samlStatusAttributes">
            <!--Will be replaced with assertion details--->
            </div>
            <div class="verboseDetailHeader">
                Assertion Attribute Information
            </div>
            <div class="sAABox" name="samlAssertiongAttributes" id="samlAssertionAttributes">
            <!--Will be replaced with assertion details--->
            </div>
        </div>
    </div>

    <!-- simpleSamlDiv is displayed if a SAML value is passed in -->
    <div class="samlBox" id="simpleSamlDiv">
        <div class="topBar">
            SAML Assertion - Basic Detail
        </div>
          <div class="internalBoxClose">
            <div class="basicDetail" name="assertionInformation" id="assertionInformation">
                <div class="assertionInformationBox" name="assertionInformation" id="assertionInformation">
                <!--Will be replaced with assertion details--->
                </div>
            </div>
          </div>
        <div class="internalBoxSpaced">  
            <input type="submit" value="Do Nothing">
            <!---<a href="#" class="btn btn-default" onclick="javascript:retrieveParameters();">Retrieve Parameters</a>--->
        </div>
     </div>
    
    <!-- End Login Form -->    
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    
    <script>
        
        <?php
              if(isset($_POST["REF"])) {
                    echo "var postType = 'REF';";
                    echo "var refValue = " . $_POST["REF"] . ";";
              }
              if(isset($_POST["SAMLResponse"])) {
                    echo "var postType = 'SAML';" . PHP_EOL;
                    $decodedSAMLResponse = formatSAML(base64_decode($_POST["SAMLResponse"]));
                    echo "var decodedSAMLResponse = '" . $decodedSAMLResponse . "';" . PHP_EOL;
                    //$samlElements = listSAMLDetails($decodedSAMLResponse);
                    //echo "var samlElements = '" . $samlElements . "';";
              }
        ?>
        
        var instanceHeader = "";
        var authHeader = "";
        var refURL = "";
        
        function buildURL() {
            //server + path + ref + refID
            refURL = "https://" + $('#pickupURL').val() + "/ext/ref/pickup?REF=" + refvalue;
            $('#apiURL').text(refURL);
        }
        
        function buildInstanceHeader() {
            instanceHeader = $('#rAID').val();
            $('#instanceHeader').text("ping.instanceId: " + instanceHeader);
        }
        
        function buildAuthHeader() {
            authHeader = btoa($('#adapterUser').val() + ":" + $('#adatperPass').val());
            $('#authHeader').text("Authorization: BASIC " + authHeader);
        }
        
        function showSamlDetail() {
            
        }
        
        function showSamlRaw() {
            
            //Hide all SAML Divs
            $('#simpleSamlDiv').css('display', 'none');
            $('#detailedSamlDiv').css('display', 'none');
            
            $('#decodedSAML').html(decodedSAMLResponse);
            $('#decodedSamlDiv').css('display','flex');
            
        }
                
        function showSamlBasic() {
            
            //Hide all SAML Divs
            $('#detailedSamlDiv').css('display', 'none');
            $('#decodedSamlDiv').css('display', 'none');
            
            $('#simpleSamlDiv').css('display','flex');
            
        }

        function showSamlDetail() {
            
            //Hide all SAML Divs
            $('#simpleSamlDiv').css('display', 'none');
            $('#decodedSamlDiv').css('display', 'none');
            
            $('#detailedSamlDiv').css('display','flex');
            
        }
        
        
        $(document).ready(function () {
            
            console.log('Checking input');
            //Reference type
            if(postType == "REF"){
                //Populate Ref ID value in refEntryDiv
                $('#refID').val(refValue);

                //Display refEntryDiv
                $('#refEntryDiv').css('display', 'flex');
                
                //Populate base values in header
                $('#authHeader').val("Authorization: BASIC ");
                $('#instanceHeader').val("ping.instanceId: ");
                
                //Display refCall
                $('#refCall').css('display', 'flex');
            }
            
            if(postType == "SAML"){
                console.log('SAML assertion provided');
                
                //Create nav links
                let rightNav = '<span class="navtext"><a href="#" onClick="javascript:showSamlBasic();">Show SAML Basic</a>&emsp;&emsp;|&emsp;&emsp;<a href="#" onClick="javascript:showSamlDetail();">Show SAML Details</a>&emsp;&emsp;|&emsp;&emsp;<a href="#" onClick="javascript:showSamlRaw();">Show Raw SAML</a></span>';
                $('#navbar-right').html(rightNav);
                
                let samlResponseFields = '<?php echo(getSAMLResponseFields()); ?>';
                $('#samlResponseAttributes').html(samlResponseFields);
                
                let samlSignatureFields = '<?php echo(getSAMLSignatureFields()); ?>';
                $('#samlSignatureAttributes').html(samlSignatureFields);
                
                let samlStatusFields = '<?php echo(getSAMLStatusFields()); ?>';
                $('#samlStatusAttributes').html(samlStatusFields);

                let samlAssertionFields = '<?php echo(getSAMLAssertionFields()); ?>';
                $('#samlAssertionAttributes').html(samlAssertionFields);
                
                let simpleFields = '<?php echo(getSimpleFields()); ?>';
                $('#assertionInformation').html(simpleFields);
                                
                //Display Simple SAML Information
                $('#simpleSamlDiv').css('display', 'flex');
                
             
            }
                
        })
    </script>
    
</body>
</html>