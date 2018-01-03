# Envato
Envato Marketplace API Class Updated

# Envato API Class
get your api key from https://build.envato.com/register/


# Usage
        include('envato-api-class.php');

        $API = new envatoAPI();
        $API->set_api_key('ahdio270410ayap20hkdooxaadht5s');
        $API->set_username('JohnDoe');
        $API->set_api_set('verify-purchase:' . $purchase_code);

# OR
       $API->set_api_set('popular:codecanyon'); // Set the API set to request
       $data = $API->request();
