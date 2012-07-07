# Web2Project.OrderManagement #

## Introduction ##
Welcome,
Web2Project.OrderManagement is an add-on module for the [Web2Project](http://web2project.net/) Open Source project management system.
Currently (02/29-12) this module is developed by Eirik Eggesbø Ottesen and aims to become a fully features order management component
for the Web2Project system.

This module introduces functionality required to handle orders and keeping track of incoming components and prices. It does
not provide full inventory management, but can assist in keeping track of the status of each order as well as keeping a table
of standard order components and prices. It is even capable of fething exchange rates from [openexchangerates.org] (http://openexchangerates.org/)!

If you have any feature requests bugs to report please use the github issues tool to report them, or better, fork this
repository and send me a pull request with a patch!

## Installation ##
Unfortunately using dojo for things such as dialogs and grids, there are some additional steps required to fully set
up the module within the Web2Project environment.

1. Install as you would install a normal module
2. Open up the styles you have enabled in w2p and add the following to the header.php file and add the following class
to the body tag "claro".

If you don't add the class to the body you will experience weird glitches like invisible background on your dialogs and
buttons that look very strange.

## Roadmap ##
### v0.3 ###
* Support for order compoents fetched from the database
* Replaces a host of different jQuery plugins with [Dojo] (http://dojotoolkit.org)

### v0.4 ###
* Support for order templates. Templates will have preset component list and can be "copied" as new orders.
* Improved UI for all forms. Replace the jQuery parts of the UI with dojo widgets and improve workflow.

## Differences from Web2Project Core ##
The Web2Project.OrderManagement module is using several libraries and techniques that is not available in the core system. Most notable
are the TCPDF for PDF generation, Dojo for JavaScript and UI and TBS as Php template engine. The reason these libraries are included
here is to provide extra functionality that was difficult to archieve using only what was available in the w2p system. Unfortunately
the libraries cause the module to be a bit larger than it normally would be.

**WARNING!** This project is still very early in its development and is not yet ready for use for other purposes than testing, feedback
and feature requests. Database changes may appear without warning and break the module. Please *only* use commits tagged with STABLE
unless you are absolutely sure about what you are doing.