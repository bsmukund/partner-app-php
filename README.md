# partner-app-php
A very simple PHP implementation of the SAML flow required for the integration.
Note: This example currently does not contain the user session login auth & picking user identifiers 
The flow is:
1. The SAMLRequest is sent to the sso.php which acts as the SSO login URL of the partner
2. The requestId is picked from the SAMLRequest and used in the issueID field
3. Further, this app uses the library to generate the SAMLResponse using the cert mentioned in the folder and prints the final response on the output
(Note: This will in future be developed to be a complete app)
4. Since we have possibilities of using:
  a. Signed Assertion
  b. Signed Response
The $signAssertion var is used to send the required input to the signature generating function - addSign()

Constants to be set:
1. The certs are to be stored in the certs/ folder of the project
2. The $privateKey and $certificate are the variables incharge

Partners have to verify:
1. The issuer from the incoming SAMLRequest
