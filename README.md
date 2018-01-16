# Reverb Magento Plugin

This is a Magento app for integrating with Reverb's API including product sync (magento->reverb) and order sync (reverb->magento).

While this plugin can and does work out of the box for many sellers, it is intended as a base for you to customize for your own magento usage. It is only tested on Magento Community 2.1.8 and 2.2.1. Enterprise Edition customers are advised to have their own developers evaluate and customize the plugin for usage.

Please read this entire README prior to installing the application.

## Features

* Create new draft listings on Reverb from Magento products, including image ~~& category sync~~ (category sync requires bugfix) 
* Control whether price/title/inventory syncs individualy.
* Sync updates for inventory from Magento to Reverb. 
* Sync orders from Reverb to Magento
* Sync shipping tracking information from Magento to Reverb
* Configurable products - children are synced as individual listings on Reverb
* Make/model/price/finish/year/shipping_profile_name can be mapped to attributes in your magento installation

## Professional Support

If you need help with your Magento install or integration of this plugin with your Magento instance, please contact these independent consultants for support. Please note that Reverb does not manage these relationships or support the plugin directly.

##### Kevin Scruggs

Github: [kevinecd](https://github.com/kevinecd)

Email: [kevin@ecomdash.com](mailto:kevin@ecomdash.com?Subject=Reverb%20Magento%20Plugin)

## FAQ

#### Q: Why aren't things synced in real time, or failing to sync at all?

The Reverb sync runs on a cron (magento's scheduler)  that's set to every minute for product syncs and every two minutes for order syncing. This is done so that when you save a product we won't interfere with your normal magento functions, and do all the sync in the background.

However the design of Magento's cron means that other cron-based plugins that take a long time to run may interfere with each other. Reverb generally finishes its work in seconds, but we have seen plugins that can take many minutes to run, or even crash, preventing plugins like Reverb from finishing their work. 

If you're continuing to have cron issues, please install Reverb on a fresh magento instance without any other plugins as a test. If that works, the problem is with one of your other plugins. Please ensure you have no error messages in your cron and php logs prior to contacting Reverb Support.

#### Q: Why are my Reverb Make & Model incorrect or showing as "Unknown"

**Make & Model are guessed from the title unless you map those fields**. Use the configuration screen (Stores->Configuration->Reverb Configuration) to map make/model fields to attribute fields in your Magento installation. If you don't have structured make/model fields, we will attempt to guess them from the title, but this is not reliable.

#### Q: How can I map make/model and other fields?

If you don't already have make & model fields in your magento installation, you can add them by using the Stores->Product section to add two new fields (for example, "reverb_make" and "reverb_model"). Then go to Stores->Attribute Set and add those fields into your default attribute set so they appear on every product. Finally, go to Stores->Configuration->Reverb Configuration and map the make and model fields to your newly created fields. You can do the same for other reverb attributes such as finish/year/shipping_profile_name

#### Q: How can I set all my items to free shipping?

1. Set up a [Reverb Shipping Profile](https://reverb.com/my/selling/shipping_rates) with free shipping ($0), called "Free Shipping".
2. Add a magento attribute for reverb_shipping_profile from Stores->Product. Set a default value of "Free Shipping" (corresponding to the profile you created in step 1).
3. Add a mapping from Shipping Profile to your newly created attribute in the Stores->Configuration->Reverb Configuration screen.

## Installation: Part 1 - Install the App

Please follow the instructions below to download and install the app. This assumes you have shell access to your server. If you have only FTP access, please download and unzip the app into /path/to/magento/app/code/

**Command Line instructions below require update pending home repo.**

*Note: When running commands in Magento2 please make sure you are located in the magento directory and logged in as the magento user* 
```bash
# Navigate to your magento root folder
cd /path/to/magento

# Download the release
cd /tmp && wget https://github.com/reverbdotcom/magento2/archive/Reverb.tar.gz //this will depend on where the repo lives and what you name it

# Unzip the release
tar zxvf Reverb.tar.gz //this will depend on repo name

# Copy everything from the app folder into your magento app
rsync -avzp Reverb/* /path/to/magento/app/code/

# Enable All Reverb Modules
php bin/magento module:enable Reverb_Base
php bin/magento module:enable Reverb_Io
php bin/magento module:enable Reverb_Payment
php bin/magento module:enable Reverb_Process
php bin/magento module:enable Reverb_ProcessQueue
php bin/magento module:enable Reverb_Reports
php bin/magento module:enable Reverb_ReverbSync
php bin/magento module:enable Reverb_Shipping

# Update Magento 2 databse
php bin/magento setup:upgrade

# Clear your cache
php bin/magento cache:flush
php bin/magento cache:clean
```

## Installation: Part 2 - Install the Cron

The cron is used to process the listing syncing queue. To see what's in your crontab, run `crontab -l`. Please ensure that your crontab contains one of the following examples:
```bash
* * * * * /usr/bin/php /path/to/magento/bin/magento cron:run | grep -v "Ran jobs by schedule" >> /path/to/magento/var/log/magento.cron.log
* * * * * /usr/bin/php /path/to/magento/update/cron.php >> /var/www/magento2/var/log/update.cron.log
* * * * * /usr/bin/php /path/to/magento/bin/magento setup:cron:run >> /var/www/magento2/var/log/setup.cron.log
```
or

```bash
* * * * * /usr/bin/php /path/to/magento/bin/magento cron:run | grep -v "Ran jobs by schedule" >> /path/to/magento/log/magento.cron.log
```

If your crontab does not contain either of these examples, please use `crontab -e` to edit it and copy the second line (`cron.sh`) into your crontab.


## Installation: Part 3 - Configuration

* In Magento Admin, go to Stores -> Configuration -> Reverb Configuration
* Put in your API Key (grab it from https://reverb.com/my/api_settings)
* Select Yes for Enable Reverb Module to turn on the sync
* If you also want to create drafts for skus that don't exist on Reverb, select "Enable Listing Creation" in the Reverb Default section.

## Usage - Listing Sync

The listing sync to Reverb can be triggered in two ways:

1. When you Save any Product in Magento, it will automaticaly sync to Reverb. Make sure you set "Sync to Reverb" to "Yes" on the bottom of the product page, and enable the Reverb Module in your global settings (see Part 3 of installation).

2. Bulk Sync. Under the Reverb menu item, select listing or order sync and use the Bulk Sync button in the upper right. The page will update with progress over time. Please note that very large catalogs (thousands of skus) may take an hour or more to fully sync. Please refresh the page to see the sync report.

## Usage - Order Sync

Orders are automatically synced on a five minute cron timer. If you aren't seeing orders, please ~~visit the Order Creation tab under Reverb and click the button to manually sync them~~ click the "Test Order Sync" button under the Reverb menu.  This will take you to a blank page, immediately click back and visit the "Order Updates" tab (this is for the beta only). Please report any issues with periodic syncing to the [Reverb Magento Support Group](https://groups.google.com/forum/#!forum/reverb-magento)

* **Orders are synced only 24 hours into the past** if you just installed the extension and want to sync older orders, please edit the file at app/code/Reverb/ReverbSync/Helper/Orders/Retrieval/Update.php and change MINUTES_IN_PAST_FOR_CREATION_QUERY to the number in minutes you want to go into the past. For 3 days, use 3 * 60 * 24 = 4320

## Syncing Orders - Paid or All

You can select whether to sync all orders (including unpaid accepted offers) or only orders awaiting shipment, via the settings screen

![](https://i.imgur.com/xXBJQ98.png)


## Notes on Bulk Sync

The bulk sync uses multiple threads (runs in parallel). It takes some time to spin up, so it may appear that nothing is happening for approximately 1 minute until your cron runs and starts picking up the jobs.

## Troubleshooting

### Bulk sync doesn't work

1. First, check the cron log in /path/to/magento/htdocs/var/log/cron.log
2. [Enable logging](http://devdocs.magento.com/guides/v2.0/config-guide/log/log-db.html).
3. Let the cron run again (wait a minute), then check logs `tail -f /path/to/magento/htdocs/var/log/*`

### Blank pages or plugin doesn't load

Please make sure you've [cleared your magento cache](http://devdocs.magento.com/guides/v2.0/config-guide/cli/config-cli-subcommands-cache.html#config-cli-subcommands-cache-clean).

## Support and Announcements

Please join the [Reverb Magento Support Group](https://groups.google.com/forum/#!forum/reverb-magento)

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## LICENSE

Copyright 2017 Reverb.com, LLC

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
