# Custom Dashboard Controller

A comprehensive WordPress plugin for customizing the admin dashboard appearance and functionality with role-based access control.

**Version:** 1.5.1
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
- **Submenu Colors** - Full control over submenu dropdown appearance including background, text, hover, and active states

### Logo & Branding
- **Custom Logo** - Add your brand logo at the top of the admin sidebar
- **Logo Text** - Display text as logo with bold Poppins font styling
- **Responsive Design** - Logo adapts when sidebar is collapsed

### Menu Visibility Control
- **Parent Menu Visibility** - Hide specific admin menu items per user role
- **Submenu Visibility** - Granular control over individual submenu items per role
- **Role-Based Access** - Different menu configurations for different user roles

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
- Set menu background and text colors
- Configure hover and active state colors
- Set submenu colors
- Upload a custom logo or add logo text

### Menu Visibility Tab
Control which menus are visible:
- Select checkboxes to hide menu items
- Configure separately for each user role
- Manage both parent menus and submenus

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

## Changelog

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
