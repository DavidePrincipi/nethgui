<?php

/**
 * NethGui
 *
 * @package ExtensibleApi
 */

/**
 * A NethGui_Core_ModuleInterface implementation is delegated to receive input parameters,
 * validate, process and (optionally) return an html view of the Module.
 *
 * TODO: interface description.
 * @package ExtensibleApi
 */
interface NethGui_Core_ModuleInterface
{

    /**
     * Sets the host configuration Model.
     */
    public function setHostConfiguration(NethGui_Core_HostConfigurationInterface $hostConfiguration);

    /**
     * The Module Identifier is a string that univocally identifies a Module.
     * @return string Returns the unique module identifier
     */
    public function getIdentifier();

    /**
     * Sets the reference to the parent Module.
     * @see NethGui_Core_ModuleCompositeInterface addChild() operation.
     */
    public function setParent(NethGui_Core_ModuleInterface $parentModule);

    /**
     * Gets a reference to the parent Module.
     * @return ModuleInterface
     */
    public function getParent();

    /**
     * Gets the Module ``title`` attribute.
     * @return string
     */
    public function getTitle();

    /**
     * Gets the Module ``description`` attribute.
     * @return string
     */
    public function getDescription();


    /**
     * After initialization a module...
     */
    public function initialize();

    /**
     * Prevents double initialization.
     * @return bool FALSE, if not yet initialized, TRUE otherwise.
     */
    public function isInitialized();

}

/**
 * A "Top" Module is a Module displayed on the application main menu.
 * 
 * If it provides the identifier of another Module, it will be placed
 * under that Module.
 *
 * Also a "Top" Module is reachable with a specific URL, unlike other Modules.
 *
 * @package ExtensibleApi
 */
interface NethGui_Core_TopModuleInterface
{

    /**
     * @return string Unique parent module identifier
     */
    public function getParentMenuIdentifier();
}
