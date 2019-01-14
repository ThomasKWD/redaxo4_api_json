# api_json

## Abstract

[Redaxo 4.x](https://redaxo.org) *addon* to provide a _read only_ JSON api for article content.

## Requirements

### Software

* PHP 5.4
* Redaxo 4.x (Only tested with Redaxo 4.6.1 and 4.7.2!)
* Mod Rewrite access (optional).
* Correctly set parameters.

### Configuration

The addon uses 3 fields of the PHP superglobal `$_SERVER` which must be existent and have the proper content.
Under a default apache + php configuration these should not be a problem - but you should know it:

* `$_SERVER['REQUEST_METHOD']` containing the http method e.g. 'GET', case insensitive -- this var is used indirectly by calling the Redaxo function `rex_request_method()`)
* `$_SERVER['REQUEST_SCHEME']` containing the protocol ("http" or "https")
* `$_SERVER['QUERY_STRING']` containing the query part of the URI -- everything from the "?" _after rewrite rule transformation_ (e. g. "api=articles/4")

It also relies on a field of the global var `$REX` of Redaxo:

* `$REX['SERVER']` containing the complete domain and path of the project without leading protocol.

## Installation

1. *Copy* all files into  a sub directory "*api_json*" under redaxo/include/addons/ of your Redaxo 4.x installation. Then start "Install" on the "Addons" page in the backend.

2. Add a *rewrite rule* to your .htaccess or apache config. Just convert all links starting with 'api/' to a param like this: `RewriteRule ^api[/]?(.*)$ index.php?api=$1`. This is recommanded to provide an easy syntax.

## Usage

The structure of JSON is similar to Redaxo article structure but simplified.
But you can easily get titles or full body contents of several sub-articles at once. You specify a valid article id or can get root categories.

Only articles and categories with status "online" are included.

You also can request rendered article content by appending `/content`. This explicit keyword is for  minimizing respond data load because often just a list of article links is needed.

Try your Redaxo project URI with `/api`. It provides an entry point and suggestions. More usage examples and explanations can be found in the response itself.

Examples:

* `yourdomain.tld/api`
* `yourdomain.tld/api/articles/1` ("1" must be the id of an existing article with status "online")
* `yourdomain.tld/api/articles/4/content` ("4" must be the id of an existing article with status "online")

You can use the api without a rewrite rule. Type e.g. "yourdomain.tld/index.php?api=articles/4".
