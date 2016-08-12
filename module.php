<?php

class MagicLinkWebclientModule extends AApiModule
{
	protected $oMinModuleDecorator;
	
	protected $sRegisterModuleHash = '';
	
	protected $aRequireModules = array(
		'Min'
	); 
	
	protected $aSettingsMap = array(
		'RegisterModuleName' => array('StandardRegisterFormWebclient', 'string'),
	);
	
	/**
	* Returns Min module decorator.
	* 
	* @return \CApiModuleDecorator
	*/
	private function getMinModuleDecorator()
	{
		if ($this->oMinModuleDecorator === null)
		{
			$this->oMinModuleDecorator = \CApi::GetModuleDecorator('Min');
		}
		
		return $this->oMinModuleDecorator;
	}
	
	/**
	 * Returns register module hash.
	 * 
	 * @return string
	 */
	protected function getRegisterModuleHash()
	{
		if (empty($this->sRegisterModuleHash))
		{
			$oRegisterModuleDecorator = \CApi::GetModuleDecorator($this->getConfig('RegisterModuleName'));
			$oRegisterModuleSettings = $oRegisterModuleDecorator->GetAppData();
			$this->sRegisterModuleHash = $oRegisterModuleSettings['HashModuleName'];
		}
		return $this->sRegisterModuleHash;
	}
	
	/**
	 * Initializes module.
	 */
	public function init()
	{
		$this->subscribeEvent('CreateOAuthAccount', array($this, 'onCreateOAuthAccount'));
		$this->includeTemplate('AdminPanelWebclient_EditUserView', 'Edit-User-After', 'templates/MagicLinkView.html', $this->sName);
	}
	
	/**
	 * Returns module settings.
	 * 
	 * @return array
	 */
	public function GetAppData()
	{
		return array(
			'RegisterModuleHash' => $this->getRegisterModuleHash(),
			'RegisterModuleName' => $this->getConfig('RegisterModuleName'),
		);
	}
	
	/**
	 * Returns magic link hash for specified user.
	 * 
	 * @param int $UserId User identificator.
	 * @return string
	 */
	public function GetMagicLinkHash($UserId)
	{
		$mHash = '';
		$oMin = $this->getMinModuleDecorator();
		if ($oMin)
		{
			$sMinId = implode('|', array($UserId, md5($UserId)));
			$mHash = $oMin->GetMinById($sMinId);

			if (!$mHash)
			{
				$mHash = $oMin->CreateMin($sMinId, array($UserId));
			}
			else 
			{
				if (isset($mHash['__hash__']))
				{
					$mHash = $mHash['__hash__'];
				}
			}
		}
		
		$oAuthenticatedUser = \CApi::getAuthenticatedUser();
		if (empty($oAuthenticatedUser) || $oAuthenticatedUser->Role !== \EUserRole::SuperAdmin)
		{
			return '';
		}
		
		return $mHash;
	}
	
	/**
	 * Returns user for magic link from cookie.
	 * 
	 * @param \CUser $oUser
	 */
	public function onCreateOAuthAccount(&$oUser)
	{
		if (isset($_COOKIE['MagicLink']))
		{
			$oMin = $this->getMinModuleDecorator();
			if ($oMin)
			{
				$mHash = $oMin->GetMinByHash($_COOKIE['MagicLink']);
				if (isset($mHash['__hash__'], $mHash[0]))
				{
					$iUserId = $mHash[0];
					$oCore = \CApi::GetModuleDecorator('Core');
					if ($oCore)
					{
						$oUser = $oCore->GetUser($iUserId);
					}
				}
			}			
		}
	}
}
