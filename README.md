# api_json

[Redaxo 4.x](https://redaxo.org) *addon* to provide a JSON api for article content.

## Requirements

* Redaxo 4.x (Only tested with Redaxo 4.7.2!)
* Mod Rewrite access.

## Installation

1. *Copy* all files into  a sub directory "*api_json*" under redaxo/include/addons/ of your Redaxo 4.x installation. Then start "Install" on the "Addons" page in the backend.

2. Add a *rewrite rule* to your .htaccess or apache config. Just convert all links starting with 'api/' to a param like this: `RewriteRule ^api[/]?(.*)$ index.php?api=$1`. You can test the api without a rewrite rule. Type e.g. `mydomain.tld/index.php?api=articles/4`.

## Usage

The structure of JSON is similar to Redaxo article structure but simplified.
But you can easily get titles or full body contents of several sub-articles at once.

You specify a valid article id or get root categories.

You reequest rendered article content by appending `/content`/

Try `mydomain.tld/api`. It provides an entry point and suggestions. More sensible examples can be seen in the response itself.
