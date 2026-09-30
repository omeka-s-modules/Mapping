# Mapping

Mapping is a module for Omeka S that allows you to geolocate Omeka S items and add interactive maps to your sites.

The module adds a "Mapping" tab to item editing, where users can add map pins and shapes, along with batch-editing options. Map data can also be bulk-added to items using the CSV Import module. 

For sites, it adds page blocks that can display maps and timelines for browsing items, and a "Map Browse" page to each site's Navigation settings. It adds resource blocks to items and item sets, where an item set's map shows all of its items. Sites can have location-based search fields added to their advanced search forms. 

Maps can display a variety of visual base maps, and can show overlays using IIIF, WMTS, WMS, and GeoJSON.

See the [Omeka S user manual](https://omeka.org/s/docs/user-manual/modules/copyresources/) for how to use this module.

See the [Omeka S developer documentation](https://omeka.org/s/docs/developer/module_docs/Mapping/) for advanced information.

## Requirements
 
Most of the module works on any database Omeka S supports. The "Map by Groups" page block additionally requires MySQL 8.0.24+ or MariaDB 11.7+, for the `ST_COLLECT` spatial function. On earlier versions the block reports the requirement when you configure it, and displays nothing on the page.

## Copyright

Mapping is Copyright © 2016-present Corporation for Digital Scholarship, Vienna,
Virginia, USA http://digitalscholar.org

The Corporation for Digital Scholarship distributes the Omeka source code under
the GNU General Public License, version 3 (GPLv3). The full text of this license
is given in the license file.

The Omeka name is a registered trademark of the Corporation for Digital Scholarship.

Third-party copyright in this distribution is noted where applicable.

All rights not expressly granted are reserved.
