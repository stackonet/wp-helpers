#### 1.1.7
* Fix error undefined variable panel for Setting Api.
* Fix prepare statement error for creating and updating multiple items.

#### 1.1.6
* Add new method to create multiple database records.
* Refactor code to format item for a table when create new single/multiple record.
* Add new method to DatabaseModel class to update multiple rows on a single query.
* Add default setting api handler for default WordPress setting page design.
* Add setting api example.

#### 1.1.5
* Add method to set global GET parameters for all request.
* Add example for creating meta field for term.
* Add WooCommerce My Account menu page example.
* Add example class for adding custom product data to WooCommerce order item.
* Add GitHub Updater class for updating plugin from github.
* Add support for a batch (trash, restore, delete) operation.
* Add support for multiple columns for order_by parameters.

#### 1.1.4
* Rename `EmailTemplate` class to `EmailTemplateBase`
* Add two new email template class `ActionEmailTemplate` and `BillingEmailTemplate`
* Add cache support for `DatabaseModal` class
* Add example classes to illustrate users of various utility classes including
    * Background Tasks
    * Various Email Templates
    * REST Api example for Media Uploader and Web Login
    * Rest Client example for working with third party API
