# Changelog

All notable changes to the WooCommerce Hide Prices Until Approved plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.4] - 2024-12-19

### Added
- Configurable Request Access button URL mapping
- Visual indicators for custom user roles in admin interface
- Enhanced product-specific price hiding logic with hierarchical settings
- Improved request access system with flexible URL configuration

### Fixed
- User role checkbox selection functionality in admin settings
- Array handling for user role selection and sanitization
- Request access button logic for better user experience

### Changed
- Enhanced admin interface with better user experience
- Improved single product page request access button display
- Updated all price hiding methods to use advanced product-specific logic

### Security
- Enhanced input sanitization for user role arrays
- Improved validation for request access URL settings

## [1.8.3] - 2024-12-18

### Added
- Advanced product-specific controls with meta boxes in product editor
- Category-based restriction system for hiding prices by category
- Custom CSS styling options for frontend integration
- Hierarchical settings logic (Product → Category → Global)
- User access expiration functionality with date selection
- Enhanced admin interface with tabbed navigation

### Enhanced
- Professional admin interface with improved UX
- Better user role management and display
- Comprehensive settings organization

### Technical
- Maintained single-file architecture for stability
- Improved compatibility with various themes
- Enhanced error handling and validation

## [1.8.2] - 2024-12-17

### Added
- Comprehensive user role management system
- Enhanced admin interface with professional styling
- Improved role registration and management
- Better integration with WordPress user system

### Fixed
- Role registration timing issues
- Compatibility with older PHP versions
- Various activation and deactivation hooks

### Enhanced
- Performance optimizations for large sites
- Better error handling and debugging capabilities
- Improved theme compatibility

## [1.8.1] - 2024-12-16

### Fixed
- Critical activation issues that prevented plugin from loading
- Compatibility problems with various hosting environments
- PHP version compatibility issues

### Security
- Enhanced security measures for admin functions
- Improved input validation and sanitization
- Better prevention of direct file access

### Changed
- Simplified activation process for better reliability
- Improved error messages and user feedback

## [1.8.0] - 2024-12-15

### Added
- Initial release with core functionality
- Custom user roles (`wholesale_customer`, `vip_customer`)
- Basic price hiding features for unauthorized users
- Admin interface for plugin configuration
- WooCommerce High-Performance Order Storage (HPOS) compatibility
- Translation ready with internationalization support

### Features
- Global price restriction toggle
- Role-based access control
- Configurable replacement text for hidden prices
- Request access system for unauthorized users
- Clean uninstall process

### Technical
- Single-file architecture for maximum compatibility
- Comprehensive WordPress and WooCommerce compatibility checks
- Professional error handling and admin notices
- Fail-safe activation and deactivation processes

## [Unreleased]

### Planned Features
- Integration with popular membership plugins
- Advanced user access analytics
- Bulk user role management tools
- Email notification system for access requests
- Advanced caching compatibility
- REST API endpoints for external integrations

### Known Issues
- None currently reported

---

## Version History Summary

- **v1.8.4**: Request Access URL mapping, role selection fixes
- **v1.8.3**: Advanced controls, category restrictions, custom CSS
- **v1.8.2**: User role management, admin interface improvements
- **v1.8.1**: Critical fixes, security enhancements
- **v1.8.0**: Initial release with core functionality

## Support

For questions about any version or to report issues:
- Email: [support@greatwhitenorthdesign.ca](mailto:support@greatwhitenorthdesign.ca)
- Website: [https://greatwhitenorthdesign.ca](https://greatwhitenorthdesign.ca)

## License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details. 