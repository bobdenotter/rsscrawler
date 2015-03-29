Bolt RSS Crawler
================

A RSS Crawler extension for the [Bolt CMS](http://www.bolt.cm). Grabs RSS feeds
from a remote source, and inserts them into your local database. Can be used to
to a one-time import, or to do periodical updates.

Instructions
------------

Add fastfeed/fastfeed to your Composer dependencies:

    php composer.phar require fastfeed/fastfeed

Edit the config file, and run it.

You should have a contenttype, that has the following structure:

```
feeditems:
    name: Feeditems
    singular_name: Feeditem
    description: "Crawled feed items. Do not change manually."
    fields:
        title:
            type: text
            class: large
            group: content
        slug:
            type: slug
            uses: title
        itemid:
            type: text
            variant: inline
        content:
            type: html
        raw:
            type: textarea
        source:
            type: text
            variant: inline
        author:
            type: text
            variant: inline
        image:
            type: text
            variant: inline
        sitetitle:
            type: text
            variant: inline
        sitesource:
            type: text
            variant: inline
    taxonomy: [ tags ]
```


Support
-------

Please use the issue tracker: [Github](http://github.com/bobdenotter/RSSCrawler/issues)