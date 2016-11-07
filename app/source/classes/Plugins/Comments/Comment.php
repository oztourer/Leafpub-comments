<?php
//
// Postleaf\Comments: methods for working with comments
//
namespace Postleaf\Plugins\Comments;

use Postleaf\Postleaf,
    Postleaf\Session,
    Postleaf\User,
    Postleaf\Post,
    Postleaf\Setting,
    Postleaf\Renderer,
    Postleaf\Theme,
    Postleaf\Controller\Controller;

class Comment extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        INVALID_ID = 1,
        COMMENT_NOT_FOUND = 2,
        INVALID_USER = 3,
	INVALID_POST = 4,
	NO_CONTENT = 5;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Private methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Normalize data types for certain fields
    private static function normalize($comment) {
        // Cast to integer
        $comment['id'] = (int) $comment['id'];
        $comment['author'] = (int) $comment['author'];

        // Convert dates from UTC to local
        $comment['pub_date'] = self::utcToLocal($comment['pub_date']);
	
	if (Session::isRole(['owner', 'admin', 'editor']) ||
		$comment['author'] == Session::user('id'))
	    $comment['editable'] = true;
	else
	    $comment['editable'] = false;

        return $comment;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds a comment
    public static function add($properties) {

        // Comment cannot be empty
        if (empty($properties['comment']))
            throw new \Exception('Empty content.', self::NO_CONTENT);

        // Generate publish date as UTC
	$properties['pub_date'] = gmdate("Y-m-d H:i:s");

        // Translate post slug to id
	// getId seems to have been removed from Post
        //$properties['post'] = Post::getId($properties['post']);
        $properties['post'] = Post::get($properties['post'])['id'];
        if (!$properties['post'])
            throw new \Exception('Invalid post.', self::INVALID_POST);

	// Translate author slug to ID
        $properties['author'] = Session::user('id');
        if (!$properties['author'])
            throw new \Exception('Invalid user.', self::INVALID_USER);

        // Status must be `published` or `draft`
        //if ($properties['status'] !== 'draft') $properties['status'] = 'published';

        try {
            // Create the comment
            $st = self::$database->prepare('
                INSERT INTO __comments SET
		    post = :post,
                    author = :author,
                    comment = :comment,
                    pub_date = :pub_date
            ');
           $st->bindParam(':post', $properties['post']);
            $st->bindParam(':author', $properties['author']);
            $st->bindParam(':comment', $properties['comment']);
            $st->bindParam(':pub_date', $properties['pub_date']);
            $st->execute();
            $comment_id = (int) self::$database->lastInsertId();
            if ($comment_id <= 0)
		return false;
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }

    }

    // Returns the total number of comments for a post
    public static function count($options = null) {
        // Merge options
        $options = array_merge([
            'post' => null,
            'author' => null,
            'end_date' => date('Y-m-d H:i:s'),
            'start_date' => null,
        ], (array) $options);

        // Convert dates to UTC
        if ($options['start_date'])
	    $start_date = self::localToUtc($options['start_date']);
        if ($options['end_date'])
	    $end_date = self::localToUtc($options['end_date']);

        if ($options['start_date'])
	    $start_date = self::localToUtc($options['start_date']);
        if ($options['end_date'])
	    $end_date = self::localToUtc($options['end_date']);

        // Build count query
        $sql = 'SELECT COUNT(*) FROM __comments WHERE 1 = 1';

        // Add options to query
        if ($options['post'])
	    $sql .= ' AND post = (SELECT id FROM __posts WHERE slug = :post)';
        if ($options['author'])
	    $sql .= ' AND author = (SELECT id FROM __users WHERE slug = :author)';
        if ($options['start_date'])
	    $sql .= ' AND pub_date >= :start_date';
        if ($options['end_date'])
	    $sql .= ' AND pub_date <= :end_date';

        // Fetch results
        try {
            $st = self::$database->prepare($sql);
            if ($options['start_date'])
		$st->bindParam(':start_date', $start_date);
            if ($options['end_date'])
		$st->bindParam(':end_date', $end_date);
            if ($options['author'])
		$st->bindParam(':author', $options['author']);
            if ($options['post'])
		$st->bindParam(':post', $options['post']);
            $st->execute();
            return (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }
    }


    // Tells whether a comment exists
    public static function exists($id) {
        try {
            $st = self::$database->prepare('SELECT id FROM __comments WHERE id = :id');
            $st->bindParam(':id', $id);
            $st->execute();
            return !!$st->fetch();
        } catch(\PDOException $e) {
            return false;
        }
    }

    // Gets a single comment. Returns an array on success, false if not found.
    public static function get($id) {
        try {
            $st = self::$database->prepare('
                SELECT
                    id, author,
                    (SELECT slug FROM __posts WHERE id = __comments.post) AS post,
                    (SELECT slug FROM __users WHERE id = __comments.author) AS author_slug,
                    comment,
		    pub_date
                FROM __comments
                WHERE id = :id
            ');
            $st->bindParam(':id', $id);
            $st->execute();
            $comment = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$comment)
		return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        return self::normalize($comment);
    }

    // Gets one comment immediately before or after the target comment
    public static function getAdjacent($id, $options = null) {
        // Merge options
        $options = array_merge([
            'author' => null,
            'direction' => 'next',
            'end_date' => date('Y-m-d H:i:s'),
            'start_date' => null
        ], (array) $options);

        // Convert dates to UTC
        if ($options['start_date'])
	    $start_date = self::localToUtc($options['start_date']);
        if ($options['end_date'])
	    $end_date = self::localToUtc($options['end_date']);

        // Build query
        $sql = '
            SELECT
                id, author,
		(SELECT slug FROM __posts WHERE id = __comments.post) AS post,
		(SELECT slug FROM __users WHERE id = __comments.author) AS author_slug,
                comment, pub_date
            FROM __comments
            WHERE 1 = 1
        ';

        if ($options['author'])
	    $sql .= 'AND author = (SELECT id FROM __users WHERE slug = :author)';
        if ($options['start_date'])
	    $sql .= ' AND pub_date >= :start_date';
        if ($options['end_date'])
	    $sql .= ' AND pub_date <= :end_date';

        // Determine direction
        $sort = $options['direction'] === 'next' ? 'ASC' : 'DESC';
        $compare = $options['direction'] === 'next' ? '>=' : '<=';

        $sql .= '
            AND id != :id
            AND CONCAT(pub_date, id) ' . $compare . ' (
                SELECT CONCAT(pub_date, id)
                FROM __comments
                WHERE id = :id
            )
            ORDER BY pub_date ' . $sort . '
            LIMIT 1
        ';

        try {
            $st = self::$database->prepare($sql);
            $st->bindParam(':id', $id);
            if ($options['author'])
		$st->bindParam(':author', $options['author']);
            if ($options['start_date'])
		$st->bindParam(':start_date', $start_date);
            if ($options['end_date'])
		$st->bindParam(':end_date', $end_date);
            $st->execute();
            $post = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$post) return false;
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        $comment = self::normalize($comment);

        return $comment;
    }

    // Gets multiple comments. Returns an array of comments on success, false if not found. 
    // If $pagination is specified, it will be populated with pagination data generated by
    // Postleaf::paginate().
    public static function getMany($options = null, &$pagination = null) {
        // Merge options with defaults
        $options = array_merge([
            'post' => null,
            'end_date' => date('Y-m-d H:i:s'),
            'items_per_page' => 10,
            'page' => 1,
            'start_date' => null
        ], (array) $options);

        // Convert dates to UTC
        if ($options['start_date'])
	    $start_date = self::localToUtc($options['start_date']);
        if ($options['end_date'])
	    $end_date = self::localToUtc($options['end_date']);

//SELECT postleaf_comments.id, comment, pub_date, author, slug as author_slug, name, avatar
//FROM postleaf_users LEFT JOIN postleaf_comments
//ON postleaf_users.id = postleaf_comments.author
//WHERE 1 = 1 AND post = 6;

 	// Generate select SQL
        $select_sql = '
            SELECT
                __comments.id, comment, pub_date, author, slug AS author_slug, name, avatar
        FROM __users
	LEFT JOIN __comments
	ON __users.id = __comments.author';

        // Generate where SQL
        $where_sql = ' WHERE 1 = 1';
        if ($options['start_date'])
	    $where_sql .= ' AND pub_date >= :start_date';
        if ($options['end_date'])
	    $where_sql .= ' AND pub_date <= :end_date';
        if ($options['post'])
	    $where_sql .= ' AND post = :post';

        // Generate order SQL
	$order_sql = ' ORDER BY pub_date, id';

        // Generate limit SQL
        $limit_sql = ' LIMIT :offset, :count';

        // Assemble count query to determine total matching posts
        $count_sql = "SELECT COUNT(*) FROM __comments $where_sql";

        // Assemble data query to fetch comments
        $data_sql = "$select_sql $where_sql $order_sql $limit_sql";

        // Run the count query
        try {
            // Get count of all matching rows
            $st = self::$database->prepare($count_sql);
            if ($options['start_date'])
		$st->bindParam(':start_date', $start_date);
            if ($options['end_date'])
		$st->bindParam(':end_date', $end_date);
            if ($options['post'])
		$st->bindParam(':post', $options['post']);
            if ($options['name'])
		$st->bindParam(':name', $options['name']);
            $st->execute();
            $total_items = (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }

        // Generate pagination
        $pagination = self::paginate(
            $total_items,
            $options['items_per_page'],
            $options['page']
        );
        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        // Run the data query
        try {
            // Get matching rows
            $st = self::$database->prepare($data_sql);
            $st->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $st->bindParam(':count', $count, \PDO::PARAM_INT);
            if ($options['start_date'])
		$st->bindParam(':start_date', $start_date);
            if ($options['end_date'])
		$st->bindParam(':end_date', $end_date);
            if ($options['post'])
		$st->bindParam(':post', $options['post']);
            $st->execute();
            $comments = $st->fetchAll(\PDO::FETCH_ASSOC);
        } catch(\PDOException $e) {
            return false;
        }

        // Normalize fields
        foreach($comments as $key => $value) {
            $comments[$key] = self::normalize($value);
        }

        return $comments;
    }

    // Renders a comment
    public static function render($slug, $page = 1) {
        // Get the post identified by $slug
	$post = Post::get($slug);
	if (!$post) return false;

        // Get (a block of) the post's comments
        $comments = self::getMany([
            'post' => $post['id'],
            'page' => $page,
            'items_per_page' => Setting::get('posts_per_page')
        ], $pagination);

	//return $comments;
	
        // Make sure the requested page exists
        if($page > $pagination['total_pages']) return false;

        // Add previous/next links to pagination
        $pagination['next_page_url'] = $pagination['next_page'] ?
            self::url("comments/$slug/page", $pagination['next_page']) : null;
        $pagination['previous_page_url'] = $pagination['previous_page'] ?
            self::url("comments/$slug/page", $pagination['previous_page']) : null;

        // Render it
        return Renderer::render([
            'template' => Theme::getPath('comments.hbs'),
            'data' => [
                'post' => $post,
                'comments' => $comments,
                'pagination' => $pagination
            ],
            'special_vars' => [
                'meta' => [
                    'title'=> !empty($post['meta_title']) ? $post['meta_title'] : $post['title'],
                    'description' => !empty($post['meta_description']) ?
                        $post['meta_description'] :
                        self::getChars(strip_tags($post['content']), 160),
                    // JSON linked data (schema.org)
                    'ld_json' => [
                        '@context' => 'https://schema.org',
                        '@type' => 'Series',
                        'publisher' => [
                            '@type' => 'Organization',
                            'name' => Setting::get('title'),
                            'logo' => !empty(Setting::get('logo')) ?
                                parent::url(Setting::get('logo')) : null
                        ],
			'url' => self::url($slug, $page),
                        'image' => empty($post['image']) ? null : parent::url($post['image']),
                    ],
                    // Open Graph
                    'open_graph' => [
                        'og:type' => 'article',
                        'og:site_name' => Setting::get('title'),
                        'og:title' => !empty($post['meta_title']) ? $post['meta_title'] : $post['title'],
			'og:description' => !empty($post['meta_description']) ?
			    $post['meta_description'] :
			    self::getChars(strip_tags($post['content']), 160),
                        'og:url' => self::url($slug, $page),
                        'og:image' => empty($post['image']) ? '' : parent::url($post['image']),
                        'article:published_time' => $post['page'] ?
                            null : self::strftime('%FT%TZ', strtotime($post['pub_date'])),
                        'article:modified_time' => $post['page'] ?
                            null : self::strftime('%FT%TZ', strtotime($post['pub_date'])),
                        'article:tag' => $post['page'] ?
                            null : implode(', ', (array) $post['tags'])
                    ],
                    // Twitter Card
                    'twitter_card' => [
                        'twitter:card' => !empty($post['image']) ?
                            'summary_large_image' :
                            'summary',
                        'twitter:site' => !empty(Setting::get('twitter')) ?
                            '@' . Setting::get('twitter') : null,
                        'twitter:title' => !empty($post['meta_title']) ?
                            $post['meta_title'] :
                            $post['title'],
                        'twitter:description' => self::getWords(strip_tags($post['content']), 50),
                        'twitter:creator' => !empty($author['twitter']) ?
                            '@' . $author['twitter'] : null,
                        'twitter:url' => self::url($post['slug']),
                        'twitter:image' => !empty($post['image']) ?
                            parent::url($post['image']) :
                            null,
                        'twitter:label1' => !$post['page'] ?
                            'Written by' : null,
                        'twitter:data1' => !$post['page'] ?
                            $author['name'] : null,
                        'twitter:label2' => !$post['page'] ?
                            'Tagged with' : null,
                        'twitter:data2' => !$post['page'] ?
                            implode(', ', (array) $post['tags']) : null
                    ]
                ]
            ],
            'helpers' => ['url', 'utility', 'theme']
        ]);
    }

    // Updates a comment
    public static function update($id, $properties) {
        // Get the comment
        $comment = self::get($id);
        if (!$comment) {
            throw new \Exception('Comment not found.', self::COMMENT_NOT_FOUND);
        }
	if (!Session::isRole(['owner', 'admin', 'editor']) &&
	    $comment['author'] != Session::user('id'))
            throw new \Exception('Invalid ID.', self::INVALID_ID);

        // Merge options
        $comment = array_merge($comment, (array) $properties);

        // Parse publish date format and convert to UTC
        $comment['pub_date'] = self::localToUtc(self::parseDate($comment['pub_date']));

        // Empty content not allowed
        if (empty($comment['comment']))
            throw new \Exception('Empty content.', self::NO_CONTENT);

        try {
            // Update the comment
            $st = self::$database->prepare('
                UPDATE __comments SET
                    author = :author,
                    comment = :comment,
                    pub_date = :pub_date
                WHERE id = :id
            ');
            $st->bindParam(':author', $comment['author']);
            $st->bindParam(':comment', $comment['comment']);
            $st->bindParam(':pub_date', $comment['pub_date']);
            $st->bindParam(':id', $id);
            $st->execute();
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }

    // Deletes a comment
    public static function delete($id) {
        // Get the comment
        $comment = self::get($id);
        if (!$comment) {
            throw new \Exception('Comment not found: ' . $id, self::COMMENT_NOT_FOUND);
        }
	if (!Session::isRole(['owner', 'admin', 'editor']) &&
	    $comment['author'] != Session::user('id'))
            throw new \Exception('Invalid ID.', self::INVALID_ID);

        // Delete the comment
        try {
            // Delete the comment
            $st = self::$database->prepare('DELETE FROM __comments WHERE id = :id');
            $st->bindParam(':id', $id);
            $st->execute();
            return $st->rowCount() > 0;
        } catch(\PDOException $e) {
            throw new \Exception('Database error: ' . $e->getMessage());
        }
    }
}
