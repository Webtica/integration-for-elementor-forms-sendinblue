=== Integration for Elementor forms - Sendinblue ===
Contributors: webtica
Tags: sendinblue, elementor, elementor pro, forms, integration, marketing, lists, send, blue, automation
Requires at least: 5.0
Tested up to: 5.8.2
Requires PHP: 5.4
Stable tag: 1.3.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

A lightweight but feature packed Sendinblue integration for Elementor forms.
With this integration you can send your form data and contacts to Sendinblue as easily as the standard integrations. 
Keeping performance in mind this integration doesn't add any additional scripts on page load. 
Feel free to post any feature requests and possible issues.

== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater
* PHP version 5.4 or greater
* MySQL version 5.0 or greater
* [Elementor Pro](https://elementor.com) 3 or greater

= We recommend your host supports: =

* PHP version 7.0 or greater
* MySQL version 5.6 or greater
* WordPress Memory limit of 64 MB or greater (128 MB or higher is preferred)

= Installation =

1. Install using the WordPress built-in Plugin installer, or Extract the zip file and drop the contents in the `wp-content/plugins/` directory of your WordPress installation.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Pages > Add New
4. Press the 'Edit with Elementor' button.
5. Drag and drop the form widget of Elementor Pro from the left panel onto the content area, and find the Sendinblue action in the "Actions after submit" dropdown.
6. Fill your Sendinblue data and Key and you are all set. All users will be added after they submit the form.

== Frequently Asked Questions ==

**Why is Elementor Pro required?**

Because this integration works with the Form Widget, which is a Elementor Pro unique feature not available in the free plugin.

**Can I still use other integrations if I install this integration?**

Yes, all the other form widget integrations will be available.

== Changelog ==

= 1.3.0 - 2021-11-19 =
* Added Firstname attribute mapping
* Added Lastname attribute mapping
* Tested Elementor up to 3.4.8

= 1.2.0 - 2021-11-13 =
* Added GDPR checkbox functionality
* Added dynamic tags to the double optin field
* Added dynamic tags to the email field
* Added dynamic tags to the name field
* Added dynamic tags to the lastname field
* Tested Elementor PRO up to 3.5.1
* Tested Elementor up to 3.4.7
* Tested WordPress up to 5.8.2

= 1.1.0 - 2021-10-05 =
* Added dynamic tags to the API key field
* Added dynamic tags to the redirect url field
* Added dynamic tags to the list ID field

= 1.0.0 - 2021-09-25 =
* Initial Release