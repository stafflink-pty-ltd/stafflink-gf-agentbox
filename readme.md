=== Agentbox Integration ===

Contributors: amielSL, mattneal-stafflink
Tags: comments, spam
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gravtiy forms and Agentbox integration

# Description
An addon feed plugin for Gravity Forms that allows seamless integration with Agentbox Enquiries. This addon enables autmatic form feed submissions from your website directly into you Agentbox account.

# Features
- ### Custom Field Mapping: 
Map Gravity form fields to Agentbox fields to ensure accurate data transfer.

- ### Conditional Logic: 
Set conditions to control when form data should be sent to Agentbox.

- ### Entry Notes and Metabox:
Automtatically add notes and feed status directly from the Gravity Forms entry detail page.

- ### EasyPropertyListing integration:
Seamlessly integrate with EPL Listing pages

- ### Hooks and Filters:
Available hooks and filters to extend the current plugin's functionality
- ### Error Logging:
Track any issues that occur during the data transfer process.

# Requirements
- Wordpress 5.0 Higher
- Gravity Forms 2.8 or higher
- Agentbox account with API Access
- EasyPropertyListing (optional)

# Installation
1. Download the Plugin. Download the latest version of the Agentbox Feed Addon for Gravity Forms from the release page.
2. Install the Plugin. Upload the plugin files to the `/wp-content/plugins` directory.
3. Activate the Plugin. Activate the plugin throught the 'Plugins' screen in Wordpress.
4. Add your Agentbox API credentials via `.env` file
```
# Agentbox
AGENTBOX_CLIENT_ID=''
AGENTBOX_CLIENT_SECRET=''
```

# Usage
1. Create a Form. Create or edit a form using Gravity Forms.
2. Create an Agentbox Field
    - Go to the form settings under Realcoder 
    - Create a new feed
    - Configure the feed by mapping the Gravity form fields to Agentbox fields.
3. Save and Test

# Change logs

##### 1.0.0
- Initial Release