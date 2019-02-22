# Direct Debit for Membership Extras
## Is Direct Debit for Membership Extras for me?
If you are a UK-based organisation that collects Direct Debit from your customers in a regular basis, this could be the right extension for you. This extension could potentially work with EU based SEPA too.

Direct Debit for Membership Extras is designed to give organisations control over every step of a typical Direct Debit process while help automating the repetitive tasks.

Please note that Direct Debit for Membership Extras is a companion extension of our [Membership Extras extension](https://github.com/compucorp/uk.co.compucorp.membershipextras). Membership Extras is designed to overcome many challenges that prevent CiviCRM from being a great membership management system. It is required as an dependency of Direct Debit for Membership Extras.

#### Direct Debit batch management
Direct Debit batch is a very cost-efficient way for processing Direct Debit transactions. Direct Debit for Membership Extras operates on the Direct Debit batch process. Two types of batches are included in the extension for different transaction types:

* New instruction batch - allow selecting and batching any newly submitted Direct Debit mandates
* Payment collection batch - allow selecting and batching any Direct Debit contributions that link to approved Direct Debit mandates

An AUDDIS standard export can be generated for any type of batch so that they can be submitted to online Direct Debit processing portals such as PT-X.

#### Multi-originator support
Direct Debit for Membership Extras understands the common structure which multiple business entities exist in one organisation. Therefore, the extension supports multiple originators throughout it’s whole process. This allows organisations to manage their income stream for all business entities within a single CRM.

#### Direct Debit emails and letters
With Direct Debit for Membership Extras, the following actions in the Direct Debit process will also be recorded as activities in the system:

* New Direct Debit Recurring Payment
* Update Direct Debit Recurring Payment
* Direct Debit Payment Collection Reminder
* Offline Direct Debit Auto-renewal
* Direct Debit Mandate Update

Scheduled reminders can be created basing on these activities to automate the corresponding communications. The extension also provides five custom made message templates that will magically populate Direct Debit related information when used in scheduled reminders.

Staff can also choose to manually send Direct Debit emails or download Direct Debit letters in Contribution/ Membership search results by using Direct Debit bulk actions and selecting any of the five templates.

#### Smart Debit integration (coming soon)
We are also planning to integrate Direct Debit for Membership Extras with Smart Debit soon which will probably make the extension “less manual” :)

## How do I get Direct Debit for Membership Extras?
Manual is designed to work with CiviCRM 4.7.x or 5.x plus. If you are on an earlier version of CiviCRM, you will need to upgrade your site first or contact info@compucorp.co.uk if you needs assistance to do so.

If your CiviCRM is already on CiviCRM 4.7.x or 5.x plus and this is the first time you use an extension,  please see [Here](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions "CiviCRM Extensions Installation") for full instructions and information on how to set and configure extensions.

To use Direct Debit for Membership Extras, you will need to firstly install a dependency extension - Membership Extras. You can get the latest release of Membership Extras from [CiviCRM extension directory page](https://civicrm.org/extensions/membership-extras) or our [Github repository release page](https://github.com/compucorp/uk.co.compucorp.membershipextras/releases).

The latest release of Direct Debit for Membership Extras can be found on [CiviCRM extension directory page](https://civicrm.org/extensions/manual-direct-debit) or our [Github repository release page](https://github.com/compucorp/uk.co.compucorp.manualdirectdebit/releases).

#### 
integration
If you are using Drupal and you would like to use Manual Direct Debit with Webform CiviCRM, you can simply download and install [the Membership Extras companion Drupal module](https://github.com/compucorp/webform_civicrm_membership_extras/releases)  and [the Direct Debit for Membership Extras companion Drupal module](https://github.com/compucorp/webform-manualdd/releases) and there you have it!

## How to configure Direct Debit for Membership Extras?
Every organisation have their own Direct Debit related information and different Direct Debit process. Direct Debit for Membership Extras have some basic settings that can be adjusted to largely improve your process efficiency and profitability. 

#### 1. Mandate settings
A few configurations need to be set In order for the system to correctly generate mandate information.

Firstly you should specify your organisation’s Direct Debit originator number. You can do that by going to **Administer -> Direct Debit -> Direct Debit Originator Number** and adding a new originator number. If you are operation multiple business entities that have different Direct Debit originator numbers, you can create all of them on this screen.

The system also needs to know the format of your organisation’s Direct Debit mandate reference so it can generate the these references correctly every time a new mandate is created. Go to **Administer -> Direct Debit -> Direct Debit Configuration** mandate configuration section. Here you can find two settings: “Default reference prefix” and “Minimum mandate reference length”.

CivCRM generates mandate references based on a simple sequence starting from “1” and increases by one with each new mandate. These two settings work together to dictate the format of any mandate reference generated from CiviCRM.  “Default Reference Prefix” indicates the prefix that should be added to the front of any of your mandate references. The number in “Minimum mandate reference length” setting will ensure your mandate reference is at least that number of characters long . It achieves that by filling any gaps with “0”.

In a typical example where your organisation’s mandate always starts with “M” and it’s at least 6 characters long. You should set “Default Reference Prefix” to “M” and “Minimum mandate reference length” to 6. In this case, the mandate references will generated as following:

* First mandate: “M” + “0000” (4 characters short hence filled with 4 “0”s) + “1” = M00001

* Eleventh mandate: “M” + “000” (3 characters short hence filled with 4 “0”s) + “11” = M00011

* 100,000th mandate: “M” + “100000” = M100000 (minimum length met by concatenating prefix and sequence hence no filling “0”s)

Please note that you might want to change to use a different prefix if you have been taking Direct Debit payments before installing Direct Debit for Membership Extras to avoid a reference clash.

#### 2. Batch settings
Whenever a collection day is not specified during the creation of a Direct Debit payment plan, Direct Debit for Membership Extras will calculate the optimal cycle day (collection day) of a month for you based on your current batching process.

There are three pieces of information of your batching process that the extension needs to collect in order to perform the best calculation. Go to **Administer -> Direct Debit -> Direct Debit Configuration** payment configuration section and you will find the following three settings:

* New instruction run dates: let the extension know which days of the month your organisation normally submit a new instruction batch
* Payment collection run dates: let the extension know which days of the month your organisation normally submit a payment collection batch
* Minimum days from new instruction to first payment: most of the banks will take a number of working days to process and approve new Direct Debit mandates. The is typically 3 working days. This means that you should not expect to collect payments from a newly submitted mandate until at least 3 working days after the mandate is submitted to the bank. In the example of 3 working days, we recommend you to set this setting to “5” days to offset the potential weekend days in between.

If you are interested in knowing how the calculation works, you can read this [sample timeline](https://compucorp.atlassian.net/wiki/spaces/PS/pages/189988971/Direct+Debit+Spec?preview=/189988971/241533058/Screen%20Shot%202018-01-22%20at%2017.12.22.png).

#### 3. Batch permissions
For non-super-admin users, two permissions are required to fully manage Direct Debit batches:

* CiviCRM: view all manual batches
* CiviCRM : Can manage Direct Debit Batches

Make sure these permissions are granted to trusted personnels. 

#### 4. Direct Debit payment collection reminder settings
If you would like the system to send out Direct Debit payment collection reminder to users automatically, you will need to enable the “Send Direct Debit Payment Collection Reminders” scheduled job.

With admin permissions,  go to **Administer -> System Settings -> Scheduled Jobs**. Simply enabled the “Send Direct Debit Payment Collection Reminders” scheduled job. You also have a chance to configure the job to have a custom frequency.

You can also tell the system, to send out the notification a number of days in advance of the expected payment receive date by going to **Administer -> Direct Debit -> Direct Debit Configurations** and specify the number of days you want the automated notification to be sent out in advance in the “Days in advance for Collection Reminder” setting.

#### 5. Direct Debit accounting code
Direct Debit for Membership Extras provides a Direct Debit payment method which is then used for all Direct Debit payment processor. Contact your accountant to find out which Financial Account should Direct Debit payment method use in order to ensure the income via this payment method is allocated to the correct accounting code.

To create the code, go to **Administer -> CiviContribute -> Financial Accounts** and add a new financial account with “Revenue” type and the accounting code your accountant suggested.

Once the account is created, you can link the account to the Direct Debit payment method by going to **Administer -> CiviContribute -> Payment Methods** and edit the “Direct Debit” item. Simply select the new account in the “Financial Account” setting and save.

## Support
CiviCRM extension directory page: [https://civicrm.org/extensions/manual-direct-debit](https://civicrm.org/extensions/manual-direct-debit)

Please contact the follow email if you have any question: <hello@compucorp.co.uk>

Paid support for this extension is available, please contact us either via Github or at <hello@compucorp.co.uk>
