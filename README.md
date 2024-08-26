# Order Confirmer

**Plugin Name:** Order Confirmer  
**Description:** This plugin allows administrators to confirm WooCommerce orders and copy order details to the clipboard. It provides a settings page for customizing message templates and handles secure confirmation links and AJAX requests for confirming orders.  
**Author:** Yousseif Ahmed  
**Version:** 1.2.4

## Description

The "Order Confirmer" plugin enhances WooCommerce by enabling order confirmation functionality and providing an easy way to copy order details. Key features include:

- **Settings Page:** Customize message templates for different payment methods (COD and BACS).
- **Secure Confirmation Links:** Generate secure links for order confirmation.
- **AJAX Support:** Confirm orders and copy details to the clipboard via AJAX.
- **Custom Columns:** Add custom columns to the WooCommerce orders page for quick access to copy details.

## Usage

- **Settings Page:** Customize the message templates for COD and Direct Bank payment methods in the plugin settings.
- **Order Confirmation:** Use the secure confirmation links to confirm orders.
- **Copy Details:** Click the "Copy Message" link on the WooCommerce orders page to copy order details to the clipboard.

## Features

- **Custom Message Templates:** Define custom message templates for different payment methods.
- **Order Confirmation Links:** Generate secure links for order confirmation.
- **AJAX Integration:** Confirm orders and copy details without reloading the page.
- **Custom Columns in Orders Page:** View and interact with custom columns for quick access.

## Screenshots

- **Settings Page:** Screenshot of the settings page where templates can be configured.
- **Orders Page:** Screenshot showing custom columns and "Copy Message" links.

## Frequently Asked Questions

**Q: How do I configure the message templates?**  
A: Go to the "Order Confirmer" settings page under the admin menu and enter your templates in the provided text areas.

**Q: What are the placeholders available for message templates?**  
A: The available placeholders are `{id}`, `{phone}`, `{address}`, `{total}`, `{items}`, `{date}`, and `{link}`.

**Q: How do I clear old copy request logs?**  
A: Use the "Clear Data" section on the settings page to remove copy request logs before a specified cutoff date.

## Changelog

### 1.2.4
- Fixed issues with secure confirmation link generation.
- Improved AJAX handling for order confirmation.

## Note

This plugin is a sample provided for demonstration purposes. It may require customization to fit specific project needs and should be tested thoroughly in a staging environment before deploying to a live site.

## License

This plugin is released under the [GPLv3 License](https://www.gnu.org/licenses/gpl-3.0.en.html).
