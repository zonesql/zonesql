# ZoneSQL

## Overview

ZoneSQL is a web based database interface application allowing SQL access to a 
number of different DBMS's including MySQL, Microsoft SQL Server and SQLite. With 
the convenience of a web based architecture, ZoneSQL provides a rich efficient 
interface with the familiar look and feel of some existing powerful software 
based DBMS tools. This provides an optimal platform for working with SQL and 
data. 

The UI provides resizable panels including a full tree view of the server,
databases and tables, a syntax highlighted SQL query entry window, and a fully 
featured rich dynamic grid output. The product is useful for setting up direct 
access to databases at remote sites via simple web based access. Security and 
authentication are configurable.

## Download and Installation

### Download Release

To download the latest release, pre-built, optimised and ready for use, visit 
http://www.zonesql.com and click one of the available releases under the 
"Download and Installation" section.

### Download Source

The ZoneSQL full source can be downloaded using the following command:

git clone --recurse-submodules https://github.com/zonesql/zonesql

This will clone the ZoneSQL package as full uncompiled source code including 
all of it's dependency submodules. The submodules are third party repos 
including dojo, ace editor, dgrid and are cloned into the /src/  directory. 
The following script is supplied which will use the dojo tool to compile the 
source js into a /dist/ directory:

/zonesql/src/buildZoneSQL.sh

The 'environment' property in the /zonesql/api/config.php file can be used to 
switch the ZoneSQL installation between 'development' and 'production', which 
will use the uncompiled /src/ or the compiled /dist/ path respectively.

## Configuration

Once downloaded/installed, ensure the /zonesql/ directory is in a web 
accessible path, or set up an apache alias pointing to this path as required. It
is STRONGLY recommended this web application is served through a https/SSL 
connection as highly sensitive information and passwords may be passed through 
it.

Update the application configuration via the /zonesql/api/config.php file. 
Detailed information for each setting can be found in the config file comments. 
Check all settings but in particular ensure the following items are set as 
necessary. 

* db_interface
* authentication
* connection_methods
* environment

## Requirements

Apache, PHP

## Acknowledgements

ZoneSQL includes the following components:

* Slim Framework - http://www.slimframework.com/
* ADOdb Database Abstraction Library for PHP - http://adodb.sourceforge.net/
* Dojo Toolkit - https://dojotoolkit.org/
* ACE Editor - http://ace.c9.io/
* dgrid - http://dgrid.io/

## Licensing

View LICENSE file for details.

## Author

Adam Tandowski (Author and Developer)

## Contributors

Daniel Tandowski (DevOps and Technical Consultant)

