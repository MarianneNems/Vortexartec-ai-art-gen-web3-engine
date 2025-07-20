# Vortex Artec Professional Platform

**Version:** 2.1.0  
**Author:** Vortex Artec Development Team  
**License:** GPL v2 or later  
**Requires:** WordPress 6.0+, PHP 8.0+

A complete blockchain marketplace platform with professional swap shortcodes and modal agreement system for seed artwork commitment and TOLA masterpiece participation.

## 🚀 Features

### Core Blockchain Shortcodes

- **[vortex_swap]** - Token swap interface
- **[vortex_wallet]** - Wallet dashboard
- **[vortex_metric_ranking]** - User leaderboard

### Modal Agreement System

- **Seed Artwork Commitment** - Required at registration
- **TOLA Masterpiece Opt-In** - Optional for marketplace uploads

### Technical Features

- Multi-network blockchain support (Ethereum, Polygon, BSC)
- Professional responsive UI with magenta branding
- Real-time exchange rates and wallet integration
- Comprehensive security and validation
- REST API endpoints for all functionality

## 📦 Installation

1. Upload the plugin files to `/wp-content/plugins/vortex-artec-professional/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure settings if needed (plugin works out of the box)

## 🎯 Core Shortcodes Usage

### 1. Token Swap Interface

```php
[vortex_swap]
```

**Attributes:**
- `theme` - Theme variant (default: 'default')
- `width` - Container width (default: '100%')
- `height` - Container height (default: 'auto')
- `default_from` - Default from token (default: 'ETH')
- `default_to` - Default to token (default: 'USDC')

**Example:**
```php
[vortex_swap theme="dark" default_from="ETH" default_to="USDC"]
```

**Features:**
- Real-time exchange rates
- Multi-token support (ETH, USDC, USDT, DAI, WBTC)
- Flip tokens functionality
- Loading states and error handling
- Mobile responsive design

### 2. Wallet Dashboard

```php
[vortex_wallet]
```

**Attributes:**
- `theme` - Theme variant (default: 'default')
- `show_balances` - Show token balances (default: 'true')
- `show_transactions` - Show transaction history (default: 'true')
- `show_actions` - Show send/receive buttons (default: 'true')
- `max_transactions` - Maximum transactions to display (default: '10')

**Example:**
```php
[vortex_wallet show_balances="true" show_transactions="true" max_transactions="5"]
```

**Features:**
- MetaMask/Solana wallet connection
- Token balances display
- Transaction history
- Send/Receive modals with QR codes
- Address copying functionality

### 3. User Metrics Leaderboard

```php
[vortex_metric_ranking]
```

**Attributes:**
- `theme` - Theme variant (default: 'default')
- `metric` - Metric type (default: 'volume')
- `timeframe` - Time period (default: '7d')
- `limit` - Number of users to show (default: '50')
- `show_filters` - Show filter controls (default: 'true')
- `show_search` - Show search functionality (default: 'true')

**Example:**
```php
[vortex_metric_ranking metric="sales" timeframe="30d" limit="25"]
```

**Features:**
- Sortable columns (Rank, User, Value, Change, Address)
- Timeframe filters (24h, 7d, 30d, 90d, all-time)
- Search by username or address
- Pagination for large datasets
- Real-time data updates

## 🔐 Modal Agreement System

### Seed Artwork Commitment (Registration)

**Trigger:** Automatically appears during user registration  
**Requirement:** Mandatory for artist role assignment

**Agreement Text:**
> By clicking "I Agree," you commit to upload at least two new artworks weekly into your private library and uphold the highest ethics in all transactions.

**Implementation:**
- Blocks registration if declined
- Stores `seed_commitment_agreed = 1` in user meta
- Automatically assigns "artist" role when agreed
- Tracks agreement in database with IP and timestamp

### TOLA Masterpiece Opt-In (Product Publishing)

**Trigger:** Appears when publishing products to marketplace  
**Requirement:** Optional but required for TOLA participation

**Agreement Text:**
> By clicking "I Agree," you opt in to the daily TOLA Masterpiece program for this artwork. You understand:
> 
> • Daily at 00:00 ET, HURAII will include all opted-in artworks in a composite NFT masterpiece.  
> • 15% commission to marketplace; of the remaining 85%, 5% to Mariana Villard and 95% split equally among participants.  
> • You cover any gas fees and shipping costs for physical sales, and commit to ethical fulfillment.

**Implementation:**
- Saves `tola_participate = 1` in post meta if agreed
- Only includes opted-in artworks in daily masterpiece scheduler
- Checkbox integration in product edit interface
- Modal validation before saving

## 🌐 REST API Endpoints

### Swap Endpoint
```
POST /wp-json/vortex/v1/swap
```

**Parameters:**
- `from_token` (required) - Source token symbol
- `to_token` (required) - Destination token symbol  
- `amount` (required) - Amount to swap

**Response:**
```json
{
  "success": true,
  "data": {
    "from_token": "ETH",
    "to_token": "USDC", 
    "from_amount": "1.0",
    "to_amount": "2500.00",
    "exchange_rate": 2500.00,
    "transaction_hash": "0x...",
    "timestamp": 1234567890
  }
}
```

### Wallet Endpoint
```
GET /wp-json/vortex/v1/wallet
```

**Response:**
```json
{
  "success": true,
  "data": {
    "address": "0x...",
    "balances": [...],
    "transactions": [...]
  }
}
```

### Metrics Endpoint
```
GET /wp-json/vortex/v1/metrics?timeframe=7d&metric=volume&limit=50
```

**Response:**
```json
{
  "success": true,
  "data": {
    "users": [...],
    "total": 50,
    "page": 1,
    "per_page": 50
  },
  "meta": {
    "timeframe": "7d",
    "metric": "volume",
    "last_updated": 1234567890
  }
}
```

## 🎨 Customization

### CSS Variables

The plugin uses CSS custom properties for easy theming:

```css
:root {
  --vortex-primary: #FF00FF;
  --vortex-primary-light: #FF33FF;
  --vortex-primary-dark: #CC00CC;
  /* ... more variables */
}
```

### Custom Agreement Text

Use WordPress filters to customize agreement content:

```php
add_filter('vortex_agreement_content', function($content, $agreement_type) {
    if ($agreement_type === 'seed_commitment') {
        $content['content'] = 'Your custom agreement text here...';
    }
    return $content;
}, 10, 2);
```

### Supported Networks

Add custom networks using the filter:

```php
add_filter('vortex_swap_supported_networks', function($networks) {
    $networks['custom'] = array(
        'name' => 'Custom Network',
        'symbol' => 'CUSTOM',
        'chain_id' => 999,
        'rpc_url' => 'https://custom-rpc.com',
        'explorer' => 'https://custom-explorer.com'
    );
    return $networks;
});
```

### Supported Tokens

Add custom tokens using the filter:

```php
add_filter('vortex_swap_supported_tokens', function($tokens) {
    $tokens['CUSTOM'] = array(
        'name' => 'Custom Token',
        'symbol' => 'CUSTOM',
        'decimals' => 18,
        'networks' => array('ethereum'),
        'contracts' => array(
            'ethereum' => '0x...'
        )
    );
    return $tokens;
});
```

## 🗃️ Database Schema

### User Agreements Table
```sql
wp_vortex_user_agreements
- id (bigint, auto_increment)
- user_id (bigint, foreign key)
- agreement_type (varchar)
- agreement_version (varchar)
- agreed (tinyint)
- ip_address (varchar)
- user_agent (text)
- created_at (datetime)
```

### Product Agreements Table
```sql
wp_vortex_product_agreements
- id (bigint, auto_increment)
- product_id (bigint, foreign key)
- user_id (bigint, foreign key)  
- agreement_type (varchar)
- agreement_version (varchar)
- agreed (tinyint)
- ip_address (varchar)
- user_agent (text)
- created_at (datetime)
```

### Exchange Rates Table
```sql
wp_vortex_swap_rates
- id (bigint, auto_increment)
- from_token (varchar)
- to_token (varchar)
- rate (decimal)
- network (varchar)
- source (varchar)
- timestamp (datetime)
```

### Swap Transactions Table
```sql
wp_vortex_swap_transactions
- id (bigint, auto_increment)
- user_id (bigint, foreign key)
- transaction_hash (varchar)
- from_token (varchar)
- to_token (varchar)
- from_amount (decimal)
- to_amount (decimal)
- exchange_rate (decimal)
- network (varchar)
- status (varchar)
- gas_used (bigint)
- gas_price (decimal)
- block_number (bigint)
- created_at (datetime)
- updated_at (datetime)
```

## 🔧 Hooks and Actions

### Available Actions

```php
// Triggered when user agrees to seed commitment
do_action('vortex_seed_commitment_agreed', $user_id);

// Triggered when user agrees to TOLA masterpiece
do_action('vortex_tola_masterpiece_agreed', $post_id, $user_id);

// Triggered when swap transaction is recorded
do_action('vortex_swap_transaction_recorded', $transaction_id, $transaction_data);

// Triggered when activity is logged
do_action('vortex_swap_activity_logged', $log_data);
```

### Available Filters

```php
// Customize supported networks
apply_filters('vortex_swap_supported_networks', $networks);

// Customize supported tokens  
apply_filters('vortex_swap_supported_tokens', $tokens);

// Customize agreement content
apply_filters('vortex_agreement_content', $content, $agreement_type);
```

## 🚨 Security Features

- WordPress nonce verification on all forms
- Input sanitization and validation
- SQL injection protection via prepared statements
- XSS prevention through output escaping
- Rate limiting (100 requests/hour per user)
- Comprehensive permission checks
- HTTPS enforcement for sensitive operations

## 📱 Mobile Responsiveness

All shortcodes are fully responsive:

- **Desktop:** Full-featured interface with all controls
- **Tablet:** Optimized layout with stacked elements
- **Mobile:** Touch-friendly design with simplified navigation
- **Accessibility:** ARIA labels, keyboard navigation, screen reader support

## 🔍 Troubleshooting

### Common Issues

**Shortcodes not rendering:**
- Ensure the plugin is activated
- Check for PHP errors in debug log
- Verify WordPress version compatibility

**Modal not appearing:**
- Check browser console for JavaScript errors
- Ensure jQuery is loaded
- Verify nonce security tokens

**API calls failing:**
- Check REST API permissions
- Verify nonce tokens are valid
- Ensure user has required capabilities

### Debug Mode

Enable debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📊 Performance

- **Asset Loading:** Conditional loading only when shortcodes are used
- **Caching:** Exchange rates cached for 5 minutes
- **Database:** Optimized queries with proper indexing
- **CDN Ready:** All assets can be served from CDN
- **Minification:** Compressed CSS/JS for production

## 🤝 Support

For support, feature requests, or bug reports:

- **Email:** support@vortexartec.com
- **Documentation:** https://docs.vortexartec.com
- **GitHub:** https://github.com/vortexartec/platform

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
```

## 🚀 Changelog

### Version 2.1.0
- Added three core blockchain shortcodes ([vortex_swap], [vortex_wallet], [vortex_metric_ranking])
- Implemented modal agreement system for seed commitment and TOLA participation
- Added multi-network blockchain support (Ethereum, Polygon, BSC)
- Comprehensive REST API implementation
- Professional responsive UI with magenta branding
- Database schema for agreements, transactions, and analytics
- Security enhancements and rate limiting
- Complete documentation and usage examples

### Version 2.0.0  
- Initial professional release
- Basic swap functionality
- Admin interface foundation

---

**© 2024 Vortex Artec Development Team. All rights reserved.**
