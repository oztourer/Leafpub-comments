# <img src="https://www.postleaf.org/content/themes/postleaf/img/logo-color-text.svg" alt="Postleaf" width="300">

**A basic commenting system integrated into Postleaf.**

Created by [Steve Pike](https://twitter.com/oztourer)

## Postleaf requirements

- PHP 5.5+
- MySQL 5.5.3+

## Development status

- The code was not developed using the prescribed Postleaf tools of node.js, composer and gulp, and the provided composer.json and gulpfile.js are untested skeleton files.
- The SQL Comments table includes a parent field, allowing nested replies to comments, but this has not yet been implemented in the Comments class.


## Installation

- Install [Postleaf](https://github.com/Postleaf)!
- Copy the source files from app/source to the source directory of the Postleaf installation.
- Run defaults/default.comments.sql on the existing Postleaf database to create the Comments table (you may need to edit prefix 'postleaf_' to suit your installation.
- Merge index-comments.php into Postleaf's index.php. This manual step will be automated once plugins have been implemented in Postleaf.
- No changes should be required to the existing Postleaf classes.
- Install theme [wildgeese](https://github.com/oztourer/Postleaf-theme-wildgeese) as an example implementation of comments within a theme (other themes should still work but will not show or support comments).

## Versioning

Postleaf is maintained under the [Semantic Versioning guidelines](http://semver.org/) and this plugin attempts to adhere to the same guidelines.

## Developers

**Steve Pike**

- https://twitter.com/oztourer
- https://github.com/oztourer

## License

© 2016 [Steve Pike](https://twitter.com/oztourer)

This software is copyrighted. You may use it under the terms of the GNU GPLv3 or later. See LICENSE.md for licensing details.

