# WooCommerce Hide Prices Until Approved

A professional WordPress plugin that hides prices and add-to-cart buttons from unauthorized users. Perfect for wholesale, B2B, and restricted access WooCommerce stores.

![WordPress Plugin Version](https://img.shields.io/badge/version-1.8.4-blue.svg)
![WordPress Compatibility](https://img.shields.io/badge/WordPress-5.6%2B-green.svg)
![WooCommerce Compatibility](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)
![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-orange.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg)

## 🌟 Key Features

- **Global Price Hiding**: Hide all prices with one click
- **Custom User Roles**: Built-in `wholesale_customer` and `vip_customer` roles
- **Role-Based Access**: Flexible user role management system
- **Request Access System**: Configurable access request functionality
- **Product-Specific Controls**: Override global settings per product
- **Category-Based Restrictions**: Apply restrictions to entire categories
- **Custom Styling**: Add your own CSS for perfect theme integration
- **User Expiration**: Set access expiration dates for users
- **Professional Admin Interface**: Easy-to-use settings panel

## 🎯 Perfect For

- **Wholesale Stores**: Restrict pricing to approved wholesale customers
- **B2B Websites**: Create member-only purchasing experiences
- **Private Stores**: Limit access to exclusive customer groups
- **Membership Sites**: Integrate with existing membership systems

## 🚀 Installation

### From GitHub

1. Download the latest release or clone this repository
2. Upload the plugin files to `/wp-content/plugins/woocommerce-hide-prices/`
3. Activate the plugin through the 'Plugins' screen in WordPress
4. Go to **Settings → Hide Prices** to configure the plugin

### Requirements

- WordPress 5.6 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## 📖 Quick Start Guide

### 1. Basic Setup

1. Navigate to **Settings → Hide Prices** in your WordPress admin
2. Enable **Global Price Restriction** to hide all prices by default
3. Select which user roles can see prices (Administrator, Shop Manager, etc.)
4. Configure your replacement text for hidden prices

### 2. User Management

- Assign users to the `wholesale_customer` or `vip_customer` roles
- Set expiration dates for user access (optional)
- Users with approved roles will see normal prices and can purchase

### 3. Advanced Features

- **Product-Specific Controls**: Override global settings for individual products
- **Category Restrictions**: Hide prices for entire product categories
- **Custom CSS**: Add your own styling in the Advanced tab
- **Request Access**: Configure how users can request access to view prices

## ⚙️ Configuration Options

### Global Settings
- **Global Restriction**: Enable/disable price hiding globally
- **Allowed Roles**: Select which user roles can see prices
- **Replacement Text**: Customize the text shown instead of prices
- **Request Access System**: Configure access request functionality

### Advanced Settings
- **Product-Specific Controls**: Enable per-product price hiding controls
- **Category Restrictions**: Enable category-based price hiding
- **Custom CSS**: Add custom styling for better theme integration

## 🔧 Developer Information

### Hooks and Filters

The plugin provides several hooks for developers:

```php
// Filter to modify price hiding logic
apply_filters( 'wchp_should_hide_price', $should_hide, $product );

// Filter to modify replacement text
apply_filters( 'wchp_replacement_text', $text, $product );

// Action hook after price hiding is initialized
do_action( 'wchp_price_hiding_initialized' );
```

### Custom User Roles

The plugin automatically creates two custom user roles:

- `wholesale_customer`: Designed for wholesale customers
- `vip_customer`: Designed for VIP/premium customers

### Architecture

- **Single-file architecture** for maximum stability and compatibility
- **No complex autoloading** to prevent conflicts
- **HPOS compatible** for future WooCommerce versions
- **Translation ready** with full internationalization support

## 🐛 Troubleshooting

### Common Issues

**Plugin won't activate**
- Ensure WooCommerce is installed and activated
- Check PHP version (7.4+ required)
- Verify WordPress version (5.6+ required)

**Prices still showing**
- Check user roles - logged-in users may have admin privileges
- Verify Global Restriction is enabled
- Check product-specific settings if using advanced features

**Request Access button not working**
- Configure the Request Access URL in settings
- Ensure the button is enabled in General settings

## 📝 Changelog

### Version 1.8.4
- Added configurable Request Access button URL mapping
- Fixed user role checkbox selection functionality
- Enhanced admin interface with better user experience
- Improved product-specific price hiding logic
- Added visual indicators for custom user roles

### Version 1.8.3
- Added advanced product-specific controls
- Implemented category-based restrictions
- Added custom CSS styling options
- Enhanced admin interface with better UX
- Improved user role management
- Added user access expiration functionality

[View full changelog](CHANGELOG.md)

## 🤝 Contributing

We welcome contributions! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup

1. Clone this repository
2. Set up a local WordPress development environment
3. Install WooCommerce
4. Activate the plugin for testing

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🔗 Links

- **Author**: [Great White North Design](https://greatwhitenorthdesign.ca)
- **Support**: [support@greatwhitenorthdesign.ca](mailto:support@greatwhitenorthdesign.ca)
- **Donate**: [Support Development](https://greatwhitenorthdesign.ca/donate)

## 📧 Support

For support, feature requests, or bug reports, please:

1. Check the [Issues](../../issues) section
2. Contact us at [support@greatwhitenorthdesign.ca](mailto:support@greatwhitenorthdesign.ca)
3. Visit our website: [greatwhitenorthdesign.ca](https://greatwhitenorthdesign.ca)

---

**Made with ❤️ by Great White North Design** 