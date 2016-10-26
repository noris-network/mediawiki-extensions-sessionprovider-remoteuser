<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

use MediaWiki\Session\SessionInfo;
use MediaWiki\Session\UserInfo;

/**
 * Session provider for apache/authz authenticated users.
 *
 * Class AuthRemoteuser
 */
class AuthRemoteuser extends MediaWiki\Session\ImmutableSessionProviderWithCookie {

    /**
     * @param array $params Keys include:
     *  - priority: (required) Set the priority
     *  - sessionCookieName: Session cookie name. Default is '_AuthRemoteuserSession'.
     *  - sessionCookieOptions: Options to pass to WebResponse::setCookie().
     */
    public function __construct(array $params = []) {
        if (!isset($params['sessionCookieName'])) {
            $params['sessionCookieName'] = '_AuthRemoteuserSession';
        }
        parent::__construct( $params );

        if ( !isset( $params['priority'] ) ) {
            throw new \InvalidArgumentException(__METHOD__ . ': priority must be specified');
        }
        if ($params['priority'] < SessionInfo::MIN_PRIORITY ||
            $params['priority'] > SessionInfo::MAX_PRIORITY
        ) {
            throw new \InvalidArgumentException(__METHOD__ . ': Invalid priority');
        }

        $this->priority = $params['priority'];
    }

    /**
     * @inheritDoc
     */
    public function provideSessionInfo(WebRequest $request)
    {
        // Have a session ID?
        $id = $this->getSessionIdFromCookie($request);
        if (null === $id) {
            $username = $this->getRemoteUsername();
            $sessionInfo = $this->newSessionForRequest($username, $request);

            return $sessionInfo;
        }

        $sessionInfo = new SessionInfo($this->priority, [
            'provider' => $this,
            'id' => $id,
            'persisted' => true
        ]);

        return $sessionInfo;
    }

    /**
     * @inheritDoc
     */
    public function newSessionInfo($id = null)
    {
        return null;
    }

    /**
     * @param $username
     * @param WebRequest $request
     * @return SessionInfo
     */
    protected function newSessionForRequest($username, WebRequest $request)
    {
        $id = $this->getSessionIdFromCookie($request);

        $user = User::newFromName($username, 'usable');
        if (!$user) {
            throw new \InvalidArgumentException('Invalid user name');
        }

        $this->initUser($user, $username);

        $info = new SessionInfo(SessionInfo::MAX_PRIORITY, [
            'provider' => $this,
            'id' => $id,
            'userInfo' => UserInfo::newFromUser($user, true),
            'persisted' => false
        ]);
        $session = $this->getManager()->getSessionFromInfo($info, $request);
        $session->persist();

        return $info;
    }

    /**
     * When creating a user account, optionally fill in
     * preferences and such.  For instance, you might pull the
     * email address or real name from the external user database.
     *
     * @param $user User object.
     * @param $autocreate bool
     */
    protected function initUser(&$user, $username)
    {
        if (Hooks::run("AuthRemoteUserInitUser",
            array($user, true))
        ) {
            // Check if above hook or some other effect (e.g.: https://phabricator.wikimedia.org/T95839 )
            // already created a user in the db. If so, reuse that one.
            $userFromDb = $user->getInstanceForUpdate();
            if (null !== $userFromDb) {
                $user = $user->getInstanceForUpdate();
            }

            $this->setRealName($user);

            $this->setEmail($user, $username);

            $user->mEmailAuthenticated = wfTimestampNow();
            $user->setToken();

            $this->setNotifications($user);
        }

        $user->saveSettings();
    }

    /**
     * Sets the real name of the user.
     *
     * @param User
     */
    protected function setRealName(User $user)
    {
        global $wgAuthRemoteuserName;

        if ($wgAuthRemoteuserName) {
            $user->setRealName($wgAuthRemoteuserName);
        } else {
            $user->setRealName('');
        }
    }

    /**
     * Return the username to be used.  Empty string if none.
     *
     * @return string
     */
    protected function getRemoteUsername()
    {
        global $wgAuthRemoteuserDomain;

        if (isset($_SERVER['REMOTE_USER'])) {
            $username = $_SERVER['REMOTE_USER'];

            if ($wgAuthRemoteuserDomain) {
                $username = str_replace("$wgAuthRemoteuserDomain\\",
                    "", $username);
                $username = str_replace("@$wgAuthRemoteuserDomain",
                    "", $username);
            }
        } else {
            $username = "";
        }

        return $username;
    }

    /**
     * Sets the email address of the user.
     *
     * @param User
     * @param String username
     */
    protected function setEmail(User $user, $username)
    {
        global $wgAuthRemoteuserMail, $wgAuthRemoteuserMailDomain;

        if ($wgAuthRemoteuserMail) {
            $user->setEmail($wgAuthRemoteuserMail);
        } elseif ($wgAuthRemoteuserMailDomain) {
            $user->setEmail($username . '@' .
                $wgAuthRemoteuserMailDomain);
        } else {
            $user->setEmail($username . "@example.com");
        }
    }

    /**
     * Set up notifications for the user.
     *
     * @param User
     */
    protected function setNotifications(User $user)
    {
        global $wgAuthRemoteuserNotify;

        // turn on e-mail notifications
        if ($wgAuthRemoteuserNotify) {
            $user->setOption('enotifwatchlistpages', 1);
            $user->setOption('enotifusertalkpages', 1);
            $user->setOption('enotifminoredits', 1);
            $user->setOption('enotifrevealaddr', 1);
        }
    }


}