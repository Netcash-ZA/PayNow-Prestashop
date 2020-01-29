Netcash Pay Now PrestaShop Credit Card Payment Module 2.0.0
=========================================================

Introduction
------------
PrestaShop is an open source e-commerce platform.

This is the Netcash Pay Now module which gives you the ability to take credit card transactions online.

Download Instructions
-------------------------

Download the files from the location below to a temporary location on your computer.

Configuration
-------------

Prerequisites:

You will need:
* Netcash account
* Pay Now service activated
* Netcash account login credentials (with the appropriate permissions setup)
* Netcash - Pay Now Service key
* Cart admin login credentials

A. Netcash Account Configuration Steps:
1. Log into your Netcash account:
	https://merchant.netcash.co.za/SiteLogin.aspx
2. Type in your Username, Password, and PIN
2. Click on ACCOUNT PROFILE on the top menu
3. Select NETCONNECTOR from tghe left side menu
4. Click on PAY NOW from the subsection
5. ACTIVATE the Pay Now service
6. Type in your EMAIL address
7. It is highly advisable to activate test mode & ignore errors while testing
8. Select the PAYMENT OPTIONS required (only the options selected will be displayed to the end user)
9. Remember to remove the "Make Test Mode Active" indicator to accept live payments

* For immediate assistance contact Netcash on 0861 338 338



Netcash Pay Now Callback

6. Choose the following URLs for your Notify, Redirect, Accept and Decline URLs:
	http://www.your_domain_name.co.za/modules/paynow/paynow_callback.php

Netcash Pay Now Plugin Installation and Activation

7. Upload the contents of the downloaded ZIP archive to your site.
	In _/modules/_ there should be a _paynow/_ folder.
	No files should be overriden.
8. Login to your PrestaShop website as admin

PrestaShop Configuration

9. Select "Modules" > "Modules" in the admin menu.
10. Look for or search for "PayNow" and click "Install".
11. Put in you Service Key and click "Save".
12. Turn off debugging if you're in a production/live environment.


Revision History
----------------

* 19 Aug 2015/2.0.0 Add EFT and retail payment support
* 03 Feb 2015/1.0.0
** Initial release
* 04 Feb 2015/1.0.0
** Fix URL inconsistencies

Tested with PrestaShop v1.6.0.11


Issues / Feedback / Feature Requests
------------------------------------

Please do the following should you encounter any problems:

* Ensure at Sage that your Accept and Decline URLs are "http://www.example.com/modules/paynow/paynow\_callback.php".
For example, if your site is 'www.mysite.co.za', use:
http://www.mysite.co.za/modules/paynow/paynow\_callback.php
* There are three steps that will enable maximum debugging
** Enable Debugging in the Pay Now module

Ensure that there's a paynow.log file in _/modules/paynow_ and that it is writeable.

Turn OFF debugging when you are in a production environment.

We welcome your feedback and suggestions.

Please do not hesitate to contact Netcash if you have any suggestions or comments or log an issue on GitHub.
