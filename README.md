RestAPI 插件（兼容 WordPress REST API）

为 Typecho 提供与 WordPress REST API 结构兼容的只读/部分写入接口，方便现有依赖 `wp-json/wp/v2` 的前端或客户端无痛接入。

特性
- 兼容路径与结构：`/wp-json`、`/wp-json/wp/v2`、`/wp-json/wp/v2/*`
- 内容资源：posts、pages、categories、tags
- 站点设置：`/wp-json/wp/v2/settings`（读取 RestAPI 插件内的设置项：头部代码、页脚代码、备案号）
- 友情链接：`/wp-json/wp/v2/links`（读 Links 插件数据表，缺失时返回空数组）
- 评论：`/wp-json/wp/v2/comments`（GET 列表、POST 提交）
- 用户：`/wp-json/wp/v2/users`、`/wp-json/wp/v2/users/{uid}`
- 分页兼容：返回 `X-WP-Total`、`X-WP-TotalPages` 并通过 `Access-Control-Expose-Headers` 暴露
- CORS：默认允许任何来源跨域（含预检 OPTIONS）

安装与启用
1. 将本插件目录放置于 `usr/plugins/RestAPI`。
2. 后台启用插件，并在设置中根据需要填写：
   - 头部代码（`header_code`）
   - 页脚代码（`footer_code`）
   - 备案号（`icp`）

路由与端点
- 根发现：
  - `GET /wp-json`、`GET /wp-json/`
  - 返回 `namespaces: ["wp/v2"]` 与部分路由清单
- 命名空间：
  - `GET /wp-json/wp/v2`、`GET /wp-json/wp/v2/`
  - 列出可用集合（posts/pages/categories/tags/settings/links/comments/users）
- 文章与页面：
  - `GET /wp-json/wp/v2/posts`
  - `GET /wp-json/wp/v2/posts/{cid}`
  - `GET /wp-json/wp/v2/pages`
  - `GET /wp-json/wp/v2/pages/{cid}`
  - 过滤参数：`page`, `per_page`, `search`, `slug`, `categories`, `tags`, `_embed`
  - 字段说明：
    - `excerpt.rendered` 仅取自文章自定义字段 `summary`
    - `content.rendered` 会移除 `<!--markdown-->` 标记
    - `categories`、`tags` 由关系表解析
- 分类与标签：
  - `GET /wp-json/wp/v2/categories`
  - `GET /wp-json/wp/v2/categories/{mid}`
  - `GET /wp-json/wp/v2/tags`
  - `GET /wp-json/wp/v2/tags/{mid}`
- 设置：
  - `GET /wp-json/wp/v2/settings`
  - 返回：`title`, `description`, `siteurl`, `restapi_header_code`, `restapi_footer_code`, `restapi_icp`
- 友情链接：
  - `GET /wp-json/wp/v2/links`
  - 过滤：`sort`, `state`, `page`, `per_page`
  - 字段：`id`, `name`, `url`, `sort`, `email`, `avatar`, `description`, `user`, `state`, `order`
  - 当 Links 插件数据表缺失时返回 `[]`
- 评论：
  - `GET /wp-json/wp/v2/comments`
    - 过滤：`post`, `parent`, `status`（`approve/approved`、`hold/waiting/pending`、`spam`、`any`）、`orderby`（`date`/`id`）、`order`（`asc`/`desc`）、`page`, `per_page`
  - `POST /wp-json/wp/v2/comments`
    - 提交字段：`post`, `author_name`, `author_email`, `author_url`, `content`, `parent`
    - 行为：创建 `waiting`（待审核）评论，返回 201；读取默认仅返回 `approved`
- 用户：
  - `GET /wp-json/wp/v2/users`（支持 `page`, `per_page`, `search`）
  - `GET /wp-json/wp/v2/users/{uid}`
  - 字段：`id`, `name`, `slug`, `url`, `description`, `link`, `avatar_urls(24/48/96)`, `meta`

示例
- 列出文章：
  - `GET https://example.com/wp-json/wp/v2/posts?page=1&per_page=10&_embed=1`
- 指定 slug 获取文章：
  - `GET https://example.com/wp-json/wp/v2/posts?slug=hello-world`
- 获取友情链接：
  - `GET https://example.com/wp-json/wp/v2/links?sort=friend&state=1&page=1&per_page=50`
- 获取评论（按时间升序）：
  - `GET https://example.com/wp-json/wp/v2/comments?post=4&per_page=100&orderby=date&order=asc`
- 提交评论：
  - `POST https://example.com/wp-json/wp/v2/comments`
    - Body（`application/x-www-form-urlencoded` 或 `application/json`）：
      - `post=4&author_name=Alice&author_email=alice@example.com&author_url=&content=Nice!&parent=0`

CORS
- 默认设置为允许任何来源：`Access-Control-Allow-Origin: *`
- 支持 `GET, POST, OPTIONS`，并处理预检请求返回 `204`
- 如需限制域名或启用凭证模式，可在插件内扩展配置

兼容性与限制
- 以只读为主；写入目前仅支持评论创建。若需更多写操作（媒体、用户、文章），需设计鉴权方案（如 Token/JWT）。
- Permalink 链接通过 Typecho 路由生成，基本兼容主题和固定链接设置。
- `excerpt` 仅取自定义字段 `summary`；若内容中存在 `<!--markdown-->`，会在输出中移除。

安全建议
- 评论接口已做基础校验（邮箱格式等），但未内置验证码与防水墙。请结合站点需求使用其他防护方案。
- 若开放更多写接口，请务必使用安全的鉴权方式。

致谢与扩展
- 参考 WordPress REST API 结构与字段命名，尽量减少对客户端的改动成本。
- 欢迎提出需求（如 users/me、包含/排除过滤、更多资源集合）以持续提升兼容度。

