Symfony Api Platform Edition
========================

**WARNING**: This distribution support only Symfony 5.4. See the
[Installing & Setting up the Symfony Framework][15] page to find the Technical requirements
and steps to run Symfony 5.4 applications.

Welcome to the Symfony Api Platform Edition - a fully-functional Symfony
application that you can use as the skeleton for your new applications.

For details on how to download and get started with Symfony, see the
[Installation][1] chapter of the Symfony Documentation.

What's inside?
--------------

The Symfony Api Platform Edition is a custom configured Symfony 5.4 platform for creating Balu 2.0 Apis configured with the following defaults:

  * A name space named App, where we start coding

  * Twig as the only configured template engine;

  * Doctrine ORM/DBAL;

  * Swiftmailer;

  * Annotations enabled for everything.
  
It only provides a skeleton of the platform where we have to configure with the following bundles:

  * **FrameworkBundle** - The core Symfony framework bundle

  * [**DoctrineBundle**][7] - Adds support for the Doctrine ORM

  * [**TwigBundle**][8] - Adds support for the Twig templating engine

  * [**SecurityBundle**][9] - Adds security by integrating Symfony's security
    component

  * [**SwiftmailerBundle**][10] - Adds support for Swiftmailer, a library for
    sending emails

  * [**MonologBundle**][11] - Adds support for Monolog, a logging library
  
  * [**NelmioDocBundle**][18] - Adds support for generate documentation in the OpenAPI (Swagger) 
    format and provides a sandbox to interactively experiment with the API.
  
  * [**SensioGeneratorBundle**][13] (in dev env) - Adds code generation
    capabilities

  * [**WebServerBundle**][14] (in dev env) - Adds commands for running applications
    using the PHP built-in web server

   * [**SymfonySerializer**][2]  - Used to turn objects into a specific format (XML, JSON, YAML, …) and the other way around.

   * [**SymfonyUID**][3] - Provides utilities to work with unique identifiers (UIDs) such as UUIDs and ULIDs.

   * [**Oauth2-client-bundle**][4] - Easily integrate with an OAuth2 server (e.g. Facebook, GitHub) for Social authentication / login
   
   * [**Rabbitmq-bundle**][4] - Easily integrate with Rabbitmq 

  * **DebugBundle** (in dev/test env) - Adds Debug and VarDumper component
    integration

All libraries and bundles included in the Symfony Standard Edition are
released under the MIT or BSD license.

Environment Setup
--------------
Make sure that composer is installed in your system and make a clone from the repository

   - cd to the project folder
   - Run <code>composer install</code>
   - Configure database (Example DATABASE_URL=mysql://root:@localhost:3306/wedo?serverVersion=8.0.25)
   - Configure RABBITMQ (Example RABBITMQ_URL=amqp://guest:guest@localhost:5672) 
   - Run php bin/console rabbitmq:setup-fabric


## Development Guide

### Branches

- `master`: This branch stores the official release history.
- `develop`: This branch serves as an integration branch for features.
- `staging`: This branch for testing purposes.
- `feature/feature-name`: feature branches use `develop` as their parent branch. When a feature is complete, it gets merged back into `develop`. Features should never interact directly with `master`.
- `hotfix/hotfix-name`: Maintenance or "hotfix" branches are used to quickly patch production releases. This is the only branch that should fork directly off of `master`. As soon as the fix is complete, it should be merged into both `master` and `develop`, and `master` should be tagged with an updated version number.
- `fix/bugid-bug-name`: fix branches use `develop` as their parent branch. When a fix is complete, it gets merged back into `develop`. fix should never interact directly with `master`.

### Creating a feature branch

Without the _git-flow_ extensions:

```
git checkout develop
git checkout -b feature/feature-name
```

### Finishing a feature branch

When you’re done with the development work on the feature, the next step is to make _merge request_ the feature branch into `develop`.

### Creating a hotfix branch

Without the _git-flow_ extensions:

```
git checkout master
git checkout -b hotfix/hotfix-name
```

### Finishing a hotfix branch

When you’re done with the development work on the hotfix, the next step is to make _merge request_ the hotfix branch into `master`.

[1]:  https://symfony.com/doc/5.3/setup.html
[6]:  https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
[7]:  https://symfony.com/doc/5.3/doctrine.html
[8]:  https://symfony.com/doc/5.3/templating.html
[9]:  https://symfony.com/doc/5.3/security.html
[10]: https://symfony.com/doc/5.3/email.html
[11]: https://symfony.com/doc/5.3/logging.html
[13]: https://symfony.com/doc/current/bundles/SensioGeneratorBundle/index.html
[14]: https://symfony.com/doc/current/setup/built_in_web_server.html
[15]: https://symfony.com/doc/5.3/setup.html
[18]: https://symfony.com/doc/current/bundles/NelmioApiDocBundle/index.html
[2]: https://symfony.com/doc/current/components/serializer.html
[3]: https://symfony.com/doc/current/components/uid.html
[4]: https://github.com/knpuniversity/oauth2-client-bundle
