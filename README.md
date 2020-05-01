# Phenotyping Sensor Network: Server
PSN is a network of wireless battery-powered sensing nodes designed specifically for the phenotyping domain. This codebase contains the website that enables users to control the sensor network and view the data reported by sensor nodes.

# Usage
- Install the dependencies (see the Dependencies section)
- Ensure the PHP time zone is set to UTC
- On Microsoft Azure, create a new app registration
- Create the configuration file (see the Configuration section)
- Navigate to the website in a browser

# Configuration
A file called `config.ini` is required in the root directory of the codebase. It must follow the following format:

- `[database]`
- `host=` -- Address of the server hosting the MySQL database (e.g. `localhost`)
- `name=` -- Name of the database to use for storing and accessing data
- `username=` -- Username to access the database with
- `password=` -- Password of the above username
- `[authentication]`
- `admin_password=` -- Password to use for the website administrator account
- `guest_password=` -- Password to use for the website guest account
- `oauth_client_id=` -- Application client ID of the Microsoft Azure app
- `oauth_client_secret=` -- Client secret of the Microsoft Azure app
- `oauth_redirect_url=` -- Some form of `http://localhost/psn-server/resources/routines/oauth-success.php`. Modify for your hostname. This must be a registered redirect URI in the Microsoft Azure app
- `oauth_authorise_url="https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize"`
- `oauth_access_token_url="https://login.microsoftonline.com/organizations/oauth2/v2.0/token"`
- `oauth_resource_owner_url=`
- `oauth_scopes="openid profile offline_access user.read"`
- `session_timeout=` -- Number of hours to automatically log users out after
- `oauth_post_logout_url=` -- Some form of `"https://login.microsoftonline.com/common/oauth2/v2.0/logout?post_logout_redirect_uri=http%3A%2F%2Flocalhost%2Fpsn-server%2Flogin.php"`. `post_logout_redirect_uri` must be a registered redirect URI in the Microsoft Azure app
- `[display]`
- `local_time_zone=` -- Time zone to display dates and times in (e.g. `Europe/London`). Must be a valid name in the "tz database"

# Dependencies
- Apache2 (or other web server)
- PHP 7.3
- php7.3-mysql
- php7.3-zip
- Composer
- microsoft/microsoft-graph (via Composer)
- league/oauth2-client (via Composer)