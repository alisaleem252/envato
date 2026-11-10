# Envato
PHP class for the current Envato API (v3, token auth). Verify purchase codes, list sales, search items and more.

> The old `marketplace.envato.com/api/edge` (username + API key) API has been retired. This class now uses `https://api.envato.com` with a personal token.

## Setup
1. Create a personal token at https://build.envato.com/create-token/ with the permissions you need (e.g. *View and search Envato sites*, *View your items' sales history*).
2. Pass it to the class or export `ENVATO_TOKEN`. Never commit it.

Requires PHP 5.6+ with cURL.

## Usage
```php
require 'envato-api-class.php';

$API = new envatoAPI('YOUR_TOKEN');   // or leave empty and set ENVATO_TOKEN
```

### Verify a purchase (author)
```php
$sale = $API->verify_purchase($purchase_code);                 // sale array or false
$sale = $API->verify_purchase_for_item($purchase_code, 12345); // also checks item id
if ($sale && $API->is_supported($sale)) { /* support active */ }
```

### Other methods
| Method | Endpoint |
|---|---|
| `author_sales($page)` / `author_sales_all()` | `/v3/market/author/sales` |
| `buyer_purchase($code)` / `buyer_purchases()` | `/v3/market/buyer/purchase`, `/list-purchases` |
| `buyer_download($item_id, $code)` | `/v3/market/buyer/download` |
| `item($id)` / `collection($id)` | `/v3/market/catalog/item`, `/collection` |
| `search_items(['site'=>'codecanyon.net','term'=>'seo'])` | `/v1/discovery/search/search/item` |
| `popular('codecanyon')` | `/v1/market/popular:codecanyon.json` |
| `username()` / `email()` / `account()` | `/v1/market/private/user/*.json` |
| `get($path, $params)` | any endpoint |

On failure methods return `false`; see `$API->last_status` and `$API->last_error`.

### Features
- Bearer-token auth with proper `User-Agent`, TLS verification and timeouts
- Automatic single retry on HTTP 429 (honours `Retry-After`)
- Optional file cache: `$API->set_cache(__DIR__.'/cache', 300);`
- Purchase code format validation before hitting the API

### Legacy usage
`set_api_set('popular:codecanyon')` / `set_api_set('verify-purchase:CODE')` plus `request()` still work and are mapped to the new endpoints. `set_api_key()` is an alias of `set_token()`; `set_username()` is ignored.

## Examples
- `popular.php` - popular items
- `verify-purchase.php` - purchase verification form
