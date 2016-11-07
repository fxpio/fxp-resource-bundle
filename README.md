Sonatra Resource Bundle
=======================

[![Latest Version](https://img.shields.io/packagist/v/sonatra/resource-bundle.svg)](https://packagist.org/packages/sonatra/resource-bundle)
[![Build Status](https://img.shields.io/travis/sonatra/SonatraResourceBundle/master.svg)](https://travis-ci.org/sonatra/SonatraResourceBundle)
[![Coverage Status](https://img.shields.io/coveralls/sonatra/SonatraResourceBundle/master.svg)](https://coveralls.io/r/sonatra/SonatraResourceBundle?branch=master)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/sonatra/SonatraResourceBundle/master.svg)](https://scrutinizer-ci.com/g/sonatra/SonatraResourceBundle?branch=master)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9048a77d-9d67-40cb-8d31-1783e5bf6738.svg)](https://insight.sensiolabs.com/projects/9048a77d-9d67-40cb-8d31-1783e5bf6738)

The Sonatra ResourceBundle is a resource management layer for doctrine. This bundle has been
designed to facilitate the creation of a Batch API for processing a list of resources<sup>1</sup>
(ex. external data loader).

However, it is entirely possible to build an API Bulk above this bundle.

It allows to easily perform actions on Doctrine using the best practices automatically according
to selected options (flush for each resource or for all resources, but also skip errors of the
invalid resources), whether for a resource or set of resources.

Features include:

- All features of [Sonatra Resource](https://github.com/sonatra/sonatra-resource)
- Compiler pass to override or add a custom resource domain
- Compiler pass to add a custom converter

Documentation
-------------

The bulk of the documentation is stored in the `Resources/doc/index.md`
file in this bundle:

[Read the Documentation](Resources/doc/index.md)

Installation
------------

All the installation instructions are located in [documentation](Resources/doc/index.md).

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

[Resources/meta/LICENSE](Resources/meta/LICENSE)

About
-----

Sonatra ResourceBundle is a [sonatra](https://github.com/sonatra) initiative.
See also the list of [contributors](https://github.com/sonatra/SonatraResourceBundle/graphs/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/sonatra/SonatraResourceBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.
