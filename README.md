# api_json

## Abstract 

[Redaxo 4.x](https://redaxo.org) *addon* to provide a _read only_ JSON api for article content.

## Requirements

### Software 

* PHP 5.4
* Redaxo 4.x (Only tested with Redaxo 4.6.1 and 4.7.2!)
* Mod Rewrite access.
* Correctly set parameters.

### Configuration

The addon uses 3 fields of the PHP superglobal `$_SERVER' which must be existent and have the proper content:

* `$_SERVER['REQUEST_METHOD']` containing the http method e.g. 'GET' (case insensitive')
* `$_SERVER['REQUEST_SCHEME']` containing the protocol ("http://" or "https:")
* `$_SERVER['QUERY_STRING']` containing the protocol ("http://" or "https:")

It also relies on a field of the global var `$REX` of Redaxo:

* `$REX['SERVER']` containing the complete domain and path of the project including a trailing slash ("/"), e. g. "yourdomain.tld/"

## Installation

1. *Copy* all files into  a sub directory "*api_json*" under redaxo/include/addons/ of your Redaxo 4.x installation. Then start "Install" on the "Addons" page in the backend.

2. Add a *rewrite rule* to your .htaccess or apache config. Just convert all links starting with 'api/' to a param like this: `RewriteRule ^api[/]?(.*)$ index.php?api=$1`. You can test the api without a rewrite rule. Type e.g. `mydomain.tld/index.php?api=articles/4`.

## Usage

The structure of JSON is similar to Redaxo article structure but simplified.
But you can easily get titles or full body contents of several sub-articles at once. You specify a valid article id or can get root categories.
To avoid boilerplate code _no_ standard like "JSON:api" is used.

Only articles and categories with status `online` are included.

You also can request rendered article content by appending `/content`/. This explicit keyword is to minimize respond data load.

Try your Redaxo project URI with `/api`. It provides an entry point and suggestions. More usage examples and explanations can be found in the response itself.

Examples:

* `yourdomain.tld/api`
* `yourdomain.tld/api/articles/1` (`1` must be an existing article with status `online`
* `yourdomain.tld/api/articles/4/content` (`4` must be an existing article with status `online`

