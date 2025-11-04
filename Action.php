<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

class RestAPI_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $options;
    private $db;

    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->options = $this->widget('Widget_Options');
        $this->db = Typecho_Db::get();
        // Set permissive CORS and handle preflight
        $this->cors();
        if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
            $this->response->setStatus(204);
            exit;
        }
    }

    private function cors()
    {
        header('Access-Control-Allow-Origin: *');
        header('Vary: Origin');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    private function json($data, $status = 200)
    {
        $this->response->setStatus($status);
        $this->response->setContentType('application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function get_pagination()
    {
        $page = max(1, (int)$this->request->get('page', 1));
        $per = min(100, max(1, (int)$this->request->get('per_page', 10)));
        $offset = ($page - 1) * $per;
        return [$page, $per, $offset];
    }

    public function root()
    {
        $base = rtrim($this->options->siteUrl, '/');
        $this->json([
            'name' => $this->options->title,
            'description' => $this->options->description,
            'url' => $base,
            'home' => $base,
            'namespaces' => ['wp/v2'],
            'routes' => [
                '/wp/v2' => ['namespace' => 'wp/v2', 'methods' => ['GET']],
                '/wp/v2/posts' => ['namespace' => 'wp/v2', 'methods' => ['GET']],
                '/wp/v2/pages' => ['namespace' => 'wp/v2', 'methods' => ['GET']],
                '/wp/v2/categories' => ['namespace' => 'wp/v2', 'methods' => ['GET']],
                '/wp/v2/tags' => ['namespace' => 'wp/v2', 'methods' => ['GET']],
            ],
        ]);
    }

    public function wpv2()
    {
        $base = rtrim($this->options->siteUrl, '/');
        $this->json([
            'name' => 'wp/v2',
            'routes' => [
                '/wp/v2/posts' => ['methods' => ['GET']],
                '/wp/v2/pages' => ['methods' => ['GET']],
                '/wp/v2/categories' => ['methods' => ['GET']],
                '/wp/v2/tags' => ['methods' => ['GET']],
                '/wp/v2/settings' => ['methods' => ['GET']],
                '/wp/v2/links' => ['methods' => ['GET']],
                '/wp/v2/comments' => ['methods' => ['GET','POST']],
                '/wp/v2/users' => ['methods' => ['GET']],
                '/wp/v2/users/(?P<id>[\\d]+)' => ['methods' => ['GET']],
            ],
            '_links' => [
                'collection' => [['href' => $base . '/wp-json/wp/v2']],
            ],
        ]);
    }

    public function settings()
    {
        // read plugin config from RestAPI
        $cfg = Typecho_Widget::widget('Widget_Options')->plugin('RestAPI');
        $header = isset($cfg->header_code) ? (string)$cfg->header_code : '';
        $footer = isset($cfg->footer_code) ? (string)$cfg->footer_code : '';
        $icp = isset($cfg->icp) ? (string)$cfg->icp : '';

        $this->json([
            'title' => $this->options->title,
            'description' => $this->options->description,
            'siteurl' => rtrim($this->options->siteUrl, '/'),
            'restapi_header_code' => $header,
            'restapi_footer_code' => $footer,
            'restapi_icp' => $icp,
        ]);
    }

    public function links()
    {
        $db = $this->db;
        $prefix = $db->getPrefix();
        // Check existence of links table
        try {
            $select = $db->select()
                ->from($prefix . 'links')
                ->order('`order`', Typecho_Db::SORT_ASC);
            // Optional filters
            $sort = trim((string)$this->request->get('sort', ''));
            if ($sort !== '') {
                $select->where('sort = ?', $sort);
            }
            $state = $this->request->get('state');
            if ($state !== null && $state !== '') {
                $select->where('state = ?', (int)$state);
            }
            list($page, $per, $offset) = $this->get_pagination();
            $select->limit($per, $offset);

            $rows = $db->fetchAll($select);
            $count = $db->fetchObject(
                $db->select(['COUNT(1)' => 'cnt'])->from($prefix . 'links')
            )->cnt;

            $items = [];
            foreach ($rows as $r) {
                $items[] = [
                    'id' => (int)$r['lid'],
                    'name' => $r['name'],
                    'url' => $r['url'],
                    'sort' => $r['sort'],
                    'email' => $r['email'],
                    'avatar' => $r['image'],
                    'description' => $r['description'],
                    'user' => $r['user'],
                    'state' => isset($r['state']) ? (int)$r['state'] : 1,
                    'order' => isset($r['order']) ? (int)$r['order'] : 0,
                ];
            }

            header('X-WP-Total: ' . (int)$count);
            header('X-WP-TotalPages: ' . (int)ceil($count / $per));
            header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');
            $this->json($items);
        } catch (Exception $e) {
            // Table missing or other issue → return empty array per requirement
            $this->json([]);
        }
    }

    private function build_comment_row($row)
    {
        $content = $row['text'];
        // resolve post/page link
        $post = $this->db->fetchRow(
            $this->db->select()->from('table.contents')->where('cid = ?', $row['cid'])->limit(1)
        );
        $permalink = $post ? Typecho_Router::url($post['type'] === 'page' ? 'page' : 'post', $post, $this->options->siteUrl) : '';

        return [
            'id' => (int)$row['coid'],
            'post' => (int)$row['cid'],
            'parent' => (int)$row['parent'],
            'author_name' => $row['author'],
            'author_email' => $row['mail'],
            'author_url' => $row['url'],
            'date' => date('c', $row['created']),
            'date_gmt' => gmdate('c', $row['created']),
            'content' => ['rendered' => $content],
            'link' => $permalink ? ($permalink . '#comment-' . (int)$row['coid']) : '',
            'type' => 'comment',
            'status' => $row['status'],
            'meta' => (object)[],
        ];
    }

    public function comments()
    {
        if ($this->request->isPost()) {
            return $this->create_comment();
        }

        list($page, $per, $offset) = $this->get_pagination();
        $select = $this->db->select()->from('table.comments')
            ->limit($per, $offset);

        // status filter (WP: approve/hold/spam/any)
        $statusParam = strtolower(trim((string)$this->request->get('status', 'approved')));
        $statusMap = ['approve' => 'approved', 'approved' => 'approved', 'hold' => 'waiting', 'pending' => 'waiting', 'waiting' => 'waiting', 'spam' => 'spam'];
        $mappedStatus = isset($statusMap[$statusParam]) ? $statusMap[$statusParam] : $statusParam;
        if ($mappedStatus !== '' && $mappedStatus !== 'any' && $mappedStatus !== 'all') {
            $select->where('status = ?', $mappedStatus);
        }

        // filter by post id
        $postId = (int)$this->request->get('post', 0);
        if ($postId > 0) {
            $select->where('cid = ?', $postId);
        }
        // filter by parent
        $parent = $this->request->get('parent');
        if ($parent !== null && $parent !== '') {
            $select->where('parent = ?', (int)$parent);
        }

        // orderby/order
        $orderby = strtolower(trim((string)$this->request->get('orderby', 'date')));
        $order = strtolower(trim((string)$this->request->get('order', 'desc')));
        $orderCol = ($orderby === 'id') ? 'coid' : 'created';
        $orderDir = ($order === 'asc') ? Typecho_Db::SORT_ASC : Typecho_Db::SORT_DESC;
        $select->order($orderCol, $orderDir);

        $rows = $this->db->fetchAll($select);

        // count with same filters
        $countSelect = $this->db->select(['COUNT(1)' => 'cnt'])->from('table.comments');
        if ($mappedStatus !== '' && $mappedStatus !== 'any' && $mappedStatus !== 'all') {
            $countSelect->where('status = ?', $mappedStatus);
        }
        if ($postId > 0) {
            $countSelect->where('cid = ?', $postId);
        }
        if ($parent !== null && $parent !== '') {
            $countSelect->where('parent = ?', (int)$parent);
        }
        $count = $this->db->fetchObject($countSelect)->cnt;

        $items = [];
        foreach ($rows as $r) $items[] = $this->build_comment_row($r);

        header('X-WP-Total: ' . (int)$count);
        header('X-WP-TotalPages: ' . (int)ceil($count / $per));
        header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');
        $this->json($items);
    }

    private function create_comment()
    {
        // Expected fields per WP: post, author_name, author_email, author_url, content, parent
        $postId = (int)$this->request->get('post');
        $content = trim((string)$this->request->get('content'));
        $author = trim((string)$this->request->get('author_name'));
        $email = trim((string)$this->request->get('author_email'));
        $url = trim((string)$this->request->get('author_url'));
        $parent = (int)$this->request->get('parent', 0);

        if ($postId <= 0 || $content === '' || $author === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['code' => 'invalid', 'message' => 'Invalid parameters'], 400);
        }

        // Verify post exists and is open to comments
        $post = $this->db->fetchRow($this->db->select()->from('table.contents')
            ->where('cid = ?', $postId)->limit(1));
        if (!$post || $post['status'] !== 'publish') {
            $this->json(['code' => 'not_found', 'message' => 'Post not found'], 404);
        }

        // Insert comment (hold for moderation by default consistent with Typecho settings)
        $time = time();
        $ip = $this->request->getIp();
        $agent = $this->request->getAgent();
        $row = [
            'cid' => $postId,
            'created' => $time,
            'author' => $author,
            'authorId' => 0,
            'ownerId' => (int)$post['authorId'],
            'mail' => $email,
            'url' => $url,
            'ip' => $ip,
            'agent' => $agent,
            'text' => $content,
            'type' => 'comment',
            'status' => 'waiting',
            'parent' => $parent,
        ];
        $this->db->query($this->db->insert('table.comments')->rows($row));
        $coid = $this->db->getAdapter()->lastInsertId();

        // Update comments count for the post
        $this->db->query(
            $this->db->update('table.contents')
                ->rows(['commentsNum' => (int)$post['commentsNum'] + 1])
                ->where('cid = ?', $postId)
        );

        // Return created comment (as pending)
        $created = $row;
        $created['coid'] = $coid;
        $this->json($this->build_comment_row($created), 201);
    }

    public function users()
    {
        list($page, $per, $offset) = $this->get_pagination();
        $select = $this->db->select()->from('table.users')
            ->order('uid', Typecho_Db::SORT_ASC)
            ->limit($per, $offset);

        $search = trim((string)$this->request->get('search', ''));
        if ($search !== '') {
            $kw = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $select->where('(name LIKE ? OR screenName LIKE ? OR mail LIKE ?)', $kw, $kw, $kw);
        }

        $rows = $this->db->fetchAll($select);
        $countSelect = $this->db->select(['COUNT(1)' => 'cnt'])->from('table.users');
        if ($search !== '') {
            $kw = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $countSelect->where('(name LIKE ? OR screenName LIKE ? OR mail LIKE ?)', $kw, $kw, $kw);
        }
        $count = $this->db->fetchObject($countSelect)->cnt;

        $items = [];
        foreach ($rows as $u) {
            $items[] = [
                'id' => (int)$u['uid'],
                'name' => $u['screenName'],
                'slug' => $u['name'],
                'url' => $u['url'],
                'description' => '',
                'link' => $u['url'],
                'avatar_urls' => [
                    '24' => $this->gravatar($u['mail'], 24),
                    '48' => $this->gravatar($u['mail'], 48),
                    '96' => $this->gravatar($u['mail'], 96),
                ],
                'meta' => (object)[],
            ];
        }

        header('X-WP-Total: ' . (int)$count);
        header('X-WP-TotalPages: ' . (int)ceil($count / $per));
        header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');
        $this->json($items);
    }

    private function gravatar($mail, $size)
    {
        $hash = md5(strtolower(trim((string)$mail)));
        return 'https://www.gravatar.com/avatar/' . $hash . '?s=' . (int)$size . '&d=identicon';
    }

    public function user()
    {
        $uid = (int)$this->request->get('uid');
        $u = $this->db->fetchRow($this->db->select()->from('table.users')->where('uid = ?', $uid)->limit(1));
        if (!$u) {
            $this->json(['code' => 'not_found', 'message' => 'User not found'], 404);
        }
        $this->json([
            'id' => (int)$u['uid'],
            'name' => $u['screenName'],
            'slug' => $u['name'],
            'url' => $u['url'],
            'description' => isset($u['screenName']) ? $u['screenName'] : '',
            'link' => $u['url'],
            'avatar_urls' => [
                '24' => $this->gravatar($u['mail'], 24),
                '48' => $this->gravatar($u['mail'], 48),
                '96' => $this->gravatar($u['mail'], 96),
            ],
            'meta' => (object)[],
        ]);
    }

    private function build_post_row($row)
    {
        $routeName = ($row['type'] === 'page') ? 'page' : 'post';
        $permalink = Typecho_Router::url($routeName, $row, $this->options->siteUrl);
        $author = $this->db->fetchRow($this->db->select()
            ->from('table.users')->where('uid = ?', $row['authorId'])->limit(1));
        $summary = $this->get_custom_field((int)$row['cid'], 'summary');
        $excerpt = (string)$summary;
        // terms
        list($catIds, $tagIds) = $this->get_post_terms((int)$row['cid']);

        $item = [
            'id' => (int)$row['cid'],
            'date' => date('c', $row['created']),
            'date_gmt' => gmdate('c', $row['created']),
            'modified' => date('c', $row['modified']),
            'modified_gmt' => gmdate('c', $row['modified']),
            'slug' => $row['slug'],
            'status' => $row['status'] === 'publish' ? 'publish' : $row['status'],
            'type' => $row['type'],
            'link' => $permalink,
            'guid' => ['rendered' => $permalink],
            'title' => ['rendered' => $row['title']],
            'content' => [
                'rendered' => str_replace('<!--markdown-->', '', $row['text']),
                'protected' => false,
            ],
            'excerpt' => [
                'rendered' => $excerpt,
                'protected' => false,
            ],
            'author' => (int)$row['authorId'],
            'featured_media' => 0,
            'comment_status' => 'open',
            'ping_status' => 'open',
            'sticky' => false,
            'template' => '',
            'format' => 'standard',
            'meta' => (object)[],
            'categories' => $catIds,
            'tags' => $tagIds,
        ];

        if ($this->request->get('_embed')) {
            $item['_embedded'] = [
                'author' => [[
                    'id' => (int)$row['authorId'],
                    'name' => $author ? $author['screenName'] : '',
                    'url' => $author ? $author['url'] : '',
                    'description' => $author ? $author['screenName'] : '',
                ]],
            ];
        }

        return $item;
    }

    private function get_custom_field($cid, $name)
    {
        $f = $this->db->fetchRow(
            $this->db->select()->from('table.fields')
                ->where('cid = ?', $cid)
                ->where('name = ?', $name)
                ->limit(1)
        );
        if (!$f) return null;
        switch ($f['type']) {
            case 'str':
            default:
                return isset($f['str_value']) ? (string)$f['str_value'] : '';
            case 'int':
                return isset($f['int_value']) ? (string)$f['int_value'] : '';
            case 'float':
                return isset($f['float_value']) ? (string)$f['float_value'] : '';
        }
    }

    private function get_post_terms($cid)
    {
        $rels = $this->db->fetchAll(
            $this->db->select('table.metas.mid', 'table.metas.type')
                ->from('table.relationships')
                ->join('table.metas', 'table.relationships.mid = table.metas.mid')
                ->where('table.relationships.cid = ?', $cid)
        );
        $cats = [];
        $tags = [];
        foreach ($rels as $r) {
            if ($r['type'] === 'category') $cats[] = (int)$r['mid'];
            if ($r['type'] === 'tag') $tags[] = (int)$r['mid'];
        }
        return [$cats, $tags];
    }

    private function list_contents($type)
    {
        list($page, $per, $offset) = $this->get_pagination();
        $select = $this->db->select()->from('table.contents')
            ->where('type = ?', $type)
            ->where('status = ?', 'publish')
            ->where('created <= ?', time())
            ->order('created', Typecho_Db::SORT_DESC)
            ->limit($per, $offset);
        // search
        $search = trim((string)$this->request->get('search', ''));
        if ($search !== '') {
            $kw = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $select->where("(title LIKE ? OR text LIKE ?)", $kw, $kw);
        }
        // filter by slug (WordPress often uses ?slug=foo)
        $slug = trim((string)$this->request->get('slug', ''));
        if ($slug !== '') {
            $select->where('slug = ?', $slug);
        }

        // taxonomy filters (ids separated by comma)
        $catParam = trim((string)$this->request->get('categories', ''));
        $tagParam = trim((string)$this->request->get('tags', ''));
        if ($catParam !== '' || $tagParam !== '') {
            $select->join('table.relationships', 'table.relationships.cid = table.contents.cid')
                ->join('table.metas', 'table.metas.mid = table.relationships.mid');
            if ($catParam !== '') {
                $ids = array_filter(array_map('intval', explode(',', $catParam)));
                if ($ids) {
                    $select->where('table.metas.type = ?', 'category')
                        ->where('table.metas.mid IN ?', $ids);
                }
            }
            if ($tagParam !== '') {
                $ids = array_filter(array_map('intval', explode(',', $tagParam)));
                if ($ids) {
                    $select->where('table.metas.type = ?', 'tag')
                        ->where('table.metas.mid IN ?', $ids);
                }
            }
        }

        $rows = $this->db->fetchAll($select);
        // total count (rough, without filters apart from search/tax for simplicity)
        $countSelect = $this->db->select(['COUNT(1)' => 'cnt'])->from('table.contents')
            ->where('type = ?', $type)
            ->where('status = ?', 'publish')
            ->where('created <= ?', time());
        if ($search !== '') {
            $kw = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $search) . '%';
            $countSelect->where("(title LIKE ? OR text LIKE ?)", $kw, $kw);
        }
        if ($slug !== '') {
            $countSelect->where('slug = ?', $slug);
        }
        if ($catParam !== '' || $tagParam !== '') {
            $countSelect->join('table.relationships', 'table.relationships.cid = table.contents.cid')
                ->join('table.metas', 'table.metas.mid = table.relationships.mid');
            if ($catParam !== '') {
                $ids = array_filter(array_map('intval', explode(',', $catParam)));
                if ($ids) {
                    $countSelect->where('table.metas.type = ?', 'category')
                        ->where('table.metas.mid IN ?', $ids);
                }
            }
            if ($tagParam !== '') {
                $ids = array_filter(array_map('intval', explode(',', $tagParam)));
                if ($ids) {
                    $countSelect->where('table.metas.type = ?', 'tag')
                        ->where('table.metas.mid IN ?', $ids);
                }
            }
        }
        $count = $this->db->fetchObject($countSelect)->cnt;

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->build_post_row($row);
        }

        // WP style headers
        header('X-WP-Total: ' . (int)$count);
        header('X-WP-TotalPages: ' . (int)ceil($count / $per));
        header('Access-Control-Expose-Headers: X-WP-Total, X-WP-TotalPages');

        $this->json($items);
    }

    private function get_content_item($cid, $type)
    {
        $row = $this->db->fetchRow($this->db->select()->from('table.contents')
            ->where('cid = ?', $cid)->limit(1));
        if (!$row || $row['type'] !== $type || $row['status'] !== 'publish') {
            $this->json(['code' => 'not_found', 'message' => 'Not found'], 404);
        }
        $this->json($this->build_post_row($row));
    }

    public function posts() { $this->list_contents('post'); }
    public function post() { $this->get_content_item((int)$this->request->get('cid'), 'post'); }
    public function pages() { $this->list_contents('page'); }
    public function page() { $this->get_content_item((int)$this->request->get('cid'), 'page'); }

    private function build_term_row($row, $taxonomy)
    {
        return [
            'id' => (int)$row['mid'],
            'count' => (int)$row['count'],
            'description' => $row['description'],
            'link' => Typecho_Router::url($taxonomy, $row, $this->options->siteUrl),
            'name' => $row['name'],
            'slug' => $row['slug'],
            'taxonomy' => $taxonomy === 'category' ? 'category' : 'post_tag',
            'parent' => (int)$row['parent'],
            'meta' => (object)[],
        ];
    }

    private function list_terms($taxonomy)
    {
        list($page, $per, $offset) = $this->get_pagination();
        $rows = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('type = ?', $taxonomy)
                ->order('mid', Typecho_Db::SORT_ASC)
                ->limit($per, $offset)
        );
        $count = $this->db->fetchObject(
            $this->db->select(['COUNT(1)' => 'cnt'])->from('table.metas')
                ->where('type = ?', $taxonomy)
        )->cnt;

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->build_term_row($row, $taxonomy === 'category' ? 'category' : 'tag');
        }

        header('X-WP-Total: ' . (int)$count);
        header('X-WP-TotalPages: ' . (int)ceil($count / $per));
        $this->json($items);
    }

    private function get_term_item($mid, $taxonomy)
    {
        $row = $this->db->fetchRow($this->db->select()->from('table.metas')
            ->where('mid = ?', $mid)->limit(1));
        if (!$row || $row['type'] !== $taxonomy) {
            $this->json(['code' => 'not_found', 'message' => 'Not found'], 404);
        }
        $this->json($this->build_term_row($row, $taxonomy === 'category' ? 'category' : 'tag'));
    }

    public function categories() { $this->list_terms('category'); }
    public function category() { $this->get_term_item((int)$this->request->get('mid'), 'category'); }
    public function tags() { $this->list_terms('tag'); }
    public function tag() { $this->get_term_item((int)$this->request->get('mid'), 'tag'); }

    public function action() {}
}
