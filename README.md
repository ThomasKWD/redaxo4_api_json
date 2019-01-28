# api_json

## Abstract

[Redaxo 4.x](https://redaxo.org) *addon* to provide a _read only_ JSON api for category and article content.

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
* `$_SERVER['QUERY_STRING']` containing the query part of the URI -- everything from the "?" _after rewrite rule transformation_ (e. g. "api=categories/4")

It also relies on a field of the global var `$REX` of Redaxo:

* `$REX['SERVER']` containing the complete domain and path of the project without leading protocol.

## Installation

1. *Copy* all files into  a sub directory "*api_json*" under redaxo/include/addons/ of your Redaxo 4.x installation. Then start "Install" on the "Addons" page in the backend.

2. Add a *rewrite rule* to your ".htaccess" file or apache config. Just convert all links starting with 'api/' to a param e. g.: `RewriteRule ^api[/]?(.*)$ index.php?api=$1`. This is recommanded to provide an easy syntax.

## Usage

The structure of JSON is similar to [Redaxo](https://redaxo.org) structure but simplified.
You can easily get titles or full body contents of 1 level of sub categories. You specify a valid category ID or get root categories.

You also can request rendered article content by appending `/articles/contents`. This explicit keyword is for  minimizing respond data load because often just a list of article titles or links are needed.

By adding a number after `/contents/` you select a ctype ID. Otherwise always ctype 1 is used.

Just try your Redaxo project URI with `/api`. It provides an entry point and suggestions. More usage examples and explanations can be found in the response itself.

### Syntax Examples:

`yourdomain.tld/api` entry point, currently provides root categories.

`yourdomain.tld/api/categories/1` returns category and its sub categories ("1" must be the id of an existing categoriy with status "online").

`yourdomain.tld/api/categories/3/articles` returns category with ID == 3 and all articles in it and in its sub categories.

`yourdomain.tld/api/categories/3/articles/content/2` returns "article content" of ctype 2 of all articles found.

You can use the api without a rewrite rule. Type e.g. `yourdomain.tld/index.php?api=categories/4`.

## Response

Always returns a body in JSON. On HTTP erros the body contains explanations.

Assuming public content header `Access-Control-Allow-Origin: *` is always sent.

Only articles and categories with status "online" are returned.

Currently no "metainfo" data is included except titles and "online from" and "online to" of articles.

"createdate" and "updatedate" contain UNIX time stamps.

Example response made from "https://www.kuehne-webdienste.de/api/categories/3/0/articles":

```
{
	request: "api/categories/3/0/articles",
	id: "3",
	name: "Referenzen",
	createdate: "1280159918",
	updatedate: "1544285487",
	link: "https://www.kuehne-webdienste.de/api/categories/3/0",
	clang_id: 0,
	categories: [
		{
			id: "12",
			name: "Shuri Ryu Berlin",
			createdate: "1404049179",
			updatedate: "1543838561",
			link: "https://www.kuehne-webdienste.de/api/categories/12/0/articles",
			articles: [
				{
					id: "12",
					name: "Shuri Ryu Berlin",
					is_start_article: true,
					createdate: "1404049179",
					updatedate: "1543838561",
					onlinefrom: "",
					onlineto: "1570053600",
				}
			]
		},
		{
			id: "7",
			name: "Tangará Brasil",
			createdate: "1280159902",
			updatedate: "1486048701",
			link: "https://www.kuehne-webdienste.de/api/categories/7/0/articles",
			articles: [
				{
					id: "7",
					name: "Tangará Brasil",
					is_start_article: true,
					createdate: "1280159902",
					updatedate: "1486048701",
					onlinefrom: "",
					onlineto: ""
				}
			]
		},
		{
			id: "13",
			name: "Moldt Events",
			createdate: "1404049185",
			updatedate: "1410461900",
			link: "https://www.kuehne-webdienste.de/api/categories/13/0/articles",
			articles: [
				{
					id: "13",
					name: "Moldt Events",
					is_start_article: true,
					createdate: "1404049185",
					updatedate: "1410461900",
					onlinefrom: "",
					onlineto: ""
				}
			]
		}
		],
		articles: [
			{
				id: "3",
				name: "Referenzen, Auswahl",
				is_start_article: true,
				createdate: "1280159918",
				updatedate: "1544285487",
				onlinefrom: "",
				onlineto: ""
			}
	]
}
```
Note: This example has been copied from an formatter for better readability. The actual response **has** quoted field names and escaped slashes.
