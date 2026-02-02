# Custom Dashboard Controller

A comprehensive WordPress plugin for customizing the admin dashboard appearance and functionality with role-based access control.

**Version:** 1.6.0
**Author:** Penxel Studio
**Requires WordPress:** 6.0+
**Requires PHP:** 7.4+
**License:** GPL v2 or later

## Features

### Color Customization
- **Menu Background Color** - Customize the admin sidebar background
- **Menu Text Color** - Change the color of menu item text
- **Hover States** - Separate background and text colors for hover effects
- **Active Menu States** - Custom colors for currently active menu items
- **Submenu Colors** - Full control over submenu dropdown appearance
- **Preset Color Schemes** - Quick-apply pre-built color themes

### Logo & Branding
- **Custom Logo** - Add your brand logo at the top of the admin sidebar
- **Logo Text** - Display text as logo with bold Poppins font styling
- **Responsive Design** - Logo adapts when sidebar is collapsed

### Menu Visibility Control
- **Parent Menu Visibility** - Hide specific admin menu items per user role
- **Submenu Visibility** - Granular control over individual submenu items per role
- **Role-Based Access** - Different menu configurations for different user roles
- **Lockout Protection** - Dashboard Controller menu protected for administrators

### Menu Ordering
- **Drag & Drop Reordering** - Easily reorder parent menu items
- **Submenu Reordering** - Customize the order of submenu items within each parent
- **Live Preview** - See changes in real-time before saving

### Admin Bar Customization
- **Hide Admin Bar Items** - Remove specific items from the admin toolbar per role
- **Frontend Visibility** - Control admin bar visibility on the frontend per role
- **Custom Links** - Add your own custom links to the admin bar with role-based display

### Dashboard Widgets
- **Shortcode Widgets** - Create custom dashboard widgets using any shortcode
- **Auto-Refresh** - Widgets automatically refresh content (1-hour interval)
- **Role-Based Display** - Show widgets only to specific user roles

### Login Page Branding
- **Custom Login Logo** - Replace WordPress logo on login page
- **Background Options** - Solid color, image, or gradient backgrounds
- **Two-Column Layout** - Modern split-screen login design
- **Form Styling** - Customize login form appearance

### Tools
- **Preset Color Schemes** - One-click color theme application
- **Import/Export Settings** - Transfer settings between sites
- **Reset to Defaults** - Restore all settings to original state

## Installation

1. Download the plugin zip file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the zip file
4. Click "Install Now" and then "Activate"

Or manually:

1. Upload the `custom-dashboard-controller` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress

## Usage

After activation, navigate to **Dashboard Controller** in the WordPress admin menu.

### Basic Settings Tab
Configure colors and logo:
- Choose a preset color scheme for quick styling
- Set menu background and text colors
- Configure hover and active state colors
- Set submenu colors
- Upload a custom logo or add logo text

### Menu Visibility Tab
Control which menus are visible:
- Select checkboxes to hide menu items
- Configure separately for each user role
- Manage both parent menus and submenus
- Dashboard Controller is protected for administrators

### Menu Order Tab
Reorder admin menus:
- Drag and drop to reorder parent menus
- Select a parent menu to reorder its submenus
- Click "Save Order" to apply changes

### Admin Bar Tab
Customize the admin toolbar:
- Hide specific admin bar items per role
- Toggle frontend admin bar visibility
- Add custom links with role-based access

### Dashboard Widgets Tab
Create custom dashboard widgets:
- Add shortcode-based widgets
- Configure role visibility
- Widgets appear on the main dashboard

### Login Page Tab
Brand the WordPress login page:
- Upload custom login logo
- Choose background type (color, image, gradient)
- Select centered or two-column layout
- Preview changes before saving

### Tools Tab
Manage plugin settings:
- Export all settings to JSON file
- Import settings from another site
- Reset all settings to defaults

## Changelog

### 1.6.0
- Added preset color schemes for quick styling
- Added import/export settings functionality
- Added reset all settings option
- Added Settings link on Plugins page
- Added protection to prevent hiding Dashboard Controller for administrators
- Moved preset schemes to Basic Settings tab for better UX

### 1.5.2
- Added login page branding feature
- Custom login logo support
- Background color, image, and gradient options
- Two-column login layout option

### 1.5.1
- Added submenu color controls (background, text, hover, active states)

### 1.5.0
- Added separate color pickers for hover states
- Added separate color pickers for active menu items

### 1.4.2
- Added custom dashboard widgets with shortcode support
- Added auto-refresh capability for widgets

### 1.4.1
- Added admin bar customization
- Added custom admin bar links
- Added frontend admin bar visibility control

### 1.4.0
- Added submenu visibility control
- Added submenu reordering
- New tabbed settings interface

### 1.0.0
- Initial release
- Menu color customization
- Custom logo support
- Menu visibility control
- Menu reordering

## Support

For issues and feature requests, please visit:
- GitHub: [https://github.com/penxelstudio/wp-custom-dashboard-controller](https://github.com/penxelstudio/wp-custom-dashboard-controller)
- Website: [https://penxelstudio.com](https://penxelstudio.com)

## License

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
