![Banner](./src/resources/banner.png)

# weglot

Translate your Craft website in 110+ languages within minutes with Weglot Translate, without any coding.

## Requirements

This plugin requires Craft CMS 5.8.0 or later, and PHP 8.2 or later.

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project’s Control Panel and search for “weglot”. Then press “Install”.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require weglot/craft-weglot

# tell Craft to install the plugin
./craft plugin/install weglot
```

#### With DDEV

If you are managing your project with DDEV, you can use its built-in commands to achieve the same result. Run these commands from the root of your project on your local machine:

```bash
# tell Composer to load the plugin via DDEV
ddev composer require weglot/craft-weglot

# tell Craft to install the plugin via DDEV
ddev craft plugin/install weglot
```