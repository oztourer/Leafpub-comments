<?php
/*
Comments: a Leafpub plugin.
Copyright 2016 Steve Pike

For now, the code below needs to be manually merged into index.php, pending
implementation of plugins in Leafpub. 

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/*
 * To integrate Comments into Leafpub, add the following line near the
 * end of Leafpub's index.php, just above $app->run():
 *    require 'index_comments.php';
 */

namespace Leafpub;

// Add comments slug from settings, or use default value
$frag_comments = Setting::get('frag_comments') ?: 'comments';

////////////////////////////////////////////////////////////////////////////////////////////////////
// API routes
////////////////////////////////////////////////////////////////////////////////////////////////////

// Protected
$app->group("/api", function() {

    // Comments
    $this->get('/comments', 'Leafpub\Plugins\Comments\APICommentController:getComments');
    $this->post('/comments', 'Leafpub\Plugins\Comments\APICommentController:addComment');
    $this->put('/comments/{id}', 'Leafpub\Plugins\Comments\APICommentController:updateComment');
    $this->delete('/comments/{id}', 'Leafpub\Plugins\Comments\APICommentController:deleteComment');

//});
})->add('Leafpub\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Admin views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Protected
$app->group("/$frags->admin", function() {

    // Comments
    $this->get('/comments', 'Leafpub\Controller\AdminController:comments');
    $this->get('/comments/new', 'Leafpub\Controller\AdminController:newComment');
    $this->get('/comments/{id}', 'Leafpub\Controller\AdminController:editComment');

})->add('Leafpub\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Theme views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Comments
$app->group("/$frag_comments", function() use ($frags) {
    $this->get("/{slug}[/$frags->page/{page:[0-9]+}]", 'Leafpub\Plugins\Comments\ThemeCommentController:comments');
})->add('Leafpub\Middleware:adjustPageNumbers');


////////////////////////////////////////////////////////////////////////////////////////////////////
// Custom handlers
////////////////////////////////////////////////////////////////////////////////////////////////////

