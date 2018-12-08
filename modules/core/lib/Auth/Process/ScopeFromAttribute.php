<?php

namespace SimpleSAML\Module\core\Auth\Process;

/**
 * Retrieve a scope from a source attribute and add it as a virtual target
 * attribute.
 *
 * For instance, add the following to $simplesamldir/config.php, entry
 * authproc.sp
 *
 * 51 => array(
 *             'class'         => 'core:ScopeFromAttribute',
 *             'sourceAttribute' => 'eduPersonPrincipalName',
 *             'targetAttribute' => 'scope',
 * ),
 *
 * to add a virtual 'scope' attribute from the eduPersonPrincipalName
 * attribute.
 */

class ScopeFromAttribute extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * The attribute where the scope is taken from
     *
     * @var string
     */
    private $sourceAttribute;

    /**
     * The name of the attribute which includes the scope
     *
     * @var string
     */
    private $targetAttribute;

    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);
        assert(is_array($config));

        $config = \SimpleSAML\Configuration::loadFromArray($config, 'ScopeFromAttribute');
        $this->targetAttribute = $config->getString('targetAttribute');
        $this->sourceAttribute = $config->getString('sourceAttribute');
    } // end constructor

    /**
     * Apply this filter.
     *
     * @param array &$request  The current request
     */
    public function process(&$request)
    {
        assert(is_array($request));
        assert(array_key_exists('Attributes', $request));

        $attributes = &$request['Attributes'];

        if (!isset($attributes[$this->sourceAttribute])) {
            return;
        }

        // will not overwrite existing attribute
        if (isset($attributes[$this->targetAttribute])) {
            return;
        }

        $sourceAttrVal = $attributes[$this->sourceAttribute][0];

        /* Treat the first @ as usually the beginning of the scope
         * string, as per eduPerson recommendation. */
        $scopeIndex = strpos($sourceAttrVal, '@');

        if ($scopeIndex !== false) {
            $attributes[$this->targetAttribute] = [];
            $scope = substr($sourceAttrVal, $scopeIndex + 1);
            $attributes[$this->targetAttribute][] = $scope;
            SimpleSAML\Logger::debug('ScopeFromAttribute: Inserted new attribute '.
                                     $this->targetAttribute.', with scope '.$scope);
        } else {
            SimpleSAML\Logger::warning('ScopeFromAttribute: The configured source attribute '.
                                       $this->sourceAttribute.
                                       ' does not have a scope. Did not add attribute '.
                                       $this->targetAttribute.'.');
        }
    } /* end process */
}
