Aligent Database Tools Bundle for OroCRM
==============================================

Facts
-----
- version: 1.0.0
- composer name: aligent/oro-dbtools

Description
-----------


Installation Instructions
-------------------------
1. Install this module via Composer

        composer require aligent/oro-dbtools

1. Clear cache

        php app/console cache:clear
        
        
Commands
-------

        php app/console oro:db:info
        
        php app/console oro:db:dump

        php app/console oro:db:console
        
        php app/console oro:db:import filename.sql
        
        php app/console oro:db:query SELECT * FROM database.table;
        
        php app/console oro:db:drop
        
        php app/console oro:db:create


Support
-------
If you have any issues with this bundle, please create a [pull request](https://github.com/aligent/oro-dbtools/pulls) with a failing test that demonstrates the problem you've found.  If you're really stuck, feel free to open [GitHub issue](https://github.com/aligent/oro-dbtools/issues).

Contribution
------------
Any contribution is highly appreciated. The best way to contribute code is to open a [pull request on GitHub](https://help.github.com/articles/using-pull-requests).

Developer
---------
Adam Hall <adam.hall@aligent.com.au>

Licence
-------
[MIT](https://opensource.org/licenses/mit)

Copyright
---------
(c) 2017 Aligent Consulting

Thanks
---------
[netz98](https://github.com/netz98) - For [n98magerun](https://github.com/netz98/n98-magerun) which this tool is based on.
