phpkg-core
==========

Core package for phpkg component series.

Installation
------------

Install composer in your project:

    curl -s https://getcomposer.org/installer | php

Create a composer.json file in your project root:

    {
        "require": {
            "pokelabo/core": "*"
        },
        "repositories": [
            {
                "type": "git",
                "url": "https://github.com/pokelabo/phpkg-core.git"
            }
        ]
    }

What's in it?
-------------

Currently configuration module is included.

`config/Config.php`  
`config/ConfigLoader.php`  
`config/ConfigRepository.php`  

License
-------

MIT Public License
