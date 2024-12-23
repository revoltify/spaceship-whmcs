<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Module\Registrar\Spaceship\SpaceshipWHMCS;

function spaceship_MetaData()
{
    return [
        'DisplayName' => 'Spaceship',
        'APIVersion' => '1.1',
    ];
}

/**
 * Module configuration options
 *
 * @return array
 */
function spaceship_getConfigArray()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Spaceship Domain Registrar',
        ],
        'Description' => [
            'Type' => 'System',
            'Value' => 'Register and manage domains using the Spaceship API.',
        ],
        'ApiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your API Key here',
        ],
        'ApiSecret' => [
            'FriendlyName' => 'API Secret',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Enter your API Secret here',
        ],
    ];
}

/**
 * Register Domain
 *
 * @param array $params
 * @return array
 */
function spaceship_RegisterDomain($params)
{
    return [
        'success' => true,
    ];
}

/**
 * Transfer Domain
 *
 * @param array $params
 * @return array
 */
function spaceship_TransferDomain($params)
{
    return [
        'success' => true,
    ];
}

/**
 * Renew Domain
 *
 * @param array $params
 * @return array
 */
function spaceship_RenewDomain($params)
{
    return [
        'success' => true,
    ];
}

/**
 * Get nameservers
 *
 * @param array $params
 * @return array
 */
function spaceship_GetNameservers($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->getNameservers($params);
}

/**
 * Save nameservers
 *
 * @param array $params
 * @return array
 */
function spaceship_SaveNameservers($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->saveNameservers($params);
}

/**
 * Get registrar lock status
 *
 * @param array $params
 * @return string
 */
function spaceship_GetRegistrarLock($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->getRegistrarLock($params);
}

/**
 * Set registrar lock status
 *
 * @param array $params
 * @return array
 */
function spaceship_SaveRegistrarLock($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->toggleRegistrarLock($params);
}

/**
 * Get EPP Code
 *
 * @param array $params
 * @return array
 */
function spaceship_GetEPPCode($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->getEPPCode($params);
}

/**
 * Get contact details
 *
 * @param array $params
 * @return array
 */
function spaceship_GetContactDetails($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->getContactDetails($params);
}

/**
 * Save contact details
 *
 * @param array $params
 * @return array
 */
function spaceship_SaveContactDetails($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->updateContactDetails($params);
}

/**
 * Enable/Disable ID Protection
 *
 * @param array $params
 * @return array
 */
function spaceship_IDProtectToggle($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->toggleIdProtection($params);
}


/**
 * Sync domain status
 *
 * @param array $params
 * @return array
 */
function spaceship_Sync($params)
{
    $service = new SpaceshipWHMCS($params);
    return $service->syncDomainStatus($params);
}