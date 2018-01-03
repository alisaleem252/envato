<?php
include('envato-api-class.php');
if(isset($_POST['submit']))
{

    // Assign data to variables
    $username = $_POST['username'];
    $item_id = $_POST['item_id'];
    $purchase_code = $_POST['purchase_code'];

    // Check if all fields are filled in
    if(!empty($username) && !empty($item_id) && !empty($purchase_code))
    {
        $API = new envatoAPI();
        $API->set_api_key('ahdio270410ayap20hkdooxaadht5s');
        $API->set_username('JohnDoe');
        $API->set_api_set('verify-purchase:' . $purchase_code);

        $data = $API->request();

        if(!empty($data))
        {
            // We got a valid API response let's match the item id and the username
            if($data['verify-purchase']['item_id'] == $item_id && $data['verify-purchase']['buyer'] == $username)
            {
                // Everything seems to be correct! Purchase verified!
                // Show some info like purchase date and licence
                echo '<p>You have purchased the item on ' . $data['verify-purchase']['created_at'] . ' with the ' . $data['verify-purchase']['licence'] . '!</p>';
            }

        }
        else{
            // Response from the API was empty, return error
            echo '<p>Sorry, we are unable to verify your purchase.</p>';
        }

    }
}
?>


<style>
form{
    background: #f8f8f8;
    width: 300px;
    border: 1px solid #e2e2e2;
}
form fieldset{
    border: 0;
}
form label{
    display: block;
    padding: 3px;
}
form input[type=text]{
    padding: 3px;
    border: 1px solid #d7d7d7;
}
</style>

<h2>Verify Purchase</h2>

<form action="" method="post">
    <fieldset>
        <p><label>Marketplace Username:</label>
        <input type="text" name="username" value="" /></p>

        <p><label>Item ID:</label>
        <input type="text" name="item_id" value="" /></p>

        <p><label>Purchase code:</label>
        <input type="text" name="purchase_code" value="" /></p>

        <p><input type="submit" name="submit" value="Verify" /></p>
    </fieldset>
</form>
