### connectreseller.com WHMCS Registrar Plugin

***

##### [ReleaseNotes]

connectreseller.com WHMCS Registrar plug-in is an open-source plug-in that is
distributed free of charge. This repository is a community-maintained fork
(current module version **3.0.1**). It focuses on integrating ConnectReseller as
a domain registrar at WHMCS.

After the integration you can setup ConnectReseller as the default registrar for
your customers and decide which services and TLDs to offer to your customers
from within the WHMCS admin area.

##### Pre-requisites

- Access to WHMCS admin area.
- An understanding of ConnectReseller environments.
- ConnectReseller account .


##### Download and Installation

- Download the latest release zip from this repository’s GitHub Releases and
  extract it into the WHMCS root so `modules/registrars/connectreseller/` and
  (optionally) `modules/addons/connect_reseller/` exist.
- Keep the registrar folder name `connectreseller`. Logo files must be binary
  `logo.png` / `logo.gif` beside `connectreseller.php` (do not text-convert
  them). The plug-in installation is complete.

##### Configuration

To configure WHMCS for use with ConnectReseller, perform the following steps:

1. Login to your **WHMCS admin** panel.
2. Click on **Setup** menu, select **Products/Services** and click on **Domain Registrars**.
3. Click on Activate next to ConnectReseller in the list:
 ![Activate Plugin](https://global.connectreseller.com//images/activate.jpg "Activate Plugin")

4. Enter your API credentials. Enter the API Key and Brand Id 
(To know your API key and brand ID, Once you have logged into Your Reseller Panel, Go to Settings > API )
	![Activate Plugin](https://global.connectreseller.com/images/config.png "Configure Plugin")
5. Click Save Changes.

##### Optional addon (TLD price sync)

1. Activate **ConnectReseller** under Setup → Addon Modules.
2. In the addon admin UI, open **Sync TLDs**, select TLDs, and **Import TLDs**.
3. Open **Automation Setting**, enable statuses for TLDs that should auto-sync.
4. Rely on the WHMCS system cron (`AfterCronJob` + addon **Cron Frequency**,
   default 24h). Optional `crons/*.php` scripts are not required when system
   cron is active. KYC continues via the registrar `DailyCronJob`.

That’s it. The ConnectReseller plug-in is now ready for use and will function just like any other built-in WHMCS registrar module. You can now make ConnectReseller as the automatic registrar, configure TLDs and services for all your customers. To perform these actions, click on the Setup menu, select Products/Services and click on Domain Pricing in your WHMCS admin panel:

Note: 
1. You need to whitelist your WHMCS IP Address into you ConnectReseller panel.

2.You can turn off Emails which are sent to your customers from your Reseller Panel as WHMCS do send Emails to your customers. This way your customer will receive only one Email instead of two. Kindly Login into your Reseller Panel and go to Settings > Panel settings > Customer Emails to stop Emails.



##### Support

Please [submit a ticket](http://support.connectreseller.com) to report bugs, provide feedback or receive assistance.
