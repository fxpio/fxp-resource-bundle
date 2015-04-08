Sonatra Resource Bundle
=======================

[![Latest Version](https://img.shields.io/packagist/v/sonatra/resource-bundle.svg)](https://packagist.org/packages/sonatra/resource-bundle)
[![Build Status](https://img.shields.io/travis/sonatra/SonatraResourceBundle/master.svg)](https://travis-ci.org/sonatra/SonatraResourceBundle)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9048a77d-9d67-40cb-8d31-1783e5bf6738.svg)](https://insight.sensiolabs.com/projects/9048a77d-9d67-40cb-8d31-1783e5bf6738)

The Sonatra ResourceBundle is a resource management layer for doctrine. This bundle has been
designed to facilitate the creation of a Batch API for processing a list of resources<sup>1</sup>
(ex. external data loader).

However, it is entirely possible to build an API Bulk above this bundle.

It allows to easily perform actions on Doctrine using the best practices automatically according
to selected options (flush for each resource or for all resources, but also skip errors of the
invalid resources), whether for a resource or set of resources.

Features include:

- Resource Domain Manager for get a resource domain for an doctrine resource
- Resource Domain for each doctrine resource for easy management:
  - generate new instance of resource with default value configured by Sonatra DefaultValueBundle
  - create one resource with validation (for object or Form instance)
  - create a list of resources with validation for each resource (for object or Form instance)
  - update one resource with validation (for object or Form instance)
  - update a list of resources with validation for each resource (for object or Form instance)
  - upsert one resource with validation (create or update for object or Form instance)
  - upsert a list of resources with validation for each resource (create or update for object or Form instance)
  - delete one resource with soft delete or hard delete for compatible resources
  - delete a list of resources with soft delete or hard delete for compatible resources
  - undelete one resource for compatible resources with soft delete
  - undelete a list of resources for compatible resources with soft delete
- Each resource domain allow:
  - to have the possibility to do an transaction with rollback for each resource of the list or for all resources in only one time
  - to have the possibility to skip the errors of an resource, and continue to run the rest of the list (compatible only with the transaction for each resource)
  - to return the list of resources with the status of the action (created, updated, error ...) on each resource of the list
- Compiler pass for override or add a custom resource domain

> **Note:**
> <sup>1</sup> A resource is an doctrine entity or doctrine document

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
See also the list of [contributors](https://github.com/sonatra/SonatraResourceBundle/contributors).

Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker](https://github.com/sonatra/SonatraResourceBundle/issues).

When reporting a bug, it may be a good idea to reproduce it in a basic project
built using the [Symfony Standard Edition](https://github.com/symfony/symfony-standard)
to allow developers of the bundle to reproduce the issue by simply cloning it
and following some steps.
