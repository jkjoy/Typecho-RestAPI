RestAPI Plugin (WordPress REST API compatible)

Provides a WordPress-compatible REST API structure for Typecho, making it easy to use clients or front-ends that depend on `wp-json/wp/v2`.

Features
- WordPress-style routes: `/wp-json`, `/wp-json/wp/v2`, `/wp-json/wp/v2/*`
- Content resources: posts, pages, categories, tags
- Site settings: `GET /wp-json/wp/v2/settings` (reads plugin options: header code, footer code, ICP)
- Links: `GET /wp-json/wp/v2/links` (reads Links plugin table; returns empty array if missing)
- Comments: `GET /wp-json/wp/v2/comments`, `POST /wp-json/wp/v2/comments`
- Users: `GET /wp-json/wp/v2/users`, `GET /wp-json/wp/v2/users/{uid}`
- Pagination headers: `X-WP-Total`, `X-WP-TotalPages` (exposed via `Access-Control-Expose-Headers`)
- CORS: permissive by default (incl. OPTIONS preflight)

Install & Activate
1. Place this plugin under `usr/plugins/RestAPI`.
2. Activate in Typecho admin and configure:
   - Header code (`header_code`)
   - Footer code (`footer_code`)
   - ICP record (`icp`)

Routes & Endpoints
- Discovery
  - `GET /wp-json` or `GET /wp-json/`
  - Returns `namespaces: ["wp/v2"]` and a subset of routes
- Namespace
  - `GET /wp-json/wp/v2` or `GET /wp-json/wp/v2/`
  - Lists available collections (posts/pages/categories/tags/settings/links/comments/users)
- Posts & Pages
  - `GET /wp-json/wp/v2/posts`, `GET /wp-json/wp/v2/posts/{cid}`
  - `GET /wp-json/wp/v2/pages`, `GET /wp-json/wp/v2/pages/{cid}`
  - Filters: `page`, `per_page`, `search`, `slug`, `categories`, `tags`, `_embed`
  - Fields:
    - `excerpt.rendered` is from custom field `summary` only
    - `content.rendered` removes the `<!--markdown-->` marker
    - `categories` and `tags` are resolved via relationship tables
- Categories & Tags
  - `GET /wp-json/wp/v2/categories`, `GET /wp-json/wp/v2/categories/{mid}`
  - `GET /wp-json/wp/v2/tags`, `GET /wp-json/wp/v2/tags/{mid}`
- Settings
  - `GET /wp-json/wp/v2/settings`
  - Returns: `title`, `description`, `siteurl`, `restapi_header_code`, `restapi_footer_code`, `restapi_icp`
- Links
  - `GET /wp-json/wp/v2/links`
  - Filters: `sort`, `state`, `page`, `per_page`
  - Fields: `id`, `name`, `url`, `sort`, `email`, `avatar`, `description`, `user`, `state`, `order`
  - Returns `[]` when Links table is missing
- Comments
  - `GET /wp-json/wp/v2/comments`
    - Filters: `post`, `parent`, `status` (`approve/approved`, `hold/waiting/pending`, `spam`, `any`), `orderby` (`date`/`id`), `order` (`asc`/`desc`), `page`, `per_page`
  - `POST /wp-json/wp/v2/comments`
    - Body fields: `post`, `author_name`, `author_email`, `author_url`, `content`, `parent`
    - Behavior: creates comment with `waiting` status; reads default to `approved`
- Users
  - `GET /wp-json/wp/v2/users` (supports `page`, `per_page`, `search`)
  - `GET /wp-json/wp/v2/users/{uid}`
  - Fields: `id`, `name`, `slug`, `url`, `description`, `link`, `avatar_urls(24/48/96)`, `meta`

Examples
- List posts
  - `GET https://example.com/wp-json/wp/v2/posts?page=1&per_page=10&_embed=1`
- Get a post by slug
  - `GET https://example.com/wp-json/wp/v2/posts?slug=hello-world`
- Get links
  - `GET https://example.com/wp-json/wp/v2/links?sort=friend&state=1&page=1&per_page=50`
- Get comments ascending by date
  - `GET https://example.com/wp-json/wp/v2/comments?post=4&per_page=100&orderby=date&order=asc`
- Submit a comment (curl)
  - `curl -X POST https://example.com/wp-json/wp/v2/comments -H "Content-Type: application/x-www-form-urlencoded" -d "post=4&author_name=Alice&author_email=alice@example.com&content=Nice!&parent=0"`

JavaScript fetch examples
- Fetch posts
  - `fetch('https://example.com/wp-json/wp/v2/posts?page=1&per_page=10&_embed=1').then(r => r.json()).then(console.log)`
- Create a comment
  - `fetch('https://example.com/wp-json/wp/v2/comments', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ post: 4, author_name: 'Alice', author_email: 'alice@example.com', content: 'Nice!', parent: 0 }) }).then(r => r.json()).then(console.log)`

CORS
- Default: `Access-Control-Allow-Origin: *`
- Supports `GET, POST, OPTIONS` and auto-handles preflight with 204
- You can restrict origins or enable credentials by extending the plugin

Compatibility & Limits
- Mostly read-only; write support currently only for comments. For more write ops (media/users/posts), design an auth method (Token/JWT).
- Permalink links are generated via Typecho router and should work with your theme/permalink settings.
- `excerpt` comes only from custom field `summary`; `<!--markdown-->` is removed from `content.rendered`.

Security
- Basic validation for comments (email, required fields); captcha/antispam is not included.
- Use secure authentication if exposing more write endpoints.

Acknowledgments & Extensibility
- Aligns with WordPress REST API naming and shape to minimize client changes.
- Feel free to request additions (users/me, include/exclude filters, more resources).

