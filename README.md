# MyAdmin Monitoring Plugin

Server and website monitoring plugin for the [MyAdmin](https://github.com/detain/myadmin) control panel. Provides IP-based service monitoring (ping, HTTP, SMTP, FTP, DNS, IMAP, POP, SSH), notification management, and website security scanning via the Sucuri SiteCheck API.

## Badges

[![Build Status](https://github.com/detain/myadmin-monitoring-plugin/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-monitoring-plugin/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-monitoring-plugin/version)](https://packagist.org/packages/detain/myadmin-monitoring-plugin)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-monitoring-plugin/downloads)](https://packagist.org/packages/detain/myadmin-monitoring-plugin)
[![License](https://poser.pugx.org/detain/myadmin-monitoring-plugin/license)](https://packagist.org/packages/detain/myadmin-monitoring-plugin)

## Features

- Monitor server availability across eight protocols (ping, HTTP, SMTP, FTP, DNS, IMAP, POP, SSH)
- Per-IP service status tracking with history (Up / Down / Unknown)
- Configurable notification preferences (notify once or repeatedly while down)
- Admin and customer role separation with ACL-based access control
- Website virus/malware scanning through Sucuri SiteCheck integration
- Automatic detection of unmonitored active services
- Integrates with the MyAdmin event dispatcher plugin system

## Installation

Install with Composer:

```sh
composer require detain/myadmin-monitoring-plugin
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](https://www.gnu.org/licenses/old-licenses/lgpl-2.1.en.html) license.
