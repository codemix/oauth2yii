<?php
namespace OAuth2Yii\Storage;

use \OAuth2\Storage\ScopeInterface;

/**
 * Storage for scopes
 *
 * @author Michael Härtl <haertl.mike@gmail.com>
 */
class Scope extends Storage implements ScopeInterface
{
    public function scopeExists($scope, $client_id=null)
    {
        $scopes     = explode(' ',trim($scope));
        $allScopes  = $this->getOAuth2()->scopes;

        if(!$allScopes) {
            return true;
        }

        return count(array_diff($scopes, $allScopes))==0;
    }

    public function getDefaultScope($client_id = null)
    {
        return $this->getOAuth2()->defaultScope;
    }
}
