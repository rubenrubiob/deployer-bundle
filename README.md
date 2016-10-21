Deployer Bundle
===============

This is a Symfony bundle to deploy to multiple hosts at once using the
git strategy. It provides a command to do it easily.

Each host may have independent configuration, i.e., you may want to
checkout a development branch in your staging server, whereas you may
want the master branch in your production server.

By default, the bundle only executes a git pull and clears the cache,
but there are optional tasks that may also be executed. The order of
execution is:

1. git pull
2. composer update (if set)
3. assets install (if set)
4. database migrations (if set)
5. cache clear

Installation
============

Step 0: Install fabric
----------------------

This bundle uses [Fabric](http://www.fabfile.org) to connect to hosts
and execute tasks on them. You usually install it via
[pip](http://pip-installer.org):

```bash
pip install fabric
```

Check fabric [installation chapter](http://www.fabfile.org/installing.html)
for further information about installation.

Step 1: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require rrb/deployer-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Step 2: Enable the Bundle
-------------------------

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...

            new Rrb\DeployerBundle\RrbDeployerBundle(),
        );

        // ...
    }

    // ...
}
```

Step 3: Configure the Bundle
----------------------------

This bundle requires a minimal configuration in order to work (you
have to tell it where to deploy, right?), so you have to enable and
configure it in your `config.yml` file. This is the minimal configuration
that you require:

```yml
rrb_deployer:
    hosts:
        host_name_1:
            environment:
                src: '/path/to/src/in/host/'
            host:
                user: 'username'
                server: 'hostname'
        host_name_2:
            environment:
                src: '/path/to/src/in/host/'
            host:
                user: 'username'
                server: 'hostname'
        host_name_3:
            environment:
                src: '/path/to/src/in/host/'
            host:
                user: 'username'
                server: 'hostname'
        ...
```

You may have as many hosts as you want. Check the 
[configuration reference](#configuration-reference) to know more about
all available configuration options.

By default, fabric forwards the ssh-agent, so it is best if you deploy
using RSA keys. But, of course, there is the option of connect using
a password: just remember to add it to your `parameters.yml` file and
use its value in your `config.yml` file. Never commit your passwords! 

Step 3: Deploy!
---------------

Now that you have the bundle configured, you are ready to deploy. Deploy
will always be sequential.

You may deploy to all hosts:

```bash
bin/console rrb:deployer:deploy --all
```

You may deploy to only one host
 
```bash
bin/console rrb:deployer:deploy host_name_2
```

...or to a list of hosts, but not all:

```bash
bin/console rrb:deployer:deploy host_name_2 host_name_3
```

If no host is specified, first host defined will be used:

```bash
bin/console rrb:deployer:deploy
```

is the same as executing:

```bash
bin/console rrb:deployer:deploy host_name_1
```

You may also force a task execution besides deploy, overriding the default
configuration:

```bash
bin/console rrb:deployer:deploy host_name_1 --composer-update
```

Check the [command reference](#command-reference) to know all available
options. Options supplied to command take precedence over the ones
defined in the `config.yml` file.

Remember to replace `bin/console` by `app/console` if you are using
an older version of Symfony.

Configuration reference
-----------------------

These are the default values, that may be overridden:

```yml
# Default configuration for "RrbDeployerBundle"
rrb_deployer:
    fabric:               fab
    timeout:              3600
    idle_timeout:         600
    hosts:                # Required

        # Prototype
        default:
            environment:
                php:                  php

                # This is the absolute path to the source code within server
                src:                  ~ # Required
                env:                  prod
            host:
                user:                 ~ # Required
                password:             null

                # This is the domain name or the IP of the server to deploy to
                server:               ~ # Required
                port:                 22
            git:
                remote:               origin
                branch:               master
            tasks:
                database_migration:
                    enabled:              false
                composer_update:
                    enabled:              false
                    bin:                  composer
                assets_install:
                    enabled:              false
                    symlink:              true
                    relative:             false
                    path:                 web
```

Command reference
-----------------

```bash
Usage:
  rrb:deployer:deploy [options] [--] [<hosts>]...

Arguments:
  hosts                     Host(s) name(s) defined in config.yml to deploy to (separate multiple hosts with a space). If omitted, first host defined will be used.

Options:
      --all                 If set, it will deploy to all servers defined in config.yml. It overrides hosts argument.
      --composer-update     If set, it will update composer. It overrides value in config.yml.
      --assets-install      If set, it will install assets. It overrides value in config.yml.
      --database-migration  If set, it will execute migrations of Doctrine. It overrides value in config.yml.
  -h, --help                Display this help message
  -q, --quiet               Do not output any message
  -v|vv|vvv, --verbose      Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
 Command to deploy changes to hosts using git
```