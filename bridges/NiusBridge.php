<?php

class NiusBridge extends XPathAbstract
{
    const NAME = 'Nius';
    const URI = 'https://www.nius.de/news';
    const DESCRIPTION = 'Die Stimme der Mehrheit';
    const MAINTAINER = 'Niehztog';
    //const PARAMETERS = array();
    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = 'https://www.nius.de/news';
    const XPATH_EXPRESSION_ITEM = './/div[contains(@class, "compact-story") or contains(@class, "regular-story")]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/h2[@class="title"]//node()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/h2[@class="title"]//node()';
    const XPATH_EXPRESSION_ITEM_URI = './/a[1]/@href';

    const XPATH_EXPR_AUTHOR_PART1 = 'normalize-space(.//span[@class="author"]/text()[1])';
    const XPATH_EXPR_AUTHOR_PART2 = 'normalize-space(.//span[@class="author"]/text()[2])';
    const XPATH_EXPRESSION_ITEM_AUTHOR = 'substring-after(concat(' . self::XPATH_EXPR_AUTHOR_PART1 . ', " ", ' . self::XPATH_EXPR_AUTHOR_PART2 . '), " ")';

    //const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/td[3]';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img[1]/@src';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = './/div[@class="subtitle"]/text()';
    const SETTING_FIX_ENCODING = false;

    protected function cleanMediaUrl($mediaUrl)
    {
        $result = preg_match('~https:\/\/www\.nius\.de\/_next\/image\?url=(.*)\?~', $mediaUrl, $matches);
        return $result ? $matches[1] : $mediaUrl;
    }
}
