# <img src="https://www.postleaf.org/content/themes/postleaf/img/logo-color-text.svg" alt="Postleaf" width="300">

**A basic commenting system integrated into Postleaf.**

Created by [Steve Pike](https://twitter.com/oztourer)

## Postleaf requirements

- PHP 5.5+
- MySQL 5.5.3+

## Development status

- The code was not developed using the prescribed Postleaf tools of node.js, composer and gulp, and the provided composer.json and gulpfile.js are untested skeleton files.
- The SQL Comments table includes a parent field, allowing nested replies to comments, but this has not yet been implemented in the Comments class.
- There is currently no admin interface to comments.
- Comments are for now just plain text - no HTML or Markup support.


## Installation

- Install [Postleaf](https://github.com/Postleaf)!
- Copy the files from directory 'app' to the root directory of the Postleaf installation. If done correctly you should have index_comments.php in the same directory as Postleaf's index.php, and a new directory Plugins below source/Classes.
- Run defaults/default.comments.sql on the existing Postleaf database to create the Comments table (you may need to edit prefix 'postleaf_' to suit your installation).
- To enable Comments add the following line near the end of Postleaf's index.php, just above $app->run():
 - require 'index_comments.php';
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

