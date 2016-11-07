<?php
/*
Comments: a Postleaf plugin.
Copyright 2016 Steve Pike

For now, the code below needs to be manually merged into index.php, pending
implementation of plugins in Postleaf. 

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

// Get base slugs from settings
$frags = (object) [
    'comments' => Setting::get('frag_comments') || 'comments'
];

////////////////////////////////////////////////////////////////////////////////////////////////////
// API routes
////////////////////////////////////////////////////////////////////////////////////////////////////

// Protected
$app->group("/api", function() {
    // Comments
    $this->get('/comments', 'Postleaf\Plugins\Comments\APICommentController:getComments');
    $this->post('/comments', 'Postleaf\Plugins\Comments\APICommentController:addComment');
    $this->put('/comments/{id}', 'Postleaf\Plugins\Comments\APICommentController:updateComment');
    $this->delete('/comments/{id}', 'Postleaf\Plugins\Comments\APICommentController:deleteComment');

})->add('Postleaf\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Admin views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Protected
$app->group("/$frags->admin", function() {
    // Comments
    $this->get('/comments', 'Postleaf\Controller\AdminController:comments');
    $this->get('/comments/new', 'Postleaf\Controller\AdminController:newComment');
    $this->get('/comments/{id}', 'Postleaf\Controller\AdminController:editComment');
})->add('Postleaf\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Theme views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Comments
$app->group("/$frags->comments", function() use ($frags) {
    $this->get("/{slug}[/$frags->page/{page:[0-9]+}]", 'Postleaf\Plugins\Comments\ThemeCommentController:comments');
})->add('Postleaf\Middleware:adjustPageNumbers');


////////////////////////////////////////////////////////////////////////////////////////////////////
// Custom handlers
////////////////////////////////////////////////////////////////////////////////////////////////////

