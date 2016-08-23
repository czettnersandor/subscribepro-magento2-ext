<?php

namespace Swarming\SubscribePro\Gateway\Config;

use SubscribePro\Tools\Config as PlatformConfig;

class ConfigProvider
{
    const CODE = 'subscribe_pro';

    const VAULT_CODE = 'subscribe_pro_vault';

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Payment\Model\CcConfig
     */
    protected $ccConfig;

    /**
     * @var \Magento\Payment\Model\CcConfigProvider
     */
    protected $ccConfigProvider;

    /**
     * @var \Swarming\SubscribePro\Platform\Tool\Config
     */
    protected $platformConfigTool;

    /**
     * @param \Swarming\SubscribePro\Gateway\Config\Config $config
     * @param \Magento\Payment\Model\CcConfig $ccConfig
     * @param \Magento\Payment\Model\CcConfigProvider $ccConfigProvider
     * @param \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool
     */
    public function __construct(
        \Swarming\SubscribePro\Gateway\Config\Config $config,
        \Magento\Payment\Model\CcConfig $ccConfig,
        \Magento\Payment\Model\CcConfigProvider $ccConfigProvider,
        \Swarming\SubscribePro\Platform\Tool\Config $platformConfigTool
    ) {
        $this->config = $config;
        $this->ccConfig = $ccConfig;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->platformConfigTool = $platformConfigTool;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'vaultCode' => self::VAULT_CODE,
            'isActive' => $this->config->isActive(),
            'environmentKey' => $this->platformConfigTool->getConfig(PlatformConfig::CONFIG_TRANSPARENT_REDIRECT_ENVIRONMENT_KEY),
            'availableCardTypes' => $this->getCcAvailableTypes(),
            'ccTypesMapper' => $this->config->getCcTypesMapper(),
            'hasVerification' => $this->config->hasVerification(),
            'cvvImageUrl' => $this->ccConfig->getCvvImageUrl(),
            'icons' => $this->ccConfigProvider->getIcons()
        ];
    }

    /**
     * @return array
     */
    protected function getCcAvailableTypes()
    {
        $types = $this->ccConfig->getCcAvailableTypes();
        $availableTypes = $this->config->getAvailableCardTypes();
        return $availableTypes ? array_intersect_key($types, array_flip($availableTypes)) : $types;
    }
}
