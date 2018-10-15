# Elasticsearch plugin for Craft CMS 3.x

Bring the power of Elasticsearch to you Craft 3 CMS project.

![Plugin screenshot](resources/img/plugin-screenshot.png)



## Requirements

This plugin requires **Craft CMS 3.0.0-RC1** or later.

In order to index data, you will need an **Elasticsearch 6.0** (or later) 
instance, with the Ingest attachment processor plugin activated.


## Installation

### The easy way

Just install the plugin from the Craft Plugin Store.

### Using Composer

  - Install with Composer from your project directory: `composer require la-hautes-societe/craft-elasticsearch`
  - In the Craft Control Panel, go to Settings → Plugins and click the **Install** button for Elasticsearch.
 

 
## Elasticsearch plugin Overview

Elasticsearch plugin will automatically index each entry on your site(s).

It will figure out the best Elasticsearch mapping for you based on your site(s)' language. 



## Configuring the Elasticsearch plugin

You can configure the Elasticsearch plugin from the Craft Control Panel (some settings only), of from the 
_config/elasticsearch.php_ file in your Craft installation (all settings). If a setting is defined both in the CP and in
the configuration file, the latter takes precedence.

The [src/config.php](./src/config.php), file is a configuration template to be copied to _config/elasticsearch.php_.


### In both the configuration file and the CP

  - `http_address` _(string)_: the hostname and port (colon-separated) used to connect to the Elasticsearch server
  - `auth_enabled` _(bool)_: a boolean indicating whether authentication in required on the Elasticsearch server
  - `auth_username` _(string)_: the username used to authenticate on the Elasticsearch server if it's protected by
    X-Pack Security. Useless if `auth_enabled` is set to `false`
  - `auth_password` _(string)_: the password used to authenticate on the Elasticsearch server if it's protected by
    X-Pack Security. Useless if `auth_enabled` is set to `false`
  - `highlight` _(array)_: the elasticsearch configuration used to highlight query results. Only `pre_tags` and 
    `post_tags` are configurable in the CP, advanced config must be done in the file. 
    For more options, refer to the [elasticsearch documentation][]
  - `blacklistedSections` _(int[])_: an array of section ids of which entries should not be indexed


### Only in the configuration file

  - `allowedIPs` _(string[])_: an array of IP addresses allowed to use the Elasticsearch console commands
  - `allowedHosts` _(string[])_: an array of hostnames allowed to use the Elasticsearch console commands
  - `contentExtractorCallback` _(callable)_: a callback (`function(string $entryContent): string`) used to extract the
    content to be indexed from the full HTML source of the entry's page. The default is to extract the HTML code between
    those 2 comments: `<!-- BEGIN elasticsearch indexed content -->` and `<!-- END elasticsearch indexed content -->`

[elasticsearch documentation]: https://www.elastic.co/guide/en/elasticsearch/reference/6.x/search-request-highlighting.html



## Indexable content

By default, the content indexed in each entry is between the `<!-- BEGIN elasticsearch indexed content -->` 
and `<!-- END elasticsearch indexed content -->` HTML comments in the source of the entry page.

If you're using semantic HTML in your templates, then putting your `<main>` or `<article>` element between 
those comments should be ideal. 

If you need more control over what is indexed, you'll have to set up a custom `contentExtractorCallback`.


## Running a search

The search feature can be used from a frontend template file by calling the 
`craft.elasticsearch.results('Something to search')` variable.
For instance, in a template `search/index.twig`:

```twig
{% set results = craft.elasticsearch.results(craft.app.request.get('q')) %}

{% block content %}
    <h1>{{ "Search"|t }}</h1>

    <form action="{{ url('search') }}">
        <input type="search" name="q" placeholder="Search" value="{{ craft.app.request.get('q') }}">
        <input type="submit" value="Go">
    </form>

    {% if results|length %}
        <h2>{{ "Results"|t }}</h2>

        {% for result in results %}
            <h3>{{ result.title }}</h3>
            <p>
                <small><a href="{{ result.url|raw }}">{{ result.url }}</a><br/>
                    {% if result.highlights|length %}
                        {% for highligh in result.highlights %}
                            {{ highligh|raw }}<br/>
                        {% endfor %}
                    {% endif %}
                </small>
            </p>
            <hr>
        {% endfor %}
    {% else %}
        {% if craft.app.request.get('q') is not null %}
            <p>
                <em>{{ "No results"|t }}</em>
            </p>
        {% endif %}
    {% endif %}
{% endblock %}
```

Each entry consists of the following attributes:

  - `id`: unique ID of the result
  - `title`: page title
  - `url`: full url to the page
  - `score`: entry result score
  - `highlights`: array of highlighted content matching the query terms



## Elasticsearch plugin utilities

If your Elasticsearch index becomes out of sync with your sites contents, you 
can go to Utilities → Elasticsearch then click the **Reindex all** button.



## Elasticsearch plugin console commands

The plugin provides an extension to the Craft console command that lets you reindex all entries or recreate empty 
indexes.


### Recreate empty indexes

Remove index & create an empty one for all sites

````sh
./craft elasticsearch/elasticsearch/recreate-empty-indexes
````

Reindex all sites 

````sh
./craft elasticsearch/elasticsearch/reindex-all http://example.com
````



## Elasticsearch plugin Roadmap

* Handle dependencies update 
* Detect need for re-indexation

Brought to you by [![LHS Logo](resources/img/lhs.png) La Haute Société][lhs-site].

[![Elastic](resources/img/elastic-logo.png)][elastic-site]  
Elasticsearch is a trademark of Elasticsearch BV, registered in the U.S. and in
other countries.

[lhs-site]: https://www.lahautesociete.com
[elastic-site]: https://www.elastic.co/brand
