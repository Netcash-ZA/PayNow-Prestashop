Sage Pay Now PrestaShop Credit Card Payment Module 2.0.0
=========================================================

Introduction
------------
PrestaShop is an open source e-commerce platform.

This is the Sage Pay Now module which gives you the ability to take credit card transactions online.

Download Instructions
-------------------------

Download the files from the location below to a temporary location on your computer.

Configuration
-------------

Prerequisites:

You will need:
* Sage Pay Now login credentials
* Sage Pay Now Service key
* PrestaShop admin login credentials

Sage Pay Now Gateway Server Configuration Steps:

1. Log into your Sage Pay Now Gateway Server configuration page:
	https://merchant.sagepay.co.za/SiteLogin.aspx
2. Go to Account / Profile
3. Click Sage Connect
4. Click Pay Now
5. Make a note of your Service key

Sage Pay Now Callback

6. Choose the following URLs for your Notify, Redirect, Accept and Decline URLs:
	http://www.your_domain_name.co.za/modules/paynow/paynow_callback.php

Sage Pay Now Plugin Installation and Activation

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

Please do not hesitate to contact Sage Pay if you have any suggestions or comments or log an issue on GitHub.
