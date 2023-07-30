<?php
header("content-type:text/plain; charset=ISO-8859-15");
require 'vendor/autoload.php';
require 'extlib/xmlseclibs/xmlseclibs.php';
require 'lib/Saml2/Utils.php';
require 'lib/Saml2/Constants.php';

if (isset($_GET['SAMLRequest'])) {
  // Retrieve the SAMLRequest value
  $samlRequest = $_GET['SAMLRequest'];
  // Now you can use $samlRequest for further processing or decoding the SAML request data
  // For example, you can use base64_decode() to decode the SAML request:
  $decodedSamlRequest = base64_decode($samlRequest);
  //echo $decodedSamlRequest;
  // Convert the decoded SAMLRequest string to an XML object
  $xml = simplexml_load_string($decodedSamlRequest);
  //echo $xml;

  if ($xml === false) {
    // Handle any errors that occur during XML parsing
    echo "Error parsing the SAMLRequest XML.";
  } else {
  // Now you can work with the XML object
      // For example, you can print the XML content or access specific elements:
        //echo "SAMLRequest XML:";
        //echo "<pre>" . htmlspecialchars($decodedSamlRequest) . "</pre>";
  
        // Register the SAML namespace with a prefix
        $xml->registerXPathNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xml->registerXPathNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
  
        // Use XPath to access elements within the SAML namespace
        $issuer = $xml->xpath('//saml:Issuer');
        $authNRequest = $xml->xpath('//samlp:AuthnRequest');
        if (count($issuer) > 0) {
          // Access the first Issuer element in the XML
          $issuerValue = (string)$issuer[0];
          //Validate the McAfee issuer here
          //
          //

          //Picking the issuer ID from the AuthNRequest
          $issueID = (string)$authNRequest[0]['ID'];

          // Replace these with your own values
          $issuer = 'https://partner-test.com';
          $ccid = '12345';
          $affid = '67890';
          $culture = 'en-US';
          //Set this value as per the SAMLResponse type required
          $signAssertion = true;
          $acsUrl = 'https://idpartner.mcafee.com/login/callback';

          // Create a new DOMDocument for the SAMLResponse
          $doc = new DOMDocument('1.0');
          $doc->formatOutput = true;

          // Create the SAMLResponse root element
          $samlResponse = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:protocol', 'samlp:Response');
          $doc->appendChild($samlResponse);

          // Set attributes for the SAMLResponse
          $samlResponse->setAttribute('ID', '_' . uniqid('saml-response-'));
          $samlResponse->setAttribute('Version', '2.0');
          $samlResponse->setAttribute('IssueInstant', date('Y-m-d\TH:i:s\Z'));
          $samlResponse->setAttribute('Destination', $acsUrl);
          $samlResponse->setAttribute('InResponseTo', $issueID);

          // Create the Issuer element
          $issuerElement = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Issuer', $issuer);
          $samlResponse->appendChild($issuerElement);

          // Create the Status element
          $status = $doc->createElement('samlp:Status');
          $samlResponse->appendChild($status);

          // Create the StatusCode element
          $statusCode = $doc->createElement('samlp:StatusCode');
          $statusCode->setAttribute('Value', 'urn:oasis:names:tc:SAML:2.0:status:Success');
          $status->appendChild($statusCode);

          // Create the Assertion element with the saml namespace prefix
          $assertion = $doc->createElementNS('urn:oasis:names:tc:SAML:2.0:assertion', 'saml:Assertion');
          $samlResponse->appendChild($assertion);
          // Set attributes for the Assertion
          $assertion->setAttribute('ID', '_' . uniqid('saml-assertion-'));
          $assertion->setAttribute('Version', '2.0');
          $assertion->setAttribute('IssueInstant', date('Y-m-d\TH:i:s\Z'));
          
          // Create the Issuer element inside the Assertion
          $issuerElement = $doc->createElement('saml:Issuer', $issuer);
          $assertion->appendChild($issuerElement);
          
          // Create the AttributeStatement element
          $attributeStatement = $doc->createElement('saml:AttributeStatement');
          $assertion->appendChild($attributeStatement);
          
          // Create and append the CCID attribute
          $ccidAttribute = $doc->createElement('saml:Attribute');
          $ccidAttribute->setAttribute('Name', 'ccid');
          //$ccidAttribute->setAttribute('Format', 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified');
          $ccidAttributeValue = $doc->createElement('saml:AttributeValue', $ccid);
          $ccidAttribute->appendChild($ccidAttributeValue);
          $attributeStatement->appendChild($ccidAttribute);
          
          // Create and append the AFFID attribute
          $affidAttribute = $doc->createElement('saml:Attribute');
          $affidAttribute->setAttribute('Name', 'affid');
          //$affidAttribute->setAttribute('Format', 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified');
          $affidAttributeValue = $doc->createElement('saml:AttributeValue', $affid);
          $affidAttribute->appendChild($affidAttributeValue);
          $attributeStatement->appendChild($affidAttribute);
          
          // Create and append the Culture attribute
          $cultureAttribute = $doc->createElement('saml:Attribute');
          $cultureAttribute->setAttribute('Name', 'culture');
          //$cultureAttribute->setAttribute('Format', 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified');
          $cultureAttributeValue = $doc->createElement('saml:AttributeValue', $culture);
          $cultureAttribute->appendChild($cultureAttributeValue);
          $attributeStatement->appendChild($cultureAttribute);
          
          // Sign the Assertion
          //$privateKey = '/path/to/your/private_key.pem'; // Replace with the path to your private key
          //$certificate = '/path/to/your/certificate.pem'; // Replace with the path to your certificate
          $privateKey = __DIR__ . '/certs/biglobe-test-private.pem'; // Replace with the path to your private key
          $certificate = __DIR__ . '/certs/biglobe-test-public.pem'; // Replace with the path to your certificate
                  
          
          $strAssertion = simplexml_import_dom($assertion)->saveXML();
          if (!$signAssertion) {
            # code...
            $strTest = OneLogin_Saml2_Utils::addSign($doc->saveXML(), file_get_contents($privateKey), file_get_contents($certificate));
          }
          else {
            # code...
            echo "===========Attempting to sign only Assertion-========== ";
            $strTest = OneLogin_Saml2_Utils::addSign($strAssertion, file_get_contents($privateKey), file_get_contents($certificate));
          
            $newdoc = new DOMDocument();
            $newdoc->loadXML($strTest);
          }
          
          //echo $strTest . "\n----------------------\n";
          
          $xpath = new DOMXPath($doc);
          
          $fragment = $doc->createDocumentFragment();
          $fragment->appendXML(preg_replace("/<\\?xml.*\\?>/", '', $strTest));
          
          $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
          $finalNode = $xpath->query('//saml:Assertion')->item(0)->parentNode->replaceChild($fragment,$assertion);
          echo $doc->saveXML();
          $parameters = array('SAMLResponse' => base64_encode($doc->saveXML()));
          $parameters['RelayState'] = $_GET['RelayState'];
          }
          }
          }
?>
