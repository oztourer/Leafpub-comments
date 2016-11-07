<?php
//
// Extends the Controller for theme views to support Comments
//
namespace Postleaf\Plugins\Comments;

use Postleaf\Plugins\Comments\Comment,
    Postleaf\Controller\Controller;



class ThemeCommentController extends Controller {

    public function comments($request, $response, $args) {
        $html = Comment::render($args['slug'], $args['page']);

	return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }

}
