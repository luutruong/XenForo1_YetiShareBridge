<?php

class Truonglv_YetiShareBridge_XenForo_Model_UserExternal extends XFCP_Truonglv_YetiShareBridge_XenForo_Model_UserExternal
{
    /**
     * @var null|int
     */
    protected $_YetiShare_associateUserId = null;

    public function updateExternalAuthAssociation($provider, $providerKey, $userId, array $extra = null)
    {
        parent::updateExternalAuthAssociation($provider, $providerKey, $userId, $extra);

        $this->_YetiShare_associateUserId = $userId;
    }

    /**
     * @return null|int
     */
    public function getYetiShareAssociateUserId()
    {
        return $this->_YetiShare_associateUserId;
    }
}
