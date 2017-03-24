Counter
=======

A Plugin for moziloCMS 2.0

Counts, stores and analyzes current page visits.

## Installation
#### With moziloCMS installer
To add (or update) a plugin in moziloCMS, go to the backend tab *Plugins* and click the item *Manage Plugins*. Here you can choose the plugin archive file (note that it has to be a ZIP file with exactly the same name the plugin has) and click *Install*. Now the Counter plugin is listed below and can be activated.

#### Manually
Installing a plugin manually requires FTP Access.
- Upload unpacked plugin folder into moziloCMS plugin directory: ```/<moziloroot>/plugins/```
- Set default permissions (chmod 777 for folders and 666 for files)
- Go to the backend tab *Plugins* and activate the now listed new Counter plugin

## Syntax
```
{Counter}
```
Inserts the Counter and depending on backend configuration different or all counter elements.
If visits shall be counted for the whole website, the plugin tag can be placed in the *template.html* of the current template (e.g. in the footer section).

## License
This Plugin is distributed under *GNU General Public License, Version 3* (see LICENSE) or, at your choice, any further version.

## Documentation
A detailed documentation and demo can be found on DEVMOUNT's website:
http://t.devmount.de/Develop/moziloCMS/Plugins/Counter.html
