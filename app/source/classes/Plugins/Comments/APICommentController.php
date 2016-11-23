<?php
//
// Extends the Controller for API endpoints to support Comments
//
namespace Leafpub\Plugins\Comments;

use Leafpub\Plugins\Comments\Comment,
    Leafpub\Session,
    Leafpub\Admin,
    Leafpub\Language,
    Leafpub\Post,
    Leafpub\Controller\Controller;


class APICommentController extends Controller {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Comments
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // GET api/comments
    public function getComments($request, $response, $args) {
        $params = $request->getParams();

        // Anyone can view comments
        //if(!Session::isRole(['owner', 'admin', 'editor'])) {
        //    return $response->with(403);
        //}

        // Get comments
        $comments = Comment::getMany([
            'items_per_page' => 10,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        //], $pagination);
        ]);

        // Render comment list
        $html = Admin::render('partials/comment-list', [
            'comments' => $comments
        ]);

        // Send response
        return $response->withJson([
            'success' => true,
            'html' => $html,
            'pagination' => $pagination
        ]);
    }

    // Private method to handle add and update
    private function addUpdateComment($action, $request, $response, $args) {
        $params = $request->getParams();
        $properties = $params['properties'];
        $id = $action === 'add' ? null : $args['id'];

  
        // Must be logged in to add/update comments
	if (!Session::isRole(['owner', 'admin', 'editor', 'author'])) {
            return $response->withStatus(403);
        }

        // Add/update the comment
        try {
            if($action === 'add') {
                Comment::add($properties);
            } elseif ($action === 'del') {
                Comment::delete($id, $properties);
	    } else {
                Comment::update($id, $properties);
            }
        } catch(\Exception $e) {
            // Handle errors
            switch($e->getCode()) {
                case Comment::INVALID_ID:
                    $invalid = ['id'];
                    $message = Language::term('the_id_you_provided_cannot_be_used');
                    break;
                case Comment::COMMENT_NOT_FOUND:
                    $invalid = ['id'];
                    $message = Language::term('the_comment_id_you_entered_cannot_be_found');
                    break;
                case Comment::INVALID_USER:
                    $invalid = ['id'];
                    $message = Language::term('the_user_id_you_provided_cannot_be_used');
                    break;
                case Comment::INVALID_POST:
                    $invalid = ['id'];
                    $message = Language::term('the_post_id_you_provided_cannot_be_used');
                    break;
                default:
                    $message = $e->getMessage();
            }

            return $response->withJson([
                'success' => false,
                'invalid' => $invalid,
                'message' => $message
            ]);
        }

        return $response->withJson([
            'success' => true
        ]);
    }

    // POST api/comments
    public function addComment($request, $response, $args) {
        return $this->addUpdateComment('add', $request, $response, $args);
    }

    // PUT  api/comments/{id}
    public function updateComment($request, $response, $args) {
        return $this->addUpdateComment('update', $request, $response, $args);
    }

    // DELETE api/comments/{id}
    public function deleteComment($request, $response, $args) {
        return $this->addUpdateComment('del', $request, $response, $args);
    }
    
}

