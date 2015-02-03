<?php // encoding: utf-8
/*
	Copyright 2014  qTranslate Team  (email : qTranslateTeam@gmail.com )

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* qTranslate-X Hooks */

function qtranxf_localeForCurrentLanguage($locale){
	global $q_config;
	if( !isset($q_config['language']) ) return $locale;
	// try to figure out the correct locale
	$lang = $q_config['language'];
	$locale_lang=$q_config['locale'][$lang];
	$locale = array();
	$locale[] = $locale_lang.".utf8";
	$locale[] = $locale_lang."@euro";
	$locale[] = $locale_lang;
	$locale[] = $q_config['windows_locale'][$lang];
	$locale[] = $lang;

	// return the correct locale and most importantly set it (wordpress doesn't, which is bad)
	// only set LC_TIME as everything else doesn't seem to work with windows
	setlocale(LC_TIME, $locale);

	return $locale_lang;
}

function qtranxf_useCurrentLanguageIfNotFoundShowEmpty($content) {
	global $q_config;
	return qtranxf_use($q_config['language'], $content, false, true);
}

function qtranxf_useCurrentLanguageIfNotFoundShowAvailable($content) {
	global $q_config;
	return qtranxf_use($q_config['language'], $content, true);
}

function qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($content) {
	global $q_config;
	return qtranxf_use($q_config['language'], $content, false);
}

function qtranxf_useDefaultLanguage($content) {
	global $q_config;
	return qtranxf_use($q_config['default_language'], $content, false);
}

function qtranxf_excludePages($pages) {
	global $wpdb, $q_config;
	static $exclude = 0;
	if(!$q_config['hide_untranslated']) return $pages;
	if(is_array($exclude)) return array_merge($exclude, $pages);
		$query = "SELECT id FROM $wpdb->posts WHERE post_type = 'page' AND post_status = 'publish' AND NOT ($wpdb->posts.post_content LIKE '%<!--:".qtranxf_getLanguage()."-->%')" ;
	$hide_pages = $wpdb->get_results($query);
	$exclude = array();
	foreach($hide_pages as $page) {
		$exclude[] = $page->id;
	}
	return array_merge($exclude, $pages);
}

function qtranxf_versionLocale() {
	return 'en_US';
}

function qtranxf_useRawTitle($title, $raw_title = '', $context = 'save') {
	if($raw_title=='') $raw_title = $title;
	if('save'==$context) {
		$raw_title = qtranxf_useDefaultLanguage($raw_title);
		$title = remove_accents($raw_title);
	}
	return $title;
}

function qtranxf_checkCanonical($redirect_url, $requested_url) {
	// fix canonical conflicts with language urls
	if(qtranxf_convertURL($redirect_url)==qtranxf_convertURL($requested_url))
		return false;
	return $redirect_url;
}

/*
//no longer needed since we adjusted home_url()
function qtranxf_fixSearchForm($form) {
	$form = preg_replace('#action="[^"]*"#','action="'.trailingslashit(qtranxf_convertURL(get_home_url())).'"',$form);
	return $form;
}
*/

function qtranxf_fixAdminBar($wp_admin_bar) {
	global $wp_admin_bar;
	if(!isset($wp_admin_bar)) return;
	$nodes=$wp_admin_bar->get_nodes();
	//qtranxf_dbg_echo('$nodes:',$nodes);
	if(!isset($nodes)) return;//sometimes $nodes is NULL
	foreach($nodes as $node) {
		$wp_admin_bar->add_node(qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage($node));
	}
}

//function qtranxf_gettext($translated_text, $text, $domain) {
function qtranxf_gettext($translated_text) {
	//same as qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage
	global $q_config;
	if(!isset($q_config['language'])){
		//qtranxf_dbg_log('$q_config[language] is not set:',debug_backtrace());
		return $translated_text;
	}
	return qtranxf_use($q_config['language'], $translated_text, false);
}

//function qtranxf_gettext_with_context($translated_text, $text, $context, $domain) {
function qtranxf_gettext_with_context($translated_text) {
	//same as qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage
	global $q_config;
	if(!isset($q_config['language'])){
		//qtranxf_dbg_log('$q_config[language] is not set:',debug_backtrace());
		return $translated_text;
	}
	return qtranxf_use($q_config['language'], $translated_text, false);
}

// Hooks for Plugin compatibility
/*
//no need any more since $_SERVER['REQUEST_URI'] is no longer modified.
function qtranxf_supercache_dir($uri) {
	global $q_config;
	if(isset($q_config['url_info']['original_url'])) {
		$uri = $q_config['url_info']['original_url'];
	} else {
		$uri = $_SERVER['REQUEST_URI'];
	}
	$uri = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', str_replace( '/index.php', '/', str_replace( '..', '', preg_replace("/(\?.*)?$/", '', $uri ) ) ) );
	$uri = str_replace( '\\', '', $uri );
	$uri = strtolower(preg_replace('/:.*$/', '',  $_SERVER["HTTP_HOST"])) . $uri; // To avoid XSS attacks
	return $uri;
}
add_filter('supercache_dir', 'qtranxf_supercache_dir',0);
*/
/*
//it was a test
function qtranxf_wpseo_replacements($replacements){
	foreach($replacements as $key => $s) {
		$replacements[$key]=__($s);
	}
	return $replacements;
}
*/

// Hooks defined differently in admin and frontend
add_filter( 'wp_trim_words', 'qtranxf_trim_words', 0, 4);


// Hooks (Actions)
// add_action('category_edit_form', 'qtranxf_modifyTermFormFor');
// //add_action('post_tag_edit_form', 'qtranxf_modifyTermFormFor');
// add_action('link_category_edit_form', 'qtranxf_modifyTermFormFor');
// add_action('category_add_form', 'qtranxf_modifyTermFormFor');
// add_action('post_tag_add_form', 'qtranxf_modifyTermFormFor');
// add_action('link_category_add_form', 'qtranxf_modifyTermFormFor');
add_action('plugins_loaded', 'qtranxf_init_language', 2);//user is not authenticated yet
add_action('init', 'qtranxf_init');//user is authenticated
add_action('widgets_init', 'qtranxf_widget_init');

// Hooks (execution time critical filters) 
add_filter('gettext', 'qtranxf_gettext',0);
add_filter('gettext_with_context', 'qtranxf_gettext_with_context',0);
//add_filter('gettext', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
//add_filter('gettext_with_context', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);

add_filter('sanitize_title', 'qtranxf_useRawTitle',0, 3);

add_filter('comment_moderation_subject', 'qtranxf_useDefaultLanguage',0);
add_filter('comment_moderation_text', 'qtranxf_useDefaultLanguage',0);

add_filter('the_content', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 0);
add_filter('the_excerpt', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 0);
add_filter('the_excerpt_rss', 'qtranxf_useCurrentLanguageIfNotFoundShowAvailable', 0);
add_filter('get_comment_date', 'qtranxf_dateFromCommentForCurrentLanguage',0,2);
add_filter('get_comment_time', 'qtranxf_timeFromCommentForCurrentLanguage',0,4);
add_filter('get_post_modified_time', 'qtranxf_timeModifiedFromPostForCurrentLanguage',0,3);
add_filter('get_the_time', 'qtranxf_timeFromPostForCurrentLanguage',0,3);
add_filter('get_the_date', 'qtranxf_dateFromPostForCurrentLanguage',0,2);
add_filter('locale', 'qtranxf_localeForCurrentLanguage',99);
//add_filter('the_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0);//WP: fires for display purposes only
add_filter('post_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage', 0);
add_filter('tag_rows', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
//add_filter('list_cats', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('wp_list_categories', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
//add_filter('wp_dropdown_cats', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('wp_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('single_post_title', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('bloginfo', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('get_others_drafts', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('get_bloginfo_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('get_wp_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('wp_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_title_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_content_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('get_pages', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
//add_filter('category_description', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('bloginfo_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_category_rss', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
//add_filter('wp_generate_tag_cloud', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('term_links-post_tag', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('link_name', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('link_description', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);
add_filter('the_author', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage',0);

add_filter('pre_option_rss_language', 'qtranxf_getLanguage',0);

add_filter('_wp_post_revision_field_post_title', 'qtranxf_showAllSeperated', 0);
add_filter('_wp_post_revision_field_post_content', 'qtranxf_showAllSeperated', 0);
add_filter('_wp_post_revision_field_post_excerpt', 'qtranxf_showAllSeperated', 0);

// // Hooks (execution time non-critical filters) 
add_filter('author_feed_link', 'qtranxf_convertURL');
add_filter('author_link', 'qtranxf_convertURL');
add_filter('author_feed_link', 'qtranxf_convertURL');
add_filter('day_link', 'qtranxf_convertURL');
add_filter('get_comment_author_url_link', 'qtranxf_convertURL');
add_filter('month_link', 'qtranxf_convertURL');
add_filter('page_link', 'qtranxf_convertURL');
add_filter('post_link', 'qtranxf_convertURL');
add_filter('year_link', 'qtranxf_convertURL');
add_filter('category_feed_link', 'qtranxf_convertURL');
add_filter('category_link', 'qtranxf_convertURL');
add_filter('tag_link', 'qtranxf_convertURL');
add_filter('term_link', 'qtranxf_convertURL');
add_filter('the_permalink', 'qtranxf_convertURL');
add_filter('feed_link', 'qtranxf_convertURL');
add_filter('post_comments_feed_link', 'qtranxf_convertURL');
add_filter('tag_feed_link', 'qtranxf_convertURL');
add_filter('get_pagenum_link', 'qtranxf_convertURL');

//add_filter('get_search_form', 'qtranxf_fixSearchForm', 10, 1);//no longer needed since we adjusted home_url()
add_filter('wp_list_pages_excludes', 'qtranxf_excludePages');

add_filter('comment_notification_text', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
add_filter('comment_notification_headers', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');
add_filter('comment_notification_subject', 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage');

// add_filter('the_editor', 'qtranxf_modifyRichEditor');
//add_filter('admin_footer', 'qtranxf_modifyExcerpt');
add_filter('bloginfo_url', 'qtranxf_convertBlogInfoURL',10,2);
add_filter('core_version_check_locale', 'qtranxf_versionLocale');

add_filter('redirect_canonical', 'qtranxf_checkCanonical', 10, 2);
?>
