#### 1.3.0

* Add `ApiCrudOperations` trait to handle default crud operations.

#### 1.2.1

* Fix `Sanitize::deep()` method fail on null value.
* Add `Filesystem` class to work with file system.

#### 1.2.0

* Add class`DefaultController` for handling REST crud operation.
* Update `DataStoreInterface` interface class
* Update `PostTypeModel` class

#### 1.1.12

* Timezone has been set to UTC for creating all database record.

#### 1.1.11

* Update PostTypeModel class.
* Fix composer version check error.

#### 1.1.10

* Update DatabaseModal::create_multiple() method to return newly created records ids.

#### 1.1.9

* Update PostTypeModel class.
* Add QueryBuilder class (alpha).
* Add method to sanitize deep mixed content.
* Update Data class to set initial data.
* Add new method to get foreign key constant name.
* Add FormBuilder method to generate html field.

#### 1.1.8

* Update Data class to make compatible with array_column function.
* Add Cacheable trait for handling caching functionality.
* Add TableInfo trait for reading table metadata from database.
* Update validate time method for validating 24 hours time.
* Update term field example javaScript.
* Add PostTypeModel class for working with custom post type.
* Add TermModel class for working with custom term.
* Add default value for minimum per_page for pagination.
* Add sanitize method for REST sort parameter.

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
