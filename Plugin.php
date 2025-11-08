<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * WordPress-compatible REST API for Typecho
 * @version 1.0.3
 * @author jkjoy
 * @link https://www.imsun.org
 * @package RestAPI
 */
class RestAPI_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        // Root discovery: /wp-json and /wp-json/
        Helper::addRoute('restapi_root_noslash', '/wp-json', 'RestAPI_Action', 'root');
        Helper::addRoute('restapi_root', '/wp-json/', 'RestAPI_Action', 'root');
        Helper::addRoute('restapi_root_index_noslash', '/index.php/wp-json', 'RestAPI_Action', 'root');
        Helper::addRoute('restapi_root_index', '/index.php/wp-json/', 'RestAPI_Action', 'root');

        // Namespace index: /wp-json/wp/v2 and with trailing slash
        Helper::addRoute('restapi_wp_v2', '/wp-json/wp/v2', 'RestAPI_Action', 'wpv2');
        Helper::addRoute('restapi_wp_v2_index', '/index.php/wp-json/wp/v2', 'RestAPI_Action', 'wpv2');
        Helper::addRoute('restapi_wp_v2_slash', '/wp-json/wp/v2/', 'RestAPI_Action', 'wpv2');
        Helper::addRoute('restapi_wp_v2_slash_index', '/index.php/wp-json/wp/v2/', 'RestAPI_Action', 'wpv2');

        // Posts
        Helper::addRoute('restapi_posts', '/wp-json/wp/v2/posts', 'RestAPI_Action', 'posts');
        Helper::addRoute('restapi_posts_index', '/index.php/wp-json/wp/v2/posts', 'RestAPI_Action', 'posts');
        Helper::addRoute('restapi_posts_slash', '/wp-json/wp/v2/posts/', 'RestAPI_Action', 'posts');
        Helper::addRoute('restapi_posts_slash_index', '/index.php/wp-json/wp/v2/posts/', 'RestAPI_Action', 'posts');
        Helper::addRoute('restapi_post_item', '/wp-json/wp/v2/posts/[cid]', 'RestAPI_Action', 'post');
        Helper::addRoute('restapi_post_item_index', '/index.php/wp-json/wp/v2/posts/[cid]', 'RestAPI_Action', 'post');
        Helper::addRoute('restapi_post_item_slash', '/wp-json/wp/v2/posts/[cid]/', 'RestAPI_Action', 'post');
        Helper::addRoute('restapi_post_item_slash_index', '/index.php/wp-json/wp/v2/posts/[cid]/', 'RestAPI_Action', 'post');
        // Posts by tag slug
        Helper::addRoute('restapi_posts_by_tag', '/wp-json/wp/v2/posts/tag/[slug]', 'RestAPI_Action', 'posts_by_tag');
        Helper::addRoute('restapi_posts_by_tag_index', '/index.php/wp-json/wp/v2/posts/tag/[slug]', 'RestAPI_Action', 'posts_by_tag');
        Helper::addRoute('restapi_posts_by_tag_slash', '/wp-json/wp/v2/posts/tag/[slug]/', 'RestAPI_Action', 'posts_by_tag');
        Helper::addRoute('restapi_posts_by_tag_slash_index', '/index.php/wp-json/wp/v2/posts/tag/[slug]/', 'RestAPI_Action', 'posts_by_tag');
        // Posts by slug
        Helper::addRoute('restapi_post_by_slug', '/wp-json/wp/v2/posts/slug/[slug]', 'RestAPI_Action', 'post_by_slug');
        Helper::addRoute('restapi_post_by_slug_index', '/index.php/wp-json/wp/v2/posts/slug/[slug]', 'RestAPI_Action', 'post_by_slug');

        // Pages
        Helper::addRoute('restapi_pages', '/wp-json/wp/v2/pages', 'RestAPI_Action', 'pages');
        Helper::addRoute('restapi_pages_index', '/index.php/wp-json/wp/v2/pages', 'RestAPI_Action', 'pages');
        Helper::addRoute('restapi_pages_slash', '/wp-json/wp/v2/pages/', 'RestAPI_Action', 'pages');
        Helper::addRoute('restapi_pages_slash_index', '/index.php/wp-json/wp/v2/pages/', 'RestAPI_Action', 'pages');
        Helper::addRoute('restapi_page_item', '/wp-json/wp/v2/pages/[cid]', 'RestAPI_Action', 'page');
        Helper::addRoute('restapi_page_item_index', '/index.php/wp-json/wp/v2/pages/[cid]', 'RestAPI_Action', 'page');
        Helper::addRoute('restapi_page_item_slash', '/wp-json/wp/v2/pages/[cid]/', 'RestAPI_Action', 'page');
        Helper::addRoute('restapi_page_item_slash_index', '/index.php/wp-json/wp/v2/pages/[cid]/', 'RestAPI_Action', 'page');
        // Pages by slug
        Helper::addRoute('restapi_page_by_slug', '/wp-json/wp/v2/pages/slug/[slug]', 'RestAPI_Action', 'page_by_slug');
        Helper::addRoute('restapi_page_by_slug_index', '/index.php/wp-json/wp/v2/pages/slug/[slug]', 'RestAPI_Action', 'page_by_slug');

        // Categories
        Helper::addRoute('restapi_categories', '/wp-json/wp/v2/categories', 'RestAPI_Action', 'categories');
        Helper::addRoute('restapi_categories_index', '/index.php/wp-json/wp/v2/categories', 'RestAPI_Action', 'categories');
        Helper::addRoute('restapi_categories_slash', '/wp-json/wp/v2/categories/', 'RestAPI_Action', 'categories');
        Helper::addRoute('restapi_categories_slash_index', '/index.php/wp-json/wp/v2/categories/', 'RestAPI_Action', 'categories');
        Helper::addRoute('restapi_category_item', '/wp-json/wp/v2/categories/[mid]', 'RestAPI_Action', 'category');
        Helper::addRoute('restapi_category_item_index', '/index.php/wp-json/wp/v2/categories/[mid]', 'RestAPI_Action', 'category');
        Helper::addRoute('restapi_category_item_slash', '/wp-json/wp/v2/categories/[mid]/', 'RestAPI_Action', 'category');
        Helper::addRoute('restapi_category_item_slash_index', '/index.php/wp-json/wp/v2/categories/[mid]/', 'RestAPI_Action', 'category');

        // Tags
        Helper::addRoute('restapi_tags', '/wp-json/wp/v2/tags', 'RestAPI_Action', 'tags');
        Helper::addRoute('restapi_tags_index', '/index.php/wp-json/wp/v2/tags', 'RestAPI_Action', 'tags');
        Helper::addRoute('restapi_tags_slash', '/wp-json/wp/v2/tags/', 'RestAPI_Action', 'tags');
        Helper::addRoute('restapi_tags_slash_index', '/index.php/wp-json/wp/v2/tags/', 'RestAPI_Action', 'tags');
        Helper::addRoute('restapi_tag_item', '/wp-json/wp/v2/tags/[mid]', 'RestAPI_Action', 'tag');
        Helper::addRoute('restapi_tag_item_index', '/index.php/wp-json/wp/v2/tags/[mid]', 'RestAPI_Action', 'tag');
        Helper::addRoute('restapi_tag_item_slash', '/wp-json/wp/v2/tags/[mid]/', 'RestAPI_Action', 'tag');
        Helper::addRoute('restapi_tag_item_slash_index', '/index.php/wp-json/wp/v2/tags/[mid]/', 'RestAPI_Action', 'tag');

        // Settings (read-only in this plugin)
        Helper::addRoute('restapi_settings', '/wp-json/wp/v2/settings', 'RestAPI_Action', 'settings');
        Helper::addRoute('restapi_settings_index', '/index.php/wp-json/wp/v2/settings', 'RestAPI_Action', 'settings');
        Helper::addRoute('restapi_settings_slash', '/wp-json/wp/v2/settings/', 'RestAPI_Action', 'settings');
        Helper::addRoute('restapi_settings_slash_index', '/index.php/wp-json/wp/v2/settings/', 'RestAPI_Action', 'settings');

        // Links
        Helper::addRoute('restapi_links', '/wp-json/wp/v2/links', 'RestAPI_Action', 'links');
        Helper::addRoute('restapi_links_index', '/index.php/wp-json/wp/v2/links', 'RestAPI_Action', 'links');
        Helper::addRoute('restapi_links_slash', '/wp-json/wp/v2/links/', 'RestAPI_Action', 'links');
        Helper::addRoute('restapi_links_slash_index', '/index.php/wp-json/wp/v2/links/', 'RestAPI_Action', 'links');

        // Link Categories
        Helper::addRoute('restapi_link_categories', '/wp-json/wp/v2/link-categories', 'RestAPI_Action', 'linkCategories');
        Helper::addRoute('restapi_link_categories_index', '/index.php/wp-json/wp/v2/link-categories', 'RestAPI_Action', 'linkCategories');
        Helper::addRoute('restapi_link_categories_slash', '/wp-json/wp/v2/link-categories/', 'RestAPI_Action', 'linkCategories');
        Helper::addRoute('restapi_link_categories_slash_index', '/index.php/wp-json/wp/v2/link-categories/', 'RestAPI_Action', 'linkCategories');

        // Comments (GET list, POST create)
        Helper::addRoute('restapi_comments', '/wp-json/wp/v2/comments', 'RestAPI_Action', 'comments');
        Helper::addRoute('restapi_comments_index', '/index.php/wp-json/wp/v2/comments', 'RestAPI_Action', 'comments');
        Helper::addRoute('restapi_comments_slash', '/wp-json/wp/v2/comments/', 'RestAPI_Action', 'comments');
        Helper::addRoute('restapi_comments_slash_index', '/index.php/wp-json/wp/v2/comments/', 'RestAPI_Action', 'comments');

        // Users (GET list)
        Helper::addRoute('restapi_users', '/wp-json/wp/v2/users', 'RestAPI_Action', 'users');
        Helper::addRoute('restapi_users_index', '/index.php/wp-json/wp/v2/users', 'RestAPI_Action', 'users');
        Helper::addRoute('restapi_users_slash', '/wp-json/wp/v2/users/', 'RestAPI_Action', 'users');
        Helper::addRoute('restapi_users_slash_index', '/index.php/wp-json/wp/v2/users/', 'RestAPI_Action', 'users');

        // User item
        Helper::addRoute('restapi_user_item', '/wp-json/wp/v2/users/[uid]', 'RestAPI_Action', 'user');
        Helper::addRoute('restapi_user_item_index', '/index.php/wp-json/wp/v2/users/[uid]', 'RestAPI_Action', 'user');
        Helper::addRoute('restapi_user_item_slash', '/wp-json/wp/v2/users/[uid]/', 'RestAPI_Action', 'user');
        Helper::addRoute('restapi_user_item_slash_index', '/index.php/wp-json/wp/v2/users/[uid]/', 'RestAPI_Action', 'user');

        return 'RestAPI 插件已启用，提供基础 wp/v2 兼容接口';
    }

    public static function deactivate()
    {
        Helper::removeRoute('restapi_root');
        Helper::removeRoute('restapi_root_index');

        Helper::removeRoute('restapi_wp_v2');
        Helper::removeRoute('restapi_wp_v2_index');

        Helper::removeRoute('restapi_posts');
        Helper::removeRoute('restapi_posts_index');
        Helper::removeRoute('restapi_post_item');
        Helper::removeRoute('restapi_post_item_index');

        Helper::removeRoute('restapi_pages');
        Helper::removeRoute('restapi_pages_index');
        Helper::removeRoute('restapi_page_item');
        Helper::removeRoute('restapi_page_item_index');

        Helper::removeRoute('restapi_categories');
        Helper::removeRoute('restapi_categories_index');
        Helper::removeRoute('restapi_category_item');
        Helper::removeRoute('restapi_category_item_index');

        Helper::removeRoute('restapi_tags');
        Helper::removeRoute('restapi_tags_index');
        Helper::removeRoute('restapi_tag_item');
        Helper::removeRoute('restapi_tag_item_index');
    }

    public static function config(Typecho_Widget_Helper_Form $form) {
        $siteTitle = new Typecho_Widget_Helper_Form_Element_Text(
            'site_title', NULL, '', _t('网站标题'), _t('用于 REST API 返回的网站标题，留空则使用系统默认标题')
        );
        $siteDescription = new Typecho_Widget_Helper_Form_Element_Textarea(
            'site_description', NULL, '', _t('网站描述'), _t('用于 REST API 返回的网站描述，留空则使用系统默认描述')
        );
        $header = new Typecho_Widget_Helper_Form_Element_Textarea(
            'head_html', NULL, '', _t('头部HTML代码'), _t('输出在页面<head>或顶部区域的自定义代码')
        );
        $footer = new Typecho_Widget_Helper_Form_Element_Textarea(
            'site_footer_text', NULL, '', _t('页脚文本'), _t('输出在页面底部区域的自定义代码')
        );
        $icp = new Typecho_Widget_Helper_Form_Element_Text(
            'site_icp', NULL, '', _t('备案号'), _t('网站备案号')
        );
        $form->addInput($siteTitle);
        $form->addInput($siteDescription);
        $form->addInput($header);
        $form->addInput($footer);
        $form->addInput($icp);
    }
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
}
