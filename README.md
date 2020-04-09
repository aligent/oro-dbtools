Aligent Database Tools
==============================================

- version: 3.0.0
- composer name: aligent/dbtools

Description
-----------
This application provides some commands that help interact with a database from the console and provide sanitized database dumps for use in development environments.

Installation Instructions
-------------------------
1. Install this module via Composer

        composer require aligent/dbtools

1. List Commands

        php bin/dbtools
        
Configuration
-------
This tool can be configured by including --database-{option}, using environment variables, or the db-tools-config.yml placed in the application root. It will fallback in 
through the configuration in that order e.g. --database-name will override the DATABASE_NAME environment variable and DATABASE_NAME would in turn override the database_name placed in the db-tools-config.yml.

####Environment Variables
```dotenv
DATABASE_DRIVER=mysql
DATABASE_NAME=oro
DATABASE_USER=oro
DATABASE_PASSWORD=hunter2
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
```

####Configuration File
If file named db-tools-config.yml is placed in the application root (in the same directory as vendor/ and bin/) it will be auto-loaded by the application. The file path can be overridden using the --config=/path/to/file.yml 
```yaml
connection:
  database_driver: mysql
  database_host: 127.0.0.1
  database_port: 3306
  database_name: oro
  database_user: oro
  database_password: hunter2

definitions:
    oro_commerce:
        truncate:
          - oro_message_queue_state
        update:
          oro_address:
            columns:
              street: md5
              city: md5
              postal_code: md5
              first_name: md5
              last_name: md5
```

####Global Command Options
```
  --config[=CONFIG]                                   The file that should be used to configure this utility.
  -name, --database-name[=DATABASE-NAME]              The database name to operate on.
  -user, --database-user[=DATABASE-USER]              The database user to authenticate with.
  -password, --database-password[=DATABASE-PASSWORD]  The database users password.
  -host, --database-host[=DATABASE-HOST]              The hostname of the database.
  -port, --database-port[=DATABASE-PORT]              The port used to connect with the database.
  -driver, --database-driver[=DATABASE-DRIVER]        The database driver that should be used.
```

Commands
-------

        # Prints a table of database information
        php bin/dbtools info
        
        # Dumps the configured database to file (use --sanitize={definition_name} to create development dumps)
        php bin/dbtools dump

        # Opens mysql console (requires mysql client to be installed on host)
        php bin/dbtools console
        
        # Import a SQL dump (supports gz with --compression, requires gzip to be installed on host)
        php bin/dbtools import filename.sql
        
        # Run an SQL Query
        php bin/dbtools query SELECT * FROM database.table;
        
        # Drop the configured database
        php bin/dbtools drop
        
        # Create an empty database with the configured database name
        php bin/dbtools create
        
        # Prints a list of configured sanitization definitions for use with the dump command
        php bin/dbtools list-definitions
        
        
Sanitization Definitions
-------
This tools includes a sanitization definition for Oro Commerce, however it can be extended with custom definitions for any database. To add/extend definitions simply add a 
definitions block to your db-tools-config.yml. If you wish to add a custom definition it must have it's own name, if you wish to extend an existing definition you can use the existing name
and anything you add will be merged on top of the core definition.

For example to add a custom table to the existing Oro Commerce definition you would add the following to your db-tools-config.yml:

```yaml
definitions:
    oro_commerce:
        truncate:
          - table_to_truncate
        update:
          customer_table_name:
            columns:
              first_name: md5
              last_name: md5
```

To add a custom definition you would add the following to your db-tools-config.yml:

```yaml
definitions:
    custom_definition:
        truncate:
          - table_to_truncate
        update:
          customer_table_name:
            columns:
              first_name: md5
              last_name: md5
```

Tables listed under the truncate key will only have the table structure exported to the sanitized dump. While everything under the update key will have each of the defined columns
passed through the a sanitize function (value of the column name is the function used).

We are open to supporting more platforms by default, feel free to open a PR with extra definitions to the resources/definitions.yml file.

####Included Sanitizers
md5 - Replaces the contents of the column with a MD5 hash of the database value.

attachment - Replaces email attachment content stored in the database with a base54 encoded 1px x 1px png. (Fairly Oro Specific)

datetime - Replaces a DateTime field with the current DateTime

email - Performs a sha function on the email and appends @really.invalid 

password - Creates a MD5 hash of a value returned by PHP's rand() function

random - Just PHP's rand() function

We are open to additions here as well, if you need a custom sanitizer open a PR and we will get it merged ASAP.

Requirements
-------
Some commands require the `mysql` binaries to be installed on the system. The dump command uses ifsnop/mysqldump-php for dumping and sanitizing the databases.

Currently only mysql based databases are supported, however we are working on support for postgres. 

Support
-------
If you have any issues with this bundle, please create a [pull request](https://github.com/aligent/dbtools/pulls) with a failing test that demonstrates the problem you've found.  If you're really stuck, feel free to open [GitHub issue](https://github.com/aligent/dbtools/issues).

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
(c) 2017-20 Aligent Consulting

Thanks
---------
[netz98](https://github.com/netz98) - For [n98magerun](https://github.com/netz98/n98-magerun) which this tool is based on.
