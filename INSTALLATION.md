# Nivoda Diamond Connector - Installation & Quick Start Guide

## 🎉 Your plugin is ready!

**Package Location:** `nivoda-diamond-connector-1.0.0.zip` (on your Desktop)

---

## 📦 Installation

### Step 1: Install the Plugin

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins → Add New**
3. Click the **Upload Plugin** button at the top
4. Click **Choose File** and select `nivoda-diamond-connector-1.0.0.zip`
5. Click **Install Now**
6. After installation completes, click **Activate Plugin**

### Step 2: Configure API Settings

1. Go to **Nivoda Diamonds → Settings** in the WordPress admin menu
2. Enter your **Nivoda API Key** (get this from your Nivoda account)
3. Set **API URL**: `https://api.nivoda.net/graphql` (default)
4. Configure **Markup Percentage** (e.g., 10 for 10% markup)
5. Set **Cache Duration**: `3600` seconds (1 hour recommended)
6. Enable **Caching** (recommended for performance)
7. Click **Save Settings**
8. Click **Test Connection** to verify your API setup

---

## 🚀 Quick Start

### Display Diamond Search

Add this shortcode to any page or post:

```
[nivoda_search]
```

This displays the full search interface with:
- Shape selector (Round, Princess, Cushion, etc.)
- Carat weight slider
- Color filter (D-M grades)
- Clarity filter (FL-I2)
- Cut quality filter
- Price range slider
- Real-time AJAX results

### Show a Specific Diamond

```
[nivoda_diamond id="YOUR_DIAMOND_ID"]
```

Replace `YOUR_DIAMOND_ID` with the actual Nivoda diamond ID.

---

## ⚙️ Admin Tools

### Cache Management
**Location:** Nivoda Diamonds → Tools

- View cache statistics
- Clear all cached data
- Monitor performance

### Diamond Sync
Sync diamonds to local WordPress posts:

1. Go to **Nivoda Diamonds → Tools**
2. Click **Sync Diamonds**
3. Syncs 100 latest diamonds to WP Custom Post Type

### API Diagnostics
Test your connection:

1. Go to **Nivoda Diamonds → Tools**
2. Click **Run Diagnostics**
3. Verify API status and available inventory

---

## 🎨 Customization

### Override Styles

Add to your theme's `style.css`:

```css
/* Customize diamond cards */
.ndc-diamond-card {
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}

/* Customize price color */
.ndc-diamond-price {
    color: #d97706;
}
```

### Override Templates

1. Create folder: `wp-content/themes/YOUR-THEME/nivoda-diamond-connector/`
2. Copy `search.php` or `single-diamond.php` from plugin's `templates/` folder
3. Customize the copied files

---

## 📁 Plugin Structure

```
nivoda-diamond-connector/
├── nivoda-diamond-connector.php  # Main plugin file
├── includes/
│   ├── class-ndc-api.php         # Nivoda API wrapper
│   ├── class-ndc-cache.php       # Caching layer
│   ├── class-ndc-settings.php    # Admin settings
│   ├── class-ndc-cpt.php         # Custom Post Type
│   ├── class-ndc-ajax.php        # AJAX handlers
│   ├── class-ndc-woocommerce.php # WooCommerce integration
│   └── class-ndc-admin-tools.php # Admin tools
├── assets/
│   ├── css/
│   │   ├── frontend.css          # Frontend styles
│   │   └── admin.css             # Admin styles
│   └── js/
│       ├── frontend.js           # Search UI logic
│       └── admin.js              # Admin scripts
├── templates/
│   ├── search.php                # Search interface
│   └── single-diamond.php        # Single diamond view
└── languages/                     # Translation files
```

---

## 🔧 Features Included

### ✅ Core Features
- [x] Nivoda GraphQL API integration
- [x] Advanced diamond search with 6+ filters
- [x] Real-time AJAX filtering
- [x] Range sliders for carat & price
- [x] Responsive grid layout
- [x] Image carousel (Slick.js)
- [x] WooCommerce add-to-cart
- [x] Automatic product creation
- [x] Configurable markup pricing

### ✅ Performance
- [x] WordPress transients caching
- [x] Configurable cache TTL
- [x] Rate limiting for API calls
- [x] Efficient pagination

### ✅ Admin Tools
- [x] Settings page with validation
- [x] API connection testing
- [x] Cache statistics & management
- [x] Diamond sync tool
- [x] System diagnostics

### ✅ Developer Features
- [x] Custom Post Type for diamonds
- [x] Taxonomies (shape, color, clarity)
- [x] WordPress coding standards
- [x] Comprehensive error handling
- [x] Extensible with hooks/filters

---

## 🛠️ Troubleshooting

### "API key is not configured"
➜ Go to Settings and enter your Nivoda API key

### Search results not loading
1. Open browser console (F12) for JavaScript errors
2. Verify WooCommerce is active
3. Clear cache in Tools
4. Test API connection

### Diamonds not adding to cart
1. Ensure WooCommerce is properly configured
2. Check PHP error logs
3. Verify product creation permissions

### Slow performance
1. Enable caching in Settings
2. Set cache TTL to 3600-7200 seconds
3. Limit results to 20-30 per page

---

## 📊 Recommended Settings

| Setting | Recommended Value | Notes |
|---------|------------------|-------|
| Cache TTL | 3600 seconds | 1 hour - balances freshness & performance |
| Markup % | 10-15% | Typical retail markup |
| Results per page | 20 | Optimal for performance |
| Enable caching | Yes | Essential for production |

---

## 🎯 Next Steps

1. **Test the installation** - Add `[nivoda_search]` to a test page
2. **Customize appearance** - Override CSS to match your theme
3. **Configure pricing** - Set appropriate markup in Settings
4. **Test checkout flow** - Verify WooCommerce integration works
5. **Monitor cache** - Check cache stats after initial usage
6. **Sync diamonds** (optional) - Use Tools to sync inventory locally

---

## 📝 Support & Documentation

- **Full Documentation:** See `README.md` in plugin directory
- **WordPress.org:** See `readme.txt` for WordPress.org format
- **API Documentation:** https://nivoda.net/api-docs

---

## 🔐 Security Notes

- Store your API key securely
- Don't share your API credentials
- Use HTTPS for production sites
- Keep WordPress & WooCommerce updated

---

## 📜 License

GPL v2 or later - Free to use and modify

---

**Congratulations! Your Nivoda Diamond Connector is ready to use.** 🎉

For questions or support, refer to the included README.md file.
