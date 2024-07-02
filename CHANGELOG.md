# Changelog ##

## 2.4.4 - 02 Jul 2024
* Include new `Acf_Json_Block` file during plugin scaffolding

## 2.4.3 - 23 Fev 2024
* Fix: ensure path to namespace in autoload is always relative to the package root

## 2.4.2 - 17 Jul 2023
* Fix: ensure command return an int

## 2.4.1 - 01 Mar 2022
* Support composer/installers ^2.0
* Don't create empty `Admin` directory during plugin scaffolding

## 2.4.0 - 20 Sept 2021
* Synchronize scaffolding process with the new classes in boilerplate
* Add replacement for plugin description

## 2.3.1 - 01 Mar 2021
* Synchronize scaffolding process with the new classes in boilerplate

## 2.3.0 - 25 Oct 2020
* Add support for Composer 2

## 2.2.2 - 15 Sept 2020
* Fix PSR-4 detection

## 2.2.1 - 19 May 2020
* Remove trailing slash from plugin path when creating PSR4 mapping

## 2.2.0 - 05 May 2020
* Handle psr-4 and no psr-4 plugins
* Add the autoload of files directly to the composer.json file
* Add the --no-autoload option
* Add the --boilerplate-version option
* Handle the case when the widget component is not available

## 2.1.0 - 17 Dec 2019
* Fix hardcoded path when scaffolding a new plugin

## 2.0.0 - 18 Nov 2019
* Composer cname change

## 1.0.0 - 16 Mai 2018
* Composer package
