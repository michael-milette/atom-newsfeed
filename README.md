# rss-atom-newsfeed
Converts the specified XML RSS Atom news feed into &lt;li> HTML entries.

Package   Newsfeed.php

Author    Michael Milette <www.github.com/michael-milette/rss-atom-newsfeed>

Copyright © 2019 TNG Consulting Inc. <www.tngconsulting.ca>

Version   1.0 - 2019-01-07

License   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

## Apache SSI Example
    <!--#include virtual="/newsfeed.php?lang=en&max=50&url=https%3A%2F%2Fwww.yoursite.ca%2Fyourfeed.atom.xml"-->

## Tips
  - If you see brackets around the date, the news feed can't be retrieved and is being delivered from an expired cache.
  - You can reset the cache in the URL by setting timeout=-1.
  - You can manually reset the cache by deleting all of the files called /tmp/newsfeed-* .
  - If it is not working for you, set $debug = true, reload your page with newsfeed.php?timeout=-1 in the URL and view source code to see additional info.
  
## Configuration
Look for the configuration section near the top of the newsfeed.php file. You will be able to configure the defaults for:
- $max - maximum number of entries to be displayed.
- desc - Enable/disable the description / summary.
- timeout - Default number of seconds for cache to live before it will be refreshed.
- lang - language. Currently supports English and French but can be easily expanded to include others.
- words - Maximum number of words (including HTML tags) to be displayed per entry.

Most of these can be overridden through optional parameters in the URL.

## Optional URL parameters 
(with default values)
  IMPORTANT: URL must be encoded in the next line:
  url=https%3A%2F%2Fwww.yoursite.com%2Fyourfeed.atom.xml   
  max=3       // Maximum number of entries to be displayed. 0 = All.
  desc=0      // 1 = Show, 0 = Hide description/summary.
  timeout=600 // Default number of seconds for cache to live (600 = 10 minutes, 0 = no cache, -1 = purge cache).
  lang=en     // Default language for error messages - fr or en (default).
  words=0     // Maximum number of (HTML) words to be displayed per entry (0 = unlimited).

### DEBUG Instructions
Not working for you? Enable debugging.

1. set: debug = true
2. Execute the PHP script. No worries, errors won't be visible.
3. View source to see error messages.

Don't forget to set debug=false when you are done troubleshooting.

## Dependencies
  - Requires "allow_url_fopen = On" in php.ini.

## Language:
Has been tested with PHP versions 5.6 to 7.2.

Release history:
  - 1.0 - 2019-01-07 - Initial public release.
  - 1.1 - 2019-01-30 - Added option to debug, no longer saving/overwriting cache if source unavailable, will now display feed when clearing cache.

Inspired by RSS to HTML(http://www.systutorials.com/136102/a-php-function-for-fetching-rss-feed-and-outputing-feed-items-as-html/) by Eric Z Ma
