# OST Loyalty 2.0
OST Loyalty is an add-on for the e-commerce software [cs-cart](https://www.cs-cart.com) and enables store owners to create
and use their own customer loyalty points system on the blockchain with [OST Simple Token](https://www.ost.com).
 
# Add-On installation
If you just want to try it out: Download a CS-Cart copy from the official site, install and activate the demo licence, then 
download this add-on (you will find it on the [CS-Cart marketplace](https://marketplace.cs-cart.com/)) and extract it to your CS-Cart base folder 
(like any other cs-cart add-on).

The [CS-Cart Composer installation](https://github.com/TechupBusiness/cscart-composer) could make your life easier.

Afterwards you need to "install" it in your cs-cart admin backend. First click on `Add-ons`, then `Manage add-ons` and `Browse all available add-ons`
like seen on the following screenshot:
![Screenshot install add-on][docs/images/add-on-installation.png]

Now simply select `SimpleToken - OST Loyalty (TechupBusiness Addon)` and click install. After a short moment the page should refresh and you
will see a success message. The add-on is not yet active (status=disabled). Please follow the `Add-On setup` instructions below before you activate
OST Loyalty in your store.

# Add-On setup
## Connect to OST
First click on the add-on to show up the configuration dialog and enter your OST KIT **API Key** and **Secret**. The mode needs to be set to **Test** 
until the production side-chains go live.
![Configure add-on][docs/images/config-credentials.png]

There you need to enter your **API Key** and **Secret** that are provided in your 
[OST KIT Dashboard](https://kit.ost.com) under the Developer section:
![Finding OST KIT login data][docs/images/ost-kit-credentials.png]

## Configure values

## Configure order statuses

## Configure fiat currencies

## Enable add-on

# Use as marketing actions


# Known issues
- Deletion of accepted orders does not revert the token transfer (only setting a proper status can revert the transaction - please see section `Configure order statuses` above)