<?php
/**
 * [envatoAPI Example Got From Tutsplus Article]
 * 01/03/2018
 * Using API Set
 */

class envatoAPI{

    private $api_url = 'http://marketplace.envato.com/api/edge/'; // Default URL
    private $api_set; // This will hold the chosen API set like "user-items-by-site"
    private $username; // The username of the author only needed to access the private sets
    private $api_key; // The api key of the author only needed to access the private sets


    /**
    * set_api_url()
    *
    * Set the API URL
    *
    * @access   public
    * @param    string
    * @return       void
    */
    public function set_api_url($url)
    {
        $this->api_url = $url;
    }


    /**
    * get_api_url()
    *
    * Return the API URL
    *
    * @access   public
    * @return       string
    */
    public function get_api_url()
    {
        return $this->api_url;
    }

    /**
    * set_username()
    *
    * Set the Username
    *
    * @access   public
    * @param    string
    * @return       void
    */
    public function set_username($username)
    {
        $this->username = $username;
    }


    /**
    * set_api_key()
    *
    * Set the API key
    *
    * @access   public
    * @param    string
    * @return       void
    */
    public function set_api_key($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
    * set_api_set()
    *
    * Set the API set
    *
    * @access   public
    * @param    string
    * @return       void
    */
    public function set_api_set($api_set)
    {
        $this->api_set = $api_set;
    }


    /**
    * request()
    *
    * Request data from the API
    *
    * @access   public
    * @param    void
    * @return       array
    */
    public function request() {

      if(!empty($this->username) && !empty($this->api_key))
    {
        // Build the private url
        $this->api_url .= $this->username . '/'.$this->api_key.'/'.$this->api_set . '.json'; // Sample: http://marketplace.envato.com/api/edge/JohnDoe/ahdio270410ayap20hkdooxaadht5s/popular:codecanyon.json
    }
    else
    {
        // Build the public url
        $this->api_url .=  $this->api_set . '.json'; // Sample: http://marketplace.envato.com/api/edge/popular:codecanyon.json
    }


    $ch = curl_init($this->api_url); // Initialize a cURL session & set the API URL
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); //The number of seconds to wait while trying to connect
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string instead of outputting it out directly.
    $ch_data = curl_exec($ch); // Perform a cURL session
    curl_close($ch);  //Close a cURL session


    // Check if the variable contains data
    if(!empty($ch_data))
    {
        return json_decode($ch_data, true); // Decode the requested data into an array
    }
    else
    {
        return('We are unable to retrieve any information from the API.'); // Return error message
    }


}


}
