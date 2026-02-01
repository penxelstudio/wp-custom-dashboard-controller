=== Custom Dashboard Controller ===
Contributors: penxelstudio
Donate link: https://penxelstudio.com/donate
Tags: admin dashboard, custom login, menu visibility, admin customization, white label
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.6.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize WordPress admin dashboard colors, logo, menu visibility, submenu control, admin bar, login page branding, and more with role-based access.

== Description ==

**Custom Dashboard Controller** is a comprehensive WordPress plugin for customizing the admin dashboard appearance and functionality with role-based access control. Perfect for agencies, developers, and site administrators who want to create a branded, streamlined admin experience.

= Key Features =

**🎨 Color Customization**

* Menu background and text colors
* Hover state colors (background and text)
* Active menu item colors
* Submenu color controls (background, text, hover, active states)
* Preset color schemes for quick styling

**🖼️ Logo & Branding**

* Custom logo in admin sidebar
* Logo text option with custom styling
* Responsive design (adapts when sidebar collapses)

**👁️ Menu Visibility Control**

* Hide specific admin menu items per user role
* Submenu visibility control per role
* Protection prevents accidental lockout

**📋 Menu Ordering**

* Drag and drop menu reordering
* Submenu reordering within each parent
* Role-based menu order settings

**🔧 Admin Bar Customization**

* Hide admin bar items per role
* Control frontend admin bar visibility
* Add custom links to admin bar

**📊 Dashboard Widgets**

* Create custom widgets using shortcodes
* Auto-refresh capability
* Role-based widget display

**🔐 Login Page Branding**

* Custom login logo
* Background colors, images, or gradients
* Two-column layout option
* Custom form styling

**🛠️ Tools**

* Preset color schemes
* Import/Export settings
* Reset to defaults

= Role-Based Control =

Every feature supports WordPress user roles, allowing you to create different experiences for Administrators, Editors, Authors, Contributors, and Subscribers.

= Perfect For =

* **Agencies** - White-label the WordPress admin for clients
* **Developers** - Create custom admin experiences
* **Site Owners** - Simplify the admin interface for your team
* **Multisite** - Consistent branding across networks

== Installation ==

1. Upload the `custom-dashboard-controller` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **Dashboard Controller** in the admin menu to configure settings

Or install directly from WordPress:

1. Go to Plugins > Add New
2. Search for "Custom Dashboard Controller"
3. Click "Install Now" and then "Activate"

== Frequently Asked Questions ==

= Will this plugin slow down my site? =

No. The plugin only loads assets on admin pages and the login page. It has no impact on your site's frontend performance.

= Can I hide the Dashboard Controller menu for certain users? =

For security, the Dashboard Controller menu cannot be hidden for Administrators to prevent accidental lockout. You can hide it for other user roles.

= Will my settings be preserved after plugin updates? =

Yes. All settings are stored in the WordPress database and persist through updates.

= Can I export settings to another site? =

Yes! Use the Tools tab to export your settings as a JSON file, then import on another site.

= Does it work with multisite? =

Yes, the plugin works with WordPress multisite installations.

= Is the plugin translation-ready? =

Yes, the plugin is fully translation-ready with the text domain 'custom-dashboard-controller'.

== Screenshots ==

1. Basic Settings - Color customization and preset schemes
2. Menu Visibility - Hide menus per user role
3. Menu Order - Drag and drop reordering
4. Admin Bar - Customize toolbar items
5. Dashboard Widgets - Create custom widgets
6. Login Page - Branded login experience
7. Tools - Import/Export and reset options

== Changelog ==

= 1.6.0 =
* Added preset color schemes for quick styling
* Added import/export settings functionality
* Added reset all settings option
* Added Settings link on Plugins page
* Added protection to prevent hiding Dashboard Controller for administrators
* Moved preset schemes to Basic Settings tab for better UX

= 1.5.2 =
* Added login page branding feature
* Custom login logo support
* Background color, image, and gradient options
* Two-column login layout
* Custom form styling options

= 1.5.1 =
* Added submenu color controls
* Background, text, hover, and active state colors for submenus

= 1.5.0 =
* Added separate color pickers for hover states
* Added separate color pickers for active menu items

= 1.4.2 =
* Added custom dashboard widgets with shortcode support
* Added auto-refresh capability for widgets

= 1.4.1 =
* Added admin bar customization
* Added custom admin bar links
* Added frontend admin bar visibility control per role

= 1.4.0 =
* Added submenu visibility control
* Added submenu reordering
* New tabbed settings interface

= 1.0.0 =
* Initial release
* Menu color customization
* Custom logo support
* Menu visibility control per role
* Menu reordering

== Upgrade Notice ==

= 1.6.0 =
New features: preset color schemes, import/export settings, and protection against accidental lockout. Update recommended for all users.

== Privacy Policy ==

This plugin does not:

* Track users or collect personal data
* Send data to external servers
* Use cookies for tracking
* Store any data outside your WordPress installation

All settings are stored locally in your WordPress database.
