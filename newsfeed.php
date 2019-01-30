<?php
// NewsFeed.php is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// NewsFeed.php is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License.
// If not, see <http://www.gnu.org/licenses/>.

/**
 * Purpose: Converts the specified XML Atom news feed into <li> HTML entries.
 * Based on RSS to HTML (http://www.systutorials.com/136102/a-php-function-for-fetching-rss-feed-and-outputing-feed-items-as-html/) by Eric Z Ma
 *
 * @package   Newsfeed.php
 * @author    Michael Milette <www.github.com/michael-milette/atom-newsfeed>
 * @copyright © 2019 TNG Consulting Inc. <www.tngconsulting.ca>
 * @version   1.0 - 2019-01-07
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Optional URL parameters with default values:
 *   url=https%3A%2F%2Fwww.canada.ca%2Fen%2Fstatus-women.atom.xml%3Fdisable%3Dcache   // Note: URL must be encoded.
 *   max=3       // Maximum number of entries to be displayed. 0 = All.
 *   desc=0      // 1 = Show, 0 = Hide description/summary.
 *   timeout=600 // Default number of seconds for cache to live (600 = 10 minutes).
 *   lang=en     // Default language for error messages - fr or en (default).
 *   words=0     // Maximum number of (HTML) words to be displayed per entry (0 = unlimited).
 * Example: <!--#include virtual="/includes/newsfeed.php?lang=en&max=50&url=https%3A%2F%2Fwww.canada.ca%2Fen%2Fstatus-women.atom.xml%3Fdisable%3Dcache"-->
 * Tips:
 *   - If you see brackets around the date, the news feed can't be retrieved and is being delivered from an expired cache.
 *   - You can reset the cache by deleting all of the files called /tmp/newsfeed-* .
 *   - If it is not working for you, set $debug = true, reload your page with newsfeed.php?timeout=-1 in the URL and view source code to see additional info.
 * Dependencies:
 *   - Requires "allow_url_fopen = On" in php.ini.
 * Language:    Has been tested with PHP versions 5.6 to 7.2.
 * Release history:
 *   - 1.0 - 2019-01-07 - Initial public release.
 *   - 1.1 - 2019-01-30 - Added option to debug, no longer saving/overwriting cache if source unavailable, will now display feed when clearing cache.
 */

// ----------------------------------------------
// Configuration
// ----------------------------------------------

$debug = false; // For development only - true : enabled, false: disabled. IMPORTANT: Be sure to disable in production.
if ($debug) {
    error_reporting(-1);
    ini_set('display_errors', 'On');
}

// Defaults if not specified in URL parameters. See documentation above.
// $url - Depends on language. See below.
$max = 3;
$desc = 0;
$timeout = 600;
$lang = 'en';
$words = 0;

// Configure language strings.
if (!empty($_GET['lang'])) {
    $lang =  in_array($_GET['lang'], array('fr', 'en')) ? $_GET['lang'] : 'en';
}
switch ($lang) {
    case 'fr': // French.
        $url = 'https://www.canada.ca/fr/nouvelles/fils-nouvelles/nouvelles-nationales.atom.xml'; // Note: Parameter must be encoded when specified in a URL.
        $string['unavailable'] = 'Ce fil de nouvelles n\'est pas disponible actuellement. Visiter <a href="https://www.canada.ca/fr/nouvelles.html">Canada.ca</a>.';
        $string['date'] = 'Y-m-d H:i';
        break;
    default:   // English.
        $lang = 'en';
        $url = 'https://www.canada.ca/en/news/web-feeds/national-news.atom.xml'; // Note: Parameter must be encoded when specified in a URL.
        $string['unavailable'] = 'This news feed is not currently available. Visit <a href="https://www.canada.ca/en/news.html">Canada.ca</a>.';
        $string['date'] = 'Y-m-d H:i';
}
$string['unavailable'] = '<li style="list-style-type: none;">' . $string['unavailable'] . '</li>';

// Set timezone.

$tz = 'America/Toronto'; // Your time zone.
date_default_timezone_set($tz);

// Sanitize URL parameters.

$feed_url = (!empty($_GET['url'])) ? $_GET['url'] : $url;
$max_items = (!empty($_GET['max'])) ? $_GET['max'] : $max;
$show_summary = (!empty($_GET['desc'])) ? 1 : $desc;
$cache_timeout = (!empty($_GET['timeout'])) ? $_GET['timeout'] : $timeout;
$max_words = (!empty($_GET['words'])) ? $_GET['words'] : $words;

$feed_url = filter_var($feed_url, FILTER_SANITIZE_URL);
$max_items = filter_var($max_items, FILTER_SANITIZE_NUMBER_INT);
$show_summary = filter_var($show_summary, FILTER_SANITIZE_NUMBER_INT);
$cache_timeout = filter_var($cache_timeout, FILTER_SANITIZE_NUMBER_INT);
$max_words = filter_var($max_words, FILTER_SANITIZE_NUMBER_INT);

// Retrieve the Atom XML feed and turn it into HTML.

echo get_atom_feed_as_html($feed_url, $max_items, $show_summary, $cache_timeout, $max_words, $lang);

/**
 * get_atom_feed_as_html()
 * Function to retrieve XML atom feed and convert it into HTML list items.
 *
 * @param string    $feed_url URL of the XML feed to process.
 * @param int       $max_items (optional) Maximum number of news items. Default is 10.
 * @param bool      $show_summary (optional) Show or hide description/summary. Default is Show (true).
 * @param int       $cache_timeout (optional) Newsfeed cache timeout. Default is 7200 seconds (2 hours).
 * @param int       $max_words (optional) Maximum number of words to display in summary. Default is 0 (all words).
 * @param string    $lang = 'en' (optional) Language for default strings. Default is en (English).
 * @param bool      $show_date (optional) Show or hide date. Default is Show (true).
 * @param string    $cache_prefix (optional) Prefix for cache files created in /tmp folder. Default is '/tmp/newsfeed-'.
 * @return string   Content as a series of HTML list items (<li></li>).
 */
function get_atom_feed_as_html($feed_url, $max_items = 10, $show_summary = true, $cache_timeout = 7200, $max_words = 0, $lang = 'en', $show_date = true, $cache_prefix = '/tmp/newsfeed-') {

    global $string, $debug;

    // Get feeds and parse items.

    $xml = new DOMDocument();
    $cache_file = $cache_prefix . md5($feed_url);

    // If caching is enabled, exists and has not yet expired, load the data from Cache.
    $loaded = false;
    $live = true;  // Will be false if feed can't be loaded and we are using expired cache.

    // If cache timeout (timeout parameter) is -1, reset / delete all cache files.
    if ($cache_timeout == -1) {
        foreach(glob($cache_prefix . '*') as $f) {
            unlink($f);
        }
        echo ('cache-reset');
    }

    // Load XML content from a cache file or from from the web.

    if ($cache_timeout > 0 && is_file($cache_file) && (filemtime($cache_file) + $cache_timeout > time())) {
        
        // Load the cached XML feed if it exists and has not yet expired.
        $loaded = (@$xml->load($cache_file) !== false);
        if (!$loaded) {
            // Failed to load from Cache.
            $err = libxml_get_last_error();
            $errcode = $debug ? "$err->message (1)" : '';
            // Try the live feed.
            $loaded = (@$xml->load($feed_url) !== false);
            $err = libxml_get_last_error();
            $errcode = $debug ? ", $err->message (2)" : '';
        }
        
    } else {
        
        // Load the XML feed from the web.
        $loaded = (@$xml->load($feed_url) !== false);
        if (!$loaded) {
            // Failed to load the live feed.
            $err = libxml_get_last_error();
            $errcode = $debug ? "$err->message (3)" : '';
            if (is_file($cache_file)) {
                // If the cached version is still available, use it even if it has expired.
                $loaded = (@$xml->load($cache_file) !== false);
                $err = libxml_get_last_error();
                $errcode = $debug ? ",$err->message (4)" : '';
                $live = $false;
            } else {
                $errcode .= $debug && !$loaded ? ', Cache unavailable (5)' : '';
            }
        }

        // Only save cache to file if we managed to load the feed.
        if ($cache_timeout > 0 && $loaded) {
            $xml->save($cache_file);
        }
    }

    // If failed to load, return unavailable message.
    if (!$loaded) {
        return $string['unavailable'] . ($debug ? '<span style="display:none;"> (Error: ' . $errcode . ')</span>' : '');
    }

    // Parse out XML data.
    $entries = array();
    foreach ($xml->getElementsByTagName('entry') as $node) {
        $max_items--;
        $item = array (
                'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
                'desc'  => $node->getElementsByTagName('summary')->item(0)->nodeValue,
                'link'  => $node->getElementsByTagName('link')->item(0)->getAttribute('href'),
                'date'  => $node->getElementsByTagName('updated')->item(0)->nodeValue,
        );
        $content = $node->getElementsByTagName('encoded'); // <content:encoded>
        if ($content->length > 0) {
            $item['content'] = $content->item(0)->nodeValue;
        }
        array_push($entries, $item);

        // Stop processing when we have either hit the specified max item limit.
        if (empty($max_items)) {
            break;
        }
    }

    $html = '';
    foreach ($entries as $entry) {
        $html .= '<li>';

        $title = str_replace(' & ', ' &amp; ', $entry['title']);
        $html .= '<a href="' . $entry['link'] . '" title="' . $title . '">' . $title . '</a><br>' . PHP_EOL;

        // Show date.
        if ($show_date) {
            $date = date($string['date'], strtotime($entry['date']));
            if ($live) {
                $html .= "<span class=\"small\">$date</span>" . PHP_EOL;
            } else {
                $html .= "<span class=\"small\">[$date]</span>" . PHP_EOL;
            }
        }

        // Show description/summary.
        if ($show_summary) {
            $summary = $entry['desc'];

            // Limit maximum number of words.
            if (!empty($max_words)) {
                $word_list = explode(' ', $summary);
                if ($max_words < count($word_list)) {
                    $summary = '';
                    $word_count = 0;
                    foreach($word_list as $word) {
                        $summary .= $word . ' ';
                        $word_count++;
                        if ($word_count == $max_words) {
                            break;
                        }
                    }
                    $summary = trim($summary) . '&hellip;';
                }
            }
            $html .= "<p>$summary</p>";
        }

        $html .= '</li>';
    }
    return $html;
}
