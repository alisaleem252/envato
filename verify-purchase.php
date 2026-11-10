<?php
require __DIR__ . '/envato-api-class.php';

// Your personal token needs the "View and search Envato sites" and
// "View your items' sales history" permissions.
// Set it as an environment variable (ENVATO_TOKEN) rather than hardcoding it.
$result = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id       = trim(isset($_POST['item_id']) ? $_POST['item_id'] : '');
    $purchase_code = trim(isset($_POST['purchase_code']) ? $_POST['purchase_code'] : '');

    if ($item_id === '' || $purchase_code === '') {
        $result = '<p>Please fill in all fields.</p>';
    } else {
        $API  = new envatoAPI(getenv('ENVATO_TOKEN') ?: null);
        $sale = $API->verify_purchase_for_item($purchase_code, $item_id);

        if ($sale) {
            $supported = $API->is_supported($sale) ? 'active' : 'expired';
            $result = '<p>Purchase verified. Bought on ' . htmlspecialchars($sale['sold_at'])
                    . ' with the ' . htmlspecialchars($sale['license']) . ' license. Support: '
                    . $supported
                    . (!empty($sale['supported_until']) ? ' (until ' . htmlspecialchars($sale['supported_until']) . ')' : '')
                    . '.</p>';
        } else {
            $result = '<p>Sorry, we are unable to verify your purchase.</p>';
        }
    }
}
?>
<style>
form{ background:#f8f8f8; width:300px; border:1px solid #e2e2e2; }
form fieldset{ border:0; }
form label{ display:block; padding:3px; }
form input[type=text]{ padding:3px; border:1px solid #d7d7d7; }
</style>

<h2>Verify Purchase</h2>
<?php echo $result; ?>

<form action="" method="post">
    <fieldset>
        <p><label>Item ID:</label>
        <input type="text" name="item_id" value="" /></p>

        <p><label>Purchase code:</label>
        <input type="text" name="purchase_code" value="" /></p>

        <p><input type="submit" name="submit" value="Verify" /></p>
    </fieldset>
</form>
