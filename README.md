# AuthRemoteuser: A MediaWiki Extension

The Auth_remoteuser extension allows integration with the web server's built-in
authentication system via the REMOTE_USER environment variable. This variable
is set through HTTP-Auth, LDAP, CAS, PAM, and other authentication systems.
Using the the value of the REMOTE_USER environment variable, this extension
automagically performs a login for this user. The value of this environment
variable also serves as the MediaWiki username. If an account with that name does
not exist yet, one is created.

## Installation
First, add this to your `LocalSettings.php`:

    ####################################################
    # Extension: AuthRemoteuser
    wfLoadExtension( 'AuthRemoteuser' );
    $wgAuthRemoteuserMailDomain = 'example.com';
    
    # If you want the extension to autocreate users not existing you have to add 
    $wgGroupPermissions['*']['autocreateaccount'] = true;
    
    # Settings: AuthRemoteuser
    $wgGroupPermissions['*']['createaccount']   = false;
    $wgGroupPermissions['*']['read']            = false;
    $wgGroupPermissions['*']['edit']            = false;
    ####################################################

Instead of `example.com`, you might want to use the domain of your organization.
It will be appended to the username and should form a valid email address. If
i.e. your username to login (==`REMOTE_USER`) is `jdoe`, the email of the user
will be `jdoe@example.com`.

If your environment uses a different variable then `REMOTE_USER`
you can adjust this like so:

    $wgAuthRemoteuserEnvVariable = 'HTTP_X_REMOTE_USER';

## Implementation
The constructor of AuthRemoteuser registers a hook to do the automatic login.
Storing the AuthRemoteuser object in $wgAuth tells MediaWiki that instead of the
MediaWiki AuthPlugin, use us for authentication. This way the plugin can handle
the login attempts.

# Original version

The original version of this fork can be found on the [MediaWiki extension site]
(https://www.mediawiki.org/wiki/Extension:Auth_remoteuser).

# License (GPLv2)

    Use web server authentication (REMOTE_USER) in MediaWiki.
    Copyright 2006 Otheus Shelling
	Copyright 2007 Rusty Burchfield
	Copyright 2009 James Kinsman
	Copyright 2010 Daniel Thomas
	Copyright 2010 Ian Ward Comfort
	Copyright 2014 Mark A. Hershberger
	Copyright 2015 Jonas Gröger
	Copyright 2016 Andreas Fink

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License along
    with this program; if not, write to the Free Software Foundation, Inc.,
    51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
