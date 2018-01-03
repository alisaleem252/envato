<?php
include('envato-api-class.php');

$API = new envatoAPI();

//$API->set_api_url();
//$API->set_api_key('ahdio270410ayap20hkdooxaadht5s');
//$API->set_username('JohnDoe'); // Envato username
$API->set_api_set('popular:codecanyon'); // Set the API set to request

echo '<pre>';
print_r( $API->request() );
echo '</pre>';
?>
