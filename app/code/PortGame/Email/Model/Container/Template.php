<?php
/**
 * Copyright © PortGame. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace PortGame\Email\Model\Container;

use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Email Template Container
 * 
 * Stores email template variables, options, and template ID.
 * Follows Magento native pattern from \Magento\Sales\Model\Order\Email\Container\Template
 */
class Template implements ResetAfterRequestInterface
{
    /**
     * @var array
     */
    protected $vars;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $templateId;

    /**
     * Set email template variables
     *
     * @param array $vars
     * @return void
     */
    public function setTemplateVars(array $vars)
    {
        $this->vars = $vars;
    }

    /**
     * Set email template options
     *
     * @param array $options
     * @return void
     */
    public function setTemplateOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Get email template variables
     *
     * @return array
     */
    public function getTemplateVars()
    {
        return $this->vars;
    }

    public function getTemplateVarsByKey($key)
    {
        return $this->vars[$key] ?? null;
    }
    /**
     * Get email template options
     *
     * @return array
     */
    public function getTemplateOptions()
    {
        return $this->options;
    }

    /**
     * Set email template id
     *
     * @param string $id
     * @return void
     */
    public function setTemplateId($id)
    {
        $this->templateId = $id;
    }

    /**
     * Get email template id
     *
     * @return string
     */
    public function getTemplateId()
    {
        return $this->templateId;
    }

    /**
     * Reset state after request
     *
     * @return void
     */
    public function _resetState(): void
    {
        $this->vars = null;
        $this->options = null;
        $this->templateId = null;
    }
}
